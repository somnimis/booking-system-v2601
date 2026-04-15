<?php

namespace App\Services;

use App\Services\FeeCalculatorService;
use App\Services\ScheduleFormatterService;
use Carbon\Carbon;

class RequisitionFormatterService
{
    protected $feeCalculator;
    protected $scheduleFormatter;

    public function __construct(FeeCalculatorService $feeCalculator, ScheduleFormatterService $scheduleFormatter)
    {
        $this->feeCalculator = $feeCalculator;
        $this->scheduleFormatter = $scheduleFormatter;
    }

    /**
     * Format a pending requisition form
     */
    public function formatPendingForm($form): array
    {
        return [
            'request_id' => $form->request_id,
            'user_details' => $this->getUserDetails($form),
            'form_details' => $this->getFormDetails($form),
            'schedule' => $this->getScheduleDetails($form),
            'requested_items' => $this->getRequestedItems($form),
            'fees' => $this->formatFees($form),
            'status_tracking' => $this->getStatusTracking($form),
            'documents' => $this->getDocumentDetails($form),
            'approval_info' => $this->getApprovalInfo($form),
            'access_code' => $form->access_code,
        ];
    }

    public function formatSingleForm($form): array
    {
        $feeSummary = $this->feeCalculator->getFeeSummary($form);
        $requisitionFees = $form->requisitionFees->map(fn($fee) => $this->formatRequisitionFee($fee))->values();

        $approvalCount = $form->requisitionApprovals->whereNotNull('approved_by')->count();
        $rejectionCount = $form->requisitionApprovals->whereNotNull('rejected_by')->count();
        $isFinalized = $form->is_finalized;
        $finalizedBy = $form->finalizedBy ? [
            'id' => $form->finalizedBy->admin_id,
            'name' => $form->finalizedBy->first_name . ' ' . $form->finalizedBy->last_name,
            'role' => $form->finalizedBy->role->role_title ?? 'Unknown',
        ] : null;

        return [
            'request_id' => $form->request_id,
            'user_details' => $this->getUserDetails($form),
            'form_details' => [
                'num_participants' => $form->num_participants,
                'num_tables' => $form->num_tables,
                'num_chairs' => $form->num_chairs,
                'purpose' => $form->purpose->purpose_name,
                'additional_requests' => $form->additional_requests,
                'status' => [
                    'id' => $form->formStatus->status_id,
                    'name' => $form->formStatus->status_name,
                    'color' => $form->formStatus->color_code,
                ],
                'calendar_info' => [
                    'title' => $form->calendar_title,
                    'description' => $form->calendar_description,
                ],
                'official_receipt_num' => $form->official_receipt_num,
            ],
            'schedule' => $this->getScheduleDetails($form),
            'requested_items' => [
                'facilities' => $form->requestedFacilities->map(fn($facility) => [
                    'requested_facility_id' => $facility->requested_facility_id,
                    'facility_id' => $facility->facility_id,
                    'name' => $facility->facility->facility_name,
                    'fee' => $facility->facility->base_fee,
                    'rate_type' => $facility->facility->rate_type,
                    'is_waived' => $facility->is_waived,
                ])->values(),
                'equipment' => $form->requestedEquipment->map(fn($equipment) => [
                    'requested_equipment_id' => $equipment->requested_equipment_id,
                    'name' => $equipment->equipment->equipment_name,
                    'quantity' => $equipment->quantity,
                    'fee' => $equipment->equipment->base_fee,
                    'rate_type' => $equipment->equipment->rate_type,
                    'is_waived' => $equipment->is_waived,
                    'total_fee' => $equipment->equipment->base_fee * $equipment->quantity,
                ])->values(),
                'services' => $form->requestedServices->map(fn($service) => [
                    'requested_service_id' => $service->requested_service_id,
                    'service_id' => $service->service_id,
                    'name' => $service->service->service_name,
                ])->values(),
            ],
            'fees' => [
                'tentative_fee' => $feeSummary['base_fee'] + ($form->is_late ? $form->late_penalty_fee : 0),
                'approved_fee' => $feeSummary['approved_fee'],
                'late_penalty_fee' => $form->late_penalty_fee,
                'is_late' => $form->is_late,
                'breakdown' => [
                    'subtotal' => [
                        'facilities' => collect($feeSummary['breakdown']['facilities'] ?? [])->sum('fee'),
                        'equipment' => collect($feeSummary['breakdown']['equipment'] ?? [])->sum('fee')
                    ],
                    'additional_fees' => $feeSummary['additional_fees'] ?? 0,
                    'discounts' => $feeSummary['discounts'] ?? 0,
                    'late_penalty' => $feeSummary['late_penalty'] ?? 0,
                    'total' => $feeSummary['base_fee'] ?? 0,
                ],
                'requisition_fees' => $requisitionFees,
            ],
            'status_tracking' => $this->getStatusTracking($form),
            'documents' => $this->getDocumentDetails($form),
            'approval_info' => [
                'approval_count' => $approvalCount,
                'rejection_count' => $rejectionCount,
                'approvals' => $form->requisitionApprovals->whereNotNull('approved_by')->map(fn($a) => [
                    'admin_id' => $a->approved_by,
                    'date_updated' => $a->date_updated,
                ])->values(),
                'rejections' => $form->requisitionApprovals->whereNotNull('rejected_by')->map(fn($r) => [
                    'admin_id' => $r->rejected_by,
                    'date_updated' => $r->date_updated,
                ])->values(),
                'is_finalized' => $isFinalized,
                'finalized_by' => $finalizedBy,
                'can_finalize' => $approvalCount >= 3 && !$isFinalized,
                'latest_action' => $form->requisitionApprovals()->latest('date_updated')->first(),
            ],
            'access_code' => $form->access_code,
        ];
    }

    /**
     * Format a completed/archived requisition form
     */
    public function formatCompletedForm($form): array
    {
        $feeSummary = $this->feeCalculator->getFeeSummary($form);

        return [
            'request_id' => $form->request_id,
            'user_details' => $this->getUserDetails($form),
            'form_details' => $this->getFormDetails($form),
            'schedule' => $this->getScheduleDetails($form),
            'requested_items' => $this->getRequestedItems($form),
            'fees' => [
                'tentative_fee' => $feeSummary['base_fee'] + ($form->is_late ? $form->late_penalty_fee : 0),
                'approved_fee' => $form->approved_fee,
                'late_penalty_fee' => $form->late_penalty_fee,
                'is_late' => $form->is_late,
                'breakdown' => [
                    'base_fees' => $feeSummary['base_fee'],
                    'additional_fees' => $feeSummary['additional_fees'],
                    'discounts' => $feeSummary['discounts'],
                    'late_penalty' => $feeSummary['late_penalty'],
                ],
            ],
            'status_tracking' => $this->getStatusTracking($form),
            'documents' => $this->getDocumentDetails($form),
            'approval_info' => [
                'approval_count' => $form->requisitionApprovals->whereNotNull('approved_by')->count(),
                'rejection_count' => $form->requisitionApprovals->whereNotNull('rejected_by')->count(),
                'latest_action' => $form->requisitionApprovals()->latest('date_updated')->first(),
            ],
            'access_code' => $form->access_code,
        ];
    }

    /**
     * Format a requisition form for public/requester view
     */
    public function formatPublicForm($form): array
    {
        $feeSummary = $this->feeCalculator->getFeeSummary($form);
        $schedule = $this->getScheduleDetails($form);

        $formattedStartDate = Carbon::parse($form->start_date)->format('F j, Y');
        $formattedEndDate = Carbon::parse($form->end_date)->format('F j, Y');
        $formattedStartTime = $form->all_day ? 'All Day' : Carbon::parse($form->start_time)->format('g:i A');
        $formattedEndTime = $form->all_day ? 'All Day' : Carbon::parse($form->end_time)->format('g:i A');

        if ($form->all_day) {
            $formattedSchedule = $form->start_date === $form->end_date
                ? $formattedStartDate . ' (All Day)'
                : $formattedStartDate . ' — ' . $formattedEndDate . ' (All Day)';
        } else {
            $formattedSchedule = $form->start_date === $form->end_date
                ? $formattedStartDate . ' ' . $formattedStartTime . ' — ' . $formattedEndTime
                : $formattedStartDate . ' ' . $formattedStartTime . ' — ' . $formattedEndDate . ' ' . $formattedEndTime;
        }

        return [
            'request_id' => $form->request_id,
            'user_type' => $form->user_type,
            'first_name' => $form->first_name,
            'last_name' => $form->last_name,
            'email' => $form->email,
            'organization_name' => $form->organization_name,
            'contact_number' => $form->contact_number,
            'access_code' => $form->access_code,
            'num_participants' => $form->num_participants,
            'start_date' => $form->start_date,
            'end_date' => $form->end_date,
            'start_time' => $form->start_time,
            'end_time' => $form->end_time,
            'all_day' => $form->all_day,
            'formatted_start_date' => $formattedStartDate,
            'formatted_end_date' => $formattedEndDate,
            'formatted_start_time' => $formattedStartTime,
            'formatted_end_time' => $formattedEndTime,
            'formatted_schedule' => $formattedSchedule,
            'calendar_title' => $form->calendar_title,
            'calendar_description' => $form->calendar_description,
            'requested_facilities' => $form->requestedFacilities->map(fn($rf) => [
                'requested_facility_id' => $rf->requested_facility_id,
                'facility_id' => $rf->facility_id,
                'facility_name' => $rf->facility->facility_name,
                'base_fee' => $rf->facility->base_fee,
                'rate_type' => $rf->facility->rate_type,
                'is_waived' => $rf->is_waived,
            ])->values(),
            'requested_equipment' => $form->requestedEquipment->map(fn($re) => [
                'requested_equipment_id' => $re->requested_equipment_id,
                'equipment_id' => $re->equipment_id,
                'equipment_name' => $re->equipment->equipment_name,
                'base_fee' => $re->equipment->base_fee,
                'rate_type' => $re->equipment->rate_type,
                'quantity' => $re->quantity,
                'is_waived' => $re->is_waived,
                'total_fee' => $re->equipment->base_fee * $re->quantity,
            ])->values(),
            'form_status' => $form->formStatus,
            'purpose' => $form->purpose,
            'fees' => [
                'tentative_fee' => $feeSummary['base_fee'] + ($form->is_late ? $form->late_penalty_fee : 0),
                'approved_fee' => $feeSummary['approved_fee'],
                'late_penalty_fee' => $form->late_penalty_fee,
                'is_late' => $form->is_late,
                'breakdown' => [
                    'base_fees' => $feeSummary['base_fee'],
                    'additional_fees' => $feeSummary['additional_fees'],
                    'discounts' => $feeSummary['discounts'],
                    'late_penalty' => $feeSummary['late_penalty'],
                    'facilities' => $feeSummary['breakdown']['facilities'],
                    'equipment' => $feeSummary['breakdown']['equipment'],
                ],
            ],
            'total_fee' => $feeSummary['approved_fee'],
            'is_multi_day' => $form->start_date !== $form->end_date,
        ];
    }

    // ------------------------------------------------------------------------
    // Building block methods
    // ------------------------------------------------------------------------

    public function getUserDetails($form): array
    {
        return [
            'user_type' => $form->user_type,
            'first_name' => $form->first_name,
            'last_name' => $form->last_name,
            'email' => $form->email,
            'school_id' => $form->school_id,
            'organization_name' => $form->organization_name,
            'contact_number' => $form->contact_number,
        ];
    }

    public function getFormDetails($form): array
    {
        return [
            'num_participants' => $form->num_participants,
            'num_tables' => $form->num_tables,
            'num_chairs' => $form->num_chairs,
            'purpose' => $form->purpose->purpose_name,
            'additional_requests' => $form->additional_requests,
            'status' => [
                'id' => $form->formStatus->status_id,
                'name' => $form->formStatus->status_name,
                'color' => $form->formStatus->color_code,
            ],
            'calendar_info' => [
                'title' => $form->calendar_title,
                'description' => $form->calendar_description,
            ],
            'official_receipt_num' => $form->official_receipt_num,
        ];
    }

    public function getScheduleDetails($form): array
    {
        return $this->scheduleFormatter->forApi($form);
    }

    /**
     * Format public-facing schedule (comprehensive for user endpoints)
     */
    public function formatPublicSchedule($form): array
    {
        return $this->scheduleFormatter->format($form, 'fullcalendar');
    }

    public function getRequestedItems($form): array
    {
        return [
            'facilities' => $form->requestedFacilities->map(fn($facility) => [
                'requested_facility_id' => $facility->requested_facility_id,
                'facility_id' => $facility->facility_id,
                'name' => $facility->facility->facility_name,
                'fee' => $facility->facility->base_fee,
                'rate_type' => $facility->facility->rate_type,
                'is_waived' => $facility->is_waived,
            ])->values(),
            'equipment' => $form->requestedEquipment->map(fn($equipment) => [
                'requested_equipment_id' => $equipment->requested_equipment_id,
                'name' => $equipment->equipment->equipment_name,
                'quantity' => $equipment->quantity,
                'fee' => $equipment->equipment->base_fee,
                'rate_type' => $equipment->equipment->rate_type,
                'is_waived' => $equipment->is_waived,
                'total_fee' => $equipment->equipment->base_fee * $equipment->quantity,
            ])->values(),
        ];
    }

    public function formatRequisitionFee($fee): array
    {
        return [
            'fee_id' => $fee->fee_id,
            'label' => $fee->label,
            'account_num' => $fee->account_num,
            'fee_amount' => (float) $fee->fee_amount,
            'discount_amount' => (float) $fee->discount_amount,
            'discount_type' => $fee->discount_type,
            'type' => $fee->fee_amount > 0
                ? ($fee->fee_amount > 0 && $fee->discount_amount > 0 ? 'mixed' : 'fee')
                : 'discount',
            'added_by' => $fee->addedBy ? [
                'admin_id' => $fee->addedBy->admin_id,
                'name' => $fee->addedBy->first_name . ' ' . $fee->addedBy->last_name,
            ] : null,
            'created_at' => $fee->created_at,
            'updated_at' => $fee->updated_at,
        ];
    }

    public function getStatusTracking($form): array
    {
        return [
            'is_late' => $form->is_late,
            'is_finalized' => $form->is_finalized,
            'finalized_at' => $form->finalized_at,
            'finalized_by' => $form->finalizedBy ? [
                'id' => $form->finalizedBy->admin_id,
                'name' => $form->finalizedBy->first_name . ' ' . $form->finalizedBy->last_name,
                'role' => $form->finalizedBy->role->role_title ?? 'Unknown',
            ] : null,
            'is_closed' => $form->is_closed,
            'closed_at' => $form->closed_at,
            'closed_by' => $form->closedBy ? [
                'id' => $form->closedBy->admin_id,
                'name' => $form->closedBy->first_name . ' ' . $form->closedBy->last_name,
            ] : null,
            'returned_at' => $form->returned_at,
        ];
    }

    public function getDocumentDetails($form): array
    {
        return [
            'endorser' => $form->endorser,
            'date_endorsed' => $form->date_endorsed,
            'formal_letter' => [
                'url' => $form->formal_letter_url,
                'public_id' => $form->formal_letter_public_id,
            ],
            'facility_layout' => [
                'url' => $form->facility_layout_url,
                'public_id' => $form->facility_layout_public_id,
            ],
            'official_receipt' => [
                'number' => $form->official_receipt_no,
                'url' => $form->official_receipt_url,
                'public_id' => $form->official_receipt_public_id,
            ],
            'proof_of_payment' => [
                'url' => $form->proof_of_payment_url,
                'public_id' => $form->proof_of_payment_public_id,
            ],
        ];
    }

    public function getApprovalInfo($form): array
    {
        $approvalCount = $form->requisitionApprovals->whereNotNull('approved_by')->count();
        $rejectionCount = $form->requisitionApprovals->whereNotNull('rejected_by')->count();
        $isFinalized = $form->is_finalized;

        return [
            'approval_count' => $approvalCount,
            'rejection_count' => $rejectionCount,
            'is_finalized' => $isFinalized,
            'finalized_by' => $form->finalizedBy ? [
                'id' => $form->finalizedBy->admin_id,
                'name' => $form->finalizedBy->first_name . ' ' . $form->finalizedBy->last_name,
                'role' => $form->finalizedBy->role->role_title ?? 'Unknown',
            ] : null,
            'can_finalize' => $approvalCount >= 3 && !$isFinalized,
            'latest_action' => $form->requisitionApprovals()->latest('date_updated')->first(),
        ];
    }

    /**
     * Format all fees for a form (standard method for all fee formatting)
     */
    public function formatFees($form): array
    {
        $feeSummary = $this->feeCalculator->getFeeSummary($form);

        return [
            'tentative_fee' => $feeSummary['base_fee'] + ($form->is_late ? $form->late_penalty_fee : 0),
            'approved_fee' => $feeSummary['approved_fee'],
            'late_penalty_fee' => $form->late_penalty_fee,
            'is_late' => $form->is_late,
            'breakdown' => [
                'base_fees' => $feeSummary['base_fee'],
                'additional_fees' => $feeSummary['additional_fees'],
                'discounts' => $feeSummary['discounts'],
                'late_penalty' => $feeSummary['late_penalty'],
                'facilities' => $feeSummary['breakdown']['facilities'],
                'equipment' => $feeSummary['breakdown']['equipment'],
            ],
            'requisition_fees' => $form->requisitionFees->map(
                fn($fee) => $this->formatRequisitionFee($fee)
            )->values(),
        ];
    }
}