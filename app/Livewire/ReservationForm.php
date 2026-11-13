<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
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

    public $currentStep = 1;

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

    public function getFilteredFacilitiesProperty()
    {
        $query = Facility::whereNull('parent_facility_id')->with(['images', 'children.images']);

        if ($this->facilitySearch) {
            $search = strtolower($this->facilitySearch);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(facility_name) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('children', function ($q2) use ($search) {
                        $q2->whereRaw('LOWER(facility_name) LIKE ?', ["%{$search}%"]);
                    });
            });
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

        return $query->get();
    }

    public function getFilteredEquipmentProperty()
    {
        $query = Equipment::with('images');

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

        return $query->get();
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

    public function updatedEndDate()
    {
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);
            if ($start->diffInHours($end) >= 24 || $start->diffInDays($end) >= 1) {
                $this->allDay = true;
            }
        }
    }

    public function updatedAllDay($value)
    {
        if ($value) {
            $this->startTime = null;
            $this->endTime = null;
        }
    }

    public function addItem($type, $id, $name, $fee, $rateType, $image = null)
    {
        $idField = $type . '_id';
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
            'end_time' => $this->allDay ? '23:59:59' : $this->endTime,
            'all_day' => $this->allDay,
            'items' => $this->selectedItems,
        ]);

        if ($result['available']) {
            $this->availabilityResult = 'available';
            $this->conflictHtml = null;
            $this->dispatch('toast', message: 'Time slot is available.', type: 'success');
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
                'endTime' => $this->allDay ? 'nullable' : 'required|after:startTime',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', message: 'Please complete the schedule fields.', type: 'error');
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
        $tempForm->end_time = $this->allDay ? '23:59:59' : ($this->endTime ?? '23:59:59');
        $tempForm->all_day = $this->allDay;
        $tempForm->is_late = false;
        $tempForm->late_penalty_fee = 0;

        $tempForm->setRelation('requestedFacilities', collect($facilities));
        $tempForm->setRelation('requestedEquipment', collect($equipment));
        $tempForm->setRelation('requestedServices', collect($services));
        $tempForm->setRelation('requisitionFees', collect([]));

        $feeSummary = $feeCalculator->getFeeSummary($tempForm);

        return view('livewire.admin-reservation-form', [
            'feeBreakdown' => $feeSummary,
            'facilities' => $this->filteredFacilities,
            'equipment' => $this->filteredEquipment,
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
            'end_time' => $this->allDay ? '23:59:59' : $this->endTime,
            'all_day' => $this->allDay,
            'cleanup_hours' => $this->cleanupHours,
            'event_documents_url' => $documentUrl,
        ]);
    }
}