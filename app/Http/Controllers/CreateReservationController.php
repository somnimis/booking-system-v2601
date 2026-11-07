<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\ExtraService;
use App\Models\Equipment;
use App\Models\RequisitionPurpose;
use App\Models\FormStatus;
use App\Models\RequisitionForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\EquipmentItem;
use App\Services\FeeCalculatorService;
use App\Services\CheckAvailabilityService;
use App\Services\NotificationService;
use App\Services\AccessCodeService;
use App\Services\ReceiptService;
use Illuminate\Support\Facades\DB;
use App\Models\RequestedFacility;
use App\Models\RequestedEquipment;
use App\Models\RequestedService;
use App\Models\RequisitionComment;

class CreateReservationController extends Controller
{

    protected $feeCalculator;
    protected $availabilityChecker;
    protected $notificationService;
    protected $receiptService;

    public function __construct(FeeCalculatorService $feeCalculator, CheckAvailabilityService $availabilityChecker, NotificationService $notificationService, ReceiptService $receiptService)
    {
        $this->feeCalculator = $feeCalculator;
        $this->availabilityChecker = $availabilityChecker;
        $this->notificationService = $notificationService;
        $this->receiptService = $receiptService;
    }

    /**
     * Create a new admin reservation
     */
    public function createReservation(Request $request)
    {
        try {
            Log::debug('Creating admin reservation', $request->all());

            DB::beginTransaction();

            // Validate request
            $validatedData = $this->validateReservationRequest($request);

            // Generate unique access code
            $accessCodeService = app(AccessCodeService::class);
            $validatedData['access_code'] = $accessCodeService->generateUniqueAccessCode();

            // Check for facility conflicts
            $conflictItems = [];

            foreach ($validatedData['facilities'] as $facility) {
                $facilityConflicts = $this->availabilityChecker->checkFacilityAvailability(
                    $facility['facility_id'],
                    $validatedData['start_date'],
                    $validatedData['end_date'],
                    $validatedData['start_time'] ?? '00:00:00',
                    $validatedData['end_time'] ?? '23:59:59',
                    $validatedData['all_day']
                );

                if (!empty($facilityConflicts)) {
                    $conflictItems = array_merge($conflictItems, $facilityConflicts);
                }
            }

            // Check for equipment conflicts
            if (!empty($validatedData['equipment'])) {
                foreach ($validatedData['equipment'] as $equipment) {
                    $availableCount = $this->availabilityChecker->checkEquipmentAvailability(
                        $equipment['equipment_id'],
                        $validatedData['start_date'],
                        $validatedData['end_date'],
                        $validatedData['all_day']
                    );

                    if ($availableCount < $equipment['quantity']) {
                        $equipmentName = EquipmentItem::find($equipment['equipment_id'])->equipment_name ?? 'Unknown';
                        $conflictItems[] = [
                            'type' => 'equipment',
                            'id' => $equipment['equipment_id'],
                            'name' => $equipmentName,
                            'source' => 'requisition',
                            'status' => null,
                            'message' => "Only {$availableCount} available, requested {$equipment['quantity']}"
                        ];
                    }
                }
            }

            // If conflicts exist, return them
            if (!empty($conflictItems)) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Scheduling conflicts detected',
                    'conflict_items' => $conflictItems
                ], 409);
            }

            // Create the reservation
            $requisitionForm = $this->createRequisitionForm($validatedData);

            // Add related items
            $this->addFacilities($requisitionForm->request_id, $validatedData['facilities']);
            $this->addEquipment($requisitionForm->request_id, $validatedData['equipment'] ?? []);
            $this->addServices($requisitionForm->request_id, $validatedData['services'] ?? []);

            // Add comment record
            $this->addCommentRecord($requisitionForm->request_id);

            // Create approval chain records
            try {
                $approvalChainService = app(\App\Services\ApprovalChainService::class);
                $approvalChainService->createApprovalChain($requisitionForm);
                Log::info('Approval chain created for requisition', [
                    'request_id' => $requisitionForm->request_id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create approval chain', [
                    'request_id' => $requisitionForm->request_id,
                    'error' => $e->getMessage()
                ]);
                // Don't rollback - approval chain failure shouldn't prevent reservation creation
            }

            DB::commit();

            // Send confirmation email
            try {
                $notificationService = app(NotificationService::class);
                $notificationService->sendConfirmationEmail($requisitionForm);
            } catch (\Exception $e) {
                Log::error('Failed to send confirmation email: ' . $e->getMessage());
            }

            // Send approval request emails
            try {
                $this->notificationService->sendAdminApprovalEmails($requisitionForm);
            } catch (\Exception $e) {
                Log::error('Failed to send admin approval emails: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Reservation created successfully',
                'request_id' => $requisitionForm->request_id,
                'access_code' => $requisitionForm->access_code,
                'all_day' => $requisitionForm->all_day,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create reservation: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create reservation',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate the reservation request
     */
    private function validateReservationRequest(Request $request): array
    {
        $rules = $this->buildValidationRules($request);

        return $request->validate($rules);
    }

    /**
     * Build validation rules dynamically based on all_day flag
     */
    private function buildValidationRules(Request $request): array
    {
        $rules = [
            // User details
            'user_type' => 'required|in:Internal,External',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|max:100',
            'school_id' => 'nullable|string|max:20',
            'organization_name' => 'nullable|string|max:100',
            'contact_number' => ['nullable', 'regex:/^\d{1,15}$/', 'max:15'],

            // Form details
            'purpose_id' => 'required|exists:requisition_purposes,purpose_id',
            'num_participants' => 'required|integer|min:1',
            'num_tables' => 'required|integer|min:0',
            'num_chairs' => 'required|integer|min:0',
            'num_microphones' => 'required|integer|min:0',
            'additional_requests' => 'nullable|string|max:250',

            // Event details
            'event_title' => 'nullable|string|max:100',
            'event_details' => 'nullable|string|max:100',

            // Schedule
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'all_day' => 'required|boolean',

            // Requested items
            'facilities' => 'required|array|min:1',
            'facilities.*.facility_id' => 'required|exists:facilities,facility_id',
            'equipment' => 'array',
            'equipment.*.equipment_id' => 'required|exists:equipment,equipment_id',
            'equipment.*.quantity' => 'required|integer|min:1',
            'services' => 'array', // ADDED
            'services.*.service_id' => 'required|exists:extra_services,service_id', // ADDED

            // Status
            'status_id' => 'required|exists:form_statuses,status_id',
        ];

        // Add time rules conditionally
        if (!$request->all_day) {
            $rules['start_time'] = 'required|date_format:H:i';
            $rules['end_time'] = 'required|date_format:H:i|after:start_time';
        } else {
            $rules['start_time'] = 'nullable';
            $rules['end_time'] = 'nullable';
        }

        return $rules;
    }

    /**
     * Add service records
     */
    private function addServices(int $requestId, array $services): void
    {
        foreach ($services as $service) {
            RequestedService::create([
                'request_id' => $requestId,
                'service_id' => $service['service_id'],
                'is_waived' => false,
            ]);
        }
    }

    /**
     * Create the main requisition form record
     */
    private function createRequisitionForm(array $data): RequisitionForm
    {
        return RequisitionForm::create([
            // User details
            'user_type' => $data['user_type'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'school_id' => $data['school_id'] ?? null,
            'organization_name' => $data['organization_name'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,

            // Form details
            'purpose_id' => $data['purpose_id'],
            'num_participants' => $data['num_participants'],
            'num_tables' => $data['num_tables'] ?? 0,
            'num_chairs' => $data['num_chairs'] ?? 0,
            'num_microphones' => $data['num_microphones'] ?? 0,
            'access_code' => $data['access_code'],
            'additional_requests' => $data['additional_requests'] ?? null,

            // Event details
            'event_title' => $data['event_title'] ?? 'Admin Reservation',
            'event_details' => $data['event_details'] ?? null,

            // Schedule
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $this->formatStartTime($data),
            'end_time' => $this->formatEndTime($data),
            'all_day' => $data['all_day'],

            // Status
            'status_id' => $data['status_id'],
            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => auth()->id(),
        ]);
    }



    /**
     * Format start time based on all_day flag
     */
    private function formatStartTime(array $data): string
    {
        if ($data['all_day']) {
            return '00:00:00';
        }
        return $data['start_time'] ?? '00:00:00';
    }

    /**
     * Format end time based on all_day flag
     */
    private function formatEndTime(array $data): string
    {
        if ($data['all_day']) {
            return '23:59:59';
        }
        return $data['end_time'] ?? '23:59:59';
    }

    /**
     * Add facility records
     */
    private function addFacilities(int $requestId, array $facilities): void
    {
        foreach ($facilities as $facility) {
            RequestedFacility::create([
                'request_id' => $requestId,
                'facility_id' => $facility['facility_id'],
                'is_waived' => false,
            ]);
        }
    }

    /**
     * Add equipment records
     */
    private function addEquipment(int $requestId, array $equipment): void
    {
        foreach ($equipment as $item) {
            RequestedEquipment::create([
                'request_id' => $requestId,
                'equipment_id' => $item['equipment_id'],
                'quantity' => $item['quantity'],
                'is_waived' => false,
            ]);
        }
    }


    /**
     * Add comment record
     */
    private function addCommentRecord(int $requestId): void
    {
        // Get the authenticated admin's ID
        $adminId = auth()->id();

        if (!$adminId) {
            Log::error('Cannot add comment: admin_id is null', [
                'request_id' => $requestId,
                'auth_check' => auth()->check(),
                'user' => auth()->user()
            ]);
            throw new \Exception('Admin not authenticated');
        }

        RequisitionComment::create([
            'request_id' => $requestId,
            'admin_id' => $adminId, // Use the variable, not calling auth() again
            'comment' => 'Admin created this reservation manually',
        ]);
    }


    /**
     * Get all form initialization data EXCEPT facilities and equipment
     * These will be lazy-loaded separately
     */
    public function getFormInitData(Request $request)
    {
        try {
            $admin = $request->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Only load lightweight data initially (no facilities or equipment)
            $data = [
                'purposes' => RequisitionPurpose::all(['purpose_id', 'purpose_name']),
                'services' => ExtraService::all(['service_id', 'service_name', 'service_fee']),
                'statuses' => FormStatus::whereNotIn('status_name', ['Returned', 'Late Return', 'Completed', 'Rejected', 'Cancelled'])
                    ->select(['status_id', 'status_name', 'color_code'])
                    ->get(),
                'access_code' => $this->generateUniqueAccessCode()
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get form init data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load form data'
            ], 500);
        }
    }

    /**
     * Get facilities with pagination and filtering (LAZY LOADING)
     */
    public function getFacilities(Request $request)
    {
        try {
            $admin = $request->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search', '');
            $rateType = $request->input('rate_type', '');
            $status = $request->input('status', '');

            $query = Facility::with('status')
                ->select(['facility_id', 'facility_name', 'base_fee', 'rate_type', 'capacity', 'status_id', 'location_note']);

            // Apply search filter
            if (!empty($search)) {
                $query->where('facility_name', 'like', "%{$search}%");
            }

            // Apply rate type filter
            if (!empty($rateType)) {
                $query->where('rate_type', $rateType);
            }

            // Apply status filter
            if (!empty($status)) {
                if ($status === 'available') {
                    $query->whereHas('status', function ($q) {
                        $q->where('status_name', 'Available');
                    });
                } elseif ($status === 'unavailable') {
                    $query->whereHas('status', function ($q) {
                        $q->where('status_name', '!=', 'Available');
                    });
                }
            }

            $facilities = $query->orderBy('facility_name')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $facilities->items(),
                'pagination' => [
                    'current_page' => $facilities->currentPage(),
                    'last_page' => $facilities->lastPage(),
                    'per_page' => $facilities->perPage(),
                    'total' => $facilities->total(),
                    'from' => $facilities->firstItem(),
                    'to' => $facilities->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get facilities: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load facilities'
            ], 500);
        }
    }

    /**
     * Get equipment with pagination and filtering (LAZY LOADING)
     */
    public function getEquipment(Request $request)
    {
        try {
            $admin = $request->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search', '');
            $rateType = $request->input('rate_type', '');
            $status = $request->input('status', '');

            $query = Equipment::with('status')
                ->select(['equipment_id', 'equipment_name', 'base_fee', 'rate_type', 'status_id', 'description']);

            // Apply search filter
            if (!empty($search)) {
                $query->where('equipment_name', 'like', "%{$search}%");
            }

            // Apply rate type filter
            if (!empty($rateType)) {
                $query->where('rate_type', $rateType);
            }

            // Apply status filter
            if (!empty($status)) {
                if ($status === 'available') {
                    $query->whereHas('status', function ($q) {
                        $q->where('status_name', 'Available');
                    });
                } elseif ($status === 'unavailable') {
                    $query->whereHas('status', function ($q) {
                        $q->where('status_name', '!=', 'Available');
                    });
                }
            }

            $equipment = $query->orderBy('equipment_name')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $equipment->items(),
                'pagination' => [
                    'current_page' => $equipment->currentPage(),
                    'last_page' => $equipment->lastPage(),
                    'per_page' => $equipment->perPage(),
                    'total' => $equipment->total(),
                    'from' => $equipment->firstItem(),
                    'to' => $equipment->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get equipment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load equipment'
            ], 500);
        }
    }

    /**
     * Generate a unique access code
     */
    private function generateUniqueAccessCode()
    {
        do {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $code = '';
            for ($i = 0; $i < 10; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (RequisitionForm::where('access_code', $code)->exists());

        return $code;
    }
}