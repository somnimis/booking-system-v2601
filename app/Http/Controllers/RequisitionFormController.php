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
use App\Models\ExtraService;
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
| 1. saveRequestInfo()       - Store user/schedule details in session
| 2. addToForm()             - Add facilities/equipment to booking cart
| 3. calculateFeeBreakdown() - Preview fees before submission
| 4. checkAvailability()     - Validate time slots don't conflict
| 5. submitForm()            - Finalize and create requisition record
|
| Fee strategy:
|   The session cart is a *preview only*. The authoritative `tentative_fee`
|   is recalculated from the persisted DB rows inside the submission
|   transaction. This prevents:
|     - Tampered session data affecting the fee
|     - Silent `0` fees when the preview step was skipped
|     - Drift between what the user saw and what was actually booked
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
            'user_type' => 'required|in:Internal,External',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|max:100',
            'contact_number' => 'nullable|string|max:15',
            'organization_name' => 'nullable|string|max:100',
            'school_id' => 'nullable|string|max:20',
            'additional_requests' => 'nullable|string|max:250',
            'num_participants' => 'required|integer|min:1',
            'purpose_id' => 'required|exists:requisition_purposes,purpose_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'all_day' => 'required|boolean',
        ];

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
            'items.*.type' => 'required|in:facility,equipment,service',
            'items.*.facility_id' => 'required_if:items.*.type,facility|exists:facilities,facility_id',
            'items.*.equipment_id' => 'required_if:items.*.type,equipment|exists:equipment,equipment_id',
            'items.*.service_id' => 'required_if:items.*.type,service|exists:extra_services,service_id',
            'items.*.quantity' => 'required_if:items.*.type,equipment|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
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

                $exists = collect($selectedItems)->contains(function ($existing) use ($id, $type, $idField) {
                    return isset($existing[$idField]) && $existing[$idField] == $id && $existing['type'] === $type;
                });

                if ($exists) {
                    $skippedItems[] = $id;
                    continue;
                }

                if (count($selectedItems) >= 10) {
                    $skippedItems[] = $id;
                    continue;
                }

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
                        'added_at' => now()->toDateTimeString(),
                    ];
                } elseif ($type === 'equipment') {
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
                        'added_at' => now()->toDateTimeString(),
                    ];
                } else {
                    $itemModel = ExtraService::find($id);
                    if (!$itemModel) {
                        $skippedItems[] = $id;
                        continue;
                    }
                    $newItem = [
                        'type' => 'service',
                        'service_id' => $id,
                        'name' => $itemModel->service_name,
                        'description' => null,
                        'base_fee' => $itemModel->service_fee ?? 0,
                        'total_fee' => $itemModel->service_fee ?? 0,
                        'rate_type' => 'Flat',
                        'images' => [],
                        'added_at' => now()->toDateTimeString(),
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
                    'skipped_count' => count($skippedItems),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Batch add error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding items.',
            ], 500);
        }
    }

    public function addToForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'facility_id' => 'required_without_all:equipment_id,service_id|exists:facilities,facility_id',
            'equipment_id' => 'required_without_all:facility_id,service_id|exists:equipment,equipment_id',
            'service_id' => 'required_without_all:facility_id,equipment_id|exists:extra_services,service_id',
            'type' => 'required|in:facility,equipment,service',
            'quantity' => 'required_if:type,equipment|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $selectedItems = Session::get('selected_items', []);
            $type = $request->type;
            $idField = $type . '_id';
            $id = $request->input($idField);
            $quantity = $request->quantity ?? 1;

            $existingIndex = collect($selectedItems)->search(function ($item) use ($id, $type, $idField) {
                return isset($item[$idField]) && $item[$idField] == $id && $item['type'] === $type;
            });

            if ($existingIndex !== false) {
                if ($type === 'equipment') {
                    $selectedItems[$existingIndex]['quantity'] = $quantity;
                    $selectedItems[$existingIndex]['total_fee'] = $selectedItems[$existingIndex]['base_fee'] * $quantity;
                    Session::put('selected_items', $selectedItems);

                    return response()->json([
                        'success' => true,
                        'message' => 'Equipment quantity updated.',
                        'data' => [
                            'selected_items' => $selectedItems,
                            'cart_count' => count($selectedItems),
                        ],
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'This item is already in your requisition.',
                ], 422);
            }

            if (count($selectedItems) >= 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum item limit (10) reached.',
                ], 422);
            }

            if ($type === 'facility') {
                $item = Facility::with(['images', 'category', 'status'])->find($id);
            } elseif ($type === 'equipment') {
                $item = Equipment::with(['images', 'category', 'status'])->find($id);
            } else { // service
                $item = ExtraService::find($id);
            }

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found.',
                ], 404);
            }

            $newItem = [
                'type' => $type,
                $idField => $id,
                'name' => $item->facility_name
                    ?? $item->equipment_name
                    ?? $item->service_name,
                'description' => $item->description ?? null,
                'base_fee' => $item->base_fee ?? $item->service_fee ?? 0,
                'total_fee' => $type === 'equipment'
                    ? ($item->base_fee ?? 0) * $quantity
                    : ($item->base_fee ?? $item->service_fee ?? 0),
                'rate_type' => $item->rate_type ?? 'Flat',
                'images' => $item->images ? $item->images->toArray() : [],
                'added_at' => now()->toDateTimeString(),
            ];

            // Quantity only meaningful for equipment.
            if ($type === 'equipment') {
                $newItem['quantity'] = $quantity;
            }

            $selectedItems[] = $newItem;
            Session::put('selected_items', $selectedItems);

            return response()->json([
                'success' => true,
                'message' => ucfirst($type) . ' added successfully.',
                'data' => [
                    'selected_items' => $selectedItems,
                    'cart_count' => count($selectedItems),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Add to form error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding item to form.',
            ], 500);
        }
    }

    /**
     * Calculate fee breakdown for items in session cart.
     *
     * NOTE: This is a *preview* only. The authoritative `tentative_fee` is
     * recalculated inside submitForm() from the persisted DB rows.
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

            $tempForm = $this->createTempFormObject($selectedItems, $requestInfo);
            $feeSummary = $this->feeCalculator->getFeeSummary($tempForm);

            $breakdown = $this->transformBreakdownForResponse($feeSummary['breakdown']);

            // Cache for UI continuity only — never trusted as the source of truth.
            Session::put('fee_summary', [
                'breakdown' => $breakdown,
                'total_fee' => $feeSummary['approved_fee'],
            ]);

            return $this->jsonResponse(true, 'Fee breakdown calculated.', [
                'breakdown' => $breakdown,
                'total_fee' => $feeSummary['approved_fee'],
                'duration' => $feeSummary['duration'],
            ]);
        } catch (\Exception $e) {
            Log::error('Fee calculation error: ' . $e->getMessage());
            return $this->jsonResponse(false, 'Error calculating fees.', [], 500);
        }
    }

    /**
     * Build a temporary form-like object from session data.
     *
     * The extra_services array (if present in session) is folded into a
     * `requestedServices` collection so FeeCalculatorService can treat it
     * uniformly with persisted forms.
     */
    private function createTempFormObject(array $selectedItems, array $requestInfo): object
    {
        $facilities = [];
        $equipment = [];

        foreach ($selectedItems as $item) {
            if ($item['type'] === 'facility') {
                $facilities[] = (object) [
                    'facility' => (object) [
                        'base_fee' => $item['base_fee'],
                        'facility_name' => $item['name'],
                        'rate_type' => $item['rate_type'],
                    ],
                    'is_waived' => $item['is_waived'] ?? false,
                ];
            } elseif ($item['type'] === 'equipment') {
                $equipment[] = (object) [
                    'equipment' => (object) [
                        'base_fee' => $item['base_fee'],
                        'equipment_name' => $item['name'],
                        'rate_type' => $item['rate_type'],
                    ],
                    'quantity' => $item['quantity'] ?? 1,
                    'is_waived' => $item['is_waived'] ?? false,
                ];
            } elseif ($item['type'] === 'service') {
                $services[] = (object) [
                    'service' => (object) [
                        'service_name' => $item['name'],
                        'service_fee' => $item['base_fee'],
                    ],
                    'is_waived' => $item['is_waived'] ?? false,
                ];
            }
        }

        // Services stored in session (if the UI supports picking them pre-submit)
        $services = [];
        foreach (Session::get('extra_services', []) as $serviceRow) {
            $services[] = (object) [
                'service' => (object) [
                    'service_name' => $serviceRow['service_name'] ?? 'Service',
                    'service_fee' => $serviceRow['service_fee'] ?? 0,
                ],
                'is_waived' => $serviceRow['is_waived'] ?? false,
            ];
        }

        return (object) [
            'requestedFacilities' => collect($facilities),
            'requestedEquipment' => collect($equipment),
            'requestedServices' => collect($services),
            'requisitionFees' => collect([]),
            'start_date' => $requestInfo['start_date'],
            'end_date' => $requestInfo['end_date'],
            'start_time' => $requestInfo['start_time'] ?? '00:00:00',
            'end_time' => $requestInfo['end_time'] ?? '23:59:59',
            'all_day' => $requestInfo['all_day'] ?? false,
            'is_late' => false,
            'late_penalty_fee' => 0,
        ];
    }

    /**
     * Transform calculator breakdown to match the response format the UI expects.
     */
    private function transformBreakdownForResponse(array $breakdown): array
    {
        $result = [];

        foreach ($breakdown['facilities'] as $facility) {
            $result[] = [
                'name' => $facility['name'],
                'type' => 'facility',
                'quantity' => 1,
                'rate_type' => $facility['rate_type'],
                'fee_per_unit' => $facility['fee'],
                'total_fee' => $facility['fee'],
                'is_waived' => $facility['is_waived'],
                'duration_text' => $facility['duration_text'],
            ];
        }

        foreach ($breakdown['equipment'] as $equipment) {
            $result[] = [
                'name' => $equipment['name'],
                'type' => 'equipment',
                'quantity' => $equipment['quantity'],
                'rate_type' => $equipment['rate_type'],
                'fee_per_unit' => $equipment['quantity'] > 0 ? $equipment['fee'] / $equipment['quantity'] : 0,
                'total_fee' => $equipment['fee'],
                'is_waived' => $equipment['is_waived'],
                'duration_text' => $equipment['duration_text'],
            ];
        }

        // New: services in the preview
        foreach ($breakdown['services'] ?? [] as $service) {
            $result[] = [
                'name' => $service['name'],
                'type' => 'service',
                'quantity' => 1,
                'rate_type' => $service['rate_type'],
                'fee_per_unit' => $service['unit_price'],
                'total_fee' => $service['fee'],
                'is_waived' => $service['is_waived'],
                'duration_text' => null,
            ];
        }

        return $result;
    }

    // ----- Remove items from session ----- //

    public function batchRemoveFromForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:facility,equipment,service',
            'items.*.facility_id' => 'required_if:items.*.type,facility|exists:facilities,facility_id',
            'items.*.equipment_id' => 'required_if:items.*.type,equipment|exists:equipment,equipment_id',
            'items.*.service_id' => 'required_if:items.*.type,service|exists:extra_services,service_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
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
                    'removed_count' => count($removedItems),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Batch remove error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing items.',
            ], 500);
        }
    }

    public function removeFromForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'facility_id' => 'required_without_all:equipment_id,service_id|exists:facilities,facility_id',
            'equipment_id' => 'required_without_all:facility_id,service_id|exists:equipment,equipment_id',
            'service_id' => 'required_without_all:facility_id,equipment_id|exists:extra_services,service_id',
            'type' => 'required|in:facility,equipment,service',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
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
                    'cart_count' => count($filteredItems),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Remove from form error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing item from form.',
            ], 500);
        }
    }

    public function getItems(Request $request)
    {
        $selectedItems = Session::get('selected_items', []);

        $formattedItems = array_map(function ($item) {
            $base = [
                'type' => $item['type'],
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'base_fee' => $item['base_fee'],
                'rate_type' => $item['rate_type'],
                'images' => $item['images'] ?? [],
            ];

            if ($item['type'] === 'facility') {
                $base['facility_id'] = $item['facility_id'];
            } elseif ($item['type'] === 'equipment') {
                $base['equipment_id'] = $item['equipment_id'];
                $base['quantity'] = $item['quantity'] ?? 1;
            } else { // service
                $base['service_id'] = $item['service_id'];
            }

            return $base;
        }, $selectedItems);

        return response()->json([
            'success' => true,
            'data' => [
                'selected_items' => $formattedItems,
            ],
        ]);
    }
    /**
     * Check for booking schedule conflicts.
     */
    public function checkAvailability(Request $request)
    {
        $rules = [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'all_day' => 'required|boolean',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:facility,equipment',
            'items.*.facility_id' => 'required_if:items.*.type,facility|exists:facilities,facility_id',
            'items.*.equipment_id' => 'required_if:items.*.type,equipment|exists:equipment,equipment_id',
        ];

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
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!$request->all_day && $request->start_date === $request->end_date) {
            try {
                $requestStart = Carbon::createFromFormat('Y-m-d H:i', $request->start_date . ' ' . $request->start_time);
                $requestEnd = Carbon::createFromFormat('Y-m-d H:i', $request->end_date . ' ' . $request->end_time);

                if ($requestStart >= $requestEnd) {
                    return response()->json([
                        'success' => false,
                        'message' => 'End time must be after start time for the same day.',
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date/time format.',
                ], 422);
            }
        }

        $conflicts = false;
        $conflictItems = [];

        // Normalize times once so downstream service calls are consistent.
        $startTime = $request->all_day ? '00:00:00' : $request->start_time;
        $endTime = $request->all_day ? '23:59:59' : $request->end_time;

        foreach ($request->items as $item) {
            if ($item['type'] === 'facility') {
                $facilityConflicts = $this->availabilityChecker->checkFacilityAvailability(
                    $item['facility_id'],
                    $request->start_date,
                    $request->end_date,
                    $startTime,
                    $endTime,
                    $request->all_day
                );

                if (!empty($facilityConflicts)) {
                    $conflicts = true;
                    $facility = Facility::find($item['facility_id']);

                    $conflictItems[] = [
                        'type' => 'facility',
                        'id' => $item['facility_id'],
                        'name' => $facility ? $facility->facility_name : 'Unknown Facility',
                        'conflicts' => $facilityConflicts,
                    ];
                }
            } else {
                $availableCount = $this->availabilityChecker->checkEquipmentAvailability(
                    $item['equipment_id'],
                    $request->start_date,
                    $request->end_date,
                    $request->all_day
                );

                $requestedQuantity = $item['quantity'] ?? 1;

                if ($availableCount < $requestedQuantity) {
                    $conflicts = true;
                    $equipment = Equipment::find($item['equipment_id']);

                    $conflictItems[] = [
                        'type' => 'equipment',
                        'id' => $item['equipment_id'],
                        'name' => $equipment ? $equipment->equipment_name : 'Unknown Equipment',
                        'available' => $availableCount,
                        'requested' => $requestedQuantity,
                        'message' => "Only {$availableCount} available, requested {$requestedQuantity}",
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => $conflicts ? 'Time slot conflicts with existing booking(s).' : 'Time slot is available.',
            'data' => [
                'available' => !$conflicts,
                'conflict_items' => $conflictItems,
            ],
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
            Log::debug('Pre-upload session data', ['session' => session()->all()]);

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

            $tempUploads = session('temp_uploads', []);
            $tempUploads[$field] = [
                'url' => $upload->getSecurePath(),
                'public_id' => $upload->getPublicId(),
                'token' => $uploadToken,
                'type' => $field === 'event_documents_url' ? 'Letter' : 'Setup',
            ];
            session(['temp_uploads' => $tempUploads]);

            Log::debug('Post-upload session data', ['session' => session()->all()]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'data' => $tempUploads[$field],
            ]);
        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ----- Submit requisition form ----- //

    /**
     * Persist the requisition form and all its children in one transaction.
     *
     * Fee recalculation happens AFTER items and services are inserted so the
     * persisted `tentative_fee` reflects what actually got saved to the DB —
     * not what the session cart *claimed* the user picked.
     */
    public function submitForm(RequisitionSubmitRequest $request)
    {
        Log::info('Submit form started', [
            'email' => $request->email,
            'items_count' => count(session('selected_items', [])),
        ]);

        DB::beginTransaction();

        try {
            $this->saveRequestInfoToSession($request);

            $selectedItems = $this->getValidatedSelectedItems();
            $this->validateSubmissionPrerequisites($selectedItems);
            $this->validateAvailability($selectedItems, $request);

            // 1. Create the form WITHOUT tentative_fee — it will be set in step 4.
            $requisitionForm = $this->createRequisition($request, $selectedItems);

            // 2. Persist children.
            $this->saveRequisitionItems($requisitionForm, $selectedItems);
            $this->saveCartServices($requisitionForm, $selectedItems);

            // 3. Reload relationships so FeeCalculatorService reads from DB, not session.
            $requisitionForm->load([
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requestedServices.service',
            ]);

            // 4. Calculate and persist the authoritative tentative fee.
            $tentativeFee = $this->feeCalculator->calculateBaseFee($requisitionForm);

            $requisitionForm->update([
                'tentative_fee' => round($tentativeFee, 2),
            ]);

            // 5. Build the approval chain.
            $this->approvalChainService->createApprovalChain($requisitionForm);

            DB::commit();

            $this->sendNotifications($requisitionForm);
            $this->clearSubmissionSession();

            Log::info('Submit form completed', [
                'request_id' => $requisitionForm->request_id,
                'tentative_fee' => $requisitionForm->tentative_fee,
            ]);

            return $this->jsonResponse(true, 'Requisition submitted successfully!', [
                'access_code' => $requisitionForm->access_code,
                'request_id' => $requisitionForm->request_id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Submit form failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
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

        // Normalize times once — keeps behavior consistent with createRequisition().
        $startTime = $request->all_day ? '00:00:00' : $request->start_time;
        $endTime = $request->all_day ? '23:59:59' : $request->end_time;

        foreach ($selectedItems as $item) {
            if ($item['type'] === 'facility') {
                $conflicts = $this->availabilityChecker->checkFacilityAvailability(
                    $item['facility_id'],
                    $request->start_date,
                    $request->end_date,
                    $startTime,
                    $endTime,
                    $request->all_day
                );

                if (!empty($conflicts)) {
                    $conflictItems = array_merge($conflictItems, $conflicts);
                }
            } elseif ($item['type'] === 'equipment') {
                $quantity = $item['quantity'] ?? 1;
                $available = $this->availabilityChecker->checkEquipmentAvailability(
                    $item['equipment_id'],
                    $request->start_date,
                    $request->end_date,
                    $request->all_day
                );

                if ($available < $quantity) {
                    throw new \Exception("Not enough available items for {$item['name']}. Requested: {$quantity}, Available: {$available}");
                }
            }
            // 'service' items have no availability constraints — skip.
        }

        if (!empty($conflictItems)) {
            throw new \Exception('Time slot conflicts with existing booking(s).');
        }
    }
    /**
     * Create the RequisitionForm row.
     *
     * Does NOT set `tentative_fee` — that happens in submitForm() after the
     * child rows exist. Also does NOT write `upload_token` (deprecated).
     */
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
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->all_day ? '00:00:00' : $request->start_time,
            'end_time' => $request->all_day ? '23:59:59' : $request->end_time,
            'all_day' => $request->all_day,
            'status_id' => FormStatus::where('status_name', 'Pending Approval')->value('status_id'),
            // 'tentative_fee' is set in submitForm() after children are persisted.
            // 'upload_token'  is deprecated — no longer written.
        ]);
    }

    private function saveRequisitionItems(RequisitionForm $form, array $selectedItems): void
    {
        foreach ($selectedItems as $item) {
            if ($item['type'] === 'facility') {
                RequestedFacility::create([
                    'request_id' => $form->request_id,
                    'facility_id' => $item['facility_id'],
                    'is_waived' => false,
                ]);
            } elseif ($item['type'] === 'equipment') {
                RequestedEquipment::create([
                    'request_id' => $form->request_id,
                    'equipment_id' => $item['equipment_id'],
                    'quantity' => $item['quantity'] ?? 1,
                    'is_waived' => false,
                ]);
            }
            // 'service' items are persisted by saveCartServices().
        }
    }

    /**
     * Persist requested services from the session cart.
     *
     * Counterpart to saveRequisitionItems() for services. Reads from the
     * cart (session) rather than the request, keeping the cart as the
     * single source of truth for requested items.
     *
     * @param  RequisitionForm  $form
     * @param  array            $selectedItems
     * @return void
     */
    private function saveCartServices(RequisitionForm $form, array $selectedItems): void
    {
        foreach ($selectedItems as $item) {
            if ($item['type'] !== 'service') {
                continue;
            }

            RequestedService::create([
                'request_id' => $form->request_id,
                'service_id' => $item['service_id'],
                'is_waived' => false,
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
        session()->forget(['request_info', 'selected_items', 'fee_summary', 'temp_uploads', 'extra_services']);
    }

    private function jsonResponse(bool $success, string $message, array $data = [], int $status = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public function clearSession()
    {
        session()->forget(['request_info', 'selected_items', 'fee_summary', 'temp_uploads', 'extra_services']);
        return response()->json(['success' => true]);
    }
}