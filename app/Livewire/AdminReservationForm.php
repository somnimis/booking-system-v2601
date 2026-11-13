<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\CheckAvailabilityService;
use App\Services\FeeCalculatorService;
use App\Models\Facility;
use App\Models\Equipment;
use App\Models\ExtraService;
use App\Models\RequisitionPurpose;
use App\Models\FormStatus;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Admin reservation form.
 *
 * Extends ReservationForm to reuse cart, availability, fee preview, and
 * schedule helpers. Overrides:
 *  - mount()         -> no session hydration, loads statuses instead
 *  - usesSession()   -> false (cart lives in component state only)
 *  - viewName()      -> admin view
 *  - submitAdmin()   -> POSTs to /api/admin/requisition/create with Bearer
 */
#[Layout('layouts.admin')]
class AdminReservationForm extends ReservationForm
{
    public $adminToken = null;
    public $adminId = null;
    public $statusId = null;
    public $statuses = [];

    public function mount($adminId = null)
    {
        $this->adminId = $adminId;

        $this->statuses = FormStatus::whereIn('status_name', [
            FormStatus::PENDING_APPROVAL,
            FormStatus::RESERVED,
        ])
            ->select(['status_id', 'status_name', 'color_code'])
            ->get()
            ->toArray();
    }

    /**
     * Admin override: resolve fee/rate server-side from the model by ID,
     * then delegate to parent::addItem() so cart logic stays single-sourced.
     */
    public function addItem($type, $id, $name = null, $fee = null, $rateType = null, $image = null)
    {
        if ($type === 'facility') {
            $model = Facility::find($id);
            if (!$model)
                return;
            $name = $model->facility_name;
            $fee = $model->base_fee ?? 0;
            $rateType = $model->rate_type ?? 'Per Event';
            $image = null;
        } elseif ($type === 'equipment') {
            $model = Equipment::find($id);
            if (!$model)
                return;
            $name = $model->equipment_name;
            $fee = $model->base_fee ?? 0;
            $rateType = $model->rate_type ?? 'Per Event';
            $image = null;
        } else {
            return; // services flow through selectedServices
        }

        parent::addItem($type, $id, $name, $fee, $rateType, $image);
    }

    protected function usesSession(): bool
    {
        return false;
    }

    protected function viewName(): string
    {
        return 'livewire.admin-reservation-form';
    }

    /**
     * Submit as an admin: build payload from component state and POST
     * to the admin create endpoint with Bearer auth.
     */
    public function submitAdmin()
    {
        try {
            $this->validateSchedule();
            $this->validateDetails();
            $this->validate([
                'statusId' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', message: 'Please complete all required fields.', type: 'error');
            return;
        }

        if (empty($this->selectedItems)) {
            $this->dispatch('toast', message: 'Select at least one resource.', type: 'error');
            return;
        }

        // Use the passed admin ID
        $adminId = $this->adminId;

        if (!$adminId) {
            $this->dispatch('toast', message: 'Unauthorized: Admin ID not found.', type: 'error');
            return;
        }

        $items = [];
        foreach ($this->selectedItems as $i) {
            if ($i['type'] === 'facility') {
                $items[] = ['type' => 'facility', 'facility_id' => (int) $i['facility_id']];
            } elseif ($i['type'] === 'equipment') {
                $items[] = [
                    'type' => 'equipment',
                    'equipment_id' => (int) $i['equipment_id'],
                    'quantity' => (int) ($i['quantity'] ?? 1),
                ];
            } elseif ($i['type'] === 'service') {
                $items[] = ['type' => 'service', 'service_id' => (int) $i['service_id']];
            }
        }

        $servicePayload = [
            'user_type' => $this->userType,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'contact_number' => $this->contactNumber,
            'organization_name' => $this->organizationName,
            'school_id' => $this->schoolId,
            'event_title' => $this->eventTitle,
            'event_details' => $this->eventDetails,
            'purpose_id' => (int) $this->purposeId,
            'num_participants' => (int) $this->numParticipants,
            'num_tables' => (int) $this->numTables,
            'num_chairs' => (int) $this->numChairs,
            'num_microphones' => (int) $this->numMicrophones,
            'additional_requests' => $this->additionalRequests,
            'cleanup_hours' => (int) $this->cleanupHours,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'start_time' => $this->allDay ? '00:00' : $this->startTime,
            'end_time' => $this->allDay ? '23:59' : $this->endTime,
            'all_day' => (bool) $this->allDay,
            'status_id' => (int) $this->statusId,
            'items' => $items,
            'event_documents_url' => null,
        ];

        try {
            // Call the service directly to avoid localhost cURL timeouts
            $creationService = app(\App\Services\RequisitionCreationService::class);
            $requisitionForm = $creationService->createFromPayload($servicePayload, $adminId);

            $this->dispatch('admin-submit-success', request_id: $requisitionForm->request_id);

        } catch (\App\Exceptions\BookingConflictException $e) {
            $this->conflictHtml = app(CheckAvailabilityService::class)->formatConflictsToHtml($e->getConflictItems());
            $this->dispatch('open-conflict-modal');
        } catch (\Throwable $e) {
            \Log::error('Admin submit failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Submission failed: ' . $e->getMessage(), type: 'error');
        }
    }
}