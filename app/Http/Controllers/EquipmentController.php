<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EquipmentController extends Controller
{
    // ----- Indexes ----- //

    public function publicIndex(Request $request): JsonResponse
    {
        try {
            $query = Equipment::with([
                'category',
                'status',
                'departments',
                'items' => function ($query) {
                    $query->where('status_id', '!=', 5);
                },
                'items.condition',
                'images'
            ]);

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->whereHas('items', function ($q) use ($search) {
                    $q->where('item_name', 'LIKE', "%{$search}%");
                });
            }

            if ($request->has('status') && !empty($request->status)) {
                $statusId = $request->status;
                $query->whereHas('status', function ($q) use ($statusId) {
                    $q->where('status_id', $statusId);
                });
            }

            if ($request->has('categories') && !empty($request->categories)) {
                $categories = $request->categories;
                $query->whereHas('category', function ($q) use ($categories) {
                    $q->whereIn('category_id', $categories);
                });
            }

            $perPage = $request->input('per_page', 6);
            $equipment = $query->orderBy('equipment_name')->paginate($perPage);

            $formatted = $equipment->getCollection()->map(function ($item) {
                $availableCount = $item->items
                    ->filter(fn($i) => $i->status_id == 1 && in_array($i->condition_id, [1, 2, 3]))
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
            \Log::error('Error fetching public equipment', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch equipment data', 'error' => $e->getMessage()], 500);
        }
    }

    public function getEquipmentDetails($id): JsonResponse
    {
        try {
            if (!$id) {
                return response()->json(['success' => false, 'message' => 'Equipment ID is required'], 400);
            }

            $equipment = Equipment::with(['category', 'status', 'departments', 'images'])
                ->withCount([
                    'items as total_quantity' => fn($query) => $query->where('status_id', '!=', 5),
                    'items as available_quantity' => fn($query) => $query->where('status_id', 1)->whereIn('condition_id', [1, 2, 3])
                ])
                ->find($id);

            if (!$equipment) {
                return response()->json(['success' => false, 'message' => 'Equipment not found'], 404);
            }

            $formattedEquipment = $this->formatPublicEquipment($equipment);
            $formattedEquipment['available_quantity'] = $equipment->available_quantity;
            $formattedEquipment['total_quantity'] = $equipment->total_quantity;
            $formattedEquipment['images'] = $equipment->images;

            return response()->json(['success' => true, 'data' => $formattedEquipment]);

        } catch (\Exception $e) {
            \Log::error('Error fetching equipment details', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch equipment details'], 500);
        }
    }

    // ----- EQUIPMENT MANAGEMENT SECTION ----- //

    /**
     * Create a new equipment record with optional inline items and department links.
     * Images are uploaded separately after creation via POST /admin/equipment/{id}/images
     * (parallel from the client), so they are not accepted in this payload.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'equipment_name' => 'required|string|max:100',
            'description'    => 'nullable|string|max:255',
            'brand'          => 'nullable|string|max:80',
            'storage_location' => 'required|string|max:50',
            'category_id'    => 'required|exists:equipment_categories,category_id',
            'base_fee'       => 'required|numeric|min:0',
            'rate_type'      => 'required|in:Per Hour,Per Event',
            'status_id'      => 'required|exists:availability_statuses,status_id',

            'departments'    => 'required|array|min:1',
            'departments.*'  => 'exists:departments,department_id',

            'items'                       => 'sometimes|array',
            'items.*.item_name'           => 'required_with:items|string|max:50',
            'items.*.condition_id'        => 'required_with:items|exists:conditions,condition_id',
            'items.*.barcode_number'      => 'nullable|string|max:20',
            'items.*.item_notes'          => 'nullable|string|max:100',
        ]);

        $admin = auth()->user();

        try {
            $equipment = \DB::transaction(function () use ($data, $admin) {
                $eq = Equipment::create([
                    'equipment_name'   => $data['equipment_name'],
                    'description'      => $data['description'] ?? 'No description provided for this equipment.',
                    'brand'            => $data['brand'] ?? null,
                    'storage_location' => $data['storage_location'],
                    'category_id'      => $data['category_id'],
                    'base_fee'         => $data['base_fee'],
                    'rate_type'        => $data['rate_type'],
                    'status_id'        => $data['status_id'],
                    'created_by'       => $admin->admin_id,
                ]);

                $eq->departments()->attach($data['departments']);

                if (!empty($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $eq->items()->create([
                            'item_name'      => $item['item_name'],
                            'condition_id'   => $item['condition_id'],
                            'barcode_number' => $item['barcode_number'] ?? $this->generateBarcode(),
                            'item_notes'     => $item['item_notes'] ?? 'No notes provided for this asset.',
                            'status_id'      => 1,
                            'created_by'     => $admin->admin_id,
                        ]);
                    }
                }

                return $eq;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'barcode_number')) {
                return response()->json([
                    'message' => 'A duplicate barcode was generated. Please try again.',
                ], 422);
            }
            if (str_contains($e->getMessage(), 'equipment_name')) {
                return response()->json([
                    'message' => 'This equipment name is already taken. Please choose another.',
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Equipment created successfully',
            'data'    => $this->formatEquipment($equipment->fresh())
        ], 201);
    }

    public function show($id)
    {
        try {
            $equipment = Equipment::with([
                'category', 
                'status', 
                'departments', 
                'images' => function($q) { $q->orderBy('sort_order'); }, 
                'items.condition'
            ])->findOrFail($id);
            
            return response()->json(['data' => $equipment]);
        } catch (\Exception $e) {
            \Log::error('Error fetching equipment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch equipment', 'error' => $e->getMessage()], 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'equipment_name'   => 'required|string|max:100',
                'description'      => 'nullable|string|max:255',
                'brand'            => 'nullable|string|max:80',
                'storage_location' => 'required|string|max:50',
                'category_id'      => 'required|exists:equipment_categories,category_id',
                'base_fee'         => 'required|numeric|min:0',
                'rate_type'        => 'required|in:Per Hour,Per Event',
                'status_id'        => 'required|exists:availability_statuses,status_id',
                'departments'      => 'required|array|min:1',
                'departments.*'    => 'exists:departments,department_id',
            ]);

            $equipment = Equipment::findOrFail($id);
            $admin = auth()->user();

            \DB::transaction(function () use ($equipment, $validated, $admin) {
                $equipment->update([
                    'equipment_name'   => $validated['equipment_name'],
                    'description'      => $validated['description'] ?? null,
                    'brand'            => $validated['brand'] ?? null,
                    'storage_location' => $validated['storage_location'],
                    'category_id'      => $validated['category_id'],
                    'base_fee'         => $validated['base_fee'],
                    'rate_type'        => $validated['rate_type'],
                    'status_id'        => $validated['status_id'],
                    'updated_by'       => $admin->admin_id,
                ]);

                $equipment->departments()->sync($validated['departments']);
            });

            return response()->json([
                'message' => 'Equipment updated successfully',
                'data'    => $equipment->fresh(['departments'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating equipment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update equipment', 'error' => $e->getMessage()], 500);
        }
    }

    public function getEquipmentWithSelected(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter');
        $page = $request->input('page', 1);
        $perPage = 10;

        $query = Equipment::join('availability_statuses', 'equipment.status_id', '=', 'availability_statuses.status_id')
            ->select('equipment.equipment_id', 'equipment.equipment_name', 'availability_statuses.status_name');

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

        $filteredItems = $equipment->items();
        $filteredItems = array_filter($filteredItems, fn($e) => $e->status_name !== 'Hidden');

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

        $query = Equipment::join('availability_statuses', 'equipment.status_id', '=', 'availability_statuses.status_id')
            ->select('equipment.equipment_id', 'equipment.equipment_name', 'availability_statuses.status_name')
            ->when($search, fn($q) => $q->where('equipment.equipment_name', 'like', "%{$search}%"));

        if ($filter) {
            [$type, $id] = explode(':', $filter);
            if ($type === 'category') {
                $query->where('equipment.category_id', $id);
            }
        }

        return response()->json($query->orderBy('equipment.equipment_name')->paginate(perPage: 10, page: $request->input('page', 1)));
    }

    public function getEquipmentForMassAssign(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));

        $query = Equipment::query()
            ->select('equipment_id', 'equipment_name', 'brand')
            ->when($search !== '', function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where(fn($qq) => $qq->where('equipment_name', 'ILIKE', $like)->orWhere('brand', 'ILIKE', $like));
            })
            ->orderBy('equipment_name');

        return response()->json($query->paginate(perPage: 10, page: $request->input('page', 1)));
    }

    public function massAssignDepartments(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'equipment_ids'   => 'required|array|min:1',
                'equipment_ids.*' => 'exists:equipment,equipment_id',
                'department_id'   => 'required|exists:departments,department_id',
            ]);

            $equipmentIds = $validated['equipment_ids'];
            $departmentId = $validated['department_id'];

            $results = ['success' => [], 'failed' => []];

            foreach ($equipmentIds as $equipmentId) {
                try {
                    $equipment = Equipment::findOrFail($equipmentId);
                    $equipment->departments()->sync([$departmentId]);

                    $results['success'][] = [
                        'equipment_id'   => $equipmentId,
                        'equipment_name' => $equipment->equipment_name
                    ];
                } catch (\Exception $e) {
                    \Log::error('Failed to mass assign department to equipment', ['equipment_id' => $equipmentId, 'error' => $e->getMessage()]);
                    $results['failed'][] = ['equipment_id' => $equipmentId, 'error' => $e->getMessage()];
                }
            }

            $successCount = count($results['success']);
            $failedCount  = count($results['failed']);

            if ($successCount === 0) {
                $message = "Failed to assign department to any equipment.";
            } else if ($failedCount === 0) {
                $message = "Successfully assigned department to " . ($successCount === 1 ? "1 equipment" : "{$successCount} equipment items");
            } else {
                $message = "Assigned department to {$successCount} " . ($successCount === 1 ? "equipment" : "equipment items") . ", failed for {$failedCount}";
            }

            return response()->json(['message' => $message, 'results' => $results]);
        } catch (\Exception $e) {
            \Log::error('Error in mass department assignment', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to process mass department assignment', 'error' => $e->getMessage()], 500);
        }
    }

    public function edit(Request $request)
    {
        $equipmentId = $request->query('id');
        if (!$equipmentId) return redirect('/admin/manage-equipment')->with('error', 'No equipment ID provided');
        return view('admin.edit-equipment', ['equipmentId' => $equipmentId]);
    }

    /**
     * Delete equipment and its image files from public disk.
     * Items cascade via FK; image rows cascade via FK but files must be removed manually.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $equipment = Equipment::with('images')->findOrFail($id);

            foreach ($equipment->images as $image) {
                $relativePath = str_replace('/storage/', '', $image->image_url);
                if (!empty($relativePath) && !str_contains($relativePath, 'placeholder-image')) {
                    Storage::disk('public')->delete($relativePath);
                }
            }

            $equipment->delete();
            \Log::info('Equipment deleted successfully', ['equipment_id' => $id]);
            return response()->json(['message' => 'Equipment deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Equipment not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Error deleting equipment', ['equipment_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete equipment: ' . $e->getMessage()], 500);
        }
    }

    // ----- Images ----- //

    /**
     * Upload one equipment image (server-side, public disk).
     * The client sends the exact image_type and sort_order (its position in the UI) with each file.
     */
    public function uploadImage(Request $request, $equipmentId): JsonResponse
    {
        $validated = $request->validate([
            'image'       => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'description' => 'nullable|string|max:80',
            'image_type'  => 'required|in:Primary,Secondary',
            'sort_order'  => 'required|integer|min:0',
        ]);

        $equipment = Equipment::findOrFail($equipmentId);

        $path = $request->file('image')->store("equipment-photos/{$equipment->equipment_id}", 'public');

        $image = $equipment->images()->create([
            'image_url'   => '/storage/' . $path,
            'image_type'  => $validated['image_type'],
            'description' => $validated['description'] ?? 'Equipment photo',
            'sort_order'  => $validated['sort_order'],
        ]);

        return response()->json([
            'message'    => 'Image uploaded successfully',
            'image_id'   => $image->image_id,
            'image_url'  => $image->image_url,
            'image_type' => $image->image_type,
            'sort_order' => $image->sort_order,
        ]);
    }

    /**
     * Save the layout the user arranged: writes the sort_order and image_type
     * sent by the client for each existing image. No auto-sorting or auto-promotion.
     */
    public function reorderImages(Request $request, $equipmentId): JsonResponse
    {
        $validated = $request->validate([
            'images' => 'required|array|min:1',
            'images.*.image_id' => 'required|integer|exists:equipment_images,image_id',
            'images.*.sort_order' => 'required|integer|min:0',
            'images.*.image_type' => 'required|in:Primary,Secondary',
        ]);

        $equipment = Equipment::findOrFail($equipmentId);

        \DB::transaction(function () use ($equipment, $validated) {
            foreach ($validated['images'] as $row) {
                $equipment->images()
                    ->where('image_id', $row['image_id'])
                    ->update([
                        'sort_order' => $row['sort_order'],
                        'image_type' => $row['image_type'],
                    ]);
            }
        });

        return response()->json(['message' => 'Images reordered successfully']);
    }

    /**
     * Delete one image and its file. Order and primary status are not touched here;
     * the client sends the final layout via reorderImages on save.
     */
    public function deleteImage($equipmentId, $imageId): JsonResponse
    {
        try {
            $equipment = Equipment::findOrFail($equipmentId);
            $image = $equipment->images()->findOrFail($imageId);

            // Delete file from storage, unless it's the default placeholder
            $relativePath = str_replace('/storage/', '', $image->image_url);
            if (!empty($relativePath) && !str_contains($relativePath, 'placeholder-image')) {
                Storage::disk('public')->delete($relativePath);
            }

            $image->delete();

            return response()->json(['message' => 'Image deleted successfully']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Image or equipment not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Error in deleteImage', [
                'error' => $e->getMessage(),
                'equipmentId' => $equipmentId,
                'imageId' => $imageId,
            ]);
            return response()->json([
                'message' => 'Failed to delete image: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ----- Formatting ----- //

    private function formatEquipment($equipment): array
    {
        $equipment->load(['category', 'status', 'departments', 'items', 'images']);

        return [
            'equipment_id'     => $equipment->equipment_id,
            'equipment_name'   => $equipment->equipment_name,
            'description'      => $equipment->description,
            'brand'            => $equipment->brand,
            'storage_location' => $equipment->storage_location,
            'category'         => [
                'category_id'   => $equipment->category_id,
                'category_name' => $equipment->category->category_name,
            ],
            'base_fee'  => $equipment->base_fee,
            'rate_type' => $equipment->rate_type,
            'status'    => [
                'status_id'   => $equipment->status_id,
                'status_name' => $equipment->status->status_name,
                'color_code'  => $equipment->status->color_code,
            ],
            'departments' => $equipment->departments->map(fn($d) => [
                'department_id'   => $d->department_id,
                'department_name' => $d->department_name
            ])->values()->toArray(),
            'items'      => $equipment->items,
            'images'     => $equipment->images,
            'created_at' => $equipment->created_at,
            'updated_at' => $equipment->updated_at,
        ];
    }

    private function formatPublicEquipment($equipment): array
    {
        $equipment->load(['category', 'status', 'departments', 'items.condition', 'images']);

        return [
            'equipment_id'     => $equipment->equipment_id,
            'equipment_name'   => $equipment->equipment_name,
            'description'      => $equipment->description,
            'brand'            => $equipment->brand,
            'storage_location' => $equipment->storage_location,
            'category'         => [
                'category_id'   => $equipment->category_id,
                'category_name' => $equipment->category->category_name,
            ],
            'base_fee'  => $equipment->base_fee,
            'rate_type' => $equipment->rate_type,
            'status'    => [
                'status_id'   => $equipment->status_id,
                'status_name' => $equipment->status->status_name,
                'color_code'  => $equipment->status->color_code,
            ],
            'departments' => $equipment->departments->map(fn($d) => [
                'department_id'   => $d->department_id,
                'department_name' => $d->department_name
            ])->values()->toArray(),
            'items'  => $equipment->items,
            'images' => $equipment->images,
        ];
    }

    // ----- EQUIPMENT ITEMS ----- //

    public function getItems($equipmentId): JsonResponse
    {
        try {
            $items = EquipmentItem::with(['condition'])
                ->where('equipment_id', $equipmentId)
                ->orderBy('item_name')
                ->get();

            return response()->json(['data' => $items]);
        } catch (\Exception $e) {
            \Log::error('Error fetching equipment items', ['error' => $e->getMessage(), 'equipmentId' => $equipmentId]);
            return response()->json(['message' => 'Failed to fetch equipment items', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create an item. Accepts multipart with optional `image` file — stored server-side
     * and written to image_url in this same request. Barcode is auto-generated when empty.
     */
    public function storeItem(Request $request, $equipmentId)
    {
        try {
            $admin = auth()->user();

            $validatedData = $request->validate([
                'item_name'      => 'required|string|max:50',
                'condition_id'   => 'required|exists:conditions,condition_id',
                'barcode_number' => 'nullable|string|max:20',
                'item_notes'     => 'nullable|string|max:100',
                'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            ]);

            $imageUrl = '/assets/placeholder-image.png';

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store("equipment-items/{$equipmentId}", 'public');
                $imageUrl = '/storage/' . $path;
            }

            $item = EquipmentItem::create([
                'equipment_id'   => $equipmentId,
                'item_name'      => $validatedData['item_name'],
                'condition_id'   => $validatedData['condition_id'],
                                'barcode_number' => $validatedData['barcode_number'] ?? null, 
                'item_notes'     => $validatedData['item_notes'] ?? 'No notes provided for this asset.',
                'image_url'      => $imageUrl,
                'status_id'      => 1,
                'created_by'     => $admin->admin_id,
            ]);

            return response()->json(['message' => 'Item added successfully', 'item' => $item->load('condition')]);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'barcode_number')) {
                return response()->json([
                    'message' => 'Barcode already exists. Please try saving again to generate a new one.',
                ], 422);
            }
            throw $e;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to create equipment item', ['equipment_id' => $equipmentId, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to add item', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an item. If a new `image` file is uploaded, it replaces the stored image_url
     * and deletes the prior file (except the placeholder).
     */
    public function updateItem(Request $request, $equipmentId, $itemId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'item_name'      => 'required|string|max:50',
                'condition_id'   => 'required|exists:conditions,condition_id',
                'barcode_number' => 'nullable|string|max:20',
                'item_notes'     => 'nullable|string|max:100',
                'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            ]);

            $item = EquipmentItem::where('equipment_id', $equipmentId)->where('item_id', $itemId)->firstOrFail();

            $imageUrl = $item->image_url;

            if ($request->hasFile('image')) {
                // Delete prior file if it was a server-stored upload
                $oldRelative = str_replace('/storage/', '', (string) $item->image_url);
                if (!empty($oldRelative) && !str_contains($oldRelative, 'placeholder-image')) {
                    Storage::disk('public')->delete($oldRelative);
                }

                $path = $request->file('image')->store("equipment-items/{$equipmentId}", 'public');
                $imageUrl = '/storage/' . $path;
            }

            $item->update([
                'item_name'      => $validated['item_name'],
                'condition_id'   => $validated['condition_id'],
                'barcode_number' => $validated['barcode_number'] ?? $item->barcode_number,
                'item_notes'     => $validated['item_notes'] ?? $item->item_notes,
                'image_url'      => $imageUrl,
                'updated_by'     => auth()->user()->admin_id,
            ]);

            return response()->json(['message' => 'Item updated successfully', 'data' => $item->fresh()->load('condition')]);

        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'barcode_number')) {
                return response()->json(['message' => 'Barcode already exists.'], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error updating equipment item', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update item', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteItem($equipmentId, $itemId)
    {
        try {
            $item = EquipmentItem::where('equipment_id', $equipmentId)->where('item_id', $itemId)->firstOrFail();

            // Remove stored file (skip placeholder)
            $relativePath = str_replace('/storage/', '', (string) $item->image_url);
            if (!empty($relativePath) && !str_contains($relativePath, 'placeholder-image')) {
                Storage::disk('public')->delete($relativePath);
            }

            $item->delete();
            \Log::info('Equipment item deleted successfully', ['item_id' => $itemId, 'equipment_id' => $equipmentId]);
            return response()->json(['message' => 'Item deleted successfully']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Item not found', 'details' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to delete equipment item', ['item_id' => $itemId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete item', 'details' => $e->getMessage()], 500);
        }
    }

    // ----- Helpers ----- //

    /**
     * Generate an EQ-XXXXXXXX style barcode (10 chars, fits the varchar(20) unique column).
     * Uniqueness is ultimately enforced by the DB; callers catch QueryException.
     */
    private function generateBarcode(): string
    {
        // 10 chars: randomized uppercase alphanumeric
        $randomPart = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 2)), 0, 10);
        return 'EQ-' . $randomPart;
    }
}