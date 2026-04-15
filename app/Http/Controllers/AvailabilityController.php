<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\RequisitionForm;
use App\Models\CalendarEvent;
use App\Models\FormStatus;
use App\Services\ScheduleFormatterService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvailabilityController extends Controller
{
    protected $scheduleFormatter;

    public function __construct(ScheduleFormatterService $scheduleFormatter)
    {
        $this->scheduleFormatter = $scheduleFormatter;
    }

    /**
 * Get facilities with hierarchical structure (parent-child relationships)
 */
public function getFacilitiesHierarchy(Request $request)
{
    try {
        $search = $request->input('search');
        
        // Get all active facilities
        $query = Facility::select('facility_id', 'facility_name', 'capacity', 'base_fee', 'parent_facility_id')
            ->where('status_id', 1);
        
        if ($search) {
            $query->where('facility_name', 'like', "%{$search}%");
        }
        
        $allFacilities = $query->orderBy('facility_name')->get();
        
        // Build hierarchical structure
        $parents = [];
        $children = [];
        
        foreach ($allFacilities as $facility) {
            if ($facility->parent_facility_id === null) {
                $parents[] = $facility;
            } else {
                if (!isset($children[$facility->parent_facility_id])) {
                    $children[$facility->parent_facility_id] = [];
                }
                $children[$facility->parent_facility_id][] = $facility;
            }
        }
        
        // Build response with hierarchy
        $hierarchy = [];
        foreach ($parents as $parent) {
            $parentData = [
                'facility_id' => $parent->facility_id,
                'facility_name' => $parent->facility_name,
                'capacity' => $parent->capacity,
                'base_fee' => $parent->base_fee,
                'is_parent' => true,
                'children' => isset($children[$parent->facility_id]) 
                    ? array_map(function($child) {
                        return [
                            'facility_id' => $child->facility_id,
                            'facility_name' => $child->facility_name,
                            'capacity' => $child->capacity,
                            'base_fee' => $child->base_fee,
                            'is_parent' => false,
                            'parent_facility_id' => $child->parent_facility_id
                        ];
                    }, $children[$parent->facility_id])
                    : []
            ];
            $hierarchy[] = $parentData;
        }
        
        // Also include standalone facilities (children without parents - shouldn't happen but just in case)
        $orphanedChildren = [];
        foreach ($allFacilities as $facility) {
            if ($facility->parent_facility_id !== null && !isset($children[$facility->parent_facility_id])) {
                $orphanedChildren[] = [
                    'facility_id' => $facility->facility_id,
                    'facility_name' => $facility->facility_name,
                    'capacity' => $facility->capacity,
                    'base_fee' => $facility->base_fee,
                    'is_parent' => false,
                    'parent_facility_id' => $facility->parent_facility_id
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'hierarchy' => $hierarchy,
                'orphaned' => $orphanedChildren,
                'total' => $allFacilities->count()
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error('Error loading facility hierarchy: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

    /**
     * Get minimal facility list (lightweight, no relations)
     */
    public function getFacilitiesList(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search');
            
            $query = Facility::select('facility_id', 'facility_name', 'capacity', 'base_fee')
                ->where('status_id', 1); // Only active facilities
            
            if ($search) {
                $query->where('facility_name', 'like', "%{$search}%");
            }
            
            $facilities = $query->orderBy('facility_name')
                ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $facilities->items(),
                'meta' => [
                    'current_page' => $facilities->currentPage(),
                    'last_page' => $facilities->lastPage(),
                    'total' => $facilities->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get events for a specific date range (optimized query)
     */
    public function getEventsForDate(Request $request)
    {
        try {
            $startDate = $request->input('start_date', Carbon::now()->format('Y-m-d'));
            $endDate = $request->input('end_date', $startDate);
            $facilityId = $request->input('facility_id');
            
            // Get active status IDs once
            $activeStatusIds = FormStatus::whereIn('status_name', [
                'Pending Approval', 'Scheduled', 'Ongoing'
            ])->pluck('status_id');
            
            $response = [
                'requisitions' => [],
                'calendar_events' => []
            ];
            
            // Fetch requisitions for the date range
            $requisitionQuery = RequisitionForm::with(['formStatus', 'requestedFacilities.facility'])
                ->whereIn('status_id', $activeStatusIds)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
                });
            
            if ($facilityId) {
                $requisitionQuery->whereHas('requestedFacilities', function ($q) use ($facilityId) {
                    $q->where('facility_id', $facilityId);
                });
            }
            
            $requisitions = $requisitionQuery->get();
            
            foreach ($requisitions as $req) {
                $schedule = $this->scheduleFormatter->getBaseSchedule($req);
                $response['requisitions'][] = [
                    'request_id' => $req->request_id,
                    'title' => $req->calendar_title ?: "Booking #{$req->request_id}",
                    'status' => $req->formStatus->status_name,
                    'status_color' => $req->formStatus->color_code,
                    'start_date' => $req->start_date,
                    'end_date' => $req->end_date,
                    'start_time' => $req->start_time,
                    'end_time' => $req->end_time,
                    'all_day' => $req->all_day,
                    'facilities' => $req->requestedFacilities->map(fn($rf) => [
                        'facility_id' => $rf->facility_id,
                        'facility_name' => $rf->facility->facility_name
                    ]),
                    'schedule_display' => $schedule['formatted_start_date'] . 
                        ($req->all_day ? ' (All Day)' : ' ' . $schedule['formatted_start_time'])
                ];
            }
            
            // Fetch calendar events
            $calendarEvents = CalendarEvent::where(function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                  ->where('end_date', '>=', $startDate);
            });
            
            if ($facilityId) {
                // If facility-specific, only get events that might affect this facility
                // You may need to adjust based on your event-facility relationship
                $calendarEvents->where('event_type', '!=', 'hall_booking');
            }
            
            $calendarEvents = $calendarEvents->get();
            
            foreach ($calendarEvents as $event) {
                $response['calendar_events'][] = [
                    'event_id' => $event->event_id,
                    'event_name' => $event->event_name,
                    'event_type' => $event->event_type,
                    'description' => $event->description,
                    'color' => $event->color,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'all_day' => $event->all_day
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $response,
                'date_range' => ['start' => $startDate, 'end' => $endDate]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Availability events error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get single facility's schedule for a date (lightweight)
     */
    public function getFacilitySchedule(Request $request, $facilityId)
    {
        try {
            $date = $request->input('date', Carbon::now()->format('Y-m-d'));
            
            $activeStatusIds = FormStatus::whereIn('status_name', [
                'Pending Approval', 'Scheduled', 'Ongoing'
            ])->pluck('status_id');
            
            $requisitions = RequisitionForm::with('formStatus')
                ->whereIn('status_id', $activeStatusIds)
                ->whereHas('requestedFacilities', function ($q) use ($facilityId) {
                    $q->where('facility_id', $facilityId);
                })
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->get();
            
            $timeSlots = [];
            
            foreach ($requisitions as $req) {
                if ($req->all_day) {
                    $timeSlots[] = [
                        'type' => 'requisition',
                        'request_id' => $req->request_id,
                        'title' => $req->calendar_title ?: "Booking #{$req->request_id}",
                        'status' => $req->formStatus->status_name,
                        'all_day' => true
                    ];
                } else {
                    $timeSlots[] = [
                        'type' => 'requisition',
                        'request_id' => $req->request_id,
                        'title' => $req->calendar_title ?: "Booking #{$req->request_id}",
                        'status' => $req->formStatus->status_name,
                        'start_time' => substr($req->start_time, 0, 5),
                        'end_time' => substr($req->end_time, 0, 5)
                    ];
                }
            }
            
            // Check calendar events for this facility
            $calendarEvents = CalendarEvent::where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->get();
            
            foreach ($calendarEvents as $event) {
                if ($event->all_day) {
                    $timeSlots[] = [
                        'type' => 'calendar_event',
                        'event_id' => $event->event_id,
                        'title' => $event->event_name,
                        'description' => $event->description,
                        'all_day' => true
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'facility_id' => $facilityId,
                    'date' => $date,
                    'time_slots' => $timeSlots
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}