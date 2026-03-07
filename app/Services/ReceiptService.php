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
            'requisitionApprovals.approvedBy'
        ])->findOrFail($requestId);
    }

    /**
     * Build complete receipt data array
     */
    private function buildReceiptData($form)
    {
        $feeSummary = $this->feeCalculator->getFeeSummary($form);
        $scheduleData = $this->buildScheduleData($form);
        $approvingAdmins = $this->getApprovingAdmins($form);
        $feeBreakdownItems = $this->buildFeeBreakdownItems($form, $feeSummary);

        return [
            // Basic info
            'official_receipt_num' => $form->official_receipt_num,
            'request_id' => $form->request_id,
            'issued_date' => $form->updated_at->format('F j, Y'),
            
            // User details
            'user_name' => $form->first_name . ' ' . $form->last_name,
            'user_email' => $form->email,
            'organization_name' => $form->organization_name,
            'contact_number' => $form->contact_number,
            
            // Event details
            'facility_name' => $form->requestedFacilities->first()->facility->facility_name ?? 'N/A',
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
                'duration' => $feeSummary['duration']
            ],
            'fee_breakdown' => $feeBreakdownItems,
            
            // Approvals
            'approving_admins' => $approvingAdmins,
            
            // Flags
            'is_multi_day' => $form->start_date !== $form->end_date,
            'all_day' => $form->all_day
        ];
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
            ->whereNotNull('approved_by')
            ->map(function ($approval) {
                return [
                    'admin' => $approval->approvedBy,
                    'date_approved' => $approval->date_updated 
                        ? Carbon::parse($approval->date_updated)->format('M j, Y') 
                        : 'N/A'
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
                $items[] = [
                    'description' => $facility['name'] . ($facility['is_waived'] ? ' (Waived)' : ''),
                    'amount' => $facility['fee'],
                    'type' => 'facility',
                    'is_waived' => $facility['is_waived']
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
                
                $items[] = [
                    'description' => $description,
                    'amount' => $equipment['fee'],
                    'type' => 'equipment',
                    'is_waived' => $equipment['is_waived']
                ];
            }
        }
        
        // Add additional fees
        foreach ($form->requisitionFees as $fee) {
            if ($fee->fee_amount > 0) {
                $items[] = [
                    'description' => $fee->label . ' (Fee)',
                    'amount' => $fee->fee_amount,
                    'type' => 'additional_fee',
                    'account_num' => $fee->account_num
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
                    'type' => 'discount',
                    'discount_type' => $fee->discount_type
                ];
            }
        }
        
        // Add late penalty
        if ($feeSummary['late_penalty'] > 0) {
            $items[] = [
                'description' => 'Late Penalty Fee',
                'amount' => $feeSummary['late_penalty'],
                'type' => 'penalty'
            ];
        }
        
        return $items;
    }
}