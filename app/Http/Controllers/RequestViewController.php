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

            // Duration (hours + human-readable text) — display-only, computed locally.
            $durationHours = $this->calculateDurationHours($form);
            $durationText = $this->formatDurationText($form, $durationHours);

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
                    'duration' => [
                        'hours' => $durationHours,
                        'text' => $durationText,
                    ],
                    'is_multi_day' => $form->start_date !== $form->end_date,
                    'requested_items' => [
                        'facilities' => $this->formatFacilitiesWithFees($form->requestedFacilities, $durationHours),
                        'equipment' => $this->formatEquipmentWithFees($form->requestedEquipment, $durationHours),
                        'services' => $this->formatServicesWithFees($form->requestedServices),
                    ],
                    'fees' => [
                        // Frozen at submission — never mutates. Read-only reference.
                        'tentative_fee' => (float) $form->tentative_fee,
                        // Live, current-state approved value. Persisted by every
                        // AdminActionsController mutation (fees, discounts, waivers).
                        'approved_fee' => (float) $form->approved_fee,
                        // Signed sum of all post-submission deltas. Negative = net
                        // savings (waivers/discounts). Positive = net additions.
                        'adjustments_total' => (float) $form->approved_fee - (float) $form->tentative_fee,
                    ],
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
                        // Admin ID from the authenticated token
                        'current_admin_id' => auth('sanctum')->id(),
                        'current_admin_can_act' => $this->currentAdminCanAct($form, auth('sanctum')->id()),
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
                        // Finalizable once every stage-1 officer has acted (Approved or Rejected).
                        // Rejections do NOT block finalization — the flag is a readiness signal for
                        // stage-2 approvers, not an authorization gate. Zero stage-1 rows also
                        // count as "ready" (forms with no dept-head approvers skip to stage 2).
                        'can_finalize' => !$form->is_finalized
                            && !$form->is_closed
                            && $form->requisitionApprovals
                                ->where('stage', 1)
                                ->where('status', 'Pending')
                                ->count() === 0,
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

                        // Finalization / closure tracking
                        'is_finalized' => $form->is_finalized,
                        'finalized_at' => $form->finalized_at,
                        'finalized_by' => $form->finalizedBy ? [
                            'admin_id' => $form->finalizedBy->admin_id,
                            'first_name' => $form->finalizedBy->first_name,
                            'last_name' => $form->finalizedBy->last_name,
                        ] : null,
                        'is_closed' => $form->is_closed,
                        'closed_at' => $form->closed_at,
                        'closed_by' => $form->closedBy ? [
                            'admin_id' => $form->closedBy->admin_id,
                            'first_name' => $form->closedBy->first_name,
                            'last_name' => $form->closedBy->last_name,
                        ] : null,
                        'closure_reason' => $form->closure_reason,
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
     * Returns true if the given admin has a still-pending approval row
     * for this form. Used by the UI to decide whether to render
     * Approve/Reject buttons for that specific admin.
     */
    private function currentAdminCanAct($form, $adminId): bool
    {
        if (!$adminId)
            return false;

        return $form->requisitionApprovals
            ->where('admin_id', $adminId)
            ->where('status', 'Pending')
            ->isNotEmpty();
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
     * Human-readable duration text for the Booking Details UI.
     * All-day bookings are expressed in day(s); timed bookings in hours.
     */
    private function formatDurationText($form, int $durationHours): string
    {
        if ($form->all_day) {
            $start = Carbon::parse($form->start_date);
            $end = Carbon::parse($form->end_date);
            $days = $start->diffInDays($end) + 1;
            return $days === 1 ? '1 day (All Day)' : $days . ' days (All Day)';
        }

        return $durationHours === 1 ? '1 hour' : $durationHours . ' hours';
    }

    /**
     * Format facilities for Booking Details.
     *
     * Reads the frozen `fee_snapshot' and falls back to live `base_fee`
     * for legacy rows. Emits `subtotal` for Per Hour items so the JS can render
     * the per-line total without any client-side math.
     */
    private function formatFacilitiesWithFees($facilities, int $durationHours)
    {
        return $facilities->map(function ($item) use ($durationHours) {
            $unit = (float) ($item->fee_snapshot ?? $item->facility->base_fee);
            $rateType = $item->facility->rate_type;
            $isPerHour = $rateType === 'Per Hour';

            return [
                'requested_facility_id' => $item->requested_facility_id,
                'facility_id' => $item->facility_id,
                'name' => $item->facility->facility_name,
                'rate_type' => $rateType,
                'fee' => $unit,
                'subtotal' => $isPerHour ? $unit * $durationHours : $unit,
                'is_waived' => (bool) $item->is_waived,
            ];
        })->values();
    }

    /**
     * Format equipment for Booking Details. Same snapshot rules as facilities,
     * but subtotal also multiplies by quantity for both rate types.
     */
    private function formatEquipmentWithFees($equipment, int $durationHours)
    {
        return $equipment->map(function ($item) use ($durationHours) {
            $unit = (float) ($item->fee_snapshot ?? $item->equipment->base_fee);
            $qty = (int) $item->quantity;
            $rateType = $item->equipment->rate_type;
            $isPerHour = $rateType === 'Per Hour';

            $subtotal = $unit * $qty;
            if ($isPerHour) {
                $subtotal *= $durationHours;
            }

            return [
                'requested_equipment_id' => $item->requested_equipment_id,
                'equipment_id' => $item->equipment_id,
                'name' => $item->equipment->equipment_name,
                'quantity' => $qty,
                'rate_type' => $rateType,
                'fee' => $unit,
                'subtotal' => $subtotal,
                'is_waived' => (bool) $item->is_waived,
            ];
        })->values();
    }

    /**
     * Format requested services for Booking Details. Services are flat-fee;
     * no duration or quantity multiplier.
     */
    private function formatServicesWithFees($services)
    {
        return $services->map(function ($item) {
            $unit = (float) ($item->fee_snapshot ?? $item->service->service_fee ?? 0);

            return [
                'requested_service_id' => $item->requested_service_id,
                'service_id' => $item->service_id,
                'name' => $item->service->service_name,
                'rate_type' => 'Flat',
                'fee' => $unit,
                'subtotal' => $unit,
                'is_waived' => (bool) $item->is_waived,
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
