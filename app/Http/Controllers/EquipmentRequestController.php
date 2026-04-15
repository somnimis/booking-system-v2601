<?php
// app/Http/Controllers/Api/Admin/EquipmentRequestController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestedEquipment;
use App\Models\EquipmentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipmentRequestController extends Controller
{
    /**
     * Get requested equipment that hasn't been released yet
     * This is for the "To Release" container
     */
    public function getToRelease(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            
            $toRelease = RequestedEquipment::with([
                    'requisitionForm' => function($q) {
                        $q->select('request_id', 'first_name', 'last_name', 'organization_name', 'status_id');
                    },
                    'equipment' => function($q) {
                        $q->select('equipment_id', 'equipment_name', 'category_id', 'status_id');
                    }
                ])
                ->where('is_waived', false)
                ->whereNotExists(function($query) {
                    // Only show requested equipment that doesn't have any transactions yet
                    $query->select(DB::raw(1))
                        ->from('equipment_transactions')
                        ->whereColumn('equipment_transactions.requested_equipment_id', 'requested_equipment.requested_equipment_id');
                })
                ->orderBy('requested_equipment_id', 'desc')
                ->paginate($perPage);

            // Transform the data to include the fields we need
            $transformed = collect($toRelease->items())->map(function($item) {
                $requisition = $item->requisitionForm;
                $equipment = $item->equipment;
                
                // Get the count of available items for this equipment type
                $availableItems = DB::table('equipment_items')
                    ->join('availability_statuses', 'equipment_items.status_id', '=', 'availability_statuses.status_id')
                    ->where('equipment_items.equipment_id', $item->equipment_id)
                    ->whereIn('availability_statuses.status_name', ['Available', 'Reserved'])
                    ->count();

                return [
                    'requested_equipment_id' => $item->requested_equipment_id,
                    'request_id' => $item->request_id,
                    'requester_name' => $requisition ? 
                        ($requisition->first_name . ' ' . $requisition->last_name) : 'N/A',
                    'organization_name' => $requisition->organization_name ?? 'N/A',
                    'equipment_id' => $item->equipment_id,
                    'equipment_name' => $equipment->equipment_name ?? 'Unknown Equipment',
                    'quantity_requested' => $item->quantity,
                    'available_items' => $availableItems,
                    'can_release' => $availableItems >= $item->quantity,
                    'status' => $requisition ? optional($requisition->status)->status_name : 'Unknown'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'meta' => [
                    'current_page' => $toRelease->currentPage(),
                    'last_page' => $toRelease->lastPage(),
                    'per_page' => $toRelease->perPage(),
                    'total' => $toRelease->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load requested equipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active transactions (items that are currently out)
     * This is for the "To Return" container
     */
    public function getToReturn(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            
            $toReturn = EquipmentTransaction::with([
                    'requisitionForm' => function($q) {
                        $q->select('request_id', 'first_name', 'last_name', 'organization_name');
                    },
                    'equipmentItem' => function($q) {
                        $q->select('item_id', 'equipment_id', 'item_name', 'barcode_number', 'condition_id', 'status_id');
                    },
                    'equipmentItem.equipment' => function($q) {
                        $q->select('equipment_id', 'equipment_name');
                    },
                    'facility' => function($q) {
                        $q->select('facility_id', 'facility_name', 'facility_code');
                    },
                    'condition'
                ])
                ->whereNotNull('released_at')
                ->whereNull('returned_at')
                ->orderBy('released_at', 'desc')
                ->paginate($perPage);

            // Transform the data
            $transformed = collect($toReturn->items())->map(function($transaction) {
                $requisition = $transaction->requisitionForm;
                $item = $transaction->equipmentItem;
                
                // Calculate if overdue (based on requisition end_date)
                $isOverdue = false;
                $daysOut = null;
                
                if ($transaction->released_at) {
                    $daysOut = now()->diffInDays($transaction->released_at, false);
                    
                    if ($requisition && $requisition->end_date) {
                        $endDate = \Carbon\Carbon::parse($requisition->end_date);
                        $isOverdue = now()->gt($endDate);
                    }
                }

                return [
                    'transaction_id' => $transaction->id,
                    'request_id' => $transaction->request_id,
                    'requester_name' => $requisition ? 
                        ($requisition->first_name . ' ' . $requisition->last_name) : 'N/A',
                    'organization_name' => $requisition->organization_name ?? 'N/A',
                    'item_id' => $item->item_id ?? null,
                    'item_name' => $item->item_name ?? 
                        ($item && $item->equipment ? $item->equipment->equipment_name : 'Unknown') . 
                        ($item ? ' (#' . $item->item_id . ')' : ''),
                    'barcode' => $item->barcode_number ?? 'N/A',
                    'facility_name' => $transaction->facility ? 
                        $transaction->facility->facility_name : 
                        ($transaction->destination_name ?? 'N/A'),
                    'released_at' => $transaction->released_at ? 
                        $transaction->released_at->format('Y-m-d H:i') : null,
                    'days_out' => $daysOut ? round($daysOut, 1) : 0,
                    'condition' => $transaction->condition ? [
                        'id' => $transaction->condition->condition_id,
                        'name' => $transaction->condition->condition_name,
                        'color' => $transaction->condition->color_code
                    ] : null,
                    'is_overdue' => $isOverdue,
                    'release_notes' => $transaction->release_notes
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'meta' => [
                    'current_page' => $toReturn->currentPage(),
                    'last_page' => $toReturn->lastPage(),
                    'per_page' => $toReturn->perPage(),
                    'total' => $toReturn->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load active transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available items for a specific equipment type
     * Used when clicking the Release button
     */
    public function getAvailableItems($equipmentId)
    {
        try {
            $items = DB::table('equipment_items')
                ->join('availability_statuses', 'equipment_items.status_id', '=', 'availability_statuses.status_id')
                ->join('conditions', 'equipment_items.condition_id', '=', 'conditions.condition_id')
                ->leftJoin('equipment_transactions', function($join) {
                    $join->on('equipment_items.item_id', '=', 'equipment_transactions.item_id')
                         ->whereNull('equipment_transactions.returned_at');
                })
                ->where('equipment_items.equipment_id', $equipmentId)
                ->whereIn('availability_statuses.status_name', ['Available', 'Reserved'])
                ->whereNull('equipment_transactions.id') // Not currently in an active transaction
                ->select(
                    'equipment_items.item_id',
                    'equipment_items.item_name',
                    'equipment_items.barcode_number',
                    'equipment_items.image_url',
                    'conditions.condition_name',
                    'conditions.color_code as condition_color',
                    'availability_statuses.status_name',
                    'availability_statuses.color_code as status_color'
                )
                ->get();

            return response()->json([
                'success' => true,
                'data' => $items
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load available items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Release a specific item for a requested equipment
     */
    public function releaseItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'requested_equipment_id' => 'required|exists:requested_equipment,requested_equipment_id',
                'item_id' => 'required|exists:equipment_items,item_id',
                'facility_id' => 'nullable|exists:facilities,facility_id',
                'destination_name' => 'nullable|string|max:255',
                'release_notes' => 'nullable|string',
                'condition_id' => 'required|exists:conditions,condition_id'
            ]);

            DB::beginTransaction();

            // Get the requested equipment record
            $requestedEquipment = RequestedEquipment::findOrFail($validated['requested_equipment_id']);

            // Check if this requested equipment already has a transaction
            $existingTransaction = EquipmentTransaction::where('requested_equipment_id', $validated['requested_equipment_id'])->first();
            if ($existingTransaction) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This requested equipment already has an active transaction'
                ], 400);
            }

            // Check if the item is available
            $item = DB::table('equipment_items')
                ->join('availability_statuses', 'equipment_items.status_id', '=', 'availability_statuses.status_id')
                ->where('equipment_items.item_id', $validated['item_id'])
                ->whereIn('availability_statuses.status_name', ['Available', 'Reserved'])
                ->first();

            if (!$item) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Selected item is not available for release'
                ], 400);
            }

            // Create the transaction
            $transaction = EquipmentTransaction::create([
                'request_id' => $requestedEquipment->request_id,
                'requested_equipment_id' => $validated['requested_equipment_id'],
                'item_id' => $validated['item_id'],
                'released_at' => now(),
                'released_by' => auth('admin')->id(),
                'facility_id' => $validated['facility_id'] ?? null,
                'destination_name' => $validated['destination_name'] ?? null,
                'condition_id' => $validated['condition_id'],
                'release_notes' => $validated['release_notes'] ?? null,
                'status_id' => 1 // Active status
            ]);

            // Update the equipment item status to 'In Use' (you'll need the actual status_id)
            // First, get the 'In Use' status ID from availability_statuses
            $inUseStatus = DB::table('availability_statuses')
                ->where('status_name', 'In Use')
                ->first();

            if ($inUseStatus) {
                DB::table('equipment_items')
                    ->where('item_id', $validated['item_id'])
                    ->update(['status_id' => $inUseStatus->status_id]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item released successfully',
                'data' => $transaction
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to release item',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}