<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Facility;
use App\Models\Equipment;
use App\Models\ExtraService;
use App\Services\FeeCalculatorService;
use App\Services\NotificationService;
use App\Services\CheckAvailabilityService;
use App\Services\ApprovalChainService;
use App\Services\RequisitionCreationService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\RequisitionSubmitRequest;
use App\Services\AccessCodeService;
use App\Services\RequisitionFormatterService;
use App\Services\ScheduleFormatterService;
use App\Exceptions\BookingConflictException;

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
| 4. submitForm()            - Finalize and create requisition record
|
| Status: Upon submission, requisition is set to 'Pending Approval'
| and requires admin approval before scheduling.
|
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
        ScheduleFormatterService $scheduleFormatter,
        protected RequisitionCreationService $creationService
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
            'cleanup_hours' => $request->cleanup_hours ?? 4
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

                if ($this->getBadgeCount($selectedItems) >= 10) {
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
                    'cart_count' => $this->getBadgeCount($selectedItems),
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
                            'cart_count' => $this->getBadgeCount($selectedItems),
                        ],
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'This item is already in your requisition.',
                ], 422);
            }

            if ($this->getBadgeCount($selectedItems) >= 10) {
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
                    'cart_count' => $this->getBadgeCount($selectedItems),
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
                    'cart_count' => $this->getBadgeCount($selectedItems),
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
                'facilities_html' => view('partials.cart-facilities', ['items' => $formattedItems])->render(),
                'equipment_html' => view('partials.cart-equipment', ['items' => $formattedItems])->render(),
                'cart_count' => $this->getBadgeCount($formattedItems)
            ],
        ]);
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
        try {
            $requisitionForm = $this->creationService->createFromSession($request);

            return $this->jsonResponse(true, 'Requisition submitted successfully!', [
                'access_code' => $requisitionForm->access_code,
                'request_id' => $requisitionForm->request_id,
            ]);
        } catch (BookingConflictException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'available' => false,
                    'conflict_items' => $e->getConflictItems(),
                    'conflict_html' => $this->availabilityChecker->formatConflictsToHtml($e->getConflictItems()),
                ],
            ], 409);
        } catch (\Exception $e) {
            Log::error('Submit form failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse(false, 'Submission failed: ' . $e->getMessage(), [], 500);
        }
    }

    // ------------------------------------------------------------------------
    // Private helper methods for form submission
    // ------------------------------------------------------------------------

    /**
     * Visual count for the cart badge only.
     * Excludes services since the catalog only shows facilities and equipment.
     */
    private function getBadgeCount(array $items): int
    {
        return collect($items)->whereIn('type', ['facility', 'equipment'])->count();
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