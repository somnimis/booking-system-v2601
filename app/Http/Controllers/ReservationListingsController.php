<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RequisitionApproval;
use App\Models\FormStatus;
use App\Models\Feedback;
use App\Models\RequisitionForm;
use App\Models\Admin;
use App\Services\FeeCalculatorService;
use App\Services\RequisitionFormatterService;
use App\Services\CheckAvailabilityService;
use App\Services\ScheduleFormatterService;
use App\Services\AdminActionsService;
use Illuminate\Support\Facades\DB; 
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

    /**
     * Filter requisition forms based on admin's managing departments only.
     * 
     * This function retrieves requisition forms by status and filters them
     * to only show forms that belong to departments managed by the current admin.
     * Head admins bypass all filters and see all requests.
     */
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

            // Get status filter from request (default: 1 for Pending Approval)
            $statusId = $request->input('status_id', 1);

            // Validate status ID 
            // 1=Pending Approval, 2=Awaiting Payment, 3=Verifying Payment, 4=Reserved
            // 5=Completed, 6=Rejected, 7=Cancelled
            if (!in_array($statusId, [1, 2, 3, 4, 5, 6, 7])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status ID. Must be 1-7'
                ], 400);
            }

            $isHeadAdmin = $admin->role_id === 1;

            // Get department IDs managed by this admin
            $managedDepartmentIds = $admin->departments()->pluck('departments.department_id')->toArray();

            $perPage = $request->input('per_page', 15);

            // Get sort order from request (default: 'asc' for oldest first)
            $sortOrder = $request->input('sort_order', 'asc');

            // Validate sort order to prevent SQL injection
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'asc';
            }

            // Check if this request is for counts only
            $getCountsOnly = $request->input('counts_only', false);

            // Build query with status filter
            $query = RequisitionForm::where('status_id', $statusId);

            // Apply department-based filtering for non-head admins
            if (!$isHeadAdmin && !empty($managedDepartmentIds)) {
                $query->where(function ($subQuery) use ($managedDepartmentIds) {
                    $subQuery->whereHas('requestedFacilities.facility', function ($q) use ($managedDepartmentIds) {
                        $q->whereIn('managed_by', $managedDepartmentIds);
                    })->orWhereHas('requestedEquipment.equipment', function ($q) use ($managedDepartmentIds) {
                        $q->whereIn('managed_by', $managedDepartmentIds);
                    })->orWhereHas('requestedServices.service', function ($q) use ($managedDepartmentIds) {
                        $q->whereIn('managed_by', $managedDepartmentIds);
                    })->orWhereHas('purpose', function ($q) use ($managedDepartmentIds) {
                        $q->whereIn('routes_to', $managedDepartmentIds);
                    });
                });
            } elseif (!$isHeadAdmin && empty($managedDepartmentIds)) {
                // Admin has no managed departments - return empty
                if ($getCountsOnly) {
                    return response()->json([
                        'success' => true,
                        'counts' => [
                            'pending' => 0,
                            'awaiting' => 0,
                            'verifying' => 0,
                            'reserved' => 0,
                        ]
                    ]);
                }

                $forms = $query->whereRaw('1 = 0')->paginate($perPage);
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                        'sort_order' => $sortOrder,
                        'status_id' => $statusId
                    ],
                    'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null]
                ]);
            }

            // If counts only, return just the counts for all statuses
            if ($getCountsOnly) {
                $counts = [];
                // Updated status IDs based on new seeder:
                // 1 = Pending Approval
                // 2 = Awaiting Payment
                // 3 = Verifying Payment
                // 4 = Reserved
                $statuses = [1, 2, 3, 4];

                foreach ($statuses as $status) {
                    $statusQuery = RequisitionForm::where('status_id', $status);

                    // Apply same department filtering
                    if (!$isHeadAdmin && !empty($managedDepartmentIds)) {
                        $statusQuery->where(function ($subQuery) use ($managedDepartmentIds) {
                            $subQuery->whereHas('requestedFacilities.facility', function ($q) use ($managedDepartmentIds) {
                                $q->whereIn('managed_by', $managedDepartmentIds);
                            })->orWhereHas('requestedEquipment.equipment', function ($q) use ($managedDepartmentIds) {
                                $q->whereIn('managed_by', $managedDepartmentIds);
                            })->orWhereHas('requestedServices.service', function ($q) use ($managedDepartmentIds) {
                                $q->whereIn('managed_by', $managedDepartmentIds);
                            })->orWhereHas('purpose', function ($q) use ($managedDepartmentIds) {
                                $q->whereIn('routes_to', $managedDepartmentIds);
                            });
                        });
                    }

                    $counts[$status] = $statusQuery->count();
                }

                return response()->json([
                    'success' => true,
                    'counts' => [
                        'pending' => $counts[1] ?? 0,           // Pending Approval
                        'awaiting' => $counts[2] ?? 0,          // Awaiting Payment
                        'verifying' => $counts[3] ?? 0,         // Verifying Payment
                        'reserved' => $counts[4] ?? 0,          // Reserved
                    ]
                ]);
            }

            // Apply sorting based on user preference
            $forms = $query->with([
                'formStatus',
                'purpose'
            ])->select([
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
                        'created_at',
                        'event_title',
                        'event_details'
                    ])->orderBy('created_at', $sortOrder)->paginate($perPage);

            $transformedForms = $forms->through(function ($form) {
                try {
                    $scheduleDetails = $this->formatter->getScheduleDetails($form);
                    $durationDisplay = $this->scheduleFormatter->getFormattedDuration($form);

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
                        'event_title' => $form->event_title ?? 'No Title',
                        'event_details' => $form->event_details ?? 'No Description',
                        'created_at' => $form->created_at?->toIso8601String()
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error transforming form: ' . $e->getMessage());
                    return ['request_id' => $form->request_id, 'requester' => ['name' => 'Error loading data']];
                }
            });

            return response()->json([
                'success' => true,
                'data' => $transformedForms->values(),
                'meta' => [
                    'current_page' => $forms->currentPage(),
                    'last_page' => $forms->lastPage(),
                    'per_page' => $forms->perPage(),
                    'total' => $forms->total(),
                    'from' => $forms->firstItem(),
                    'to' => $forms->lastItem(),
                    'sort_order' => $sortOrder,
                    'status_id' => $statusId
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
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching requisitions'
            ], 500);
        }
    }

        /**
     * Return requisitions where the current admin has an actionable Pending approval.
     *
     * "Actionable" means: this admin holds a requisition_approvals row with
     * status = 'Pending' at the form's currently-active stage.
     *
     * Active stage resolution:
     *   1. Any stage=1 row Pending → stage 1
     *   2. Else any stage=2 row Pending → stage 2
     *   3. Else form status = 'Verifying Payment' → stage 3 (issuing officers)
     *   4. Otherwise → form is not actionable
     *
     * Role 1 (System Administrator) bypasses the approval-row rule and instead
     * sees all forms currently in 'Verifying Payment' — for oversight and
     * emergency override only. Role 4 (Inventory Manager) has no approval
     * responsibility → empty result.
     */
    public function paginatedActionableRequests(Request $request)
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

            $perPage = (int) $request->input('per_page', 15);
            $sortOrder = $request->input('sort_order', 'asc');
            if (!in_array($sortOrder, ['asc', 'desc'], true)) {
                $sortOrder = 'asc';
            }

            $verifyingStatusId = FormStatus::where('status_name', 'Verifying Payment')
                ->value('status_id');

            $query = RequisitionForm::query();

            // ---- System Administrator: oversight view of Verifying Payment ----
            if ((int) $admin->role_id === Admin::ROLE_SYSTEM_ADMIN) {
                if (!$verifyingStatusId) {
                    return $this->emptyActionableResponse($perPage, $sortOrder);
                }
                $query->where('status_id', $verifyingStatusId);
            }
            // ---- Inventory Manager: no approval responsibility ----
            elseif ((int) $admin->role_id === 4) {
                return $this->emptyActionableResponse($perPage, $sortOrder);
            }
            // ---- Approvers (2, 3, 5): my Pending row at the active stage ----
            else {
                $query->whereHas('requisitionApprovals', function ($q) use ($admin, $verifyingStatusId) {
                    $q->where('admin_id', $admin->admin_id)
                        ->where('status', 'Pending')
                        ->where(function ($stageQ) use ($verifyingStatusId) {
                            // Stage 1: this row is stage 1 (form still has open stage-1 work)
                            $stageQ->where('stage', 1)
                                // Stage 2: this row is stage 2 AND no pending stage-1 rows exist
                                ->orWhere(function ($q2) {
                                    $q2->where('stage', 2)
                                        ->whereNotExists(function ($sub) {
                                            $sub->select(DB::raw(1))
                                                ->from('requisition_approvals as ra1')
                                                ->whereColumn('ra1.request_id', 'requisition_approvals.request_id')
                                                ->where('ra1.stage', 1)
                                                ->where('ra1.status', 'Pending');
                                        });
                                })
                                // Stage 3: this row is stage 3 AND form is Verifying Payment
                                //          AND no pending stage-1/2 rows exist
                                ->orWhere(function ($q3) use ($verifyingStatusId) {
                                    $q3->where('stage', 3)
                                        ->whereExists(function ($sub) use ($verifyingStatusId) {
                                            $sub->select(DB::raw(1))
                                                ->from('requisition_forms as rf')
                                                ->whereColumn('rf.request_id', 'requisition_approvals.request_id')
                                                ->where('rf.status_id', $verifyingStatusId);
                                        })
                                        ->whereNotExists(function ($sub) {
                                            $sub->select(DB::raw(1))
                                                ->from('requisition_approvals as ra2')
                                                ->whereColumn('ra2.request_id', 'requisition_approvals.request_id')
                                                ->whereIn('ra2.stage', [1, 2])
                                                ->where('ra2.status', 'Pending');
                                        });
                                });
                        });
                });
            }

            $forms = $query
                ->with(['formStatus', 'purpose'])
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
                    'created_at',
                    'event_title',
                    'event_details',
                ])
                ->orderBy('created_at', $sortOrder)
                ->paginate($perPage);

            $transformedForms = $forms->through(function ($form) {
                try {
                    $scheduleDetails = $this->formatter->getScheduleDetails($form);
                    $durationDisplay = $this->scheduleFormatter->getFormattedDuration($form);

                    return [
                        'request_id' => $form->request_id,
                        'requester' => [
                            'name' => trim(($form->first_name ?? '') . ' ' . ($form->last_name ?? '')),
                            'email' => $form->email ?? '',
                            'organization' => $form->organization_name ?? 'No Organization',
                        ],
                        'status' => [
                            'id' => $form->formStatus->status_id ?? null,
                            'name' => $form->formStatus->status_name ?? 'Unknown',
                            'color' => $form->formStatus->color_code ?? '#6c757d',
                        ],
                        'schedule' => [
                            'display' => ($scheduleDetails['formatted']['start'] ?? '') . ' - ' . ($scheduleDetails['formatted']['end'] ?? ''),
                            'start_date' => $form->start_date,
                            'end_date' => $form->end_date,
                            'all_day' => $form->all_day ?? false,
                            'duration' => $durationDisplay ?? 'N/A',
                        ],
                        'event_title' => $form->event_title ?? 'No Title',
                        'event_details' => $form->event_details ?? 'No Description',
                        'created_at' => $form->created_at?->toIso8601String(),
                    ];
                } catch (\Exception $e) {
                    Log::error('Error transforming actionable form: ' . $e->getMessage());
                    return ['request_id' => $form->request_id, 'requester' => ['name' => 'Error loading data']];
                }
            });

            return response()->json([
                'success' => true,
                'data' => $transformedForms->values(),
                'meta' => [
                    'current_page' => $forms->currentPage(),
                    'last_page' => $forms->lastPage(),
                    'per_page' => $forms->perPage(),
                    'total' => $forms->total(),
                    'from' => $forms->firstItem(),
                    'to' => $forms->lastItem(),
                    'sort_order' => $sortOrder,
                ],
                'links' => [
                    'first' => $forms->url(1),
                    'last' => $forms->url($forms->lastPage()),
                    'prev' => $forms->previousPageUrl(),
                    'next' => $forms->nextPageUrl(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('paginatedActionableRequests error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching actionable requisitions',
            ], 500);
        }
    }

    /**
     * Standard empty paginated response for the actionable list.
     */
    private function emptyActionableResponse(int $perPage, string $sortOrder)
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'from' => null,
                'to' => null,
                'sort_order' => $sortOrder,
            ],
            'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
        ]);
    }

    /**
     * Get dashboard stats only (pending, completed, feedback counts)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardStats()
    {
        try {
            /** @var Admin $admin */
            $admin = auth()->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated - Admin not found'
                ], 401);
            }

            $pendingStatusIds = [1];
            $completedStatusIds = [4, 5, 6];
            $isHeadAdmin = $admin->role_id === 1;

            // Get department IDs managed by this admin
            $managedDepartmentIds = $admin->departments()->pluck('departments.department_id')->toArray();

            // Build pending query with department-based filtering
            $pendingQuery = RequisitionForm::whereIn('status_id', $pendingStatusIds);
            $completedQuery = RequisitionForm::whereIn('status_id', $completedStatusIds);

            // Apply department-based filtering for non-head admins
            if (!$isHeadAdmin && !empty($managedDepartmentIds)) {
                $filterCallback = function ($query) use ($managedDepartmentIds) {
                    $query->where(function ($subQuery) use ($managedDepartmentIds) {
                        $subQuery->whereHas('requestedFacilities.facility', function ($q) use ($managedDepartmentIds) {
                            $q->whereIn('managed_by', $managedDepartmentIds);
                        })->orWhereHas('requestedEquipment.equipment', function ($q) use ($managedDepartmentIds) {
                            $q->whereIn('managed_by', $managedDepartmentIds);
                        })->orWhereHas('requestedServices.service', function ($q) use ($managedDepartmentIds) {
                            $q->whereIn('managed_by', $managedDepartmentIds);
                        })->orWhereHas('purpose', function ($q) use ($managedDepartmentIds) {
                            $q->whereIn('routes_to', $managedDepartmentIds);
                        });
                    });
                };

                $pendingQuery->where($filterCallback);
                $completedQuery->where($filterCallback);
            } elseif (!$isHeadAdmin && empty($managedDepartmentIds)) {
                // Admin has no managed departments - return zero counts
                return response()->json([
                    'success' => true,
                    'stats' => [
                        'pending_count' => 0,
                        'completed_count' => 0,
                        'feedback_count' => Feedback::count()
                    ]
                ]);
            }

            // For head admins, no additional filtering is applied (they see everything)

            return response()->json([
                'success' => true,
                'stats' => [
                    'pending_count' => $pendingQuery->count(),
                    'completed_count' => $completedQuery->count(),
                    'feedback_count' => Feedback::count()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('getDashboardStats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard stats'
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
            $excludedStatuses = ['Pending Approval', 'Awaiting Payment', 'Reserved'];
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
        return response()->json([
            'success' => true,
            'counts' => [
                'pending' => RequisitionForm::whereHas('formStatus', fn($q) => $q->where('status_name', 'Pending Approval'))->count(),
                'awaiting' => RequisitionForm::whereHas('formStatus', fn($q) => $q->where('status_name', 'Awaiting Payment'))->count(),
                'verifying' => RequisitionForm::whereHas('formStatus', fn($q) => $q->where('status_name', 'Verifying Payment'))->count(),
                'reserved' => RequisitionForm::whereHas('formStatus', fn($q) => $q->where('status_name', 'Reserved'))->count(),
            ]
        ]);
    }

    // ------------------------------------------------------------------------
    // Simple query helpers (can stay in controller or move to repository)
    // ------------------------------------------------------------------------



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

    /**
     * Get count of completed and archived transactions (Completed, Rejected, Cancelled, Overdue)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCompletedTransactionsCount()
    {
        try {
            $statusNames = ['Completed', 'Rejected', 'Cancelled', 'Overdue'];

            $statusIds = FormStatus::whereIn('status_name', $statusNames)
                ->pluck('status_id')
                ->toArray();

            $count = RequisitionForm::whereIn('status_id', $statusIds)->count();

            return response()->json([
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch completed transactions count',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}