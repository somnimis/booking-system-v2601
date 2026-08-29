<?php

namespace App\Http\Controllers;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RequisitionForm;
use App\Models\RequestedEquipment;
use App\Models\RequestedFacility;
use App\Models\RequestedService;
use App\Models\Facility;
use App\Models\Equipment;
use App\Models\FormStatus;
use App\Services\FeeCalculatorService;
use App\Services\NotificationService;
use App\Services\CheckAvailabilityService;
use App\Services\ApprovalChainService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\RequisitionSubmitRequest;
use App\Services\AccessCodeService;
use App\Services\RequisitionFormatterService;
use App\Services\ScheduleFormatterService;

/*
|--------------------------------------------------------------------------
| RequisitionFormController
|--------------------------------------------------------------------------
|
| Handles the public-facing requisition booking workflow for users.
| Uses session-based cart pattern for multi-step form completion.
|
| Workflow:
| 1. saveRequestInfo()  - Store user/schedule details in session
| 2. addToForm()        - Add facilities/equipment to booking cart
| 3. calculateFeeBreakdown() - Preview fees before submission
| 4. checkAvailability() - Validate time slots don't conflict
| 5. submitForm()       - Finalize and create requisition record
|
| Key Features:
| - Session-based cart for multi-step booking (max 10 items)
| - Real-time availability checking via CheckAvailabilityService
| - Cloudinary integration for document uploads (temp storage)
| - Automatic conflict detection before final submission
| - Email notifications on successful submission
| - Approval chain creation for admin workflow
|
| Status: Upon submission, requisition is set to 'Pending Approval'
| and requires admin approval before scheduling.
|
| Note: Only equipment items with condition_id in [1,2,3] 
| (New, Good, Fair) are available for booking.
*/

class RequisitionFormController extends Controller
{

    protected FeeCalculatorService $feeCalculator;
    protected NotificationService $notificationService;
    protected CheckAvailabilityService $availabilityChecker;
    protected ApprovalChainService $approvalChainService;
    protected AccessCodeService $accessCodeService;
    protected RequisitionFormatterService $formatter;
    protected ScheduleFormatterService $scheduleFormatter;

    public function __construct(
        ApprovalChainService $approvalChainService,
        FeeCalculatorService $feeCalculator,
        NotificationService $notificationService,
        CheckAvailabilityService $availabilityChecker,
        AccessCodeService $accessCodeService,
        RequisitionFormatterService $formatter,
        ScheduleFormatterService $scheduleFormatter
    ) {
        $this->feeCalculator = $feeCalculator;
        $this->availabilityChecker = $availabilityChecker;
        $this->notificationService = $notificationService;
        $this->approvalChainService = $approvalChainService;
        $this->accessCodeService = $accessCodeService;
        $this->formatter = $formatter;
        $this->scheduleFormatter = $scheduleFormatter;
    }

    // ----- Save form details in session ----- //
    public function saveRequestInfo(Request $request)
    {
        // Build rules array dynamically
        $rules = [
            // User information
            'user_type' => 'required|in:Internal,External',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|max:100',
            'contact_number' => 'nullable|string|max:15',
            'organization_name' => 'nullable|string|max:100',
            'school_id' => 'nullable|string|max:20',
            // Requisition details
            'additional_requests' => 'nullable|string|max:250',
            'num_participants' => 'required|integer|min:1',
            'purpose_id' => 'required|exists:requisition_purposes,purpose_id',
            // Booking schedule
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'all_day' => 'required|boolean'
        ];

        // Conditionally add time rules based on all_day flag
        if (!$request->all_day) {
            $rules['start_time'] = 'required|date_format:H:i';
            $rules['end_time'] = 'required|date_format:H:i|after:start_time';
        } else {
            $rules['start_time'] = 'nullable';
            $rules['end_time'] = 'nullable';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', ['errors' => $validator->errors()], 422);
        }

        $requestInfo = [
            'user_type' => $request->user_type,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'school_id' => $request->school_id,
            'organization_name' => $request->organization_name,
            'contact_number' => $request->contact_number,
            'num_participants' => $request->num_participants,
            'purpose_id' => $request->purpose_id,
            'additional_requests' => $request->additional_requests,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->all_day ? '00:00:00' : $request->start_time,
            'end_time' => $request->all_day ? '23:59:59' : $request->end_time,
            'all_day' => $request->all_day,
        ];

        // Sanitize inputs
        $requestInfo['email'] = filter_var($requestInfo['email'], FILTER_SANITIZE_EMAIL);
        $requestInfo['first_name'] = htmlspecialchars($requestInfo['first_name'], ENT_QUOTES);
        $requestInfo['last_name'] = htmlspecialchars($requestInfo['last_name'], ENT_QUOTES);

        session(['request_info' => $requestInfo]);

        return $this->jsonResponse(true, 'Form details saved successfully.', ['request_info' => $requestInfo]);
    }

    // ----- Add items to session ----- //

    public function batchAddToForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1|max:10',
            'items.*.type' => 'required|in:facility,equipment',
            'items.*.facility_id' => 'required_if:items.*.type,facility|exists:facilities,facility_id',
            'items.*.equipment_id' => 'required_if:items.*.type,equipment|exists:equipment,equipment_id',
            'items.*.quantity' => 'required_if:items.*.type,equipment|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $selectedItems = Session::get('selected_items', []);
            $itemsToAdd = $request->items;
            $addedItems = [];
            $skippedItems = [];

            foreach ($itemsToAdd as $item) {
                $type = $item['type'];
                $idField = $type . '_id';
                $id = $item[$idField];
                $quantity = $item['quantity'] ?? 1;

                // Check for duplicate
                $exists = collect($selectedItems)->contains(function ($existing) use ($id, $type, $idField) {
                    return isset($existing[$idField]) && $existing[$idField] == $id && $existing['type'] === $type;
                });

                if ($exists) {
                    $skippedItems[] = $id;
                    continue;
                }

                // Check item limit
                if (count($selectedItems) >= 10) {
                    $skippedItems[] = $id;
                    continue;
                }

                // Get item details
                if ($type === 'facility') {
                    $itemModel = Facility::with(['images', 'category', 'status'])->find($id);
                    if (!$itemModel) {
                        $skippedItems[] = $id;
                        continue;
                    }
                    $newItem = [
                        'type' => 'facility',
                        'facility_id' => $id,
                        'name' => $itemModel->facility_name,
                        'description' => $itemModel->description,
                        'base_fee' => $itemModel->base_fee,
                        'total_fee' => $itemModel->base_fee,
                        'rate_type' => $itemModel->rate_type,
                        'images' => $itemModel->images->toArray(),
                        'added_at' => now()->toDateTimeString()
                    ];
                } else {
                    $itemModel = Equipment::with(['images', 'category', 'status'])->find($id);
                    if (!$itemModel) {
                        $skippedItems[] = $id;
                        continue;
                    }
                    $newItem = [
                        'type' => 'equipment',
                        'equipment_id' => $id,
                        'quantity' => $quantity,
                        'name' => $itemModel->equipment_name,
                        'description' => $itemModel->description,
                        'base_fee' => $itemModel->base_fee,
                        'total_fee' => $itemModel->base_fee * $quantity,
                        'rate_type' => $itemModel->rate_type,
                        'images' => $itemModel->images->toArray(),
                        'added_at' => now()->toDateTimeString()
                    ];
                }

                $selectedItems[] = $newItem;
                $addedItems[] = $id;
            }

            Session::put('selected_items', $selectedItems);

            $message = count($addedItems) . ' item(s) added successfully.';
            if (!empty($skippedItems)) {
                $message .= ' ' . count($skippedItems) . ' item(s) skipped (already in cart or limit reached).';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'selected_items' => $selectedItems,
                    'cart_count' => count($selectedItems),
                    'added_count' => count($addedItems),
                    'skipped_count' => count($skippedItems)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Batch add error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding items.'
            ], 500);
        }
    }

    public function addToForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'facility_id' => 'required_without:equipment_id|exists:facilities,facility_id',
            'equipment_id' => 'required_without:facility_id|exists:equipment,equipment_id',
            'type' => 'required|in:facility,equipment',
            'quantity' => 'required_if:type,equipment|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $selectedItems = Session::get('selected_items', []);
            $type = $request->type;
            $idField = $type . '_id';
            $id = $request->input($idField);
            $quantity = $request->quantity ?? 1;

            // Check for duplicate item
            $existingIndex = collect($selectedItems)->search(function ($item) use ($id, $type, $idField) {
                return isset($item[$idField]) && $item[$idField] == $id && $item['type'] === $type;
            });

            if ($existingIndex !== false) {
                if ($type === 'equipment') {
                    $selectedItems[$existingIndex]['quantity'] = $quantity;
                    // Recalculate total fee for equipment
                    $selectedItems[$existingIndex]['total_fee'] = $selectedItems[$existingIndex]['base_fee'] * $quantity;
                    Session::put('selected_items', $selectedItems);
                    return response()->json([
                        'success' => true,
                        'message' => 'Equipment quantity updated.',
                        'data' => [
                            'selected_items' => $selectedItems,
                            'cart_count' => count($selectedItems)
                        ]
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'This item is already in your requisition.'
                ], 422);
            }

            if (count($selectedItems) >= 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum item limit (10) reached.'
                ], 422);
            }

            // Get item details
            if ($type === 'facility') {
                $item = Facility::with(['images', 'category', 'status'])->find($id);
            } else {
                $item = Equipment::with(['images', 'category', 'status'])->find($id);
            }

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found.'
                ], 404);
            }

            $newItem = [
                'type' => $type,
                $idField => $id,
                'quantity' => $quantity,
                'name' => $type === 'facility' ? $item->facility_name : $item->equipment_name,
                'description' => $item->description,
                'base_fee' => $item->base_fee,
                'total_fee' => $type === 'equipment' ? $item->base_fee * $quantity : $item->base_fee,
                'rate_type' => $item->rate_type,
                'images' => $item->images->toArray(),
                'added_at' => now()->toDateTimeString()
            ];

            $selectedItems[] = $newItem;
            Session::put('selected_items', $selectedItems);

            return response()->json([
                'success' => true,
                'message' => ucfirst($type) . ' added successfully.',
                'data' => [
                    'selected_items' => $selectedItems,
                    'cart_count' => count($selectedItems)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Add to form error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding item to form.'
            ], 500);
        }
    }

    /**
     * Calculate fee breakdown for items in session cart
     */
    public function calculateFeeBreakdown(Request $request)
    {
        try {
            $selectedItems = Session::get('selected_items', []);
            $requestInfo = Session::get('request_info', []);

            if (empty($selectedItems)) {
                return $this->jsonResponse(false, 'No items in cart.', [], 400);
            }

            if (empty($requestInfo)) {
                return $this->jsonResponse(false, 'Schedule information not found.', [], 400);
            }

            // Create a temporary form object with the session data for fee calculation
            $tempForm = $this->createTempFormObject($selectedItems, $requestInfo);

            // Get fee summary from calculator
            $feeSummary = $this->feeCalculator->getFeeSummary($tempForm);

            // Transform the breakdown to match expected format
            $breakdown = $this->transformBreakdownForResponse($feeSummary['breakdown']);

            // Store fee summary in session for later use
            Session::put('fee_summary', [
                'breakdown' => $breakdown,
                'total_fee' => $feeSummary['approved_fee']
            ]);

            return $this->jsonResponse(true, 'Fee breakdown calculated.', [
                'breakdown' => $breakdown,
                'total_fee' => $feeSummary['approved_fee'],
                'duration' => $feeSummary['duration'] // Optional: include duration info
            ]);

        } catch (\Exception $e) {
            Log::error('Fee calculation error: ' . $e->getMessage());
            return $this->jsonResponse(false, 'Error calculating fees.', [], 500);
        }
    }

    /**
     * Create a temporary form object from session data for fee calculation
     */
    private function createTempFormObject(array $selectedItems, array $requestInfo): object
    {
        // Separate facilities and equipment from selected items
        $facilities = [];
        $equipment = [];

        foreach ($selectedItems as $item) {
            if ($item['type'] === 'facility') {
                $facilities[] = (object) [
                    'facility' => (object) [
                        'base_fee' => $item['base_fee'],
                        'facility_name' => $item['name'],
                        'rate_type' => $item['rate_type']
                    ],
                    'is_waived' => $item['is_waived'] ?? false
                ];
            } else {
                $equipment[] = (object) [
                    'equipment' => (object) [
                        'base_fee' => $item['base_fee'],
                        'equipment_name' => $item['name'],
                        'rate_type' => $item['rate_type']
                    ],
                    'quantity' => $item['quantity'] ?? 1,
                    'is_waived' => $item['is_waived'] ?? false
                ];
            }
        }

        // Create a temporary form object
        return (object) [
            'requestedFacilities' => collect($facilities),
            'requestedEquipment' => collect($equipment),
            'requisitionFees' => collect([]), // No additional fees in cart
            'start_date' => $requestInfo['start_date'],
            'end_date' => $requestInfo['end_date'],
            'start_time' => $requestInfo['start_time'] ?? '00:00:00',
            'end_time' => $requestInfo['end_time'] ?? '23:59:59',
            'all_day' => $requestInfo['all_day'] ?? false,
            'is_late' => false,
            'late_penalty_fee' => 0
        ];
    }

    /**
     * Transform calculator breakdown to match expected response format
     */
    private function transformBreakdownForResponse(array $breakdown): array
    {
        $result = [];

        // Add facilities
        foreach ($breakdown['facilities'] as $facility) {
            $result[] = [
                'name' => $facility['name'],
                'type' => 'facility',
                'quantity' => 1,
                'rate_type' => $facility['rate_type'],
                'fee_per_unit' => $facility['fee'],
                'total_fee' => $facility['fee'],
                'is_waived' => $facility['is_waived'],
                'duration_text' => $facility['duration_text']
            ];
        }

        // Add equipment
        foreach ($breakdown['equipment'] as $equipment) {
            $result[] = [
                'name' => $equipment['name'],
                'type' => 'equipment',
                'quantity' => $equipment['quantity'],
                'rate_type' => $equipment['rate_type'],
                'fee_per_unit' => $equipment['fee'] / $equipment['quantity'], // Calculate per unit fee
                'total_fee' => $equipment['fee'],
                'is_waived' => $equipment['is_waived'],
                'duration_text' => $equipment['duration_text']
            ];
        }

        return $result;
    }

    // ----- Remove items from session ----- //

    public function batchRemoveFromForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:facility,equipment',
            'items.*.facility_id' => 'required_if:items.*.type,facility|exists:facilities,facility_id',
            'items.*.equipment_id' => 'required_if:items.*.type,equipment|exists:equipment,equipment_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $selectedItems = Session::get('selected_items', []);
            $itemsToRemove = $request->items;
            $removedItems = [];

            foreach ($itemsToRemove as $item) {
                $type = $item['type'];
                $idField = $type . '_id';
                $id = $item[$idField];

                $filteredItems = collect($selectedItems)->reject(function ($existing) use ($id, $type, $idField) {
                    return isset($existing[$idField]) && $existing[$idField] == $id && $existing['type'] === $type;
                })->values()->toArray();

                if (count($filteredItems) < count($selectedItems)) {
                    $removedItems[] = $id;
                    $selectedItems = $filteredItems;
                }
            }

            Session::put('selected_items', $selectedItems);

            return response()->json([
                'success' => true,
                'message' => count($removedItems) . ' item(s) removed successfully.',
                'data' => [
                    'selected_items' => $selectedItems,
                    'cart_count' => count($selectedItems),
                    'removed_count' => count($removedItems)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Batch remove error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing items.'
            ], 500);
        }
    }

    public function removeFromForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'facility_id' => 'required_without:equipment_id|exists:facilities,facility_id',
            'equipment_id' => 'required_without:facility_id|exists:equipment,equipment_id',
            'type' => 'required|in:facility,equipment'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $selectedItems = Session::get('selected_items', []);
            $type = $request->type;
            $idField = $type . '_id';
            $id = $request->input($idField);

            $filteredItems = collect($selectedItems)->reject(function ($item) use ($id, $type, $idField) {
                return isset($item[$idField]) && $item[$idField] == $id && $item['type'] === $type;
            })->values()->toArray();

            Session::put('selected_items', $filteredItems);

            return response()->json([
                'success' => true,
                'message' => ucfirst($type) . ' removed successfully.',
                'data' => [
                    'selected_items' => $filteredItems,
                    'cart_count' => count($filteredItems)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Remove from form error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing item from form.'
            ], 500);
        }
    }

    // Updated getItems method
    public function getItems(Request $request)
    {
        $selectedItems = Session::get('selected_items', []);

        // Ensure consistent data structure
        $formattedItems = array_map(function ($item) {
            $base = [
                'type' => $item['type'],
                'name' => $item['name'],
                'description' => $item['description'],
                'base_fee' => $item['base_fee'],
                'rate_type' => $item['rate_type'],
                'images' => $item['images'],
            ];

            if ($item['type'] === 'facility') {
                $base['facility_id'] = $item['facility_id'];
            } else {
                $base['equipment_id'] = $item['equipment_id'];
                $base['quantity'] = $item['quantity'] ?? 1;
            }

            return $base;
        }, $selectedItems);

        return response()->json([
            'success' => true,
            'data' => [
                'selected_items' => $formattedItems
            ]
        ]);
    }

    /**
     * Check for booking schedule conflicts
     */
    public function checkAvailability(Request $request)
    {
        // Build rules array dynamically
        $rules = [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'all_day' => 'required|boolean',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:facility,equipment',
            'items.*.facility_id' => 'required_if:items.*.type,facility|exists:facilities,facility_id',
            'items.*.equipment_id' => 'required_if:items.*.type,equipment|exists:equipment,equipment_id',
        ];

        // Conditionally add time rules based on all_day flag
        if (!$request->all_day) {
            $rules['start_time'] = 'required|date_format:H:i';
            $rules['end_time'] = 'required|date_format:H:i|after:start_time';
        } else {
            $rules['start_time'] = 'nullable';
            $rules['end_time'] = 'nullable';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate that end time is after start time for same-day bookings
        if (!$request->all_day && $request->start_date === $request->end_date) {
            try {
                $requestStart = Carbon::createFromFormat('Y-m-d H:i', $request->start_date . ' ' . $request->start_time);
                $requestEnd = Carbon::createFromFormat('Y-m-d H:i', $request->end_date . ' ' . $request->end_time);

                if ($requestStart >= $requestEnd) {
                    return response()->json([
                        'success' => false,
                        'message' => 'End time must be after start time for the same day.'
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date/time format.'
                ], 422);
            }
        }

        $conflicts = false;
        $conflictItems = [];

        foreach ($request->items as $item) {
            if ($item['type'] === 'facility') {
                // Check facility availability using service
                $facilityConflicts = $this->availabilityChecker->checkFacilityAvailability(
                    $item['facility_id'],
                    $request->start_date,
                    $request->end_date,
                    $request->start_time ?? '00:00:00',
                    $request->end_time ?? '23:59:59',
                    $request->all_day
                );

                if (!empty($facilityConflicts)) {
                    $conflicts = true;
                    $facility = Facility::find($item['facility_id']);

                    $conflictItems[] = [
                        'type' => 'facility',
                        'id' => $item['facility_id'],
                        'name' => $facility ? $facility->facility_name : 'Unknown Facility',
                        'conflicts' => $facilityConflicts // Optional: include detailed conflicts
                    ];
                }
            } else {
                // Check equipment availability using service
                $availableCount = $this->availabilityChecker->checkEquipmentAvailability(
                    $item['equipment_id'],
                    $request->start_date,
                    $request->end_date,
                    $request->all_day
                );

                // For equipment, we need to check quantity
                $requestedQuantity = 1; // Default to 1 if not specified
                if (isset($item['quantity'])) {
                    $requestedQuantity = $item['quantity'];
                }

                if ($availableCount < $requestedQuantity) {
                    $conflicts = true;
                    $equipment = Equipment::find($item['equipment_id']);

                    $conflictItems[] = [
                        'type' => 'equipment',
                        'id' => $item['equipment_id'],
                        'name' => $equipment ? $equipment->equipment_name : 'Unknown Equipment',
                        'available' => $availableCount,
                        'requested' => $requestedQuantity,
                        'message' => "Only {$availableCount} available, requested {$requestedQuantity}"
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => $conflicts ? 'Time slot conflicts with existing booking(s).' : 'Time slot is available.',
            'data' => [
                'available' => !$conflicts,
                'conflict_items' => $conflictItems
            ]
        ]);
    }
    public function tempUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_documents_url' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Log session before upload
            \Log::debug('Pre-upload session data', ['session' => session()->all()]);

            $field = $request->hasFile('event_documents_url');
            $file = $request->file($field);

            $folder = $field === 'event_documents_url'
                ? 'user-uploads/user-letters'
                : 'user-uploads/user-setups';

            $upload = Cloudinary::upload($file->getRealPath(), [
                'folder' => $folder,
                'resource_type' => 'auto',
            ]);

            if (!$upload->getSecurePath()) {
                throw new \Exception('Cloudinary upload failed.');
            }

            $uploadToken = Str::random(40);

            // Store upload in session with clear structure
            $tempUploads = session('temp_uploads', []);
            $tempUploads[$field] = [
                'url' => $upload->getSecurePath(),
                'public_id' => $upload->getPublicId(),
                'token' => $uploadToken,
                'type' => $field === 'event_documents_url' ? 'Letter' : 'Setup'
            ];
            session(['temp_uploads' => $tempUploads]);

            // Log session after upload
            \Log::debug('Post-upload session data', ['session' => session()->all()]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'data' => $tempUploads[$field],
            ]);

        } catch (\Exception $e) {
            \Log::error('Upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ----- Submit requisition form ----- //
    public function submitForm(RequisitionSubmitRequest $request)
    {
        Log::info('Submit form started', [
            'email' => $request->email,
            'items_count' => count(session('selected_items', []))
        ]);

        DB::beginTransaction();

        try {

            $this->saveRequestInfoToSession($request);


            $selectedItems = $this->getValidatedSelectedItems();
            $this->validateSubmissionPrerequisites($selectedItems);
            $this->validateAvailability($selectedItems, $request);

            $requisitionForm = $this->createRequisition($request, $selectedItems);
            $this->saveRequisitionItems($requisitionForm, $selectedItems);
            $this->saveExtraServices($requisitionForm, $request->extra_services ?? []);

            $this->approvalChainService->createApprovalChain($requisitionForm);

            DB::commit();

            $this->sendNotifications($requisitionForm);
            $this->clearSubmissionSession();

            Log::info('Submit form completed', ['request_id' => $requisitionForm->request_id]);

            return $this->jsonResponse(true, 'Requisition submitted successfully!', [
                'access_code' => $requisitionForm->access_code,
                'request_id' => $requisitionForm->request_id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Submit form failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse(false, 'Submission failed: ' . $e->getMessage(), [], 500);
        }
    }

    private function saveRequestInfoToSession(RequisitionSubmitRequest $request): void
{
    $requestInfo = [
        'user_type' => $request->user_type,
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'email' => $request->email,
        'school_id' => $request->school_id,
        'organization_name' => $request->organization_name,
        'contact_number' => $request->contact_number,
        'num_participants' => $request->num_participants,
        'purpose_id' => $request->purpose_id,
        'additional_requests' => $request->additional_requests,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'start_time' => $request->all_day ? '00:00:00' : $request->start_time,
        'end_time' => $request->all_day ? '23:59:59' : $request->end_time,
        'all_day' => $request->all_day,
    ];

    session(['request_info' => $requestInfo]);
}

    // ------------------------------------------------------------------------
    // Private helper methods for form submission
    // ------------------------------------------------------------------------

    private function getValidatedSelectedItems(): array
    {
        $items = session('selected_items', []);

        if (empty($items)) {
            throw new \Exception('Your booking cart is empty. Add items before submitting.');
        }

        return $items;
    }

    private function validateSubmissionPrerequisites(array $selectedItems): void
    {
        $requestInfo = session('request_info');

        if (empty($requestInfo) || !isset($requestInfo['first_name'], $requestInfo['last_name'], $requestInfo['email'])) {
            throw new \Exception('User information not found. Please fill in all required fields.');
        }
    }

    private function validateAvailability(array $selectedItems, RequisitionSubmitRequest $request): void
    {
        $conflictItems = [];

        foreach ($selectedItems as $item) {
            if ($item['type'] === 'facility') {
                $facilityId = $item['facility_id'] ?? $item['id'];
                $conflicts = $this->availabilityChecker->checkFacilityAvailability(
                    $facilityId,
                    $request->start_date,
                    $request->end_date,
                    $request->start_time ?? '00:00:00',
                    $request->end_time ?? '23:59:59',
                    $request->all_day
                );

                if (!empty($conflicts)) {
                    $conflictItems = array_merge($conflictItems, $conflicts);
                }
            } else {
                $equipmentId = $item['equipment_id'] ?? $item['id'];
                $quantity = $item['quantity'] ?? 1;
                $available = $this->availabilityChecker->checkEquipmentAvailability(
                    $equipmentId,
                    $request->start_date,
                    $request->end_date,
                    $request->all_day
                );

                if ($available < $quantity) {
                    throw new \Exception("Not enough available items for {$item['name']}. Requested: {$quantity}, Available: {$available}");
                }
            }
        }

        if (!empty($conflictItems)) {
            throw new \Exception('Time slot conflicts with existing booking(s).');
        }
    }

    private function createRequisition(RequisitionSubmitRequest $request, array $selectedItems): RequisitionForm
    {
        $accessCode = $this->accessCodeService->generateUniqueAccessCode();

        return RequisitionForm::create([
            'user_type' => $request->user_type,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'organization_name' => $request->organization_name,
            'school_id' => $request->school_id,
            'access_code' => $accessCode,
            'event_title' => $request->event_title,
            'event_details' => $request->event_details,
            'purpose_id' => $request->purpose_id,
            'num_participants' => $request->num_participants,
            'num_tables' => $request->num_tables ?? 0,
            'num_chairs' => $request->num_chairs ?? 0,
            'num_microphones' => $request->num_microphones ?? 0,
            'additional_requests' => $request->additional_requests,
            'event_documents_url' => $request->event_documents_url ?? null,
            'event_documents_public_id' => $request->event_documents_public_id ?? null,
            'upload_token' => \Str::random(40),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->all_day ? '00:00:00' : $request->start_time,
            'end_time' => $request->all_day ? '23:59:59' : $request->end_time,
            'all_day' => $request->all_day,
            'status_id' => FormStatus::where('status_name', 'Pending Approval')->value('status_id'),
            'tentative_fee' => session('fee_summary.total_fee', 0),
        ]);
    }

    private function saveRequisitionItems(RequisitionForm $form, array $selectedItems): void
    {
        foreach ($selectedItems as $item) {
            if ($item['type'] === 'facility') {
                $facilityId = $item['facility_id'] ?? $item['id'];
                RequestedFacility::create([
                    'request_id' => $form->request_id,
                    'facility_id' => $facilityId,
                    'is_waived' => false,
                ]);
            } else {
                $equipmentId = $item['equipment_id'] ?? $item['id'];
                $quantity = $item['quantity'] ?? 1;

                RequestedEquipment::create([
                    'request_id' => $form->request_id,
                    'equipment_id' => $equipmentId,
                    'quantity' => $quantity,
                    'is_waived' => false,
                ]);
            }
        }
    }

    private function saveExtraServices(RequisitionForm $form, array $serviceIds): void
    {
        foreach ($serviceIds as $serviceId) {
            RequestedService::create([
                'request_id' => $form->request_id,
                'service_id' => $serviceId,
            ]);
        }
    }

    private function sendNotifications(RequisitionForm $form): void
    {
        try {
            $this->notificationService->sendConfirmationEmail($form);
            Log::info('Confirmation email sent');
        } catch (\Exception $e) {
            Log::error('Confirmation email failed: ' . $e->getMessage());
        }

        try {
            $this->notificationService->sendAdminApprovalEmails($form);
            Log::info('Admin approval emails sent');
        } catch (\Exception $e) {
            Log::error('Admin approval emails failed: ' . $e->getMessage());
        }
    }

    private function clearSubmissionSession(): void
    {
        session()->forget(['request_info', 'selected_items', 'fee_summary', 'temp_uploads']);
    }

    private function jsonResponse(bool $success, string $message, array $data = [], int $status = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public function clearSession()
    {
        session()->forget(['request_info', 'selected_items', 'fee_summary', 'temp_uploads']);
        return response()->json(['success' => true]);
    }

}