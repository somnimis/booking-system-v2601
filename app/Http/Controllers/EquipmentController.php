<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;



class EquipmentController extends Controller
{

    // ----- Indexes ----- //

    public function publicIndex(Request $request): JsonResponse
    {
        try {
            $query = Equipment::with([
                'category',
                'status',
                'department',
                'items' => function ($query) {
                    $query->where('status_id', '!=', 5);
                },
                'items.condition',
                'images'
            ]);

            // Apply search filter on equipment items
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->whereHas('items', function ($q) use ($search) {
                    $q->where('item_name', 'LIKE', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->has('status') && !empty($request->status)) {
                $statusId = $request->status;
                $query->whereHas('status', function ($q) use ($statusId) {
                    $q->where('status_id', $statusId);
                });
            }

            // Apply category filters
            if ($request->has('categories') && !empty($request->categories)) {
                $categories = $request->categories;
                $query->whereHas('category', function ($q) use ($categories) {
                    $q->whereIn('category_id', $categories);
                });
            }

            // Apply pagination
            $perPage = $request->input('per_page', 6);
            $equipment = $query->orderBy('equipment_name')->paginate($perPage);

            $formatted = $equipment->getCollection()->map(function ($item) {
                $availableCount = $item->items
                    ->filter(function ($item) {
                        return $item->status_id == 1 && in_array($item->condition_id, [1, 2, 3]);
                    })
                    ->count();

                $totalCount = $item->items->count();

                return array_merge(
                    $this->formatPublicEquipment($item),
                    [
                        'images' => $item->images,
                        'available_quantity' => $availableCount,
                        'total_quantity' => $totalCount
                    ]
                );
            });

            return response()->json([
                'data' => $formatted,
                'current_page' => $equipment->currentPage(),
                'last_page' => $equipment->lastPage(),
                'total' => $equipment->total(),
                'per_page' => $equipment->perPage()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching public equipment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to fetch equipment data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // === EQUIPMENT DETAILS API ENDPOINT === //
    public function getEquipmentDetails($id): JsonResponse
    {
        try {
            if (!$id) {
                return response()->json(['success' => false, 'message' => 'Equipment ID is required'], 400);
            }

            $equipment = Equipment::with(['category', 'status', 'department', 'images'])
                // Count total items (excluding status_id 5)
                ->withCount([
                    'items as total_quantity' => function ($query) {
                        $query->where('status_id', '!=', 5);
                    }
                ])
                // Count available items based on status AND condition rules
                ->withCount([
                    'items as available_quantity' => function ($query) {
                        $query->where('status_id', 1)
                            ->whereIn('condition_id', [1, 2, 3]);
                    }
                ])
                ->find($id);

            if (!$equipment) {
                return response()->json(['success' => false, 'message' => 'Equipment not found'], 404);
            }

            $formattedEquipment = $this->formatPublicEquipment($equipment);

            // Map the counts from the model attributes to your response
            $formattedEquipment['available_quantity'] = $equipment->available_quantity;
            $formattedEquipment['total_quantity'] = $equipment->total_quantity;
            $formattedEquipment['images'] = $equipment->images;

            return response()->json([
                'success' => true,
                'data' => $formattedEquipment
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching equipment details', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch equipment details'
            ], 500);
        }
    }

    // ----- EQUIPMENT MANAGEMENT SECTION ----- //


    // ----- Store Equipment ----- //

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'equipment_name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:80',
            'storage_location' => 'required|string|max:50',
            'category_id' => 'required|exists:equipment_categories,category_id',
            'base_fee' => 'required|numeric|min:0',
            'rate_type' => 'required|in:Per Hour,Per Event',
            'status_id' => 'required|exists:availability_statuses,status_id',
            'managed_by' => 'required|exists:departments,department_id', // Changed from departments array
            'maximum_rental_hour' => 'nullable|integer',

            'items' => 'sometimes|array',
            'items.*.item_name' => 'sometimes|string|max:100',
            'items.*.condition_id' => 'required|exists:conditions,condition_id',
            'items.*.barcode_number' => 'nullable|string|max:100',
            'items.*.item_notes' => 'nullable|string',

            'images' => 'sometimes|array',
            'images.*.image_url' => 'required|url|max:500',
            'images.*.description' => 'nullable|string',
            'images.*.sort_order' => 'sometimes|integer',
            'images.*.image_type' => 'required|exists:image_types,image_type',
        ]);

        // Create equipment with managed_by field (department_id)
        $equipment = Equipment::create([
            'equipment_name' => $data['equipment_name'],
            'description' => $data['description'] ?? null,
            'brand' => $data['brand'] ?? null,
            'storage_location' => $data['storage_location'],
            'category_id' => $data['category_id'],
            'base_fee' => $data['base_fee'],
            'rate_type' => $data['rate_type'],
            'status_id' => $data['status_id'],
            'department_id' => $data['managed_by'], // Use managed_by as department_id
            'maximum_rental_hour' => $data['maximum_rental_hour'],
            'created_by' => auth()->id()
        ]);

        // Optional: Handle items and images if provided
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $equipment->items()->create($item);
            }
        }

        if (!empty($data['images'])) {
            foreach ($data['images'] as $image) {
                $equipment->images()->create($image);
            }
        }

        return response()->json([
            'message' => 'Equipment created successfully',
            'data' => $this->formatEquipment($equipment->fresh())
        ], 201);
    }

    // ----- Display Equipment ----- //

    public function show($id)
    {
        try {
            $equipment = Equipment::with([
                'category',
                'status',
                'department',
                'images'
            ])->findOrFail($id);

            return response()->json([
                'data' => $equipment
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching equipment: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch equipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ----- Update Equipment ----- //

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'equipment_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
                'brand' => 'nullable|string|max:255',
                'storage_location' => 'required|string|max:255',
                'category_id' => 'required|exists:equipment_categories,category_id',
                'base_fee' => 'required|numeric|min:0',
                'rate_type' => 'required|in:Per Hour,Per Event',
                'status_id' => 'required|exists:availability_statuses,status_id',
                'managed_by' => 'required|exists:departments,department_id', // Changed from departments array
                'maximum_rental_hour' => 'required|integer|min:1',
            ]);

            $equipment = Equipment::findOrFail($id);

            // Update equipment with managed_by field
            $equipment->update([
                'equipment_name' => $validated['equipment_name'],
                'description' => $validated['description'],
                'brand' => $validated['brand'],
                'storage_location' => $validated['storage_location'],
                'category_id' => $validated['category_id'],
                'base_fee' => $validated['base_fee'],
                'rate_type' => $validated['rate_type'],
                'status_id' => $validated['status_id'],
                'department_id' => $validated['managed_by'], // Use managed_by as department_id
                'maximum_rental_hour' => $validated['maximum_rental_hour'],
            ]);

            return response()->json([
                'message' => 'Equipment updated successfully',
                'data' => $equipment->fresh()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating equipment: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update equipment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getEquipmentWithSelected(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter');
        $page = $request->input('page', 1);
        $perPage = 10;

        $query = Equipment::join(
            'availability_statuses',
            'equipment.status_id',
            '=',
            'availability_statuses.status_id'
        )
            ->select(
                'equipment.equipment_id',
                'equipment.equipment_name',
                'availability_statuses.status_name'
            );

        if ($search) {
            $query->where('equipment.equipment_name', 'like', "%{$search}%");
        }

        if ($filter) {
            [$type, $id] = explode(':', $filter);
            if ($type === 'category') {
                $query->where('equipment.category_id', $id);
            }
        }

        $equipment = $query->orderBy('equipment.equipment_name')->paginate($perPage, ['*'], 'page', $page);

        // Filter out "Hidden" status
        $filteredItems = $equipment->items();
        $filteredItems = array_filter($filteredItems, fn($e) => $e->status_name !== 'Hidden');

        // Get selected item IDs from session
        $selectedItems = session('selected_items', []);
        $selectedEquipmentIds = collect($selectedItems)
            ->filter(fn($item) => $item['type'] === 'equipment')
            ->pluck('equipment_id')
            ->toArray();

        return response()->json([
            'data' => array_values($filteredItems),
            'current_page' => $equipment->currentPage(),
            'last_page' => $equipment->lastPage(),
            'total' => count($filteredItems),
            'per_page' => $perPage,
            'selected_ids' => $selectedEquipmentIds,
        ]);
    }

    public function getEquipmentForDropdown(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $filter = $request->input('filter');

        $query = Equipment::join(
            'availability_statuses',
            'equipment.status_id',
            '=',
            'availability_statuses.status_id'
        )
            ->select(
                'equipment.equipment_id',
                'equipment.equipment_name',
                'availability_statuses.status_name'
            )
            ->when($search, function ($query) use ($search) {
                $query->where(
                    'equipment.equipment_name',
                    'like',
                    "%{$search}%"
                );
            });

        if ($filter) {
            [$type, $id] = explode(':', $filter);

            if ($type === 'category') {
                $query->where('equipment.category_id', $id);
            }
        }

        return response()->json(
            $query
                ->orderBy('equipment.equipment_name')
                ->paginate(
                    perPage: 10,
                    page: $request->input('page', 1)
                )
        );
    }

    /**
     * Paginated equipment dropdown for the mass-assign modal.
     * Matches by name OR brand (ILIKE, partial, case-insensitive).
     * Returns paginator shape so the modal can paginate + search.
     */
    public function getEquipmentForMassAssign(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));

        $query = Equipment::query()
            ->select('equipment_id', 'equipment_name', 'brand')
            ->when($search !== '', function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where(function ($qq) use ($like) {
                    $qq->where('equipment_name', 'ILIKE', $like)
                        ->orWhere('brand', 'ILIKE', $like);
                });
            })
            ->orderBy('equipment_name');

        return response()->json(
            $query->paginate(perPage: 10, page: $request->input('page', 1))
        );
    }

    /**
     * Mass assign departments to multiple equipment
     */
    public function massAssignDepartments(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'equipment_ids' => 'required|array|min:1',
                'equipment_ids.*' => 'exists:equipment,equipment_id',
                'department_id' => 'required|exists:departments,department_id', // Single department ID
            ]);

            $equipmentIds = $validated['equipment_ids'];
            $departmentId = $validated['department_id'];

            $results = [
                'success' => [],
                'failed' => []
            ];

            foreach ($equipmentIds as $equipmentId) {
                try {
                    $equipment = Equipment::findOrFail($equipmentId);

                    // Update the managed_by field (department_id)
                    $equipment->update([
                        'managed_by' => $departmentId  // Changed from 'department_id' to 'managed_by'
                    ]);

                    $results['success'][] = [
                        'equipment_id' => $equipmentId,
                        'equipment_name' => $equipment->equipment_name
                    ];

                } catch (\Exception $e) {
                    \Log::error('Failed to mass assign department to equipment', [
                        'equipment_id' => $equipmentId,
                        'error' => $e->getMessage()
                    ]);

                    $results['failed'][] = [
                        'equipment_id' => $equipmentId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $successCount = count($results['success']);
            $failedCount = count($results['failed']);

            if ($successCount === 0) {
                $message = "Failed to assign department to any equipment.";
            } else if ($failedCount === 0) {
                $message = "Successfully assigned department to " . ($successCount === 1 ? "1 equipment" : "{$successCount} equipment items");
            } else {
                $message = "Assigned department to {$successCount} " . ($successCount === 1 ? "equipment" : "equipment items") .
                    ", failed for {$failedCount} " . ($failedCount === 1 ? "equipment" : "equipment items");
            }

            return response()->json([
                'message' => $message,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in mass department assignment for equipment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to process mass department assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to generate appropriate message for mass assignment
     */
    private function getMassAssignmentMessage(array $results, string $action, string $type): string
    {
        $successCount = count($results['success']);
        $failedCount = count($results['failed']);

        $actionText = [
            'add' => 'added to',
            'replace' => 'assigned to',
            'remove' => 'removed from'
        ][$action];

        if ($successCount === 0) {
            return "Failed to {$action} departments for any {$type}s.";
        }

        if ($failedCount === 0) {
            return "Successfully {$actionText} " . ($successCount === 1 ? "1 {$type}" : "{$successCount} {$type}s");
        }

        return "Departments {$actionText} {$successCount} " . ($successCount === 1 ? $type : "{$type}s") .
            ", failed for {$failedCount} " . ($failedCount === 1 ? $type : "{$type}s");
    }
    public function edit(Request $request)
    {
        $equipmentId = $request->query('id');

        if (!$equipmentId) {
            return redirect('/admin/manage-equipment')->with('error', 'No equipment ID provided');
        }

        return view('admin.edit-equipment', ['equipmentId' => $equipmentId]);
    }

    // ----- Delete Equipment ----- //
    public function destroy($id): JsonResponse
    {
        try {
            $equipment = Equipment::findOrFail($id);

            // equipment_images + equipment_items cascade-delete via FK constraints.
            $equipment->delete();

            \Log::info('Equipment deleted successfully', ['equipment_id' => $id]);

            return response()->json(['message' => 'Equipment deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Equipment not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Error deleting equipment', [
                'equipment_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to delete equipment: ' . $e->getMessage()], 500);
        }
    }

    // ----- Formatting ----- //

    private function formatEquipment($equipment): array
    {
        $equipment->load(['category', 'status', 'department', 'items', 'images']);

        return [
            'equipment_id' => $equipment->equipment_id,
            'equipment_name' => $equipment->equipment_name,
            'description' => $equipment->description,
            'brand' => $equipment->brand,
            'storage_location' => $equipment->storage_location,
            'category' => [
                'category_id' => $equipment->category_id,
                'category_name' => $equipment->category->category_name,
            ],
            'base_fee' => $equipment->base_fee,
            'rate_type' => $equipment->rate_type,
            'status' => [
                'status_id' => $equipment->status_id,
                'status_name' => $equipment->status->status_name,
                'color_code' => $equipment->status->color_code,
            ],
            'department' => [
                'department_id' => $equipment->department_id,
                'department_name' => $equipment->department->department_name,
            ],
            'maximum_rental_hour' => $equipment->maximum_rental_hour,
            'items' => $equipment->items,
            'images' => $equipment->images,
            'created_at' => $equipment->created_at,
            'updated_at' => $equipment->updated_at,
        ];
    }

    private function formatPublicEquipment($equipment): array
    {
        $equipment->load(['category', 'status', 'department', 'items.condition', 'images']);

        return [
            'equipment_id' => $equipment->equipment_id,
            'equipment_name' => $equipment->equipment_name,
            'description' => $equipment->description,
            'brand' => $equipment->brand,
            'storage_location' => $equipment->storage_location,
            'category' => [
                'category_id' => $equipment->category_id,
                'category_name' => $equipment->category->category_name,
            ],
            'base_fee' => $equipment->base_fee,
            'rate_type' => $equipment->rate_type,
            'status' => [
                'status_id' => $equipment->status_id,
                'status_name' => $equipment->status->status_name,
                'color_code' => $equipment->status->color_code,
            ],
            'items' => $equipment->items,
            'images' => $equipment->images,
        ];
    }




    // ---------  EQUIPMENT ITEMS MANAGEMENT ---------- //

    public function getItems($equipmentId): JsonResponse
    {
        try {

            $items = EquipmentItem::with(['condition'])
                ->where('equipment_id', $equipmentId)
                ->orderBy('item_name')
                ->get();

            return response()->json([
                'data' => $items
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching equipment items', [
                'error' => $e->getMessage(),
                'equipmentId' => $equipmentId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to fetch equipment items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeItem(Request $request, $equipmentId)
    {
        try {
            $admin = auth()->user(); // This gets the authenticated admin

            $validatedData = $request->validate([
                'item_name' => 'required|string|max:50',
                'condition_id' => 'required|exists:conditions,condition_id',
                'barcode_number' => 'nullable|string|max:20',
                'item_notes' => 'nullable|string|max:100',
                'image_url' => 'nullable|url',
            ]);

            // Log authentication details for debugging
            \Log::info('Creating equipment item', [
                'admin_id' => $admin->admin_id,
                'admin_email' => $admin->email,
                'equipment_id' => $equipmentId,
                'validated_data' => $validatedData
            ]);

            $item = EquipmentItem::create([
                'equipment_id' => $equipmentId,
                'item_name' => $validatedData['item_name'],
                'condition_id' => $validatedData['condition_id'],
                'barcode_number' => $validatedData['barcode_number'] ?? null,
                'item_notes' => $validatedData['item_notes'] ?? 'No notes provided for this asset.',
                'image_url' => $validatedData['image_url'] ?? 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1750895337/oxvsxogzu9koqhctnf7s.webp',
                'status_id' => 1,
                'created_by' => $admin->admin_id // Use authenticated admin's ID
            ]);

            return response()->json([
                'message' => 'Item added successfully',
                'item' => $item->load('condition')
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create equipment item', [
                'equipment_id' => $equipmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_id' => auth()->check() ? auth()->user()->admin_id : 'Not authenticated'
            ]);

            return response()->json([
                'error' => 'Failed to add item',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function updateItem(Request $request, $equipmentId, $itemId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'item_name' => 'required|string|max:50',
                'condition_id' => 'required|exists:conditions,condition_id',
                'barcode_number' => 'nullable|string|max:20',
                'item_notes' => 'nullable|string|max:100',
                'image_url' => 'nullable|url',
            ]);


            $item = EquipmentItem::where('equipment_id', $equipmentId)
                ->where('item_id', $itemId)
                ->firstOrFail();

            $item->update([
                'item_name' => $validated['item_name'],
                'condition_id' => $validated['condition_id'],
                'barcode_number' => $validated['barcode_number'] ?? null,
                'item_notes' => $validated['item_notes'] ?? $item->item_notes,
                'image_url' => $validated['image_url'] ?? $item->image_url,
                'updated_by' => auth()->id()
            ]);

            \Log::info('Equipment item updated successfully', [
                'item_id' => $itemId,
                'equipment_id' => $equipmentId,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Item updated successfully',
                'data' => $item->fresh()->load('condition')
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating equipment item', [
                'error' => $e->getMessage(),
                'equipmentId' => $equipmentId,
                'itemId' => $itemId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to update item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteItem($equipmentId, $itemId)
    {
        try {
            $item = EquipmentItem::where('equipment_id', $equipmentId)
                ->where('item_id', $itemId)
                ->firstOrFail();


            // Delete the database record
            $item->delete();

            \Log::info('Equipment item deleted successfully', [
                'item_id' => $itemId,
                'equipment_id' => $equipmentId,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Item deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Equipment item not found for deletion', [
                'equipment_id' => $equipmentId,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Item not found',
                'details' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Failed to delete equipment item', [
                'item_id' => $itemId,
                'equipment_id' => $equipmentId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to delete item',
                'details' => $e->getMessage()
            ], 500);
        }
    }


}