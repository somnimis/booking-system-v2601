<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FormStatus;
use App\Models\RequisitionForm;
use App\Models\Admin;
use App\Services\CalendarEventService;
use Illuminate\Http\Request;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalendarEventsController extends Controller
{

    protected $calendarEvents;

    // Inject via constructor
    public function __construct(CalendarEventService $calendarEvents)
    {
        $this->calendarEvents = $calendarEvents;
    }

    public function getCalendarEvents(Request $request)
    {
        try {
            // Determine if this is an admin request
            $isAdmin = $request->has('admin_view') ||
                $request->user() instanceof Admin ||
                str_contains($request->path(), 'admin');

            // Remove the different status filtering - use the same for both views
            $excludedStatuses = FormStatus::whereIn('status_name', [
                'Returned',
                'Late Return',
                'Completed',
                'Rejected',
                'Cancelled'
            ])->pluck('status_id');

            $statusQuery = function ($query) use ($excludedStatuses) {
                $query->whereNotIn('status_id', $excludedStatuses);
            };

            // Get filter parameters
            $facilityIds = $request->get('facilities');
            $statuses = $request->get('statuses');

            $query = RequisitionForm::with([
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'purpose',
                'formStatus'
            ]);

            // Apply status filter (same for both admin and public)
            $query->where($statusQuery);

            // Apply additional status filter if provided
            if ($statuses) {
                $statusIds = explode(',', $statuses);
                $query->whereIn('status_id', $statusIds);
            }

            // Filter by facilities if provided
            if ($facilityIds) {
                $facilityIdArray = explode(',', $facilityIds);
                $query->whereHas('requestedFacilities', function ($q) use ($facilityIdArray) {
                    $q->whereIn('facility_id', $facilityIdArray);
                });
            }

            // Filter by equipment_id if provided
            $equipmentId = $request->get('equipment_id');
            if ($equipmentId) {
                $query->whereHas('requestedEquipment', function ($q) use ($equipmentId) {
                    $q->where('equipment_id', $equipmentId);
                });
            }

            $forms = $query->get();

            // Transform data - SIMPLE transformation, no timezone tricks
            $events = $forms->map(function ($requisition) use ($isAdmin) {
                return $this->calendarEvents->transformCalendarEvent($requisition, $isAdmin);
            });

            return response()->json([
                'success' => true,
                'data' => $events,
                'is_admin' => $isAdmin,
                'filters' => [
                    'facilities' => $facilityIds,
                    'statuses' => $statuses
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Calendar events error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load calendar events: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

public function getAllForCalendar(Request $request)
{
    try {
        // Determine if this is an admin request
        $isAdmin = $request->has('admin_view') ||
            $request->user() instanceof Admin ||
            str_contains($request->path(), 'admin');

        // Simple query - get all calendar events, order by date
        $calendarEvents = CalendarEvent::orderBy('start_date')->get();

        // Transform to simple array format with proper date formatting
        $events = $calendarEvents->map(function ($event) {
            // Extract just the date part (Y-m-d) from the datetime strings
            $startDate = date('Y-m-d', strtotime($event->start_date));
            $endDate = date('Y-m-d', strtotime($event->end_date));
            
            return [
                'event_id' => $event->event_id,
                'event_type' => $event->event_type,
                'event_name' => $event->event_name,
                'description' => $event->description,
                'color' => $event->color,
                'display_name' => $event->display_name,
                'schedule' => [
                    'display' => $event->all_day 
                        ? date('M j, Y', strtotime($startDate)) . (($startDate !== $endDate) ? ' — ' . date('M j, Y', strtotime($endDate)) : '') . ' (All Day)'
                        : date('M j, Y', strtotime($startDate)) . ' ' . 
                          date('g:i A', strtotime($event->start_time)) . ' — ' . 
                          date('g:i A', strtotime($event->end_time)),
                    'start_date' => $startDate, // Just the date part
                    'end_date' => $endDate,     // Just the date part
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'all_day' => (bool)$event->all_day,
                    'is_multi_day' => $startDate !== $endDate
                ],
                'created_at' => $event->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $events,
            'is_admin' => $isAdmin,
            'total' => $events->count()
        ]);

    } catch (\Exception $e) {
        \Log::error('Get all calendar events error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to load calendar events: ' . $e->getMessage(),
            'data' => []
        ], 500);
    }
}

    // For updating a specific request's title and description // 
    public function updateCalendarInfo(Request $request, $requestId)
    {
        try {
            \Log::debug('Updating calendar info', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'input_data' => $request->all()
            ]);

            $validatedData = $request->validate([
                'calendar_title' => 'sometimes|string|max:50|nullable',
                'calendar_description' => 'sometimes|string|max:100|nullable',
            ]);

            $adminId = auth()->id();
            if (!$adminId) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            $form = RequisitionForm::findOrFail($requestId);

            // Update only the provided fields
            if (array_key_exists('calendar_title', $validatedData)) {
                $form->calendar_title = $validatedData['calendar_title'];
            }

            if (array_key_exists('calendar_description', $validatedData)) {
                $form->calendar_description = $validatedData['calendar_description'];
            }

            $form->save();

            \Log::info('Calendar info updated successfully', [
                'request_id' => $requestId,
                'calendar_title' => $form->calendar_title,
                'calendar_description' => $form->calendar_description,
                'admin_id' => $adminId
            ]);

            return response()->json([
                'message' => 'Calendar information updated successfully',
                'calendar_title' => $form->calendar_title,
                'calendar_description' => $form->calendar_description
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Calendar info update validation failed', [
                'request_id' => $requestId,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update calendar info', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update calendar information',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get calendar events for FullCalendar with pagination
     */
    public function index(Request $request)
    {
        try {
            // Set pagination per page (default 50 for calendar events since they're typically smaller)
            $perPage = $request->input('per_page', 50);

            // Build query with efficient selection
            $query = CalendarEvent::query()
                ->select([
                    'event_id',
                    'event_type',
                    'event_name',
                    'description',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'all_day',
                    'created_at'
                ]);

            // Apply date filters if provided
            if ($request->has('start_date')) {
                $query->where('start_date', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->where('end_date', '<=', $request->end_date);
            }

            // Use Laravel's built-in pagination
            $events = $query->orderBy('start_date')
                ->paginate($perPage);

            // Define colors for each event type (centralized in backend)
            $typeColors = [
                'hall_booking' => '#4CAF50', // Green
                'school_event' => '#FF9800', // Orange
                'holiday' => '#F44336',      // Red
            ];

            // Transform the paginated data
            $transformedEvents = $events->through(function ($event) use ($typeColors) {
                // Use Carbon for consistent date formatting
                $startDate = $event->start_date instanceof Carbon
                    ? $event->start_date
                    : Carbon::parse($event->start_date);
                $endDate = $event->end_date instanceof Carbon
                    ? $event->end_date
                    : Carbon::parse($event->end_date);

                // Format times for display
                $formattedStartTime = $event->all_day ? 'All Day' : Carbon::parse($event->start_time)->format('g:i A');
                $formattedEndTime = $event->all_day ? 'All Day' : Carbon::parse($event->end_time)->format('g:i A');

                // Create schedule display string
                $isMultiDay = $startDate->format('Y-m-d') !== $endDate->format('Y-m-d');

                if ($event->all_day) {
                    $scheduleDisplay = $isMultiDay
                        ? $startDate->format('M j, Y') . ' (All Day) — ' . $endDate->format('M j, Y') . ' (All Day)'
                        : $startDate->format('M j, Y') . ' (All Day)';
                } else {
                    $scheduleDisplay = $isMultiDay
                        ? $startDate->format('M j, Y') . ' ' . $formattedStartTime . ' — ' . $endDate->format('M j, Y') . ' ' . $formattedEndTime
                        : $startDate->format('M j, Y') . ' ' . $formattedStartTime . ' — ' . $formattedEndTime;
                }

                return [
                    'event_id' => $event->event_id,
                    'event_type' => $event->event_type,
                    'event_name' => $event->event_name,
                    'description' => $event->description,
                    'color' => $typeColors[$event->event_type] ?? '#9C27B0', // Color added here!
                    'display_name' => ucwords(str_replace('_', ' ', $event->event_type)),
                    'schedule' => [
                        'display' => $scheduleDisplay,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'start_time' => $event->start_time,
                        'end_time' => $event->end_time,
                        'all_day' => $event->all_day,
                        'is_multi_day' => $isMultiDay
                    ],
                    'created_at' => $event->created_at?->toIso8601String()
                ];
            });

            // Return with consistent pagination metadata matching other endpoints
            return response()->json([
                'success' => true,
                'data' => $transformedEvents->values(),
                'meta' => [
                    'current_page' => $events->currentPage(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                    'from' => $events->firstItem(),
                    'to' => $events->lastItem()
                ],
                'links' => [
                    'first' => $events->url(1),
                    'last' => $events->url($events->lastPage()),
                    'prev' => $events->previousPageUrl(),
                    'next' => $events->nextPageUrl()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load calendar events',
                'data' => []
            ], 500);
        }
    }

    /**
     * Get distinct event types with colors
     */
    public function getEventTypes()
    {
        try {
            // Define colors for each event type (centralized in backend)
            $typeColors = [
                'hall_booking' => '#4CAF50', // Green
                'school_event' => '#FF9800', // Orange
                'holiday' => '#F44336',      // Red
            ];

            // Get distinct event types from database
            $eventTypes = CalendarEvent::select('event_type')
                ->distinct()
                ->get()
                ->pluck('event_type');

            // If no events exist, return the enum defaults
            if ($eventTypes->isEmpty()) {
                $eventTypes = collect(['hall_booking', 'school_event', 'holiday']);
            }

            // Format the response with colors from backend
            $formattedTypes = $eventTypes->map(function ($type) use ($typeColors) {
                return [
                    'event_type' => $type,
                    'display_name' => ucwords(str_replace('_', ' ', $type)),
                    'color_code' => $typeColors[$type] ?? '#9C27B0',
                    'text_color' => '#ffffff',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedTypes
            ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Failed to load event types: ' . $e->getMessage());

            // Return enum defaults as fallback
            $defaultTypes = ['hall_booking', 'school_event', 'holiday'];
            $typeColors = [
                'hall_booking' => '#4CAF50',
                'school_event' => '#FF9800',
                'holiday' => '#F44336',
            ];

            $formattedTypes = collect($defaultTypes)->map(function ($type) use ($typeColors) {
                return [
                    'event_type' => $type,
                    'display_name' => ucwords(str_replace('_', ' ', $type)),
                    'color_code' => $typeColors[$type],
                    'text_color' => '#ffffff',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedTypes,
                'note' => 'Returning default types'
            ]);
        }
    }

    /**
     * Store a new calendar event
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'event_name' => 'required|string|max:255',
                'event_type' => 'required|in:hall_booking,school_event,holiday',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_date' => 'required|date|after_or_equal:start_date',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'all_day' => 'boolean'
            ]);

            // For all-day events, you might want to set default times
            if ($validated['all_day'] ?? false) {
                $validated['start_time'] = '00:00';
                $validated['end_time'] = '23:59';
            }

            $calendarEvent = CalendarEvent::create([
                'event_name' => $validated['event_name'],
                'event_type' => $validated['event_type'],
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'],
                'start_time' => $validated['start_time'],
                'end_date' => $validated['end_date'],
                'end_time' => $validated['end_time'],
                'all_day' => $validated['all_day'] ?? false,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'event_id' => $calendarEvent->event_id,
                    'event_name' => $calendarEvent->event_name,
                    'event_type' => $calendarEvent->event_type,
                    'description' => $calendarEvent->description,
                    'start_date' => $calendarEvent->start_date,
                    'start_time' => $calendarEvent->start_time,
                    'end_date' => $calendarEvent->end_date,
                    'end_time' => $calendarEvent->end_time,
                    'all_day' => $calendarEvent->all_day,
                    'created_at' => $calendarEvent->created_at,
                    'updated_at' => $calendarEvent->updated_at,
                ],
                'message' => 'Calendar event created successfully'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create calendar event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a calendar event
     */
    public function destroy($id)
    {
        try {
            $event = CalendarEvent::find($id);

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found'
                ], 404);
            }

            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to delete calendar event: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a calendar event
     */
    public function update(Request $request, $id)
    {
        try {
            $event = CalendarEvent::find($id);

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found'
                ], 404);
            }

            $validated = $request->validate([
                'event_name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date|after_or_equal:start_date',
                'all_day' => 'sometimes|required|boolean',
                'start_time' => 'required_if:all_day,false|nullable|date_format:H:i',
                'end_time' => 'required_if:all_day,false|nullable|date_format:H:i|after:start_time',
            ]);

            // Set default times for all-day events
            if (isset($validated['all_day']) && $validated['all_day']) {
                $validated['start_time'] = '00:00:00';
                $validated['end_time'] = '23:59:59';
            }

            $event->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => $this->calendarEvents->transformRegularEvent($event->fresh())
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update calendar event: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update event',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}