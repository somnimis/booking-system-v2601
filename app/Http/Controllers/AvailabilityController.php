<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\RequisitionForm;
use App\Models\CalendarEvent;
use App\Models\FormStatus;
use App\Services\ScheduleFormatterService;
use App\Services\FacilityHierarchyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvailabilityController extends Controller
{
    protected $scheduleFormatter;
    protected $hierarchyService;

    public function __construct(
        ScheduleFormatterService $scheduleFormatter,
        FacilityHierarchyService $hierarchyService
    ) {
        $this->scheduleFormatter = $scheduleFormatter;
        $this->hierarchyService = $hierarchyService;
    }


    /**
     * Paginated facility hierarchy for the matrix.
     * - view=venues → uses Facility::venues() scope
     * - view=rooms  → uses Facility::rooms() scope, grouped by parent
     * Returns meta for prev/next UI.
     */
    public function getFacilitiesHierarchy(Request $request)
    {
        try {
            $view = $request->input('view', 'venues');
            $perPage = (int) $request->input('per_page', 10);
            $page = max(1, (int) $request->input('page', 1));
            $parentId = $request->input('parent_id'); // Building ID
            $facilityId = $request->input('facility_id'); // Venue ID or Room ID

            if ($view === 'rooms') {
                // If a specific room is selected from the dropdown
                if ($facilityId && !str_starts_with($facilityId, 'parent_')) {
                    $roomData = $this->hierarchyService->getSingleRoom((int) $facilityId);
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'hierarchy' => $roomData ? [$roomData] : [],
                            'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => $roomData ? 1 : 0, 'per_page' => 10]
                        ]
                    ]);
                }

                // If a specific building is selected, or viewing all buildings
                $parentFilter = null;
                if ($parentId) {
                    $parentFilter = (int) $parentId;
                } elseif ($facilityId && str_starts_with($facilityId, 'parent_')) {
                    $parentFilter = (int) str_replace('parent_', '', $facilityId);
                }

                // Paginate by BUILDINGS (so 10 buildings = 10 rows)
                $paginator = $this->hierarchyService->getBuildingsWithChildren($perPage, $page, $parentFilter);
                
                $hierarchy = collect($paginator->items())->map(function ($f) {
                    // Cap children to 10 per building to prevent infinite scrolling
                    $children = $f->childFacilities->take(10)->map(fn($c) => [
                        'facility_id' => $c->facility_id,
                        'facility_name' => $c->facility_name,
                        'capacity' => $c->capacity,
                        'base_fee' => $c->base_fee,
                        'is_parent' => false,
                        'parent_facility_id' => $c->parent_facility_id,
                    ])->values();

                    return [
                        'facility_id' => $f->facility_id,
                        'facility_name' => $f->facility_name,
                        'capacity' => $f->capacity,
                        'base_fee' => $f->base_fee,
                        'is_parent' => true,
                        'children' => $children,
                    ];
                })->values();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'hierarchy' => $hierarchy,
                        'meta' => $this->hierarchyService->getPaginatorMeta($paginator),
                    ]
                ]);
            }

            // Venues View
            $paginator = $this->hierarchyService->getVenues($perPage, $page, $facilityId ? (int) $facilityId : null);
            $hierarchy = collect($paginator->items())->map(function ($f) {
                return [
                    'facility_id' => $f->facility_id,
                    'facility_name' => $f->facility_name,
                    'capacity' => $f->capacity,
                    'base_fee' => $f->base_fee,
                    'is_parent' => false,
                    'children' => [],
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'hierarchy' => $hierarchy,
                    'meta' => $this->hierarchyService->getPaginatorMeta($paginator),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error loading facility hierarchy: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    /** Flat full facility list for dropdowns (lightweight, no pagination). */
    public function getFacilitiesListFlat()
    {
        $items = Facility::select('facility_id', 'facility_name', 'capacity', 'parent_facility_id')
            ->where('status_id', 1)
            ->orderBy('facility_name')
            ->get();
        return response()->json(['success' => true, 'data' => $items]);
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

            // Displayable statuses for the calendar.
            // Excludes terminal states (Completed, Rejected, Cancelled) per FormStatusSeeder.
            $activeStatusIds = FormStatus::whereIn('status_name', [
                'Pending Approval',
                'Awaiting Payment',
                'Verifying Payment',
                'Reserved',
            ])->pluck('status_id');

            $response = [
                'requisitions' => [],
                'calendar_events' => []
            ];

            // Fetch requisitions for the date range with equipment relationship
            $requisitionQuery = RequisitionForm::with([
                'formStatus',
                'requestedFacilities.facility',
                'requestedEquipment.equipment'  // ← ADD THIS: load equipment with their details
            ])->whereIn('status_id', $activeStatusIds)
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

                // Get facilities with names (already have this)
                $facilities = $req->requestedFacilities->map(fn($rf) => [
                    'facility_id' => $rf->facility_id,
                    'facility_name' => $rf->facility->facility_name ?? 'Unknown Facility'
                ]);

                // ← ADD THIS: Get equipment with names
                $equipment = $req->requestedEquipment->map(fn($re) => [
                    'requested_equipment_id' => $re->requested_equipment_id,
                    'equipment_id' => $re->equipment_id,
                    'equipment_name' => $re->equipment->equipment_name ?? 'Unknown Equipment',
                    'quantity' => $re->quantity,
                    'is_waived' => $re->is_waived
                ]);

                $response['requisitions'][] = [
                    'request_id' => $req->request_id,
                    'title' => $req->event_title ?: "Booking #{$req->request_id}",
                    'event_details' => $req->event_details,
                    'status' => $req->formStatus->status_name,
                    'status_color' => $req->formStatus->color_code,
                    'start_date' => $req->start_date,
                    'end_date' => $req->end_date,
                    'start_time' => $req->start_time,
                    'end_time' => $req->end_time,
                    'all_day' => $req->all_day,
                    'facilities' => $facilities,
                    'equipment' => $equipment,  // ← ADD THIS: include equipment in response
                    'schedule_display' => $schedule['formatted_start_date'] .
                        ($req->all_day ? ' (All Day)' : ' ' . $schedule['formatted_start_time'])
                ];
            }

            // Fetch calendar events. Eager-load venues so the frontend can filter
// hall_bookings per facility row; school_event/holiday render globally.
            $calendarEvents = CalendarEvent::with('venues:facility_id,facility_name')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                });

            if ($facilityId) {
                $calendarEvents->where('event_type', '!=', 'hall_booking');
            }

            $calendarEvents = $calendarEvents->get();

            foreach ($calendarEvents as $event) {
                $response['calendar_events'][] = [
                    'event_id' => $event->event_id,
                    'event_name' => $event->event_name,
                    'event_type' => $event->event_type,
                    'description' => $event->description,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'all_day' => $event->all_day,
                    'venues' => $event->venues->map(fn($v) => [
                        'facility_id' => $v->facility_id,
                        'facility_name' => $v->facility_name,
                    ])->values(),
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
     * Global search across requisitions and calendar events.
     * Matches title/details on both, plus facility names via joins.
     * Decoupled from the matrix — returns a flat, date-agnostic list.
     */
    public function searchAvailability(Request $request)
    {
        try {
            $q = trim((string) $request->input('q', ''));
            $limit = min((int) $request->input('limit', 20), 50);

            if ($q === '') {
                return response()->json(['success' => true, 'data' => []]);
            }

            $like = '%' . $q . '%';

            $displayableStatuses = FormStatus::whereIn('status_name', [
                'Pending Approval',
                'Awaiting Payment',
                'Verifying Payment',
                'Reserved',
            ])->pluck('status_id');

            // Requisitions — match title/details OR any requested facility's name.
            $requisitions = RequisitionForm::with(['formStatus', 'requestedFacilities.facility'])
                ->whereIn('status_id', $displayableStatuses)
                ->where(function ($sub) use ($like) {
                    $sub->where('event_title', 'LIKE', $like)
                        ->orWhere('event_details', 'LIKE', $like)
                        ->orWhereHas('requestedFacilities.facility', function ($f) use ($like) {
                            $f->where('facility_name', 'LIKE', $like);
                        });
                })
                ->orderBy('start_date')
                ->limit($limit)
                ->get();

            // Calendar events — match name/description OR any venue's name.
            $calendarEvents = CalendarEvent::with('venues:facility_id,facility_name')
                ->where(function ($sub) use ($like) {
                    $sub->where('event_name', 'LIKE', $like)
                        ->orWhere('description', 'LIKE', $like)
                        ->orWhereHas('venues', function ($v) use ($like) {
                            $v->where('facility_name', 'LIKE', $like);
                        });
                })
                ->orderBy('start_date')
                ->limit($limit)
                ->get();

            $results = [];

            foreach ($requisitions as $req) {
                $results[] = [
                    'type' => 'requisition',
                    'id' => $req->request_id,
                    'title' => $req->event_title ?: "Booking #{$req->request_id}",
                    'event_details' => $req->event_details,
                    'status' => $req->formStatus->status_name ?? '',
                    'status_color' => $req->formStatus->color_code ?? '#6c757d',
                    'start_date' => $req->start_date,
                    'end_date' => $req->end_date,
                    'start_time' => $req->start_time,
                    'end_time' => $req->end_time,
                    'all_day' => (bool) $req->all_day,
                    'facilities' => $req->requestedFacilities
                        ->map(fn($rf) => $rf->facility->facility_name ?? null)
                        ->filter()
                        ->values(),
                ];
            }

            foreach ($calendarEvents as $ev) {
                $results[] = [
                    'type' => 'calendar_event',
                    'id' => $ev->event_id,
                    'title' => $ev->event_name,
                    'event_details' => $ev->description,
                    'status' => $ev->event_type,
                    'status_color' => null,
                    'start_date' => $ev->start_date,
                    'end_date' => $ev->end_date,
                    'start_time' => $ev->start_time,
                    'end_time' => $ev->end_time,
                    'all_day' => (bool) $ev->all_day,
                    'facilities' => $ev->venues->pluck('facility_name')->values(),
                ];
            }

            // Interleave by start_date then title.
            usort($results, function ($a, $b) {
                $cmp = strcmp((string) $a['start_date'], (string) $b['start_date']);
                return $cmp !== 0 ? $cmp : strcmp((string) $a['title'], (string) $b['title']);
            });

            return response()->json([
                'success' => true,
                'data' => array_slice($results, 0, $limit),
            ]);
        } catch (\Exception $e) {
            \Log::error('Availability search error: ' . $e->getMessage());
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

            // Displayable statuses for the calendar.
// Excludes terminal states (Completed, Rejected, Cancelled) per FormStatusSeeder.
            $activeStatusIds = FormStatus::whereIn('status_name', [
                'Pending Approval',
                'Awaiting Payment',
                'Verifying Payment',
                'Reserved',
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
                        'title' => $req->event_title ?: "Booking #{$req->request_id}",
                        'status' => $req->formStatus->status_name,
                        'all_day' => true
                    ];
                } else {
                    $timeSlots[] = [
                        'type' => 'requisition',
                        'request_id' => $req->request_id,
                        'title' => $req->event_title ?: "Booking #{$req->request_id}",
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