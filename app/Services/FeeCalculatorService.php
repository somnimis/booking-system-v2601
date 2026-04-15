<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FeeCalculatorService
{
    /**
     * Calculate base fee (facilities + equipment) for a form
     */
    public function calculateBaseFee($form): float
    {
        $duration = $this->getDurationDetails($form);
        
        $facilityTotal = $this->calculateFacilityTotal($form, $duration['hours']);
        $equipmentTotal = $this->calculateEquipmentTotal($form, $duration['hours']);
        
        return $facilityTotal + $equipmentTotal;
    }

    /**
     * Get facilities breakdown with individual fees
     */
public function getFacilitiesBreakdown($form): array
{
    $duration = $this->getDurationDetails($form);
    
    return $form->requestedFacilities->map(function ($facility) use ($duration) {
        $unitPrice = $facility->facility->base_fee;
        $fee = $this->calculateFacilityFee(
            $unitPrice,
            $facility->facility->rate_type,
            $duration['hours'],
            $facility->is_waived
        );
        
        return [
            'name' => $facility->facility->facility_name,
            'unit_price' => $unitPrice,  // ADD THIS
            'fee' => $fee,
            'rate_type' => $facility->facility->rate_type,
            'is_waived' => $facility->is_waived,
            'duration_text' => $facility->facility->rate_type === 'Per Hour' ? $duration['text'] : null,
        ];
    })->values()->toArray();
}

    /**
     * Get equipment breakdown with individual fees
     */
public function getEquipmentBreakdown($form): array
{
    $duration = $this->getDurationDetails($form);
    
    return $form->requestedEquipment->map(function ($equipment) use ($duration) {
        $unitPrice = $equipment->equipment->base_fee;
        $fee = $this->calculateEquipmentFee(
            $unitPrice,
            $equipment->equipment->rate_type,
            $equipment->quantity,
            $duration['hours'],
            $equipment->is_waived
        );
        
        return [
            'name' => $equipment->equipment->equipment_name,
            'quantity' => $equipment->quantity,
            'unit_price' => $unitPrice,  // ADD THIS
            'fee' => $fee,
            'rate_type' => $equipment->equipment->rate_type,
            'is_waived' => $equipment->is_waived,
            'duration_text' => $equipment->equipment->rate_type === 'Per Hour' ? $duration['text'] : null,
        ];
    })->values()->toArray();
}

    /**
     * Calculate approved fee (base + additional - discounts + late penalty)
     */
    public function calculateApprovedFee($form): float
    {
        $baseFee = $this->calculateBaseFee($form);
        $additionalFees = $form->requisitionFees->sum('fee_amount');
        $discounts = $this->calculateTotalDiscounts($form, $baseFee + $additionalFees);
        $latePenalty = $form->is_late ? $form->late_penalty_fee : 0;
        
        return max(0, $baseFee + $additionalFees - $discounts + $latePenalty);
    }

    /**
     * Get complete fee summary for a form
     */
    public function getFeeSummary($form): array
    {
        $duration = $this->getDurationDetails($form);
        $baseFee = $this->calculateBaseFee($form);
        $additionalFees = $form->requisitionFees->sum('fee_amount');
        $discounts = $this->calculateTotalDiscounts($form, $baseFee + $additionalFees);
        $latePenalty = $form->is_late ? $form->late_penalty_fee : 0;
        
        return [
            'duration' => $duration,
            'base_fee' => $baseFee,
            'additional_fees' => $additionalFees,
            'discounts' => $discounts,
            'late_penalty' => $latePenalty,
            'approved_fee' => $baseFee + $additionalFees - $discounts + $latePenalty,
            'breakdown' => [
                'facilities' => $this->getFacilitiesBreakdown($form),
                'equipment' => $this->getEquipmentBreakdown($form),
            ]
        ];
    }

    // ------------------------------------------------------------------------
    // Private calculation helpers
    // ------------------------------------------------------------------------

    private function calculateFacilityTotal($form, float $durationHours): float
    {
        return $form->requestedFacilities->sum(function ($facility) use ($durationHours) {
            return $this->calculateFacilityFee(
                $facility->facility->base_fee,
                $facility->facility->rate_type,
                $durationHours,
                $facility->is_waived
            );
        });
    }

    private function calculateEquipmentTotal($form, float $durationHours): float
    {
        return $form->requestedEquipment->sum(function ($equipment) use ($durationHours) {
            return $this->calculateEquipmentFee(
                $equipment->equipment->base_fee,
                $equipment->equipment->rate_type,
                $equipment->quantity,
                $durationHours,
                $equipment->is_waived
            );
        });
    }

    private function calculateFacilityFee(float $baseFee, string $rateType, float $durationHours, bool $isWaived): float
    {
        if ($isWaived) {
            return 0;
        }
        
        return $rateType === 'Per Hour' ? $baseFee * $durationHours : $baseFee;
    }

    private function calculateEquipmentFee(float $baseFee, string $rateType, int $quantity, float $durationHours, bool $isWaived): float
    {
        if ($isWaived) {
            return 0;
        }
        
        $fee = $baseFee * $quantity;
        return $rateType === 'Per Hour' ? $fee * $durationHours : $fee;
    }

    private function calculateTotalDiscounts($form, float $subtotal): float
    {
        return $form->requisitionFees->reduce(function ($carry, $fee) use ($subtotal) {
            if ($fee->discount_amount <= 0) {
                return $carry;
            }
            
            if ($fee->discount_type === 'Percentage') {
                return $carry + (($fee->discount_amount / 100) * $subtotal);
            }
            
            return $carry + $fee->discount_amount;
        }, 0);
    }

    private function getDurationDetails($form): array
    {
        if ($form->all_day) {
            $start = Carbon::parse($form->start_date);
            $end = Carbon::parse($form->end_date);
            $days = $start->diffInDays($end) + 1;
            $hours = $days * 8;
            $text = $days === 1 ? '1 day (All Day)' : $days . ' days (All Day)';
        } else {
            $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
            $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
            $hours = max(1, $startDateTime->diffInHours($endDateTime));
            $text = $hours === 1 ? '1 hour' : $hours . ' hours';
        }
        
        return [
            'hours' => $hours,
            'text' => $text
        ];
    }
}