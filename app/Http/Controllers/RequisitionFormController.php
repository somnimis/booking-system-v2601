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
use App\Models\EquipmentItem;
use App\Models\FormStatus;
use App\Services\FeeCalculatorService;
use App\Services\NotificationService;
use App\Services\CheckAvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

/* 
|-------------------------------------------------------------------------- 
| RequisitionController Documentation 
|-------------------------------------------------------------------------- 
| Handles the entire requisition workflow:
| 
| - Uses Laravel session cookies to temporarily store user form data.
| - Supports adding/removing facility and equipment items.
| - Checks booking conflicts via checkAvailability() before submission.
| - Uploads temporary files (e.g., formal letter, layout) to Cloudinary.
| - On submission:
|     * Validates form data and re-checks availability.
|     * Creates a new requisition record (status: Pending Approval).
|     * Saves related requested_facilities and requested_equipment entries.
|     * Clears the session after completion.
| - Returns a success response with request_id and access_code.
|
| Note: Only equipment in "New", "Good", or "Fair" condition is bookable.
*/



class RequisitionFormController extends Controller
{

    protected $feeCalculator;
    protected $notificationService;
    protected $availabilityChecker;

    public function __construct(FeeCalculatorService $feeCalculator, NotificationService $notificationService, CheckAvailabilityService $availabilityChecker)
    {
        $this->feeCalculator = $feeCalculator;
        $this->availabilityChecker = $availabilityChecker;
        $this->notificationService = $notificationService;
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
            'endorser' => 'nullable|string|max:50',
            'date_endorsed' => 'nullable|date_format:Y-m-d',
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
            'endorser' => $request->endorser,
            'date_endorsed' => $request->date_endorsed,
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
                    $selectedItems[$existingIndex]['total_fee'] = $selectedItems[$existingIndex]['external_fee'] * $quantity;
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
                'external_fee' => $item->external_fee,
                'total_fee' => $type === 'equipment' ? $item->external_fee * $quantity : $item->external_fee,
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
            $facilities[] = (object)[
                'facility' => (object)[
                    'external_fee' => $item['external_fee'],
                    'facility_name' => $item['name'],
                    'rate_type' => $item['rate_type']
                ],
                'is_waived' => $item['is_waived'] ?? false
            ];
        } else {
            $equipment[] = (object)[
                'equipment' => (object)[
                    'external_fee' => $item['external_fee'],
                    'equipment_name' => $item['name'],
                    'rate_type' => $item['rate_type']
                ],
                'quantity' => $item['quantity'] ?? 1,
                'is_waived' => $item['is_waived'] ?? false
            ];
        }
    }

    // Create a temporary form object
    return (object)[
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
                'external_fee' => $item['external_fee'],
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
            'formal_letter_url' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'facility_layout_url' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
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

            $field = $request->hasFile('formal_letter_url') ? 'formal_letter_url' : 'facility_layout_url';
            $file = $request->file($field);

            $folder = $field === 'formal_letter_url'
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
                'type' => $field === 'formal_letter_url' ? 'Letter' : 'Setup'
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

    // ----- Submit requisition form with overbooking protection ----- //
// ----- Submit requisition form with overbooking protection ----- //
public function submitForm(Request $request)
{
    // Log the start of submission with request data
    \Log::info('=== SUBMIT FORM STARTED ===', [
        'timestamp' => now()->toDateTimeString(),
        'environment' => app()->environment(),
        'all_day' => $request->all_day,
        'has_items' => session()->has('selected_items'),
        'items_count' => count(session('selected_items', [])),
        'user_type' => $request->user_type,
        'email' => $request->email
    ]);

    // Build rules array dynamically
    $rules = [
        'user_type' => 'required|in:Internal,External',
        'school_id' => 'required_if:user_type,Internal|nullable|string|max:20',
        'first_name' => 'required|string|max:50',
        'last_name' => 'required|string|max:50',
        'email' => 'required|email|max:100',
        'contact_number' => ['nullable', 'regex:/^\d{1,15}$/', 'max:15'],
        'organization_name' => 'nullable|string|max:100',
        'num_participants' => 'required|integer|min:1',
        'num_tables' => 'required|integer|min:0',
        'num_chairs' => 'required|integer|min:0',
        'num_microphones' => 'required|integer|min:0',
        'all_day' => 'required|boolean',
        'purpose_id' => 'required|exists:requisition_purposes,purpose_id',
        'additional_requests' => 'nullable|string|max:250',
        'endorser' => 'nullable|string|max:50',
        'date_endorsed' => 'nullable|date_format:Y-m-d',
        'start_date' => 'required|date_format:Y-m-d',
        'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        'formal_letter_url' => 'nullable|url',
        'formal_letter_public_id' => 'nullable|string|max:255',
        'facility_layout_url' => 'nullable|string|max:255',
        'facility_layout_public_id' => 'nullable|string|max:255',
        'extra_services' => 'nullable|array',
        'extra_services.*' => 'integer|exists:extra_services,service_id',
    ];

    // Conditionally add time rules based on all_day flag
    if (!$request->all_day) {
        $rules['start_time'] = 'required|date_format:H:i';
        $rules['end_time'] = 'required|date_format:H:i|after:start_time';
        \Log::info('All-day is false, requiring time fields');
    } else {
        $rules['start_time'] = 'nullable';
        $rules['end_time'] = 'nullable';
        \Log::info('All-day is true, time fields optional');
    }

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        \Log::error('=== VALIDATION FAILED ===', [
            'errors' => $validator->errors()->toArray(),
            'request_data' => $request->except(['formal_letter_url', 'facility_layout_url'])
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()->toArray()
        ], 422);
    }

    \Log::info('Validation passed successfully');

    DB::beginTransaction();

    try {
        $selectedItems = session('selected_items', []);
        \Log::info('Selected items from session', [
            'count' => count($selectedItems),
            'items_preview' => collect($selectedItems)->map(function($item) {
                return [
                    'type' => $item['type'] ?? 'unknown',
                    'id' => $item['id'] ?? ($item[$item['type'].'_id'] ?? 'unknown'),
                    'name' => $item['name'] ?? 'Unknown',
                    'quantity' => $item['quantity'] ?? 1
                ];
            })->toArray()
        ]);

        if (empty($selectedItems)) {
            \Log::error('Cart empty - throwing exception');
            throw new \Exception('Your booking cart is empty. Add items before submitting.');
        }

        \Log::debug('Selected items structure', ['items' => $selectedItems]);

        $requestInfo = session('request_info');
        $tempUploads = session('temp_uploads', []);

        if (!$request->first_name || !$request->last_name || !$request->email) {
            \Log::error('User information missing', [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email
            ]);
            throw new \Exception('User information not found. Please fill in all required fields.');
        }

        \Log::info('Checking availability before submission', [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'all_day' => $request->all_day,
            'items_count' => count($selectedItems)
        ]);

        $conflictCheck = $this->checkAvailability(new Request([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'all_day' => $request->all_day,
            'items' => array_map(function ($item) {
                return [
                    'type' => $item['type'],
                    $item['type'] . '_id' => $item[$item['type'] . '_id'] ?? $item['id']
                ];
            }, $selectedItems)
        ]));

        $conflictData = $conflictCheck->getData();
        \Log::info('Availability check result', [
            'success' => $conflictData->success ?? false,
            'available' => $conflictData->data->available ?? false,
            'message' => $conflictData->message ?? 'No message'
        ]);

        if (!$conflictData->success || !$conflictData->data->available) {
            \Log::warning('Availability conflict detected', [
                'message' => $conflictData->message ?? 'Time slot not available'
            ]);
            throw new \Exception($conflictData->message ?? 'Time slot no longer available. Please choose another.');
        }

        $accessCode = Str::upper(Str::random(10));
        \Log::debug('Generated access code', ['code' => $accessCode]);

        // Create requisition form
        \Log::info('Creating requisition form in database');
        $requisitionForm = RequisitionForm::create([
            'user_type' => $request->user_type,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'organization_name' => $request->organization_name,
            'school_id' => $request->school_id,
            'access_code' => $accessCode,
            'purpose_id' => $request->purpose_id,
            'num_participants' => $request->num_participants,
            'num_tables' => $request->num_tables ?? 0,
            'num_chairs' => $request->num_chairs ?? 0,
            'num_microphones' => $request->num_microphones ?? 0,
            'additional_requests' => $request->additional_requests,
            'endorser' => $request->endorser,
            'date_endorsed' => $request->date_endorsed,
            'formal_letter_url' => $request->formal_letter_url ?? null,
            'formal_letter_public_id' => $request->formal_letter_public_id ?? null,
            'facility_layout_url' => $request->facility_layout_url ?? null,
            'facility_layout_public_id' => $request->facility_layout_public_id ?? null,
            'upload_token' => Str::random(40),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->all_day ? '00:00:00' : $request->start_time,
            'end_time' => $request->all_day ? '23:59:59' : $request->end_time,
            'all_day' => $request->all_day,
            'status_id' => FormStatus::where('status_name', 'Pending Approval')->value('status_id'),
            'tentative_fee' => session('fee_summary.total_fee', 0),
        ]);

        \Log::info('Requisition form created', [
            'request_id' => $requisitionForm->request_id,
            'access_code' => $requisitionForm->access_code
        ]);

        // Save selected items
        $facilityIds = [];
        $equipmentIds = [];

        foreach ($selectedItems as $item) {
            if ($item['type'] === 'facility') {
                $facilityId = $item['facility_id'] ?? $item['id'];
                $facilityIds[] = $facilityId;

                RequestedFacility::create([
                    'request_id' => $requisitionForm->request_id,
                    'facility_id' => $facilityId,
                    'is_waived' => false,
                ]);
                
                \Log::debug('Facility saved', ['facility_id' => $facilityId]);
                
            } elseif ($item['type'] === 'equipment') {
                $equipmentId = $item['equipment_id'] ?? $item['id'];
                $equipmentIds[] = $equipmentId;
                $quantity = $item['quantity'] ?? 1;

                \Log::debug('Processing equipment', [
                    'equipment_id' => $equipmentId,
                    'quantity' => $quantity,
                    'item_structure' => $item
                ]);

                // Check availability - UPDATED for all-day events
                if ($request->all_day) {
                    // Check if equipment is already booked on these dates
                    $existingBookings = RequestedEquipment::where('equipment_id', $equipmentId)
                        ->whereHas('requisitionForm', function ($q) use ($request) {
                            $q->whereIn('status_id', function ($sq) {
                                $sq->select('status_id')
                                    ->from('form_statuses')
                                    ->whereIn('status_name', ['Pending Approval', 'Scheduled', 'Ongoing']);
                            })
                                ->where(function ($dateQ) use ($request) {
                                    $dateQ->where('start_date', '<=', $request->end_date)
                                        ->where('end_date', '>=', $request->start_date);
                                });
                        })
                        ->sum('quantity');

                    $availableCount = EquipmentItem::where('equipment_id', $equipmentId)
                        ->where('status_id', 1)
                        ->whereIn('condition_id', [1, 2, 3])
                        ->count();

                    $availableCount -= $existingBookings;
                    
                    \Log::debug('Equipment availability (all-day)', [
                        'equipment_id' => $equipmentId,
                        'total_available' => $availableCount + $existingBookings,
                        'existing_bookings' => $existingBookings,
                        'available' => $availableCount,
                        'requested' => $quantity
                    ]);
                } else {
                    // Regular time-based check
                    $availableCount = EquipmentItem::where('equipment_id', $equipmentId)
                        ->where('status_id', 1)
                        ->whereIn('condition_id', [1, 2, 3])
                        ->count();
                        
                    \Log::debug('Equipment availability (timed)', [
                        'equipment_id' => $equipmentId,
                        'available' => $availableCount,
                        'requested' => $quantity
                    ]);
                }

                if ($availableCount < $quantity) {
                    $errorMsg = "Not enough available items for {$item['name']}. Requested: {$quantity}, Available: {$availableCount}";
                    \Log::error($errorMsg);
                    throw new \Exception($errorMsg);
                }

                RequestedEquipment::create([
                    'request_id' => $requisitionForm->request_id,
                    'equipment_id' => $equipmentId,
                    'quantity' => $quantity,
                    'is_waived' => false,
                ]);
                
                \Log::debug('Equipment saved', [
                    'equipment_id' => $equipmentId,
                    'quantity' => $quantity
                ]);
            }
        }

        // Save extra services if selected
        $serviceIds = [];
        if ($request->has('extra_services') && is_array($request->extra_services)) {
            $serviceIds = $request->extra_services;
            foreach ($serviceIds as $serviceId) {
                RequestedService::create([
                    'request_id' => $requisitionForm->request_id,
                    'service_id' => $serviceId,
                ]);
            }
            \Log::info('Extra services saved', ['count' => count($serviceIds)]);
        }

        // Send confirmation email to requester
        \Log::info('Attempting to send confirmation email', [
            'to' => $requisitionForm->email,
            'request_id' => $requisitionForm->request_id
        ]);
        
        try {
            $this->notificationService->sendConfirmationEmail($requisitionForm);
            \Log::info('✓ Confirmation email sent successfully');
        } catch (\Exception $e) {
            \Log::error('✗ Confirmation email failed: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - we still want to complete the submission
        }

        // Clear session
        session()->forget(['request_info', 'selected_items', 'fee_summary', 'temp_uploads']);
        \Log::info('Session cleared');

        DB::commit();
        \Log::info('✓ Database transaction committed successfully');

        // Send approval request emails to responsible admins
        \Log::info('Attempting to send admin approval emails');
        try {
            $this->notificationService->sendAdminApprovalEmails($requisitionForm);
            \Log::info('✓ Admin approval emails process completed');
        } catch (\Exception $e) {
            \Log::error('✗ Failed to send admin approval emails: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - we still want to return success to user
        }

        \Log::info('=== SUBMIT FORM COMPLETED SUCCESSFULLY ===', [
            'request_id' => $requisitionForm->request_id,
            'email' => $requisitionForm->email
        ]);

        return $this->jsonResponse(true, 'Requisition submitted successfully!', [
            'access_code' => $requisitionForm->access_code,
            'request_id' => $requisitionForm->request_id,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('=== SUBMIT FORM FAILED ===', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->except(['formal_letter_url', 'facility_layout_url'])
        ]);
        
        return $this->jsonResponse(false, 'Submission failed: ' . $e->getMessage(), [], 500);
    }
}

    public function clearSession()
    {
        session()->forget(['request_info', 'selected_items', 'fee_summary', 'temp_uploads']);
        return response()->json(['success' => true]);
    }

    /**
     * Common JSON response structure
     */
    private function jsonResponse($success, $message, $data = [], $status = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], $status);
    }

}