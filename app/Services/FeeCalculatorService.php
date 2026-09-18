<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * FeeCalculatorService
 * ----------------------------------------------------------------------------
 * Centralizes all fee math for requisition forms.
 *
 * Price resolution (Task B — price snapshot):
 *   - Prefer `requested_*.fee_snapshot` when present (frozen at submission).
 *   - Fall back to the live parent price (`base_fee` / `service_fee`) for
 *     session-cart previews and any legacy rows before backfill.
 *
 * Fee composition at submission time:
 *   tentative_fee = facilities + equipment + services  (waivers NOT applied)
 *
 * Fee composition at approval time (admin-side, not this service's concern):
 *   approved_fee = tentative_fee + requisitionFees.additional - discounts
 *                  + late_penalty (waivers handled there)
 * ----------------------------------------------------------------------------
 */
class FeeCalculatorService
{
    public function calculateBaseFee($form): float
    {
        $duration = $this->getDurationDetails($form);

        return $this->calculateFacilityTotal($form, $duration['hours'])
             + $this->calculateEquipmentTotal($form, $duration['hours'])
             + $this->calculateServiceTotal($form);
    }

    public function getFacilitiesBreakdown($form): array
    {
        $duration = $this->getDurationDetails($form);

        return $form->requestedFacilities->map(function ($facility) use ($duration) {
            $unitPrice = $this->resolveUnitPrice($facility, $facility->facility, 'base_fee');
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

    public function getEquipmentBreakdown($form): array
    {
        $duration = $this->getDurationDetails($form);

        return $form->requestedEquipment->map(function ($equipment) use ($duration) {
            $unitPrice = $this->resolveUnitPrice($equipment, $equipment->equipment, 'base_fee');
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

    public function getServicesBreakdown($form): array
    {
        if (!$form->relationLoaded('requestedServices')) {
            $form->load('requestedServices.service');
        }

        return $form->requestedServices->map(function ($requestedService) {
            $service   = $requestedService->service;
            $unitPrice = $this->resolveUnitPrice($requestedService, $service, 'service_fee');
            $isWaived  = (bool) $requestedService->is_waived;

            return [
                'name'          => $service->service_name,
                'unit_price'    => $unitPrice,
                'fee'           => $isWaived ? 0 : $unitPrice,
                'rate_type'     => 'Flat',
                'is_waived'     => $isWaived,
                'duration_text' => null,
            ];
        })->values()->toArray();
    }

    public function calculateApprovedFee($form): float
    {
        $baseFee        = $this->calculateBaseFee($form);
        $additionalFees = $form->requisitionFees->sum('fee_amount');
        $discounts      = $this->calculateTotalDiscounts($form, $baseFee + $additionalFees);
        $latePenalty    = $form->is_late ? $form->late_penalty_fee : 0;

        return max(0, $baseFee + $additionalFees - $discounts + $latePenalty);
    }

    public function getFeeSummary($form): array
    {
        $duration       = $this->getDurationDetails($form);
        $baseFee        = $this->calculateBaseFee($form);
        $additionalFees = $form->requisitionFees->sum('fee_amount');
        $discounts      = $this->calculateTotalDiscounts($form, $baseFee + $additionalFees);
        $latePenalty    = $form->is_late ? $form->late_penalty_fee : 0;

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
    // Private helpers
    // ------------------------------------------------------------------------

    /**
     * Resolve the unit price for a requested item.
     *
     * Prefers the frozen `fee_snapshot` column. Falls back to the live parent
     * price for session-cart preview objects (which have no snapshot) and for
     * legacy rows created before the snapshot column existed.
     */
    private function resolveUnitPrice($requestedItem, $parentModel, string $liveField): float
    {
        if (isset($requestedItem->fee_snapshot) && $requestedItem->fee_snapshot !== null) {
            return (float) $requestedItem->fee_snapshot;
        }

        return (float) ($parentModel->{$liveField} ?? 0);
    }

    private function calculateFacilityTotal($form, float $durationHours): float
    {
        return $form->requestedFacilities->sum(function ($facility) use ($durationHours) {
            return $this->calculateFacilityFee(
                $this->resolveUnitPrice($facility, $facility->facility, 'base_fee'),
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
                $this->resolveUnitPrice($equipment, $equipment->equipment, 'base_fee'),
                $equipment->equipment->rate_type,
                $equipment->quantity,
                $durationHours,
                $equipment->is_waived
            );
        });
    }

    private function calculateServiceTotal($form): float
    {
        if (!$form->relationLoaded('requestedServices')) {
            $form->load('requestedServices.service');
        }

        return $form->requestedServices->sum(function ($requestedService) {
            if ($requestedService->is_waived) {
                return 0;
            }

            return $this->resolveUnitPrice($requestedService, $requestedService->service, 'service_fee');
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