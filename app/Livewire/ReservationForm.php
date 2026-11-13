<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Services\CheckAvailabilityService;
use App\Services\FeeCalculatorService;
use App\Models\Facility;
use App\Models\Equipment;
use App\Models\LookupTables\FacilityCategory;
use App\Models\LookupTables\EquipmentCategory;
use App\Models\ExtraService;
use App\Models\RequisitionPurpose;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

#[Layout('layouts.app')]
class ReservationForm extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $currentStep = 1;

    // Pagination state
    public $facilitiesPage = 1;
    public $equipmentPage = 1;

    // --- Schedule ---
    public $startDate;
    public $endDate;
    public $startTime;
    public $endTime;
    public $allDay = false;
    public $availabilityResult = null;
    public $conflictHtml = null;

    // --- Details ---
    public $userType = 'Internal';
    public $firstName;
    public $lastName;
    public $email;
    public $contactNumber;
    public $organizationName;
    public $schoolId;
    public $eventTitle;
    public $eventDetails;
    public $purposeId;
    public $numParticipants = 1;
    public $numChairs = 0;
    public $numTables = 0;
    public $numMicrophones = 0;
    public $additionalRequests;
    public $cleanupHours = 4;
    public $document;

    // --- Cart ---
    public $selectedItems = [];
    public $selectedServices = [];

    // --- Workflow (used by admin child view; initialized here so it's never undefined) ---
    public $statuses = [];
    public $statusId = null;

    // --- Search ---
    public $facilitySearch = '';
    public $equipmentSearch = '';

    // --- Category Filters ---
    public $facilityCategoryFilter = '';
    public $equipmentCategoryFilter = '';

    public function mount()
    {
        if (!$this->usesSession()) {
            return;
        }

        $this->selectedItems = Session::get('selected_items', []);
        $this->selectedServices = collect($this->selectedItems)
            ->where('type', 'service')
            ->pluck('service_id')
            ->toArray();
    }

    /**
     * Whether this form persists its cart to the session.
     * Public uses session; admin subclasses override to false.
     */
    protected function usesSession(): bool
    {
        return true;
    }

    public function getPaginatedFacilitiesProperty()
    {
        $query = Facility::with(['images', 'parentFacility']);

        if ($this->facilitySearch) {
            $search = strtolower($this->facilitySearch);
            $query->whereRaw('LOWER(facility_name) LIKE ?', ["%{$search}%"]);
        }

        if ($this->facilityCategoryFilter) {
            $parts = explode(':', $this->facilityCategoryFilter);
            if (count($parts) === 2) {
                if ($parts[0] === 'category') {
                    $query->where('category_id', $parts[1]);
                } elseif ($parts[0] === 'subcategory') {
                    $query->where('subcategory_id', $parts[1]);
                }
            }
        }

        return $query->paginate(15, ['*'], 'facilitiesPage');
    }

    public function getPaginatedEquipmentProperty()
    {
        $query = Equipment::with(['images', 'items']);

        if ($this->equipmentSearch) {
            $search = strtolower($this->equipmentSearch);
            $query->whereRaw('LOWER(equipment_name) LIKE ?', ["%{$search}%"]);
        }

        if ($this->equipmentCategoryFilter) {
            $parts = explode(':', $this->equipmentCategoryFilter);
            if (count($parts) === 2 && $parts[0] === 'category') {
                $query->where('category_id', $parts[1]);
            }
        }

        return $query->paginate(15, ['*'], 'equipmentPage');
    }

    public function updatedFacilitySearch()
    {
        $this->resetPage('facilitiesPage');
    }
    public function updatedFacilityCategoryFilter()
    {
        $this->resetPage('facilitiesPage');
    }
    public function updatedEquipmentSearch()
    {
        $this->resetPage('equipmentPage');
    }
    public function updatedEquipmentCategoryFilter()
    {
        $this->resetPage('equipmentPage');
    }

    public function getTimeSlotsProperty()
    {
        $times = [];
        for ($h = 0; $h < 24; $h++) {
            foreach (['00', '30'] as $m) {
                $time = sprintf('%02d:%s', $h, $m);
                $displayHour = $h % 12;
                if ($displayHour === 0) {
                    $displayHour = 12;
                }
                $times[$time] = $displayHour . ':' . $m . ' ' . ($h >= 12 ? 'PM' : 'AM');
            }
        }
        return $times;
    }

    public function updatedStartDate($value)
    {
        if ($value) {
            if (!$this->endDate || $this->endDate < $value) {
                $this->endDate = $value;
            }
        }
    }

    public function updatedStartTime($value)
    {
        if ($value) {
            if (!$this->endTime || $this->endTime <= $value) {
                $this->endTime = Carbon::parse($value)->addHour()->format('H:i');
            }
        }
    }

    /**
     * Handle the All Day toggle.
     * ON: If dates match, push end date to the next day. Set times to 00:00.
     * OFF: If end date is exactly one day after start, pull it back. Reset times.
     */
    public function updatedAllDay($value)
    {
        if ($value) {
            // Toggled ON: If dates match, push end date to the next day
            if ($this->startDate && $this->endDate === $this->startDate) {
                $this->endDate = Carbon::parse($this->startDate)->addDay()->format('Y-m-d');
            }
            // Set times to 00:00 for UI consistency
            $this->startTime = '00:00';
            $this->endTime = '00:00';
        } else {
            // Toggled OFF: If the end date is exactly one day after the start date, reset it back to match
            if ($this->startDate && $this->endDate) {
                $expectedNextDay = Carbon::parse($this->startDate)->addDay()->format('Y-m-d');
                if ($this->endDate === $expectedNextDay) {
                    $this->endDate = $this->startDate;
                }
            }
            // Reset times so user can pick a new time
            $this->startTime = null;
            $this->endTime = null;
        }
    }
    
    public function addItem($type, $id, $name, $fee, $rateType, $image = null, $quantity = 1)
    {
        $idField = $type . '_id';

        // Add fallback logic here for safety
        if ($type === 'facility') {
            $model = Facility::with('images')->find($id);
            if ($model && !$image) {
                $img = $model->images->firstWhere('image_type', 'Primary') ?? $model->images->first();
                $image = $img ? $img->image_url : null;
            }
        } elseif ($type === 'equipment') {
            $model = Equipment::with(['items', 'images'])->find($id);
            if ($model && !$image) {
                // Prefer the physical item's image
                $image = $model->items->where('status_id', 1)->whereIn('condition_id', [1, 2, 3])->first()?->image_url;
                // Fallback to the equipment image with sort_order = 1
                if (!$image) {
                    $img = $model->images->firstWhere('sort_order', 1) ?? $model->images->first();
                    $image = $img ? $img->image_url : null;
                }
            }
        }


        // Equipment: replace qty if already in cart, otherwise append
        if ($type === 'equipment') {
            $index = collect($this->selectedItems)->search(
                fn($item) => ($item['type'] ?? null) === 'equipment'
                    && (int) ($item['equipment_id'] ?? 0) === (int) $id
            );

            if ($index !== false) {
                $this->selectedItems[$index]['quantity'] = max(1, (int) $quantity);
                $this->selectedItems = array_values($this->selectedItems);
                if ($this->usesSession()) {
                    Session::put('selected_items', $this->selectedItems);
                }
                $this->dispatch('toast', message: 'Equipment quantity updated.', type: 'success');
                return;
            }

            $this->selectedItems[] = [
                'type' => 'equipment',
                $idField => $id,
                'name' => $name,
                'base_fee' => $fee,
                'rate_type' => $rateType,
                'quantity' => max(1, (int) $quantity),
                'image' => $image,
            ];
            if ($this->usesSession()) {
                Session::put('selected_items', $this->selectedItems);
            }
            $this->dispatch('toast', message: 'Equipment added.', type: 'success');
            return;
        }

        // Facilities / services: original single-add behavior
        $exists = collect($this->selectedItems)->contains(
            fn($item) => isset($item[$idField]) && $item[$idField] == $id && $item['type'] === $type
        );

        if (!$exists) {
            $this->selectedItems[] = [
                'type' => $type,
                $idField => $id,
                'name' => $name,
                'base_fee' => $fee,
                'rate_type' => $rateType,
                'quantity' => 1,
                'image' => $image,
            ];
            if ($this->usesSession()) {
                Session::put('selected_items', $this->selectedItems);
            }
            $this->dispatch('toast', message: ucfirst($type) . ' added.', type: 'success');
        }
    }

    public function removeItem($type, $id)
    {
        $idField = $type . '_id';
        $this->selectedItems = collect($this->selectedItems)->reject(function ($item) use ($id, $type, $idField) {
            return isset($item[$idField]) && $item[$idField] == $id && $item['type'] === $type;
        })->values()->toArray();

        if ($this->usesSession()) {
            Session::put('selected_items', $this->selectedItems);
        }
        $this->dispatch('toast', message: ucfirst($type) . ' removed.', type: 'success');
    }

    public function updatedSelectedServices($value)
    {
        $this->selectedItems = collect($this->selectedItems)
            ->reject(fn($item) => $item['type'] === 'service')
            ->values()
            ->toArray();

        if (!empty($value)) {
            $services = ExtraService::whereIn('service_id', $value)->get();
            foreach ($services as $service) {
                $this->selectedItems[] = [
                    'type' => 'service',
                    'service_id' => $service->service_id,
                    'name' => $service->service_name,
                    'base_fee' => $service->service_fee ?? 0,
                    'rate_type' => 'Flat',
                    'quantity' => 1,
                    'image' => null,
                ];
            }
        }
        if ($this->usesSession()) {
            Session::put('selected_items', $this->selectedItems);
        }
    }

    public function checkAvailability(CheckAvailabilityService $checker)
    {
        $this->validateSchedule();

        $result = $checker->checkAll([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'start_time' => $this->allDay ? '00:00:00' : $this->startTime,
            'end_time' => $this->allDay ? '00:00:00' : $this->endTime,
            'all_day' => $this->allDay,
            'cleanup_hours' => $this->cleanupHours,
            'items' => $this->selectedItems,
        ]);

        if ($result['available'] && empty($result['soft_conflicts'])) {
            $this->availabilityResult = 'available';
            $this->conflictHtml = null;
            $this->dispatch('toast', message: 'Time slot is available.', type: 'success');
        } elseif ($result['available'] && !empty($result['soft_conflicts'])) {
            // Soft conflict only (services) — modal opens, submit still allowed
            $this->availabilityResult = 'warning';
            $this->conflictHtml = $checker->formatConflictsToHtml($result['soft_conflicts']);
            $this->dispatch('open-conflict-modal');
            $this->dispatch('toast', message: 'Service warning — review before submitting.', type: 'warning');
        } else {
            $this->availabilityResult = 'conflict';
            $allConflicts = array_merge($result['hard_conflicts'], $result['soft_conflicts']);
            $this->conflictHtml = $checker->formatConflictsToHtml($allConflicts);
            $this->dispatch('open-conflict-modal');
            $this->dispatch('toast', message: 'Time slot conflict detected.', type: 'error');
        }
    }

    public function nextStep()
    {
        $this->validateSchedule();
        $this->validateDetails();

        $this->syncToSession();
        $this->currentStep = 2;
    }

    public function previousStep()
    {
        $this->currentStep = 1;
    }

    protected function validateSchedule()
    {
        try {
            $this->validate([
                'startDate' => 'required|date',
                'endDate' => 'required|date|after_or_equal:startDate',
                'startTime' => $this->allDay ? 'nullable' : 'required',
                'endTime' => $this->allDay ? 'nullable' : 'required',
            ]);

            // Strict blocker: Start and End cannot be identical
            if ($this->startDate === $this->endDate && $this->startTime === $this->endTime) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'endTime' => 'The start and end times cannot be identical. Please select a valid duration.',
                ]);
            }

            // Custom validation: End DateTime must be strictly AFTER Start DateTime
            if ($this->startDate && $this->endDate && !$this->allDay) {
                $start = Carbon::parse("{$this->startDate} {$this->startTime}");
                $end = Carbon::parse("{$this->endDate} {$this->endTime}");

                if ($end <= $start) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'endTime' => 'The end date and time must be after the start date and time.',
                    ]);
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            // This dispatches the exact error message to your toast
            $this->dispatch('toast', message: $e->validator->errors()->first(), type: 'error');
            throw $e;
        }
    }

    protected function validateDetails()
    {
        try {
            $this->validate([
                'userType' => 'required|in:Internal,External',
                'schoolId' => 'required_if:userType,Internal|nullable|string|max:20',
                'firstName' => 'required|string|max:50',
                'lastName' => 'required|string|max:50',
                'email' => 'required|email|max:100',
                'contactNumber' => ['nullable', 'regex:/^\d{1,15}$/', 'max:15'],
                'eventTitle' => 'required|string|max:100',
                'purposeId' => 'required',
                'numParticipants' => 'required|integer|min:1',
                'numChairs' => 'required|integer|min:0',
                'numTables' => 'required|integer|min:0',
                'numMicrophones' => 'required|integer|min:0',
                'cleanupHours' => 'required|integer|min:0|max:12',
                'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', message: 'Please complete all required details.', type: 'error');
            throw $e;
        }
    }

    public function render(FeeCalculatorService $feeCalculator)
    {
        // Reuse parent logic for fee calculation
        $facilities = [];
        $equipment = [];
        $services = [];

        foreach ($this->selectedItems as $item) {
            if ($item['type'] === 'facility') {
                $facilities[] = (object) [
                    'facility' => (object) [
                        'base_fee' => $item['base_fee'],
                        'facility_name' => $item['name'],
                        'rate_type' => $item['rate_type'],
                    ],
                    'is_waived' => false,
                ];
            } elseif ($item['type'] === 'equipment') {
                $equipment[] = (object) [
                    'equipment' => (object) [
                        'base_fee' => $item['base_fee'],
                        'equipment_name' => $item['name'],
                        'rate_type' => $item['rate_type'],
                    ],
                    'quantity' => $item['quantity'] ?? 1,
                    'is_waived' => false,
                ];
            } elseif ($item['type'] === 'service') {
                $services[] = (object) [
                    'service' => (object) [
                        'service_name' => $item['name'],
                        'service_fee' => $item['base_fee'],
                    ],
                    'is_waived' => false,
                ];
            }
        }

        $tempForm = new \App\Models\RequisitionForm();
        $tempForm->start_date = $this->startDate;
        $tempForm->end_date = $this->endDate;
        $tempForm->start_time = $this->allDay ? '00:00:00' : ($this->startTime ?? '00:00:00');
        $tempForm->end_time = $this->allDay ? '00:00:00' : ($this->endTime ?? '00:00:00');
        $tempForm->all_day = $this->allDay;
        $tempForm->is_late = false;
        $tempForm->late_penalty_fee = 0;

        $tempForm->setRelation('requestedFacilities', collect($facilities));
        $tempForm->setRelation('requestedEquipment', collect($equipment));
        $tempForm->setRelation('requestedServices', collect($services));
        $tempForm->setRelation('requisitionFees', collect([]));

        $feeSummary = $feeCalculator->getFeeSummary($tempForm);

        return view($this->viewName(), [
            'feeBreakdown' => $feeSummary,
            'facilities' => $this->paginatedFacilities,
            'equipment' => $this->paginatedEquipment,
            'services' => ExtraService::all(),
            'purposes' => RequisitionPurpose::all(),
            'facilityCategories' => FacilityCategory::with('subcategories')->get(),
            'equipmentCategories' => EquipmentCategory::all(),
        ]);
    }

    /**
     * View rendered by this component. Admin subclass overrides.
     */
    protected function viewName(): string
    {
        return 'livewire.reservation-form';
    }

    protected function syncToSession()
    {
        if (!$this->usesSession()) {
            return;
        }

        $documentUrl = null;
        $documentPublicId = null;

        if ($this->document) {
            $path = $this->document->store('event-documents', 'public');
            $documentUrl = asset('storage/' . $path);
            $documentPublicId = $path;
        }

        Session::put('request_info', [
            'user_type' => $this->userType,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'contact_number' => $this->contactNumber,
            'organization_name' => $this->organizationName,
            'school_id' => $this->schoolId,
            'num_participants' => $this->numParticipants,
            'num_chairs' => $this->numChairs,
            'num_tables' => $this->numTables,
            'num_microphones' => $this->numMicrophones,
            'purpose_id' => $this->purposeId,
            'additional_requests' => $this->additionalRequests,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'start_time' => $this->allDay ? '00:00:00' : $this->startTime,
            'end_time' => $this->allDay ? '00:00:00' : $this->endTime,
            'all_day' => $this->allDay,
            'cleanup_hours' => $this->cleanupHours,
            'event_documents_url' => $documentUrl,
        ]);
    }
}