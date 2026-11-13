<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\ExtraService;
use App\Models\Equipment;
use App\Models\RequisitionPurpose;
use App\Models\FormStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\FeeCalculatorService;
use App\Services\CheckAvailabilityService;
use App\Services\NotificationService;
use App\Services\AccessCodeService;
use App\Services\RequisitionCreationService;
use App\Services\ReceiptService;
use App\Exceptions\BookingConflictException;

class CreateReservationController extends Controller
{

    public function __construct(
        protected FeeCalculatorService $feeCalculator,
        protected CheckAvailabilityService $availabilityChecker,
        protected NotificationService $notificationService,
        protected ReceiptService $receiptService,
        protected RequisitionCreationService $creationService,
        protected AccessCodeService $accessCodeService
    ) {
    }

    /**
     * Create a new admin reservation
     */
    public function createReservation(Request $request)
    {
        try {
            $data = $this->validateReservationRequest($request);
            $adminId = auth()->id();

            if (!$adminId) {
                return response()->json([
                    'error' => 'Admin not authenticated',
                ], 401);
            }

            $requisitionForm = $this->creationService->createFromPayload($data, $adminId);

            return response()->json([
                'message' => 'Reservation created successfully',
                'request_id' => $requisitionForm->request_id,
                'access_code' => $requisitionForm->access_code,
                'all_day' => $requisitionForm->all_day,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
        } catch (BookingConflictException $e) {
            return response()->json([
                'error' => 'Scheduling conflicts detected',
                'conflict_items' => $e->getConflictItems(),
                'conflict_html' => $this->availabilityChecker->formatConflictsToHtml($e->getConflictItems()),
            ], 409);
        } catch (\Exception $e) {
            Log::error('Failed to create reservation', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
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
        $rules = [
            'user_type' => 'required|in:Internal,External',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|max:100',
            'school_id' => 'nullable|string|max:20',
            'organization_name' => 'nullable|string|max:100',
            'contact_number' => ['nullable', 'regex:/^\d{1,15}$/', 'max:15'],
            'purpose_id' => 'required|exists:requisition_purposes,purpose_id',
            'num_participants' => 'required|integer|min:1',
            'num_tables' => 'required|integer|min:0',
            'num_chairs' => 'required|integer|min:0',
            'num_microphones' => 'required|integer|min:0',
            'additional_requests' => 'nullable|string|max:250',
            'event_title' => 'nullable|string|max:100',
            'event_details' => 'nullable|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'all_day' => 'required|boolean',
            'status_id' => 'required|exists:form_statuses,status_id',
            'facilities' => 'required|array|min:1',
            'facilities.*.facility_id' => 'required|exists:facilities,facility_id',
            'equipment' => 'array',
            'equipment.*.equipment_id' => 'required|exists:equipment,equipment_id',
            'equipment.*.quantity' => 'required|integer|min:1',
            'services' => 'array',
            'services.*.service_id' => 'required|exists:extra_services,service_id',
        ];

        if (!$request->all_day) {
            $rules['start_time'] = 'required|date_format:H:i';
            $rules['end_time'] = 'required|date_format:H:i|after:start_time';
        } else {
            $rules['start_time'] = 'nullable';
            $rules['end_time'] = 'nullable';
        }

        $v = $request->validate($rules);
        $allDay = (bool) $v['all_day'];

        // Build common `items[]` shape
        $items = [];
        foreach ($v['facilities'] as $f) {
            $items[] = ['type' => 'facility', 'facility_id' => $f['facility_id']];
        }
        foreach ($v['equipment'] ?? [] as $e) {
            $items[] = [
                'type' => 'equipment',
                'equipment_id' => $e['equipment_id'],
                'quantity' => $e['quantity'],
            ];
        }
        foreach ($v['services'] ?? [] as $s) {
            $items[] = ['type' => 'service', 'service_id' => $s['service_id']];
        }

        return [
            'user_type' => $v['user_type'],
            'first_name' => $v['first_name'],
            'last_name' => $v['last_name'],
            'email' => $v['email'],
            'school_id' => $v['school_id'] ?? null,
            'organization_name' => $v['organization_name'] ?? null,
            'contact_number' => $v['contact_number'] ?? null,
            'num_participants' => $v['num_participants'],
            'purpose_id' => $v['purpose_id'],
            'additional_requests' => $v['additional_requests'] ?? null,
            'start_date' => $v['start_date'],
            'end_date' => $v['end_date'],
            'start_time' => $allDay ? '00:00:00' : $v['start_time'],
            'end_time' => $allDay ? '23:59:59' : $v['end_time'],
            'all_day' => $allDay,
            'cleanup_hours' => $v['cleanup_hours'] ?? 4,
            'event_title' => $v['event_title'] ?? 'Admin Reservation',
            'event_details' => $v['event_details'] ?? null,
            'num_tables' => $v['num_tables'] ?? 0,
            'num_chairs' => $v['num_chairs'] ?? 0,
            'num_microphones' => $v['num_microphones'] ?? 0,
            'event_documents_url' => $v['event_documents_url'] ?? null,
            'status_id' => $v['status_id'],
            'items' => $items,
        ];
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
                'access_code' => $this->accessCodeService->generateUniqueAccessCode()
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

}