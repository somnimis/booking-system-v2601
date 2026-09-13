<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * FeeCalculatorService
 * ----------------------------------------------------------------------------
 * Centralizes all fee math for requisition forms.
 *
 * Fee composition at submission time:
 *   tentative_fee = facilities + equipment + services
 *
 * Fee composition at approval time (admin-side, not this service's concern here):
 *   approved_fee = tentative_fee + requisitionFees.additional - discounts + late_penalty
 *
 * Rate types:
 *   - Facilities : 'Per Hour' or flat
 *   - Equipment  : 'Per Hour' or flat, multiplied by quantity
 *   - Services   : always flat (extra_services has no rate_type column)
 *
 * Waivers:
 *   Any requested_* row can be waived (is_waived = true) → contributes 0.
 *
 * IMPORTANT: This service must be callable on a *persisted* RequisitionForm
 * with the following relationships loaded:
 *   - requestedFacilities.facility
 *   - requestedEquipment.equipment
 *   - requestedServices.service
 * Otherwise you'll trigger N+1 queries (or null errors) during breakdown.
 * ----------------------------------------------------------------------------
 */
class FeeCalculatorService
{
    /**
     * Calculate base fee (facilities + equipment + services) for a form.
     *
     * This is the value that gets persisted to `requisition_forms.tentative_fee`
     * at submission time.
     */
    public function calculateBaseFee($form): float
    {
        $duration = $this->getDurationDetails($form);

        $facilityTotal  = $this->calculateFacilityTotal($form, $duration['hours']);
        $equipmentTotal = $this->calculateEquipmentTotal($form, $duration['hours']);
        $serviceTotal   = $this->calculateServiceTotal($form);

        return $facilityTotal + $equipmentTotal + $serviceTotal;
    }

    /**
     * Get facilities breakdown with individual fees.
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
                'name'          => $facility->facility->facility_name,
                'unit_price'    => $unitPrice,
                'fee'           => $fee,
                'rate_type'     => $facility->facility->rate_type,
                'is_waived'     => (bool) $facility->is_waived,
                'duration_text' => $facility->facility->rate_type === 'Per Hour' ? $duration['text'] : null,
            ];
        })->values()->toArray();
    }

    /**
     * Get equipment breakdown with individual fees.
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
                'name'          => $equipment->equipment->equipment_name,
                'quantity'      => $equipment->quantity,
                'unit_price'    => $unitPrice,
                'fee'           => $fee,
                'rate_type'     => $equipment->equipment->rate_type,
                'is_waived'     => (bool) $equipment->is_waived,
                'duration_text' => $equipment->equipment->rate_type === 'Per Hour' ? $duration['text'] : null,
            ];
        })->values()->toArray();
    }

    /**
     * Get services breakdown with individual fees.
     *
     * Services are flat-fee (extra_services.service_fee) and are NOT affected
     * by booking duration. Waived services contribute 0.
     */
    public function getServicesBreakdown($form): array
    {
        // Guard against unloaded relationship to keep this method safe when
        // called from contexts that may not have eager-loaded services.
        if (!$form->relationLoaded('requestedServices')) {
            $form->load('requestedServices.service');
        }

        return $form->requestedServices->map(function ($requestedService) {
            $service = $requestedService->service;
            $unitPrice = $service->service_fee ?? 0;
            $isWaived = (bool) $requestedService->is_waived;

            return [
                'name'          => $service->service_name,
                'unit_price'    => $unitPrice,
                'fee'           => $isWaived ? 0 : $unitPrice,
                'rate_type'     => 'Flat', // services have no rate_type column
                'is_waived'     => $isWaived,
                'duration_text' => null,
            ];
        })->values()->toArray();
    }

    /**
     * Calculate approved fee (base + additional - discounts + late penalty).
     *
     * Used by admin flows AFTER approval. Not used at submission time.
     */
    public function calculateApprovedFee($form): float
    {
        $baseFee       = $this->calculateBaseFee($form);
        $additionalFees = $form->requisitionFees->sum('fee_amount');
        $discounts     = $this->calculateTotalDiscounts($form, $baseFee + $additionalFees);
        $latePenalty   = $form->is_late ? $form->late_penalty_fee : 0;

        return max(0, $baseFee + $additionalFees - $discounts + $latePenalty);
    }

    /**
     * Get complete fee summary for a form.
     *
     * Used by:
     *   - The submission preview endpoint (calculateFeeBreakdown)
     *   - The admin request view (getRequestViewData)
     */
    public function getFeeSummary($form): array
    {
        $duration      = $this->getDurationDetails($form);
        $baseFee       = $this->calculateBaseFee($form);
        $additionalFees = $form->requisitionFees->sum('fee_amount');
        $discounts     = $this->calculateTotalDiscounts($form, $baseFee + $additionalFees);
        $latePenalty   = $form->is_late ? $form->late_penalty_fee : 0;

        return [
            'duration'        => $duration,
            'base_fee'        => $baseFee,
            'additional_fees' => $additionalFees,
            'discounts'       => $discounts,
            'late_penalty'    => $latePenalty,
            'approved_fee'    => $baseFee + $additionalFees - $discounts + $latePenalty,
            'breakdown'       => [
                'facilities' => $this->getFacilitiesBreakdown($form),
                'equipment'  => $this->getEquipmentBreakdown($form),
                'services'   => $this->getServicesBreakdown($form),
            ],
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

    /**
     * Sum of all requested services, respecting waivers.
     *
     * Services are always flat-fee; duration is intentionally NOT applied.
     */
    private function calculateServiceTotal($form): float
    {
        if (!$form->relationLoaded('requestedServices')) {
            $form->load('requestedServices.service');
        }

        return $form->requestedServices->sum(function ($requestedService) {
            if ($requestedService->is_waived) {
                return 0;
            }

            return (float) ($requestedService->service->service_fee ?? 0);
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
            $end   = Carbon::parse($form->end_date);
            $days  = $start->diffInDays($end) + 1;
            $hours = $days * 8;
            $text  = $days === 1 ? '1 day (All Day)' : $days . ' days (All Day)';
        } else {
            $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
            $endDateTime   = Carbon::parse($form->end_date . ' ' . $form->end_time);
            $hours = max(1, $startDateTime->diffInHours($endDateTime));
            $text  = $hours === 1 ? '1 hour' : $hours . ' hours';
        }

        return [
            'hours' => $hours,
            'text'  => $text,
        ];
    }
}