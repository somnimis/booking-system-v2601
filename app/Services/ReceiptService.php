<?php

namespace App\Services;

use App\Models\RequisitionForm;
use App\Services\FeeCalculatorService;
use App\Services\RequisitionFormatterService;
use Carbon\Carbon;

class ReceiptService
{
    protected $feeCalculator;
    protected $formatter;

    public function __construct(
        FeeCalculatorService $feeCalculator,
        RequisitionFormatterService $formatter
    ) {
        $this->feeCalculator = $feeCalculator;
        $this->formatter = $formatter;
    }

    /**
     * Generate complete receipt data for a requisition form
     */
    public function generateReceiptData($requestId)
    {
        $form = $this->getRequisitionForm($requestId);

        if (empty($form->official_receipt_num)) {
            throw new \Exception('Official receipt not generated yet');
        }

        return $this->buildReceiptData($form);
    }

    /**
     * Get requisition form with required relationships
     */
    private function getRequisitionForm($requestId)
    {
        return RequisitionForm::with([
            'requestedFacilities.facility',
            'requestedEquipment.equipment',
            'purpose',
            'requisitionFees',
            'formStatus',
            'requisitionApprovals.actedBy'
        ])->findOrFail($requestId);
    }

    /**
     * Build complete receipt data array.
     *
     * Emits a `permits` array — one entry per requested facility and per
     * requested equipment. Each permit is self-contained (has its own
     * item name, type, and code) so ground personnel can be handed a
     * single permit for a single venue/item. Services are omitted from
     * permits (they have no physical venue).
     */
    private function buildReceiptData($form)
    {
        $feeSummary = $this->feeCalculator->getFeeSummary($form);
        $scheduleData = $this->buildScheduleData($form);
        $approvingAdmins = $this->getApprovingAdmins($form);
        $feeBreakdownItems = $this->buildFeeBreakdownItems($form, $feeSummary);

        $permits = $this->buildPermits($form);

        return [
            // Basic info
            'official_receipt_num' => $form->official_receipt_num,
            'control_num' => $form->control_num,
            'request_id' => $form->request_id,
            'issued_date' => $form->updated_at->format('F j, Y'),

            // User details
            'user_name' => $form->first_name . ' ' . $form->last_name,
            'user_email' => $form->email,
            'organization_name' => $form->organization_name,
            'contact_number' => $form->contact_number,

            // Event details
            'event_title' => $form->event_title,
            'purpose' => $form->purpose->purpose_name,
            'num_participants' => $form->num_participants,

            // Schedule
            ...$scheduleData,

            // Fee information
            'total_fee' => $feeSummary['approved_fee'],
            'fee_summary' => [
                'base_fee' => $feeSummary['base_fee'],
                'additional_fees' => $feeSummary['additional_fees'],
                'discounts' => $feeSummary['discounts'],
                'late_penalty' => $feeSummary['late_penalty'],
                'approved_fee' => $feeSummary['approved_fee'],
                'duration' => $feeSummary['duration'],
            ],
            'fee_breakdown' => $feeBreakdownItems,

            // Approvals
            'approving_admins' => $approvingAdmins,

            // Permits: one per facility + one per equipment
            'permits' => $permits,

            // Flags
            'is_multi_day' => $form->start_date !== $form->end_date,
            'all_day' => $form->all_day,
        ];
    }

    /**
     * Build per-item permit payloads.
     * Each permit carries only what's needed to print one physical permit.
     * `permit_code` = {access_code}-FAC-{n} / -EQP-{n} for admin cataloging.
     */
    private function buildPermits($form): array
    {
        $permits = [];

        foreach ($form->requestedFacilities as $index => $rf) {
            if (!$rf->facility)
                continue;
            $permits[] = [
                'type' => 'facility',
                'type_label' => 'Facility',
                'item_name' => $rf->facility->facility_name,
            ];
        }

        foreach ($form->requestedEquipment as $index => $re) {
            if (!$re->equipment)
                continue;
            $permits[] = [
                'type' => 'equipment',
                'type_label' => 'Equipment',
                'item_name' => $re->equipment->equipment_name
                    . ($re->quantity > 1 ? ' (×' . $re->quantity . ')' : ''),
            ];
        }

        return $permits;
    }
    /**
     * Build schedule data array
     */
    private function buildScheduleData($form)
    {
        if ($form->all_day) {
            $startDateFormatted = Carbon::parse($form->start_date)->format('F j, Y');
            $endDateFormatted = Carbon::parse($form->end_date)->format('F j, Y');

            $scheduleString = $form->start_date === $form->end_date
                ? $startDateFormatted . ' (All Day)'
                : $startDateFormatted . ' — ' . $endDateFormatted . ' (All Day)';

            $formattedStartTime = 'All Day';
            $formattedEndTime = 'All Day';
        } else {
            $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
            $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);

            $startDateFormatted = $startDateTime->format('F j, Y');
            $endDateFormatted = $endDateTime->format('F j, Y');
            $formattedStartTime = $startDateTime->format('g:i A');
            $formattedEndTime = $endDateTime->format('g:i A');

            $scheduleString = $form->start_date === $form->end_date
                ? $startDateFormatted . ' — ' . $formattedStartTime . ' to ' . $formattedEndTime
                : $startDateFormatted . ' ' . $formattedStartTime . ' — ' . $endDateFormatted . ' ' . $formattedEndTime;
        }

        return [
            'start_date' => $form->start_date,
            'end_date' => $form->end_date,
            'start_time' => $form->start_time,
            'end_time' => $form->end_time,
            'formatted_start_date' => $startDateFormatted,
            'formatted_end_date' => $endDateFormatted,
            'formatted_start_time' => $formattedStartTime,
            'formatted_end_time' => $formattedEndTime,
            'schedule' => $scheduleString,
        ];
    }

    /**
     * Get approving admins list
     */
    private function getApprovingAdmins($form)
    {
        return $form->requisitionApprovals
            ->where('status', 'Approved')
            ->whereNotNull('acted_by')
            ->map(function ($approval) {
                return [
                    'admin' => $approval->actedBy,
                    'date_approved' => $approval->acted_at 
                        ? Carbon::parse($approval->acted_at)->format('M j, Y') 
                        : ($approval->date_updated ? Carbon::parse($approval->date_updated)->format('M j, Y') : 'N/A')
                ];
            })
            ->filter(fn($item) => !is_null($item['admin']))
            ->unique(fn($item) => $item['admin']->admin_id)
            ->map(fn($item) => [
                'name' => $item['admin']->first_name . ' ' . $item['admin']->last_name,
                'title' => $item['admin']->title ?? 'Administrator',
                'signature_url' => $item['admin']->signature_url,
                'date_approved' => $item['date_approved']
            ])
            ->toArray();
    }

    /**
     * Build fee breakdown items array
     */
    private function buildFeeBreakdownItems($form, $feeSummary)
    {
        $items = [];

        // Add facilities
        foreach ($feeSummary['breakdown']['facilities'] as $facility) {
            if ($facility['fee'] > 0 || $facility['is_waived']) {
                $description = $facility['name'];
                if ($facility['is_waived']) {
                    $description .= ' (Waived)';
                }

                $rateLabel = $facility['rate_type'] === 'Per Hour' ? '/hr' : '/event';

                $items[] = [
                    'description' => $description,
                    'amount' => $facility['fee'],
                    'rate_label' => $rateLabel
                ];
            }
        }

        // Add equipment
        foreach ($feeSummary['breakdown']['equipment'] as $equipment) {
            if ($equipment['fee'] > 0 || $equipment['is_waived']) {
                $description = $equipment['name'];
                if ($equipment['quantity'] > 1) {
                    $description .= ' (×' . $equipment['quantity'] . ')';
                }
                if ($equipment['is_waived']) {
                    $description .= ' (Waived)';
                }

                $rateLabel = $equipment['rate_type'] === 'Per Hour' ? '/hr' : '/event';

                $items[] = [
                    'description' => $description,
                    'amount' => $equipment['fee'],
                    'rate_label' => $rateLabel
                ];
            }
        }

        // Add Services
        if (isset($feeSummary['breakdown']['services'])) {
            foreach ($feeSummary['breakdown']['services'] as $service) {
                if ($service['fee'] > 0 || $service['is_waived']) {
                    $description = $service['name'];
                    if ($service['is_waived']) {
                        $description .= ' (Waived)';
                    }

                    $items[] = [
                        'description' => $description,
                        'amount' => $service['fee'],
                        'rate_label' => '/event'
                    ];
                }
            }
        }

        // Add additional fees
        foreach ($form->requisitionFees as $fee) {
            // Prevent duplicates
            $isDuplicate = collect($items)->contains('description', $fee->label);

            if ($fee->fee_amount > 0 && !$isDuplicate) {
                $items[] = [
                    'description' => $fee->label,
                    'amount' => $fee->fee_amount,
                    'rate_label' => '/event' // Default for manual fees
                ];
            }
        }

        // Add discounts
        foreach ($form->requisitionFees as $fee) {
            if ($fee->discount_amount > 0) {
                $discountDesc = $fee->label . ' (';
                if ($fee->discount_type === 'Percentage') {
                    $discountDesc .= $fee->discount_amount . '% Discount)';
                } else {
                    $discountDesc .= '₱' . number_format($fee->discount_amount, 2) . ' Discount)';
                }

                $items[] = [
                    'description' => $discountDesc,
                    'amount' => -$fee->discount_amount,
                    'rate_label' => ''
                ];
            }
        }

        // Add late penalty
        if ($feeSummary['late_penalty'] > 0) {
            $items[] = [
                'description' => 'Late Penalty Fee',
                'amount' => $feeSummary['late_penalty'],
                'rate_label' => '/event'
            ];
        }

        return $items;
    }
}