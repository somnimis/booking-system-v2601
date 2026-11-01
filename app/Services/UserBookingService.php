<?php

namespace App\Services;

use App\Models\RequisitionForm;
use Illuminate\Support\Str;

/**
 * Builds server-rendered HTML for the public "Your Bookings" page.
 * Delegates all field/schedule/fee formatting to RequisitionFormatterService
 * so this class only owns: search + view composition.
 */
class UserBookingService
{
    public function __construct(
        private RequisitionFormatterService $formatter
    ) {}

    /**
     * Search requisitions by access code OR email.
     * Returns pre-rendered card HTML plus status filter list.
     */
    public function search(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return ['success' => false, 'message' => 'Please enter a reference code or email.'];
        }

        $forms = RequisitionForm::with([
            'formStatus:status_id,status_name,color_code',
            'purpose:purpose_id,purpose_name',
            'requestedFacilities.facility:facility_id,facility_name,base_fee,rate_type',
            'requestedEquipment.equipment:equipment_id,equipment_name,base_fee,rate_type',
            'requestedServices.service:service_id,service_name,service_fee',
            'requisitionFees',
            'finalizedBy',
            'closedBy',
            'requisitionApprovals',
        ])
            ->where('access_code', $query)
            ->orWhere('email', $query)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        if ($forms->isEmpty()) {
            return ['success' => false, 'message' => 'No requisition forms found.'];
        }

        $statuses = $forms->map(fn ($f) => [
            'status_name' => $f->formStatus->status_name,
            'slug'        => Str::slug($f->formStatus->status_name),
        ])->unique('slug')->values();

        $html = $forms->map(function ($form) {
            $data = array_merge(
                $this->formatter->formatPublicForm($form),
                ['status_tracking' => $this->formatter->getStatusTracking($form)]
            );

            return view('partials.booking-card', [
                'data'       => $data,
                'statusSlug' => Str::slug($form->formStatus->status_name),
            ])->render();
        })->implode('');

        return [
            'success'  => true,
            'count'    => $forms->count(),
            'html'     => $html,
            'statuses' => $statuses,
        ];
    }
}