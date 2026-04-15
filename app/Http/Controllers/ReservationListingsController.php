<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RequisitionApproval;
use App\Models\RequisitionFee;
use App\Models\FormStatus;
use App\Models\RequisitionForm;
use App\Models\Admin;
use App\Services\FeeCalculatorService;
use App\Services\RequisitionFormatterService;
use App\Services\CheckAvailabilityService;
use App\Services\ScheduleFormatterService;
use App\Services\AdminActionsService;
;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReservationListingsController extends Controller
{
    protected $feeCalculator;
    protected $formatter;
    protected $availabilityService;
    protected $adminActionsService;
    protected $scheduleFormatter;

    public function __construct(
        FeeCalculatorService $feeCalculator,
        RequisitionFormatterService $formatter,
        CheckAvailabilityService $availabilityService,
        AdminActionsService $adminActionsService,
        ScheduleFormatterService $scheduleFormatterService
    ) {
        $this->feeCalculator = $feeCalculator;
        $this->formatter = $formatter;
        $this->availabilityService = $availabilityService;
        $this->adminActionsService = $adminActionsService;
        $this->scheduleFormatter = $scheduleFormatterService;
    }

    // ------------------------------------------------------------------------
    // Requisition API Methods
    // ------------------------------------------------------------------------

    public function getSingleRequest($requestId)
    {
        $form = RequisitionForm::with($this->getStandardRelations())
            ->findOrFail($requestId);

        // Get the base formatted data from your service
        $response = $this->formatter->formatSingleForm($form);

        // Add the extra fields specific to this endpoint
        $response['action_button'] = $this->adminActionsService->getConfig($form->status_id);
        $response['overlapping_requests'] = $this->availabilityService->getOverlappingRequests($form);

        return response()->json($response);
    }

    public function pendingRequests()
    {
        $excludedStatuses = $this->getExcludedStatusIds();
        $forms = RequisitionForm::whereNotIn('status_id', $excludedStatuses)
            ->with($this->getStandardRelations())
            ->get();

        $result = $forms->map(fn($form) => $this->formatter->formatPendingForm($form));
        return response()->json($result);
    }

    public function paginatedOngoingRequests(Request $request)
    {
        // Get ongoing status IDs (active statuses that are not pending/final)
        $ongoingStatuses = [
            $this->adminActionsService::STATUS_SCHEDULED,
            $this->adminActionsService::STATUS_ONGOING,
            $this->adminActionsService::STATUS_OVERDUE
        ];

        // Build query with efficient selection
        $query = RequisitionForm::whereIn('status_id', $ongoingStatuses)
            ->with($this->getStandardRelations())
            // Select only needed columns to reduce payload
            ->select([
                'request_id',
                'first_name',
                'last_name',
                'email',
                'organization_name',
                'status_id',
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'all_day',
                'num_participants',
                'purpose_id',
                'created_at'
            ]);

        // Get pagination parameters
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);

        // Use Laravel's built-in pagination for efficiency
        $forms = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform the paginated data using a lighter formatter method
        // instead of the heavier formatPendingForm
        $transformedForms = $forms->through(function ($form) {
            // Use existing service methods but only get what's needed
            $scheduleDetails = $this->formatter->getScheduleDetails($form);

            // Use the new formatted duration method
            $durationDisplay = $this->scheduleFormatter->getFormattedDuration($form);

            return [
                'request_id' => $form->request_id,
                'requester' => [
                    'name' => trim($form->first_name . ' ' . $form->last_name),
                    'organization' => $form->organization_name ?? 'No Organization'
                ],
                'status' => [
                    'id' => $form->formStatus->status_id ?? null,
                    'name' => $form->formStatus->status_name ?? 'Unknown',
                    'color' => $form->formStatus->color_code ?? '#6c757d'
                ],
                'schedule' => [
                    'display' => $scheduleDetails['formatted']['start'] . ' - ' . $scheduleDetails['formatted']['end'],
                    'start_date' => $form->start_date,
                    'end_date' => $form->end_date,
                    'all_day' => $form->all_day,
                    'duration' => $durationDisplay // Use the new formatted duration
                ],
                'participants' => $form->num_participants,
                'purpose' => $form->purpose->purpose_name ?? null,
                'created_at' => $form->created_at?->toIso8601String()
            ];
        });

        // Return with consistent pagination metadata
        return response()->json([
            'data' => $transformedForms->values(),
            'meta' => [
                'current_page' => $forms->currentPage(),
                'last_page' => $forms->lastPage(),
                'per_page' => $forms->perPage(),
                'total' => $forms->total(),
                'from' => $forms->firstItem(),
                'to' => $forms->lastItem()
            ],
            'links' => [
                'first' => $forms->url(1),
                'last' => $forms->url($forms->lastPage()),
                'prev' => $forms->previousPageUrl(),
                'next' => $forms->nextPageUrl()
            ]
        ]);
    }
public function paginatedPendingRequests(Request $request)
{
    try {
        /** @var Admin $admin */
        $admin = $request->user();
        
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated - Admin not found'
            ], 401);
        }
        
        // Log admin info for debugging
        \Log::info('Admin ID: ' . $admin->admin_id);
        \Log::info('Admin Role ID: ' . $admin->role_id);
        
        // Define pending status IDs
        $pendingStatusIds = [
            1, // STATUS_PENCIL_BOOKED
            2, // STATUS_PENDING_APPROVAL
            3  // STATUS_AWAITING_PAYMENT
        ];
        
        // Check if admin is Head Admin (assuming role_id = 1 for Head Admin)
        // You may need to adjust this based on your actual role_id values
        $isHeadAdmin = $admin->role_id === 1; // or check role name if you have that relationship loaded
        
        // If you have the role relationship loaded, you can also check by name:
        // $isHeadAdmin = $admin->role && $admin->role->role_name === 'Head Admin';
        
        // Get departments managed by this admin (only needed if not head admin)
        $managedDepartmentIds = [];
        if (!$isHeadAdmin) {
            $managedDepartmentIds = $admin->departments()
                ->pluck('departments.department_id')
                ->toArray();
        }
        
        // Get services managed by this admin (only needed if not head admin)
        $managedServiceIds = [];
        if (!$isHeadAdmin) {
            $managedServiceIds = $admin->services()
                ->pluck('extra_services.service_id')
                ->toArray();
        }
        
        \Log::info('Is Head Admin: ' . ($isHeadAdmin ? 'Yes' : 'No'));
        \Log::info('Managed Department IDs:', $managedDepartmentIds);
        \Log::info('Managed Service IDs:', $managedServiceIds);
        
        // Set pagination per page
        $perPage = $request->input('per_page', 15);
        
        // Build the query
        $query = RequisitionForm::whereIn('status_id', $pendingStatusIds);
        
        // Apply ownership filter ONLY if not head admin
        if (!$isHeadAdmin) {
            if (empty($managedDepartmentIds) && empty($managedServiceIds)) {
                \Log::warning('Non-head admin has no managed departments or services');
                // Return empty result if non-head admin has no managed items
                $forms = $query->whereRaw('1 = 0')->paginate($perPage);
            } else {
                $query->where(function ($subQuery) use ($managedDepartmentIds, $managedServiceIds) {
                    $hasConditions = false;
                    
                    // Subquery for facilities
                    if (!empty($managedDepartmentIds)) {
                        $subQuery->whereHas('requestedFacilities.facility', function ($q) use ($managedDepartmentIds) {
                            $q->whereIn('department_id', $managedDepartmentIds);
                        });
                        $hasConditions = true;
                    }
                    
                    // Subquery for equipment
                    if (!empty($managedDepartmentIds)) {
                        if ($hasConditions) {
                            $subQuery->orWhereHas('requestedEquipment.equipment', function ($q) use ($managedDepartmentIds) {
                                $q->whereIn('department_id', $managedDepartmentIds);
                            });
                        } else {
                            $subQuery->whereHas('requestedEquipment.equipment', function ($q) use ($managedDepartmentIds) {
                                $q->whereIn('department_id', $managedDepartmentIds);
                            });
                            $hasConditions = true;
                        }
                    }
                    
                    // Subquery for services
                    if (!empty($managedServiceIds)) {
                        if ($hasConditions) {
                            $subQuery->orWhereHas('requestedServices.service', function ($q) use ($managedServiceIds) {
                                $q->whereIn('service_id', $managedServiceIds);
                            });
                        } else {
                            $subQuery->whereHas('requestedServices.service', function ($q) use ($managedServiceIds) {
                                $q->whereIn('service_id', $managedServiceIds);
                            });
                        }
                    }
                });
            }
        } else {
            \Log::info('Head Admin - bypassing department/service filters');
            // For head admin, no additional filters needed
        }
        
        // Execute the query with eager loading
        $forms = $query->with([
                'formStatus',
                'requestedFacilities.facility' => function ($query) {
                    $query->select(['facility_id', 'facility_name', 'department_id']);
                },
                'requestedEquipment.equipment' => function ($query) {
                    $query->select(['equipment_id', 'equipment_name', 'department_id']);
                },
                'requestedServices.service' => function ($query) {
                    $query->select(['service_id', 'service_name']);
                }
            ])
            ->select([
                'request_id',
                'first_name',
                'last_name',
                'email',
                'organization_name',
                'status_id',
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'all_day',
                'created_at'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        // Transform the paginated data
        $transformedForms = $forms->through(function ($form) use ($request, $isHeadAdmin, $managedDepartmentIds, $managedServiceIds) {
            try {
                // Use RequisitionFormatterService for formatting
                $scheduleDetails = $this->formatter->getScheduleDetails($form);
                
                // Use the new formatted duration method
                $durationDisplay = $this->scheduleFormatter->getFormattedDuration($form);
                
                // Get requested facilities
                $facilities = collect();
                if ($form->relationLoaded('requestedFacilities') && $form->requestedFacilities) {
                    $facilities = $form->requestedFacilities->map(function ($facility) {
                        return [
                            'id' => $facility->facility_id,
                            'name' => $facility->facility->facility_name ?? null,
                            'quantity' => 1,
                            'type' => 'facility',
                            'department_id' => $facility->facility->department_id ?? null
                        ];
                    });
                }
                
                // Get requested equipment
                $equipment = collect();
                if ($form->relationLoaded('requestedEquipment') && $form->requestedEquipment) {
                    $equipment = $form->requestedEquipment->map(function ($equipment) {
                        return [
                            'id' => $equipment->equipment_id,
                            'name' => $equipment->equipment->equipment_name ?? null,
                            'quantity' => $equipment->quantity,
                            'type' => 'equipment',
                            'department_id' => $equipment->equipment->department_id ?? null
                        ];
                    });
                }
                
                // Get requested services
                $services = collect();
                if ($form->relationLoaded('requestedServices') && $form->requestedServices) {
                    $services = $form->requestedServices->map(function ($service) {
                        return [
                            'id' => $service->service_id,
                            'name' => $service->service->service_name ?? null,
                            'type' => 'service'
                        ];
                    });
                }
                
                // Combine all items
                $requestedItems = $facilities->concat($equipment)->concat($services)->values();
                
                // Optional debug info
                $ownershipInfo = null;
                if ($request->has('debug')) {
                    if ($isHeadAdmin) {
                        $ownershipInfo = [
                            'is_head_admin' => true,
                            'note' => 'Head admin sees all requests'
                        ];
                    } else {
                        $ownershipInfo = [
                            'has_facility_ownership' => $form->requestedFacilities && $form->requestedFacilities->contains(function ($facility) use ($managedDepartmentIds) {
                                return in_array($facility->facility->department_id ?? null, $managedDepartmentIds);
                            }),
                            'has_equipment_ownership' => $form->requestedEquipment && $form->requestedEquipment->contains(function ($equipment) use ($managedDepartmentIds) {
                                return in_array($equipment->equipment->department_id ?? null, $managedDepartmentIds);
                            }),
                            'has_service_ownership' => $form->requestedServices && $form->requestedServices->contains(function ($service) use ($managedServiceIds) {
                                return in_array($service->service_id ?? null, $managedServiceIds);
                            })
                        ];
                    }
                }
                
                return [
                    'request_id' => $form->request_id,
                    'requester' => [
                        'name' => trim(($form->first_name ?? '') . ' ' . ($form->last_name ?? '')),
                        'email' => $form->email ?? '',
                        'organization' => $form->organization_name ?? 'No Organization'
                    ],
                    'status' => [
                        'id' => $form->formStatus->status_id ?? null,
                        'name' => $form->formStatus->status_name ?? 'Unknown',
                        'color' => $form->formStatus->color_code ?? '#6c757d'
                    ],
                    'schedule' => [
                        'display' => ($scheduleDetails['formatted']['start'] ?? '') . ' - ' . ($scheduleDetails['formatted']['end'] ?? ''),
                        'start_date' => $form->start_date,
                        'end_date' => $form->end_date,
                        'all_day' => $form->all_day ?? false,
                        'duration' => $durationDisplay ?? 'N/A'
                    ],
                    'requested_items' => $requestedItems,
                    'total_items' => $requestedItems->count(),
                    'created_at' => $form->created_at?->toIso8601String(),
                    'ownership_info' => $ownershipInfo
                ];
            } catch (\Exception $e) {
                \Log::error('Error transforming form ID ' . ($form->request_id ?? 'unknown') . ': ' . $e->getMessage());
                
                return [
                    'request_id' => $form->request_id,
                    'requester' => [
                        'name' => 'Error loading data',
                        'email' => '',
                        'organization' => 'Error'
                    ],
                    'status' => [
                        'id' => null,
                        'name' => 'Error',
                        'color' => '#dc3545'
                    ],
                    'schedule' => [
                        'display' => 'Error loading schedule',
                        'start_date' => null,
                        'end_date' => null,
                        'all_day' => false,
                        'duration' => 'N/A'
                    ],
                    'requested_items' => [],
                    'total_items' => 0,
                    'created_at' => null
                ];
            }
        });
        
        // Return with pagination metadata
        return response()->json([
            'success' => true,
            'data' => $transformedForms->values(),
            'meta' => [
                'current_page' => $forms->currentPage(),
                'last_page' => $forms->lastPage(),
                'per_page' => $forms->perPage(),
                'total' => $forms->total(),
                'from' => $forms->firstItem(),
                'to' => $forms->lastItem()
            ],
            'links' => [
                'first' => $forms->url(1),
                'last' => $forms->url($forms->lastPage()),
                'prev' => $forms->previousPageUrl(),
                'next' => $forms->nextPageUrl()
            ]
        ]);
        
    } catch (\Exception $e) {
        \Log::error('paginatedPendingRequests error: ' . $e->getMessage());
        \Log::error($e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while fetching pending requests',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null
        ], 500);
    }
}

public function getAvailableForTransaction()
{
    // Status IDs that are eligible for equipment release
    // Adjust these based on your actual status IDs
    $eligibleStatusIds = [
        3, // Scheduled/Awaiting Payment? 
        4, // Approved/Scheduled
        5, // Ongoing
    ];

    $requisitions = RequisitionForm::whereIn('status_id', $eligibleStatusIds)
        ->where('start_date', '>=', now()->subDays(7)) // Only recent and upcoming
        ->with([
            'purpose',
            'requestedEquipment.equipment'
        ])
        ->select([
            'request_id',
            'first_name',
            'last_name',
            'organization_name',
            'start_date',
            'end_date',
            'status_id'
        ])
        ->orderBy('start_date', 'asc')
        ->limit(50) // Limit to prevent huge dropdowns
        ->get();

    $formatted = $requisitions->map(function ($req) {
        $requester = $req->organization_name 
            ? $req->organization_name 
            : trim($req->first_name . ' ' . $req->last_name);
        
        // Get equipment count for display
        $equipmentCount = $req->requestedEquipment->count();
        
        return [
            'request_id' => $req->request_id,
            'label' => "R-{$req->request_id} - {$requester} (" . 
                      date('M d', strtotime($req->start_date)) . " - " . 
                      date('M d', strtotime($req->end_date)) . ")" .
                      ($equipmentCount ? " [{$equipmentCount} items]" : ""),
            'start_date' => $req->start_date,
            'end_date' => $req->end_date
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $formatted
    ]);
}

    public function getRequisitionFormById($requestId)
    {
        try {
            $form = RequisitionForm::with($this->getStandardRelations())
                ->findOrFail($requestId);
            return response()->json($this->formatter->formatSingleForm($form));
        } catch (\Exception $e) {
            Log::error('Failed to fetch requisition form by ID', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Failed to fetch requisition form',
                'details' => $e->getMessage(),
            ], 404);
        }
    }

    public function completedRequests()
    {
        $includedStatuses = FormStatus::whereIn('status_name', [
            'Returned',
            'Late Return',
            'Completed',
            'Rejected',
            'Cancelled'
        ])->pluck('status_id');

        $forms = RequisitionForm::whereIn('status_id', $includedStatuses)
            ->with($this->getStandardRelations())
            ->get()
            ->map(fn($form) => $this->formatter->formatCompletedForm($form));

        return response()->json($forms);
    }

    public function getArchivedRequisitions()
    {
        try {
            // Get status IDs to exclude
            $excludedStatuses = ['Pending Approval', 'Awaiting Payment', 'Scheduled', 'Ongoing', 'Late'];
            $excludedStatusIds = FormStatus::whereIn('status_name', $excludedStatuses)
                ->pluck('status_id')
                ->toArray();

            \Log::info('Fetching archived requisitions', [
                'excluded_statuses' => $excludedStatuses,
                'excluded_status_ids' => $excludedStatusIds
            ]);

            // Get requisitions excluding the specified statuses
            $archivedRequisitions = RequisitionForm::with([
                'status',
                'purpose',
                'requestedFacilities.facility',
                'requestedEquipment.equipment'
            ])
                ->whereNotIn('status_id', $excludedStatusIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($requisition) {
                    try {
                        // Handle all-day events differently
                        if ($requisition->all_day) {
                            $startSchedule = Carbon::parse($requisition->start_date)->format('F j, Y') . ' (All Day)';
                            $endSchedule = Carbon::parse($requisition->end_date)->format('F j, Y') . ' (All Day)';
                        } else {
                            // Create datetime strings without seconds first
                            $startDateTimeStr = $requisition->start_date . ' ' . $requisition->start_time;
                            $endDateTimeStr = $requisition->end_date . ' ' . $requisition->end_time;
                            // Parse the datetime strings - Carbon will handle the parsing automatically
                            $startDateTime = Carbon::parse($startDateTimeStr);
                            $endDateTime = Carbon::parse($endDateTimeStr);

                            $startSchedule = $startDateTime->format('F j, Y \a\t g:i A');
                            $endSchedule = $endDateTime->format('F j, Y \a\t g:i A');
                        }
                    } catch (\Exception $e) {
                        \Log::error('Date formatting error for request ' . $requisition->request_id . ': ' . $e->getMessage());
                        // Fallback to original format if parsing fails
                        $startSchedule = $requisition->start_date . ' ' . $requisition->start_time;
                        $endSchedule = $requisition->end_date . ' ' . $requisition->end_time;
                    }

                    return [
                        'request_id' => $requisition->request_id,
                        'official_receipt_num' => $requisition->official_receipt_num,
                        'requester_name' => $requisition->first_name . ' ' . $requisition->last_name,
                        'email' => $requisition->email,
                        'organization_name' => $requisition->organization_name,
                        'purpose' => $requisition->purpose->purpose_name ?? 'N/A',
                        'status' => $requisition->status->status_name,
                        'status_color' => $requisition->status->color_code,
                        'start_date' => $requisition->start_date,
                        'end_date' => $requisition->end_date,
                        'start_time' => $requisition->start_time,
                        'end_time' => $requisition->end_time,
                        'all_day' => $requisition->all_day, // ADDED
                        'start_schedule' => $startSchedule,
                        'end_schedule' => $endSchedule,
                        'num_participants' => $requisition->num_participants,
                        'facilities' => $requisition->requestedFacilities->map(function ($rf) {
                            return $rf->facility->facility_name ?? 'Unknown Facility';
                        })->toArray(),
                        'equipment' => $requisition->requestedEquipment->map(function ($re) {
                            $name = $re->equipment->equipment_name ?? 'Unknown Equipment';
                            $quantity = $re->quantity > 1 ? " × {$re->quantity}" : '';
                            return $name . $quantity;
                        })->toArray(),
                        'created_at' => $requisition->created_at,
                        'updated_at' => $requisition->updated_at
                    ];
                });

            \Log::info('Archived requisitions loaded', [
                'total_archived' => $archivedRequisitions->count(),
                'excluded_status_count' => count($excludedStatusIds)
            ]);

            return response()->json([
                'success' => true,
                'data' => $archivedRequisitions
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching archived requisitions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load archived requisitions: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function getFormByAccessCode($accessCode)
    {
        try {
            $form = RequisitionForm::with([
                'formStatus:status_id,status_name,color_code',
                'requestedFacilities.facility:facility_id,facility_name,base_fee,rate_type',
                'requestedEquipment.equipment:equipment_id,equipment_name,base_fee,rate_type',
                'purpose:purpose_id,purpose_name',
                'requisitionFees',
            ])->where('access_code', $accessCode)->firstOrFail();

            return response()->json($this->formatter->formatPublicForm($form));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Form not found',
                'details' => $e->getMessage(),
            ], 404);
        }
    }

    // ------------------------------------------------------------------------
    // Public methods
    // ------------------------------------------------------------------------

    public function getRequisitionFees($requestId)
    {
        try {
            $fees = RequisitionFee::with('addedBy')
                ->where('request_id', $requestId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($fee) => $this->formatter->formatRequisitionFee($fee));

            return response()->json($fees);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch fees',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getApprovalHistory($requestId)
    {
        try {
            $approvals = RequisitionApproval::with(['approvedBy', 'rejectedBy'])
                ->where('request_id', $requestId)
                ->orderBy('date_updated', 'desc')
                ->get()
                ->map(function ($approval) {
                    $admin = $approval->approvedBy ?: $approval->rejectedBy;
                    $action = $approval->approved_by ? 'approved' : 'rejected';

                    return [
                        'admin_id' => $admin ? $admin->admin_id : null,
                        'admin_name' => $admin ? $admin->first_name . ' ' . $admin->last_name : 'Unknown Admin',
                        'admin_photo' => $admin->photo_url ?? null,
                        'action' => $action,
                        'action_class' => $approval->approved_by ? 'text-success' : 'text-danger',
                        'action_icon' => $approval->approved_by ? 'fa-thumbs-up' : 'fa-thumbs-down',
                        'remarks' => $approval->remarks,
                        'date_updated' => $approval->date_updated,
                        'formatted_date' => Carbon::parse($approval->date_updated)->format('M j, Y g:i A')
                    ];
                });

            return response()->json($approvals);
        } catch (\Exception $e) {
            Log::error('Failed to fetch approval history', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Failed to fetch approval history',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getPendingCount(Request $request)
    {
        try {
            $count = RequisitionForm::whereHas('formStatus', function ($query) {
                $query->whereIn('status_name', ['Pending Approval', 'Awaiting Payment']);
            })->count();

            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'count' => 0], 500);
        }
    }

    // ------------------------------------------------------------------------
    // Simple query helpers (can stay in controller or move to repository)
    // ------------------------------------------------------------------------

    private function getExcludedStatusIds()
    {
        return FormStatus::whereIn('status_name', [
            'Returned',
            'Late Return',
            'Completed',
            'Rejected',
            'Cancelled',
        ])->pluck('status_id');
    }

    private function getStandardRelations()
    {
        return [
            'formStatus',
            'requestedFacilities.facility',
            'requestedEquipment.equipment',
            'requestedServices.service',
            'requisitionApprovals',
            'requisitionFees.addedBy',
            'purpose',
            'finalizedBy.role',
            'closedBy',
        ];
    }
}