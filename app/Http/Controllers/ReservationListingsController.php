<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RequisitionApproval;
use App\Models\RequisitionFee;
use App\Models\FormStatus;
use App\Models\Feedback;
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
    /**
     * OPTIMIZED: Get all data needed for the request view page in a single query
     * This reduces 5-6 separate API calls into 1 efficient database query
     *
     * @param string|int $requestId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRequestViewData($requestId)
    {
        try {
            $startTime = microtime(true);

            // Single efficient query with all necessary relationships
            $form = RequisitionForm::with([
                'formStatus',
                'purpose',
                'finalizedBy',
                'closedBy',
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requestedServices.service',
                'requisitionApprovals' => function ($query) {
                    $query->with(['admin', 'actedBy'])
                        ->orderBy('date_updated', 'desc')
                        ->limit(50);
                },
                'requisitionComments' => function ($query) {
                    $query->with('admin')
                        ->orderBy('created_at', 'desc')
                        ->limit(100);
                },
                'requisitionFees' => function ($query) {
                    $query->with('addedBy')
                        ->orderBy('created_at', 'desc');
                }
            ])->findOrFail($requestId);

            // Use the existing calculateDurationHours method
            $durationHours = $this->calculateDurationHours($form);

            // Calculate base fees from items
            $feeCalculation = $this->calculateFeesFromItems($form, $durationHours);

            // Format schedule
            $scheduleFormatted = $this->scheduleFormatter->forApi($form);

            // Build the complete response
            $response = [
                'success' => true,
                'data' => [
                    'request_id' => $form->request_id,
                    'access_code' => $form->access_code,
                    'user_details' => [
                        'user_type' => $form->user_type,
                        'first_name' => $form->first_name,
                        'last_name' => $form->last_name,
                        'email' => $form->email,
                        'school_id' => $form->school_id,
                        'organization_name' => $form->organization_name,
                        'contact_number' => $form->contact_number,
                    ],
                    'form_details' => [
                        'num_participants' => $form->num_participants,
                        'num_tables' => $form->num_tables,
                        'num_chairs' => $form->num_chairs,
                        'num_microphones' => $form->num_microphones,
                        'purpose' => $form->purpose?->purpose_name,
                        'additional_requests' => $form->additional_requests,
                        'status' => [
                            'id' => $form->formStatus?->status_id,
                            'name' => $form->formStatus?->status_name,
                            'color' => $form->formStatus?->color_code,
                        ],
                        'calendar_info' => [
                            'title' => $form->event_title,
                            'description' => $form->event_details,
                        ],
                        'official_receipt_num' => $form->official_receipt_num,
                    ],
                    'schedule' => $scheduleFormatted,
                    'duration_hours' => $durationHours,
                    'is_multi_day' => $form->start_date !== $form->end_date,
                    'requested_items' => [
                        'facilities' => $this->formatFacilitiesWithFees($form->requestedFacilities, $durationHours),
                        'equipment' => $this->formatEquipmentWithFees($form->requestedEquipment, $durationHours),
                    ],
                    'fees' => $feeCalculation,
                    'documents' => [
                        'formal_letter' => [
                            'url' => $form->event_documents_url,
                            'public_id' => $form->event_documents_public_id,
                        ],
                        'proof_of_payment' => [
                            'url' => $form->proof_of_payment_url,
                            'public_id' => $form->proof_of_payment_public_id,
                        ],
                        'official_receipt' => [
                            'number' => $form->official_receipt_num,
                            'url' => null,
                            'public_id' => null,
                        ],
                    ],
                    'approval_info' => [
                        'approval_count' => $form->requisitionApprovals->where('status', 'Approved')->count(),
                        'rejection_count' => $form->requisitionApprovals->where('status', 'Rejected')->count(),
                        'pending_count' => $form->requisitionApprovals->where('status', 'Pending')->count(),
                        'total_required' => $form->requisitionApprovals->count(),
                        'stage_breakdown' => [
                            1 => [
                                'total' => $form->requisitionApprovals->where('stage', 1)->count(),
                                'approved' => $form->requisitionApprovals->where('stage', 1)->where('status', 'Approved')->count(),
                                'rejected' => $form->requisitionApprovals->where('stage', 1)->where('status', 'Rejected')->count(),
                                'pending' => $form->requisitionApprovals->where('stage', 1)->where('status', 'Pending')->count(),
                            ],
                            2 => [
                                'total' => $form->requisitionApprovals->where('stage', 2)->count(),
                                'approved' => $form->requisitionApprovals->where('stage', 2)->where('status', 'Approved')->count(),
                                'rejected' => $form->requisitionApprovals->where('stage', 2)->where('status', 'Rejected')->count(),
                                'pending' => $form->requisitionApprovals->where('stage', 2)->where('status', 'Pending')->count(),
                            ],
                            3 => [
                                'total' => $form->requisitionApprovals->where('stage', 3)->count(),
                                'approved' => $form->requisitionApprovals->where('stage', 3)->where('status', 'Approved')->count(),
                                'rejected' => $form->requisitionApprovals->where('stage', 3)->where('status', 'Rejected')->count(),
                                'pending' => $form->requisitionApprovals->where('stage', 3)->where('status', 'Pending')->count(),
                            ],
                        ],
                        'is_finalized' => $form->is_finalized,
                        'can_finalize' => $form->requisitionApprovals->where('status', 'Approved')->count() >= 3 && !$form->is_finalized,
                    ],
                    'approval_history' => $this->formatApprovalHistory($form->requisitionApprovals),
                    'comments' => $this->formatComments($form->requisitionComments),
                    'requisition_fees' => $this->formatRequisitionFees($form->requisitionFees),
                    'status_tracking' => [
                        'is_late' => $form->is_late,
                        'late_penalty_fee' => $form->late_penalty_fee,
                        'returned_at' => $form->returned_at,
                        'created_at' => $form->created_at,
                        'updated_at' => $form->updated_at,
                    ],
                ]
            ];

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            \Log::info("Request view data loaded in {$executionTime}ms for request #{$requestId}");

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Failed to load request view data', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load request data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate duration in hours based on all_day flag
     */
    private function calculateDurationHours($form)
    {
        if ($form->all_day) {
            $startDate = Carbon::parse($form->start_date);
            $endDate = Carbon::parse($form->end_date);
            $days = $startDate->diffInDays($endDate) + 1;
            return $days * 8; // 8 hours per day
        }

        try {
            $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
            $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
            return max(1, $startDateTime->diffInHours($endDateTime));
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * Calculate fees from requested items
     */
    private function calculateFeesFromItems($form, $durationHours)
    {
        $facilityBaseTotal = 0;
        $facilityWaivedTotal = 0;
        $equipmentBaseTotal = 0;
        $equipmentWaivedTotal = 0;

        // Calculate facility fees
        foreach ($form->requestedFacilities as $facility) {
            $fee = $facility->facility->base_fee;
            $total = $facility->facility->rate_type === 'Per Hour' ? $fee * $durationHours : $fee;

            if ($facility->is_waived) {
                $facilityWaivedTotal += $total;
            } else {
                $facilityBaseTotal += $total;
            }
        }

        // Calculate equipment fees
        foreach ($form->requestedEquipment as $equipment) {
            $fee = $equipment->equipment->base_fee;
            $quantity = $equipment->quantity;
            $total = $equipment->equipment->rate_type === 'Per Hour'
                ? ($fee * $durationHours) * $quantity
                : $fee * $quantity;

            if ($equipment->is_waived) {
                $equipmentWaivedTotal += $total;
            } else {
                $equipmentBaseTotal += $total;
            }
        }

        $baseTotal = $facilityBaseTotal + $equipmentBaseTotal;
        $waivedTotal = $facilityWaivedTotal + $equipmentWaivedTotal;

        // Calculate additional fees and discounts
        $additionalFeesTotal = 0;
        $discountsTotal = 0;

        foreach ($form->requisitionFees as $fee) {
            if ($fee->fee_amount > 0) {
                $additionalFeesTotal += $fee->fee_amount;
            }
            if ($fee->discount_amount > 0) {
                if ($fee->discount_type === 'Percentage') {
                    $discountsTotal += ($fee->discount_amount / 100) * ($baseTotal + $additionalFeesTotal);
                } else {
                    $discountsTotal += $fee->discount_amount;
                }
            }
        }

        $approvedFee = $baseTotal + $additionalFeesTotal - $discountsTotal;
        if ($form->is_late) {
            $approvedFee += $form->late_penalty_fee;
        }

        return [
            'base_fee' => $baseTotal,
            'waived_fee' => $waivedTotal,
            'tentative_fee' => $baseTotal + $waivedTotal,
            'additional_fees_total' => $additionalFeesTotal,
            'discounts_total' => $discountsTotal,
            'late_penalty_fee' => $form->late_penalty_fee,
            'approved_fee' => max(0, $approvedFee),
            'breakdown' => [
                'facilities_base' => $facilityBaseTotal,
                'facilities_waived' => $facilityWaivedTotal,
                'equipment_base' => $equipmentBaseTotal,
                'equipment_waived' => $equipmentWaivedTotal,
            ]
        ];
    }

    /**
     * Format facilities with fee calculations
     */
    private function formatFacilitiesWithFees($facilities, $durationHours)
    {
        return $facilities->map(function ($item) use ($durationHours) {
            $fee = $item->facility->base_fee;
            $total = $item->facility->rate_type === 'Per Hour' ? $fee * $durationHours : $fee;
            $rateDescription = $item->facility->rate_type === 'Per Hour'
                ? "₱" . number_format($fee, 2) . "/hr × " . $durationHours . " hrs"
                : "₱" . number_format($fee, 2) . "/event";

            return [
                'requested_facility_id' => $item->requested_facility_id,
                'facility_id' => $item->facility_id,
                'name' => $item->facility->facility_name,
                'fee' => $fee,
                'rate_type' => $item->facility->rate_type,
                'is_waived' => (bool) $item->is_waived,
                'total_fee' => $total,
                'rate_description' => $rateDescription,
            ];
        })->values();
    }

    /**
     * Format equipment with fee calculations
     */
    private function formatEquipmentWithFees($equipment, $durationHours)
    {
        return $equipment->map(function ($item) use ($durationHours) {
            $fee = $item->equipment->base_fee;
            $quantity = $item->quantity;
            $total = $item->equipment->rate_type === 'Per Hour'
                ? ($fee * $durationHours) * $quantity
                : $fee * $quantity;
            $rateDescription = $item->equipment->rate_type === 'Per Hour'
                ? "₱" . number_format($fee, 2) . "/hr × " . $durationHours . " hrs × " . $quantity
                : "₱" . number_format($fee, 2) . "/event × " . $quantity;

            return [
                'requested_equipment_id' => $item->requested_equipment_id,
                'equipment_id' => $item->equipment_id,
                'name' => $item->equipment->equipment_name,
                'fee' => $fee,
                'quantity' => $quantity,
                'rate_type' => $item->equipment->rate_type,
                'is_waived' => (bool) $item->is_waived,
                'total_fee' => $total,
                'rate_description' => $rateDescription,
            ];
        })->values();
    }

    /**
     * Format approval history for display
     */
    private function formatApprovalHistory($approvals)
    {
        if (!$approvals || $approvals->isEmpty()) {
            return [];
        }

        return $approvals->map(function ($approval) {
            // Determine action based on status
            $action = match ($approval->status) {
                'Approved' => 'approved',
                'Rejected' => 'rejected',
                default => 'pending'
            };

            // Get the admin who acted (if any)
            $actingAdmin = $approval->actedBy;

            // Get the required signatory
            $requiredAdmin = $approval->admin;

            // Determine icon and color for the action
            $actionIcon = match ($action) {
                'approved' => 'fa-check-circle',
                'rejected' => 'fa-times-circle',
                default => 'fa-clock'
            };

            $actionClass = match ($action) {
                'approved' => 'text-success',
                'rejected' => 'text-danger',
                default => 'text-warning'
            };

            return [
                'approval_id' => $approval->approval_id,
                'stage' => $approval->stage,
                'status' => $approval->status,
                'action' => $action,
                'action_icon' => $actionIcon,
                'action_class' => $actionClass,
                'remarks' => $approval->remarks,
                'acted_at' => $approval->acted_at,
                'date_updated' => $approval->date_updated,

                // Required signatory info
                'required_admin' => $requiredAdmin ? [
                    'id' => $requiredAdmin->admin_id,
                    'name' => $requiredAdmin->first_name . ' ' . $requiredAdmin->last_name,
                    'first_name' => $requiredAdmin->first_name,
                    'last_name' => $requiredAdmin->last_name,
                    'photo' => $requiredAdmin->photo_url ?? null,
                    'role' => $requiredAdmin->role->role_title ?? 'Signatory',
                ] : null,

                // Acting admin info (who actually approved/rejected)
                'acted_by' => $actingAdmin ? [
                    'id' => $actingAdmin->admin_id,
                    'name' => $actingAdmin->first_name . ' ' . $actingAdmin->last_name,
                    'first_name' => $actingAdmin->first_name,
                    'last_name' => $actingAdmin->last_name,
                    'photo' => $actingAdmin->photo_url ?? null,
                    'role' => $actingAdmin->role->role_title ?? 'Admin',
                ] : null,

                'formatted_date' => $approval->acted_at
                    ? $approval->acted_at->format('F j, Y g:i A')
                    : ($approval->date_updated ? date('F j, Y g:i A', strtotime($approval->date_updated)) : 'Pending'),
            ];
        })->sortBy('stage')->values()->toArray();
    }

    /**
     * Format comments for activity timeline
     */
    private function formatComments($comments)
    {
        return $comments->map(function ($comment) {
            return [
                'comment_id' => $comment->comment_id,
                'comment' => $comment->comment,
                'admin' => [
                    'admin_id' => $comment->admin?->admin_id,
                    'first_name' => $comment->admin?->first_name,
                    'last_name' => $comment->admin?->last_name,
                    'photo_url' => $comment->admin?->photo_url,
                ],
                'created_at' => $comment->created_at,
                'formatted_date' => Carbon::parse($comment->created_at)->diffForHumans(),
            ];
        })->values();
    }

    /**
     * Format requisition fees for display
     */
    private function formatRequisitionFees($fees)
    {
        return $fees->map(function ($fee) {
            return [
                'fee_id' => $fee->fee_id,
                'label' => $fee->label,
                'account_num' => $fee->account_num,
                'fee_amount' => (float) $fee->fee_amount,
                'discount_amount' => (float) $fee->discount_amount,
                'discount_type' => $fee->discount_type,
                'type' => $fee->fee_amount > 0 ? ($fee->discount_amount > 0 ? 'mixed' : 'fee') : 'discount',
                'added_by' => $fee->addedBy ? [
                    'admin_id' => $fee->addedBy->admin_id,
                    'name' => $fee->addedBy->first_name . ' ' . $fee->addedBy->last_name,
                ] : null,
                'created_at' => $fee->created_at,
                'formatted_date' => Carbon::parse($fee->created_at)->diffForHumans(),
            ];
        })->values();
    }
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
        
        // Validate status ID (1=Pending Approval, 2=Awaiting Payment, 7=Payment Submitted, 3=Reserved)
        if (!in_array($statusId, [1, 2, 7, 3])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status ID. Must be 1, 2, 7, or 3'
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