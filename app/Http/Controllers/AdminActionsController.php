<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\RequestedFacility;
use App\Models\RequestedEquipment;
use App\Models\EquipmentItem;
use App\Models\RequisitionFee;
use App\Models\FormStatus;
use App\Models\CompletedTransaction;
use App\Models\RequisitionForm;
use App\Models\RequisitionComment;
use App\Services\FeeCalculatorService;
use App\Services\CheckAvailabilityService;
use App\Services\NotificationService;
use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class AdminActionsController extends Controller
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

        // Check for conflicts
        $conflictItems = $this->checkReservationConflicts($validatedData);

        if (!empty($conflictItems)) {
            DB::rollBack();
            return $this->conflictResponse($conflictItems);
        }

        // Create the reservation
        $requisitionForm = $this->createRequisitionForm($validatedData);

        // Add related items
        $this->addFacilities($requisitionForm->request_id, $validatedData['facilities']);
        $this->addEquipment($requisitionForm->request_id, $validatedData['equipment'] ?? []);

        // Add comment record
        $this->addCommentRecord($requisitionForm->request_id);

        DB::commit();

        // Send confirmation email to requester
        try {
            $notificationService = app(NotificationService::class);
            $notificationService->sendConfirmationEmail($requisitionForm);
        } catch (\Exception $e) {
            Log::error('Failed to send confirmation email for request #' . $requisitionForm->request_id . ': ' . $e->getMessage());
        }

        // Send approval request emails to responsible admins (temporarily to your email)
        try {
            $this->notificationService->sendAdminApprovalEmails($requisitionForm);
        } catch (\Exception $e) {
            Log::error('Failed to send admin approval emails for request #' . $requisitionForm->request_id . ': ' . $e->getMessage());
        }

        return $this->successResponse($requisitionForm);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return $this->validationErrorResponse($e);
    } catch (\Exception $e) {
        DB::rollBack();
        return $this->generalErrorResponse($e);
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
            'organization_name' => 'nullable|string|max:100',
            'contact_number' => ['nullable', 'regex:/^\d{1,15}$/', 'max:15'], // ADDED

            // Form details
            'purpose_id' => 'required|exists:requisition_purposes,purpose_id',
            'num_participants' => 'required|integer|min:1',
            'num_tables' => 'required|integer|min:0',        // ADDED
            'num_chairs' => 'required|integer|min:0',        // ADDED
            'num_microphones' => 'required|integer|min:0',   // ADDED
            'access_code' => 'required|string|max:10|unique:requisition_forms,access_code',
            'additional_requests' => 'nullable|string|max:250',
            'endorser' => 'nullable|string|max:50',          // ADDED
            'date_endorsed' => 'nullable|date_format:Y-m-d', // ADDED

            // Schedule
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'all_day' => 'required|boolean',
            'calendar_title' => 'nullable|string|max:50',
            'calendar_description' => 'nullable|string|max:100',

            // Requested items
            'facilities' => 'required|array|min:1',
            'facilities.*.facility_id' => 'required|exists:facilities,facility_id',
            'equipment' => 'array',
            'equipment.*.equipment_id' => 'required|exists:equipment,equipment_id',
            'equipment.*.quantity' => 'required|integer|min:1',

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
     * Check for scheduling conflicts
     */
    private function checkReservationConflicts(array $data): array
    {
        $conflictItems = [];

        // Check facility conflicts using service
        foreach ($data['facilities'] as $facility) {
            $facilityConflicts = $this->availabilityChecker->checkFacilityAvailability(
                $facility['facility_id'],
                $data['start_date'],
                $data['end_date'],
                $data['start_time'] ?? '00:00:00',
                $data['end_time'] ?? '23:59:59',
                $data['all_day']
            );

            if (!empty($facilityConflicts)) {
                $conflictItems[] = [
                    'type' => 'facility',
                    'id' => $facility['facility_id'],
                    'name' => Facility::find($facility['facility_id'])->facility_name ?? 'Unknown',
                    'conflicts' => $facilityConflicts
                ];
            }
        }

        // Check equipment conflicts using service
        if (!empty($data['equipment'])) {
            foreach ($data['equipment'] as $equipment) {
                $availableCount = $this->availabilityChecker->checkEquipmentAvailability(
                    $equipment['equipment_id'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['all_day']
                );

                if ($availableCount < $equipment['quantity']) {
                    $equipmentName = EquipmentItem::find($equipment['equipment_id'])->equipment_name ?? 'Unknown';
                    $conflictItems[] = [
                        'type' => 'equipment',
                        'id' => $equipment['equipment_id'],
                        'name' => $equipmentName,
                        'message' => "Only {$availableCount} available, requested {$equipment['quantity']}"
                    ];
                }
            }
        }

        return $conflictItems;
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
            'organization_name' => $data['organization_name'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,

            // Form details
            'purpose_id' => $data['purpose_id'],
            'num_participants' => $data['num_participants'],
            'num_tables' => $data['num_tables'] ?? 0,        // ADDED
            'num_chairs' => $data['num_chairs'] ?? 0,        // ADDED
            'num_microphones' => $data['num_microphones'] ?? 0, // ADDED
            'access_code' => $data['access_code'],
            'additional_requests' => $data['additional_requests'] ?? null,
            'endorser' => $data['endorser'] ?? null,         // ADDED
            'date_endorsed' => $data['date_endorsed'] ?? null, // ADDED

            // Schedule
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $this->formatStartTime($data),
            'end_time' => $this->formatEndTime($data),
            'all_day' => $data['all_day'],
            'calendar_title' => $data['calendar_title'] ?? 'Admin Reservation',
            'calendar_description' => $data['calendar_description'] ?? null,

            // Status
            'status_id' => $data['status_id'],
            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => auth()->id(),
            'is_admin_created' => true,  // ADDED (already present)
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
     * Return conflict response
     */
    private function conflictResponse(array $conflictItems): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Scheduling conflicts detected',
            'conflict_items' => $conflictItems
        ], 409);
    }

    /**
     * Return success response
     */
    private function successResponse(RequisitionForm $form): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => 'Reservation created successfully',
            'request_id' => $form->request_id,
            'access_code' => $form->access_code,
            'all_day' => $form->all_day,
        ], 201);
    }

    /**
     * Return validation error response
     */
    private function validationErrorResponse(\Illuminate\Validation\ValidationException $e): \Illuminate\Http\JsonResponse
    {
        Log::error('Validation failed for admin reservation', [
            'errors' => $e->errors(),
        ]);

        return response()->json([
            'error' => 'Validation failed',
            'details' => $e->errors(),
        ], 422);
    }

    /**
     * Return general error response
     */
    private function generalErrorResponse(\Exception $e): \Illuminate\Http\JsonResponse
    {
        Log::error('Failed to create admin reservation', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'error' => 'Failed to create reservation',
            'details' => $e->getMessage(),
        ], 500);
    }


    public function addFee(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'label' => 'required|string|max:50',
                'fee_amount' => 'required|numeric|min:0.01',
                'account_num' => 'nullable|string|max:10', // Add this line
            ]);

            $admin = auth()->user();

            $fee = RequisitionFee::create([
                'request_id' => $requestId,
                'added_by' => $admin->admin_id,
                'label' => $validatedData['label'],
                'fee_amount' => $validatedData['fee_amount'],
                'discount_amount' => 0,
                'account_num' => $validatedData['account_num'] ?? null, // Add this line
            ]);

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Fee added successfully',
                'fee' => $fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add fee',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function removeFee($requestId, $feeId)
    {
        try {
            $fee = RequisitionFee::where('request_id', $requestId)
                ->where('fee_id', $feeId)
                ->firstOrFail();

            $fee->delete();

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Fee removed successfully',
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove fee',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function addDiscount(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'label' => 'required|string|max:50',
                'discount_amount' => 'required|numeric|min:0.01',
                'discount_type' => 'required|in:Fixed,Percentage',
                'account_num' => 'nullable|string|max:10', // Add this line
            ]);

            $admin = auth()->user();

            $discount = RequisitionFee::create([
                'request_id' => $requestId,
                'added_by' => $admin->admin_id,
                'label' => $validatedData['label'],
                'fee_amount' => 0,
                'discount_amount' => $validatedData['discount_amount'],
                'discount_type' => $validatedData['discount_type'],
                'account_num' => $validatedData['account_num'] ?? null, // Add this line
            ]);

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Discount added successfully',
                'discount' => $discount,
                'discount_type' => $validatedData['discount_type'],
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add discount',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function addLatePenalty(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'penalty_amount' => 'required|numeric|min:0'
            ]);

            $form = RequisitionForm::findOrFail($requestId);

            // Check if the requisition is marked as late by the system
            if (!$form->is_late) {
                return response()->json([
                    'error' => 'Cannot add late penalty',
                    'details' => 'This requisition is not marked as late by the system'
                ], 422);
            }

            $form->late_penalty_fee = $validatedData['penalty_amount'];
            $form->save();

            // Recalculate approved fee
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Late penalty added successfully',
                'penalty_amount' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add late penalty',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function removeLatePenalty(Request $request, $requestId)
    {
        try {
            $form = RequisitionForm::findOrFail($requestId);

            // Only reset the penalty fee, leave is_late status as determined by the system
            $form->late_penalty_fee = 0;
            $form->save();

            // Recalculate approved fee without penalty
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Late penalty removed successfully',
                'penalty_amount' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee,
                'is_late' => $form->is_late // Include current late status in response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove late penalty',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function waiveItems(Request $request, $requestId)
    {
        try {
            \Log::debug('Waive items request received', [
                'request_id' => $requestId,
                'waive_all' => $request->waive_all,
                'waived_facilities' => $request->waived_facilities,
                'waived_equipment' => $request->waived_equipment
            ]);

            // First, let's log all equipment for this request to see what should be valid
            $validEquipmentIds = RequestedEquipment::where('request_id', $requestId)
                ->pluck('requested_equipment_id')
                ->toArray();

            $validFacilityIds = RequestedFacility::where('request_id', $requestId)
                ->pluck('requested_facility_id')
                ->toArray();

            \Log::debug('Valid IDs for this request', [
                'valid_equipment_ids' => $validEquipmentIds,
                'valid_facility_ids' => $validFacilityIds,
                'requested_equipment' => $request->waived_equipment,
                'requested_facilities' => $request->waived_facilities
            ]);

            // Custom validation to check if items belong to this request
            $validator = Validator::make($request->all(), [
                'waive_all' => 'sometimes|boolean',
                'waived_facilities' => 'sometimes|array',
                'waived_facilities.*' => [
                    function ($attribute, $value, $fail) use ($requestId, $validFacilityIds) {
                        if (!in_array($value, $validFacilityIds)) {
                            $fail("The selected facility (ID: $value) is invalid for this request. Valid facilities: " . implode(', ', $validFacilityIds));
                        }
                    }
                ],
                'waived_equipment' => 'sometimes|array',
                'waived_equipment.*' => [
                    function ($attribute, $value, $fail) use ($requestId, $validEquipmentIds) {
                        if (!in_array($value, $validEquipmentIds)) {
                            $fail("The selected equipment (ID: $value) is invalid for this request. Valid equipment: " . implode(', ', $validEquipmentIds));
                        }
                    }
                ]
            ]);

            if ($validator->fails()) {
                \Log::error('Waive items validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all(),
                    'valid_equipment_ids' => $validEquipmentIds,
                    'valid_facility_ids' => $validFacilityIds
                ]);

                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors(),
                    'debug' => [
                        'valid_equipment_ids' => $validEquipmentIds,
                        'valid_facility_ids' => $validFacilityIds
                    ]
                ], 422);
            }
            $validatedData = $validator->validated();

            DB::beginTransaction();

            if (isset($validatedData['waive_all']) && $validatedData['waive_all']) {
                // Waive all facilities and equipment
                RequestedFacility::where('request_id', $requestId)
                    ->update(['is_waived' => true]);

                RequestedEquipment::where('request_id', $requestId)
                    ->update(['is_waived' => true]);
            } else {
                // Only update waivers for specific items
                // Update facilities based on the provided list
                if (isset($validatedData['waived_facilities'])) {
                    // Waive the specified facilities
                    RequestedFacility::where('request_id', $requestId)
                        ->whereIn('requested_facility_id', $validatedData['waived_facilities'])
                        ->update(['is_waived' => true]);

                    // Unwaive facilities not in the list
                    RequestedFacility::where('request_id', $requestId)
                        ->whereNotIn('requested_facility_id', $validatedData['waived_facilities'])
                        ->update(['is_waived' => false]);
                } else {
                    // If no facilities specified, unwaive all facilities
                    RequestedFacility::where('request_id', $requestId)
                        ->update(['is_waived' => false]);
                }

                // Update equipment based on the provided list
                if (isset($validatedData['waived_equipment'])) {
                    // Waive the specified equipment
                    RequestedEquipment::where('request_id', $requestId)
                        ->whereIn('requested_equipment_id', $validatedData['waived_equipment'])
                        ->update(['is_waived' => true]);

                    // Unwaive equipment not in the list
                    RequestedEquipment::where('request_id', $requestId)
                        ->whereNotIn('requested_equipment_id', $validatedData['waived_equipment'])
                        ->update(['is_waived' => false]);
                } else {
                    // If no equipment specified, unwaive all equipment
                    RequestedEquipment::where('request_id', $requestId)
                        ->update(['is_waived' => false]);
                }
            }

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            // Use getFeeSummary() to get both approved and base fees
            $feeSummary = $this->feeCalculator->getFeeSummary($form);
            $form->approved_fee = $feeSummary['approved_fee'];
            $form->save();

            DB::commit();

            return response()->json([
                'message' => 'Items waived successfully',
                'updated_approved_fee' => $feeSummary['approved_fee'],
                'tentative_fee' => $feeSummary['base_fee'] + ($form->is_late ? $form->late_penalty_fee : 0) // Calculate tentative fee using base_fee from summary
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to waive items', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to waive items',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new comment to a requisition form
     */
    public function addComment(Request $request, $requestId)
    {
        try {
            $admin = $request->user();

            $validated = $request->validate([
                'comment' => 'required|string|max:1000',
            ]);

            $comment = RequisitionComment::create([
                'request_id' => $requestId,
                'admin_id' => $admin->admin_id,
                'comment' => $validated['comment'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Load the admin relationship for the response
            $comment->load('admin');

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'comment' => $comment
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error adding comment', [
                'request_id' => $requestId,
                'admin_id' => $request->user()->admin_id ?? 'unknown',
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error adding comment to requisition', [
                'request_id' => $requestId,
                'admin_id' => $request->user()->admin_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment'
            ], 500);
        }
    }

    /**
     * Get all comments for a requisition form
     */
    public function getComments($requestId)
    {
        try {
            Log::info('Fetching comments', ['request_id' => $requestId]);

            $comments = RequisitionComment::where('request_id', $requestId)
                ->with('admin')
                ->orderBy('created_at', 'asc') // Change from 'desc' to 'asc'
                ->get();

            Log::debug('Comments fetched', [
                'request_id' => $requestId,
                'comment_count' => $comments->count()
            ]);

            return response()->json([
                'success' => true,
                'comments' => $comments
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching comments', [
                'request_id' => $requestId,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments'
            ], 500);
        }
    }


    public function finalizeForm(Request $request, $requestId)
    {
        try {
            \Log::debug('Finalize form attempt', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'input_data' => $request->all()
            ]);

            // Validate
            $validatedData = $request->validate([
                'calendar_title' => 'sometimes|string|max:50|nullable',
                'calendar_description' => 'sometimes|string|max:100|nullable',
            ]);

            $adminId = auth()->id();
            if (!$adminId) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            // Get form with relationships
            $form = RequisitionForm::with([
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requisitionFees'
            ])->findOrFail($requestId);

            // Update form
            $this->updateFinalizedForm($form, $validatedData, $adminId);

            // Send email notification
            $this->notificationService->sendApprovalEmail($form);

            return response()->json([
                'message' => 'Form finalized successfully',
                'new_status' => 'Awaiting Payment',
                'approved_fee' => $form->approved_fee
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Finalize form validation failed', ['request_id' => $requestId, 'errors' => $e->errors()]);
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to finalize form', ['request_id' => $requestId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to finalize form', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Update form with finalized status
     */
    private function updateFinalizedForm($form, $data, $adminId)
    {
        $form->is_finalized = true;
        $form->finalized_at = now();
        $form->finalized_by = $adminId;
        $form->status_id = FormStatus::where('status_name', 'Awaiting Payment')->first()->status_id;

        if (!empty($data['calendar_title'])) {
            $form->calendar_title = $data['calendar_title'];
        }
        if (!empty($data['calendar_description'])) {
            $form->calendar_description = $data['calendar_description'];
        }

        $form->approved_fee = $this->feeCalculator->calculateApprovedFee($form);
        $form->save();

        return $form;
    }

    public function cancelForm(Request $request, $requestId)
    {
        try {
            $adminId = auth()->id();

            if (!$adminId) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            DB::beginTransaction();

            $form = RequisitionForm::findOrFail($requestId);

            // Update the requisition form
            $form->status_id = FormStatus::where('status_name', 'Cancelled')->first()->status_id;
            $form->is_closed = true;
            $form->closed_by = $adminId;
            $form->closed_at = now();
            $form->updated_at = now();
            $form->save();

            // Create completed transaction record
            CompletedTransaction::create([
                'request_id' => $requestId,
                'official_receipt_no' => null,
                'official_receipt_url' => null,
                'official_receipt_public_id' => null
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Form cancelled successfully',
                'request_id' => $requestId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to cancel form as admin', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to cancel form',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function closeForm($requestId)
    {
        try {
            $admin = auth()->user();

            $form = RequisitionForm::findOrFail($requestId);

            $form->is_closed = true;
            $form->closed_at = now();
            $form->closed_by = $admin->admin_id;
            $form->status_id = FormStatus::where('status_name', 'Completed')->first()->status_id;
            $form->save();

            // Create completed transaction record
            CompletedTransaction::create([
                'request_id' => $requestId,
                'official_receipt_no' => null,
                'official_receipt_url' => null,
                'official_receipt_public_id' => null
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Form closed successfully',
                'form' => $form
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to close form',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function markReturned(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'is_late' => 'required|boolean',
                'late_penalty_fee' => 'required_if:is_late,true|numeric|min:0'
            ]);

            $form = RequisitionForm::findOrFail($requestId);

            $form->returned_at = now();
            $form->is_late = $validatedData['is_late'];

            if ($validatedData['is_late']) {
                $form->late_penalty_fee = $validatedData['late_penalty_fee'];
            }

            // Update status based on return time
            if ($validatedData['is_late']) {
                $form->status_id = FormStatus::where('status_name', 'Late Return')->first()->status_id;
            } else {
                $form->status_id = FormStatus::where('status_name', 'Returned')->first()->status_id;
            }

            // Recalculate approved fee
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Equipment marked as returned',
                'is_late' => $form->is_late,
                'late_penalty_fee' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to mark equipment as returned',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $requestId)
    {
        try {
            \Log::debug('Update status request received', [
                'request_id' => $requestId,
                'new_status' => $request->status_name,
                'admin_id' => auth()->id()
            ]);

            $validatedData = $request->validate([
                'status_name' => 'required|string|in:Scheduled,Ongoing,Late,Returned,Late Return,Completed',
                'late_penalty_fee' => 'sometimes|nullable|numeric|min:0'
            ]);

            $adminId = auth()->id();
            if (!$adminId) {
                \Log::warning('Admin not authenticated during status update');
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            $form = RequisitionForm::with('formStatus')->findOrFail($requestId);

            // VALIDATION: Can only mark as Late if current status is Ongoing
            if ($validatedData['status_name'] === 'Late') {
                $currentStatus = $form->formStatus->status_name;
                if ($currentStatus !== 'Ongoing') {
                    return response()->json([
                        'error' => 'Cannot mark as Late',
                        'details' => 'Can only mark forms as Late when they are in Ongoing status. Current status: ' . $currentStatus
                    ], 422);
                }
            }

            // Get the status ID for the selected status name
            $status = FormStatus::where('status_name', $validatedData['status_name'])->first();
            if (!$status) {
                \Log::error('Status not found', ['status_name' => $validatedData['status_name']]);
                return response()->json(['error' => 'Invalid status'], 422);
            }

            // Handle Late status specifically
            if ($validatedData['status_name'] === 'Late') {
                $form->is_late = true;

                // Set late penalty fee if provided
                if (isset($validatedData['late_penalty_fee']) && $validatedData['late_penalty_fee'] > 0) {
                    $form->late_penalty_fee = $validatedData['late_penalty_fee'];
                }
            }
            // Handle unmarking late (when changing from Late to another status)
            elseif ($form->formStatus->status_name === 'Late' && $validatedData['status_name'] !== 'Late') {
                $form->is_late = false;
                $form->late_penalty_fee = 0; // Reset penalty fee
            }

            // Update the form status
            $form->status_id = $status->status_id;

            // Additional logic based on status
            if (in_array($validatedData['status_name'], ['Returned', 'Late Return', 'Completed', 'Rejected', 'Cancelled'])) {
                $form->is_closed = true;
                $form->closed_at = now();
                $form->closed_by = $adminId;

                // Create completed transaction record for finalized statuses
                if (!CompletedTransaction::where('request_id', $requestId)->exists()) {
                    CompletedTransaction::create([
                        'request_id' => $requestId,
                        'official_receipt_no' => $form->official_receipt_no,
                        'official_receipt_url' => $form->official_receipt_url,
                        'official_receipt_public_id' => $form->official_receipt_public_id
                    ]);
                }
            }

            $form->save();

            // Send email notification if status changed to Late
            if ($validatedData['status_name'] === 'Late') {
                $this->notificationService->sendLatePenaltyEmail($form);
            }

            // Recalculate approved fee after status change
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            \Log::info('Status updated successfully', [
                'request_id' => $requestId,
                'old_status' => $form->getOriginal('status_id'),
                'new_status' => $form->status_id,
                'is_late' => $form->is_late,
                'late_penalty_fee' => $form->late_penalty_fee,
                'admin_id' => $adminId
            ]);

            return response()->json([
                'message' => 'Status updated successfully',
                'new_status' => $validatedData['status_name'],
                'status_id' => $status->status_id,
                'color_code' => $status->color_code,
                'is_late' => $form->is_late,
                'late_penalty_fee' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Status update validation failed', [
                'request_id' => $requestId,
                'errors' => $e->errors(),
                'input_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update status', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update status',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsScheduled(Request $request, $requestId)
    {
        try {
            \Log::debug('Mark as scheduled request received', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'official_receipt_num' => $request->official_receipt_num
            ]);

            $validatedData = $request->validate([
                'official_receipt_num' => 'required|string|max:50|unique:requisition_forms,official_receipt_num',
                'calendar_title' => 'sometimes|string|max:50|nullable',
                'calendar_description' => 'sometimes|string|max:100|nullable',
            ]);

            $adminId = auth()->id();
            if (!$adminId) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            $form = RequisitionForm::with([
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requisitionFees',
                'purpose',
                'formStatus'
            ])->findOrFail($requestId);

            // Update form with official receipt number and status
            $scheduledStatus = FormStatus::where('status_name', 'Scheduled')->first();
            if (!$scheduledStatus) {
                throw new \Exception('Scheduled status not found');
            }

            $form->official_receipt_num = $validatedData['official_receipt_num'];
            $form->status_id = $scheduledStatus->status_id;

            if (!empty($validatedData['calendar_title'])) {
                $form->calendar_title = $validatedData['calendar_title'];
            }

            if (!empty($validatedData['calendar_description'])) {
                $form->calendar_description = $validatedData['calendar_description'];
            }

            $form->save();

            // Send confirmation email
            $this->notificationService->sendScheduledConfirmationEmail($form);

            \Log::info('Form marked as scheduled successfully', [
                'request_id' => $requestId,
                'official_receipt_num' => $form->official_receipt_num,
                'admin_id' => $adminId
            ]);

            return response()->json([
                'message' => 'Form marked as scheduled successfully',
                'official_receipt_num' => $form->official_receipt_num,
                'new_status' => 'Scheduled'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Mark as scheduled validation failed', [
                'request_id' => $requestId,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to mark form as scheduled', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to mark form as scheduled',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function generateOfficialReceipt($requestId)
    {
        try {
            \Log::debug('=== GENERATE OFFICIAL RECEIPT CALLED ===', [
                'request_id' => $requestId,
            ]);

            $receiptData = $this->receiptService->generateReceiptData($requestId);

            return view('public.official-receipt', compact('receiptData'));

        } catch (\Exception $e) {
            \Log::error('Failed to generate official receipt', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
            abort(404, 'Receipt not found');
        }
    }

}
