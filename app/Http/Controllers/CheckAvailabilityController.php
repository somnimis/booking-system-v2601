<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CheckAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckAvailabilityController extends Controller
{
    protected $availabilityChecker;

    public function __construct( CheckAvailabilityService $availabilityChecker)
    {
        $this->availabilityChecker = $availabilityChecker;
    }

/**
 * Check availability for a potential reservation (pre-submission check)
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function checkAvailability(Request $request)
{
    try {
        $validatedData = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'all_day' => 'required|boolean',
            'facilities' => 'array',
            'facilities.*.facility_id' => 'exists:facilities,facility_id',
            'equipment' => 'array',
            'equipment.*.equipment_id' => 'exists:equipment,equipment_id',
            'equipment.*.quantity' => 'integer|min:1',
            'current_request_id' => 'nullable|exists:requisition_forms,request_id'
        ]);

        $conflicts = [
            'requisition_conflicts' => [],
            'calendar_conflicts' => []
        ];

        // Check facilities for conflicts
        if (!empty($validatedData['facilities'])) {
            foreach ($validatedData['facilities'] as $facility) {
                $facilityConflicts = $this->availabilityChecker->checkFacilityAvailability(
                    $facility['facility_id'],
                    $validatedData['start_date'],
                    $validatedData['end_date'],
                    $validatedData['start_time'] ?? '00:00:00',
                    $validatedData['end_time'] ?? '23:59:59',
                    $validatedData['all_day'],
                    $validatedData['current_request_id'] ?? null
                );
                
                foreach ($facilityConflicts as $conflict) {
                    if ($conflict['source'] === 'requisition') {
                        $conflicts['requisition_conflicts'][] = $conflict;
                    } else {
                        // Calendar events are warnings (non-blocking)
                        $conflict['severity'] = 'warning';
                        $conflict['is_blocking'] = false;
                        $conflicts['calendar_conflicts'][] = $conflict;
                    }
                }
            }
        }

        // Check equipment availability
        if (!empty($validatedData['equipment'])) {
            foreach ($validatedData['equipment'] as $equipment) {
                $availableCount = $this->availabilityChecker->checkEquipmentAvailability(
                    $equipment['equipment_id'],
                    $validatedData['start_date'],
                    $validatedData['end_date'],
                    $validatedData['all_day'],
                    $validatedData['current_request_id'] ?? null
                );

                $requestedQty = $equipment['quantity'] ?? 1;
                
                if ($availableCount < $requestedQty) {
                    $equipmentModel = \App\Models\Equipment::find($equipment['equipment_id']);
                    
                    // Check if there are blocking bookings (Awaiting Payment or Reserved)
                    $blockingBookings = \App\Models\RequestedEquipment::where('equipment_id', $equipment['equipment_id'])
                        ->whereHas('requisitionForm', function($q) use ($validatedData) {
                            $q->whereIn('status_id', function($sq) {
                                $sq->select('status_id')->from('form_statuses')
                                   ->whereIn('status_name', ['Awaiting Payment', 'Reserved']);
                            });
                        })->sum('quantity');
                    
                    $isBlocking = $blockingBookings > 0;
                    $severity = $isBlocking ? 'block' : 'warning';
                    $statusName = $isBlocking ? 'Booked' : 'Pending Approval';
                    
                    $conflicts['requisition_conflicts'][] = [
                        'type' => 'equipment',
                        'id' => $equipment['equipment_id'],
                        'name' => $equipmentModel->equipment_name ?? 'Unknown Equipment',
                        'source' => 'requisition',
                        'status' => $statusName,
                        'severity' => $severity,
                        'is_blocking' => $isBlocking,
                        'request_id' => null,
                        'message' => "Only {$availableCount} available, requested {$requestedQty}",
                        'conflict_reason' => $isBlocking 
                            ? "This equipment is already booked for the selected schedule" 
                            : "There is a pending request for this equipment",
                        'schedule' => null
                    ];
                }
            }
        }

        // Remove duplicate requisition conflicts (same request_id)
        $seenRequestIds = [];
        $conflicts['requisition_conflicts'] = array_filter($conflicts['requisition_conflicts'], function($conflict) use (&$seenRequestIds) {
            if (isset($conflict['request_id']) && in_array($conflict['request_id'], $seenRequestIds)) {
                return false;
            }
            if (isset($conflict['request_id'])) {
                $seenRequestIds[] = $conflict['request_id'];
            }
            return true;
        });

        // Check if ANY blocking conflicts exist (calendar events are NOT blocking)
        $hasBlockingConflicts = false;
        
        foreach ($conflicts['requisition_conflicts'] as $conflict) {
            if (isset($conflict['severity']) && $conflict['severity'] === 'block') {
                $hasBlockingConflicts = true;
                break;
            }
        }
        
        // Calendar events are NOT blocking (warnings only)
        // So they don't affect $hasBlockingConflicts

        return response()->json([
            'success' => true,
            'has_conflicts' => !empty($conflicts['requisition_conflicts']) || !empty($conflicts['calendar_conflicts']),
            'has_blocking_conflicts' => $hasBlockingConflicts,
            'conflicts' => $conflicts
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Availability check failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to check availability',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
