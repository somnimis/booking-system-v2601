<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RequisitionForm;
use App\Services\FeeCalculatorService;
use App\Services\RequisitionFormatterService;
use App\Services\CheckAvailabilityService;
use App\Services\ScheduleFormatterService;
use App\Services\AdminActionsService;
use Carbon\Carbon;

class RequestViewController extends Controller
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

}
