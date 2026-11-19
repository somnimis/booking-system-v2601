<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Department;
use App\Models\LookupTables\AvailabilityStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;


class FacilityController extends Controller
{
    // ----- Index - Show all facilities ----- //

    public function getFacilitiesWithSelected(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter');
        $page = $request->input('page', 1);
        $perPage = 10;

        // Get facilities with pagination
        $query = Facility::select(
            'facility_id',
            'parent_facility_id',
            'facility_code',
            'facility_name'
        );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('facility_name', 'like', "%{$search}%")
                    ->orWhere('facility_code', 'like', "%{$search}%");
            });
        }

        if ($filter) {
            [$type, $id] = explode(':', $filter);
            if ($type === 'category') {
                $query->where('category_id', $id);
            }
            if ($type === 'subcategory') {
                $query->where('subcategory_id', $id);
            }
        }

        $facilities = $query->orderBy('facility_name')->paginate($perPage, ['*'], 'page', $page);

        // Get selected item IDs from session
        $selectedItems = session('selected_items', []);
        $selectedFacilityIds = collect($selectedItems)
            ->filter(fn($item) => $item['type'] === 'facility')
            ->pluck('facility_id')
            ->toArray();

        // Get parent names for buildings
        $parentIds = $facilities->pluck('parent_facility_id')->filter()->unique()->toArray();
        $parentNames = [];
        if (!empty($parentIds)) {
            $parentNames = Facility::whereIn('facility_id', $parentIds)
                ->pluck('facility_name', 'facility_id')
                ->toArray();
        }

        return response()->json([
            'data' => $facilities->items(),
            'current_page' => $facilities->currentPage(),
            'last_page' => $facilities->lastPage(),
            'total' => $facilities->total(),
            'per_page' => $facilities->perPage(),
            'selected_ids' => $selectedFacilityIds,
            'parent_names' => $parentNames,
        ]);
    }

    /**
     * Lightweight conflict check used by the Add Facility UI when creating a new room tab.
     * Ensures the proposed facility_name and facility_code are not already taken in the DB.
     * Case-insensitive; empty facility_code is allowed and skipped.
     */
    public function checkConflict(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'facility_name' => 'required|string|max:50',
            'facility_code' => 'nullable|string|max:20',
        ]);

        $conflicts = [];

        $nameTaken = Facility::whereRaw('LOWER(facility_name) = ?', [mb_strtolower($validated['facility_name'])])
            ->exists();

        if ($nameTaken) {
            $conflicts['name'] = 'Facility name is already taken.';
        }

        $code = trim($validated['facility_code'] ?? '');
        if ($code !== '') {
            $codeTaken = Facility::whereRaw('LOWER(facility_code) = ?', [mb_strtolower($code)])->exists();
            if ($codeTaken) {
                $conflicts['code'] = 'Facility code is already taken.';
            }
        }

        return response()->json([
            'available' => empty($conflicts),
            'conflicts' => $conflicts,
        ]);
    }

    public function getFacilitiesForDropdown(Request $request)
    {
        $search = $request->input('search');
        $parentFacilityId = $request->input('parent_facility_id');
        $filter = $request->input('filter');
        $role = $request->input('role'); // 'container' → only top-level facilities

        $query = Facility::select(
            'facility_id',
            'parent_facility_id',
            'facility_code',
            'facility_name'
        );

        $query->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('facility_name', 'ILIKE', "%{$search}%")
                    ->orWhere('facility_code', 'ILIKE', "%{$search}%");
            });
        });

        $query->when($parentFacilityId, function ($query) use ($parentFacilityId) {
            $query->where('parent_facility_id', $parentFacilityId);
        });

        if ($role === 'container') {
            $query->where('facility_role', 'Container');
        }

        if ($filter) {
            [$type, $id] = explode(':', $filter);

            if ($type === 'category') {
                $query->where('category_id', $id);
            }

            if ($type === 'subcategory') {
                $query->where('subcategory_id', $id);
            }
        }

        $perPage = $request->input('per_page', 10); // Default to 10, but allow overrides

        return $query
            ->orderBy('facility_name')
            ->paginate($perPage);
    }

    public function publicIndex(): JsonResponse
    {
        try {
            $facilities = Facility::with([
                'category',
                'subcategory',
                'status',
                'department',
                'images'
            ])->orderBy('facility_name')->get();

            $formatted = $facilities->map(function ($facility) {
                return $this->formatPublicFacility($facility);
            });

            return response()->json(['data' => $formatted]);
        } catch (\Exception $e) {
            \Log::error('Error fetching public facilities', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to fetch facilities data',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // === PARENT FACILITIES API ENDPOINT (UPDATED) === //
    public function publicParentFacilities(Request $request): JsonResponse
    {
        try {
            // Remove 'department' from eager loading since it's legacy
            $query = Facility::with([
                'category',
                'subcategory',
                'status',
                'images',
                'childFacilities'
            ])->whereNull('parent_facility_id');

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('facility_name', 'LIKE', "%{$search}%")
                        ->orWhere('location_note', 'LIKE', "%{$search}%");
                });
            }

            // Apply pagination
            $perPage = $request->input('per_page', 12);
            $facilities = $query->orderBy('facility_name')->paginate($perPage);

            $formatted = $facilities->getCollection()->map(function ($facility) {
                $formattedFacility = $this->formatPublicFacility($facility);
                $formattedFacility['has_children'] = $facility->childFacilities->count() > 0;
                return $formattedFacility;
            });

            return response()->json([
                'success' => true,
                'data' => $formatted,
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
            \Log::error('Error fetching parent facilities', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch facilities data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // === FACILITY DETAILS API ENDPOINT === //
    public function getFacilityDetails($id): JsonResponse
    {
        try {
            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facility ID is required'
                ], 400);
            }

            $facility = Facility::with([
                'category',
                'subcategory',
                'status',
                'department',
                'images',
                'amenities',
                'parentFacility',
                'childFacilities'
            ])->find($id);

            if (!$facility) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facility not found'
                ], 404);
            }

            $formattedFacility = $this->formatPublicFacility($facility);
            $formattedFacility['has_children'] = $facility->childFacilities->count() > 0;
            $formattedFacility['parent_facility_name'] = $facility->parentFacility?->facility_name;
            $formattedFacility['amenities'] = $facility->amenities->map(function ($amenity) {
                return [
                    'amenity_id' => $amenity->amenity_id,
                    'amenity_name' => $amenity->amenity_name
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedFacility
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching facility details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch facility details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // === FORMAT PUBLIC FACILITY (NO DEPARTMENT) === //
    private function formatPublicFacility($facility)
    {
        return [
            'facility_id' => $facility->facility_id,
            'parent_facility_id' => $facility->parent_facility_id,
            'facility_name' => $facility->facility_name,
            'description' => $facility->description,
            'location_note' => $facility->location_note ?? 'Location TBA',
            'capacity' => $facility->capacity,
            'location_type' => $facility->location_type,
            'base_fee' => $facility->base_fee,
            'rate_type' => $facility->rate_type,
            'floor_level' => $facility->floor_level,
            'total_levels' => $facility->total_levels,
            'facility_code' => $facility->facility_code,
            'maximum_rental_hour' => $facility->maximum_rental_hour,

            // Safely handle relationships using nullsafe operator
            'category_name' => $facility->category?->category_name ?? 'Uncategorized',
            'subcategory_name' => $facility->subcategory?->subcategory_name,
            'status_name' => $facility->status?->status_name,

            // Images
            'images' => $facility->images->map(fn($image) => [
                'image_url' => $image->image_url,
                'is_primary' => $image->is_primary ?? false
            ])->values()->toArray(),
        ];
    }
    // ----- Create - Show add facility form ----- //
    public function create()
    {
        // Just get departments and statuses first
        $departments = Department::all();
        $statuses = AvailabilityStatus::all();

        // Return with empty categories for now
        $categories = [];
        $subcategories = [];

        return view('admin.add-facility', compact('categories', 'subcategories', 'departments', 'statuses'));
    }

    // ----- Store - Save new facility ----- //
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'facility_name' => [
                    'required', 'string', 'max:50',
                    function ($attribute, $value, $fail) {
                        if (\App\Models\Facility::whereRaw('LOWER(facility_name) = ?', [mb_strtolower($value)])->exists()) {
                            $fail('Facility name is already taken.');
                        }
                    }
                ],
                'facility_code' => [
                    'nullable', 'string', 'max:20',
                    function ($attribute, $value, $fail) {
                        if ($value && \App\Models\Facility::whereRaw('LOWER(facility_code) = ?', [mb_strtolower($value)])->exists()) {
                            $fail('Facility code is already taken.');
                        }
                    }
                ],
                'description' => 'nullable|string|max:250',
                'location_note' => 'nullable|string|max:200',
                'capacity' => 'required|integer|min:1',
                'category_id' => 'required|exists:facility_categories,category_id',
                'subcategory_id' => 'nullable|exists:facility_subcategories,subcategory_id',
                'departments' => 'required|array|min:1',
                'departments.*' => 'exists:departments,department_id',
                'location_type' => 'required|in:Indoors,Outdoors',
                'base_fee' => 'required|numeric|min:0',
                'rate_type' => 'required|in:Per Hour,Per Event',
                'status_id' => 'required|exists:availability_statuses,status_id',
                'parent_facility_id' => 'nullable|exists:facilities,facility_id',
                'floor_level' => 'nullable|integer|min:1',
                'total_levels' => 'nullable|integer|min:1',
                'facility_role' => 'required|in:Venue,Container,Room',
                'created_by' => 'required|exists:admins,admin_id',
            ]);

            // Enforce hierarchy rules based on facility_role
            $parentId = null;

            if ($data['facility_role'] === 'Room') {
                if (empty($data['parent_facility_id'])) {
                    return response()->json([
                        'message' => 'A parent container building is required when creating a Room.',
                    ], 422);
                }

                $parent = Facility::find($data['parent_facility_id']);
                if (!$parent || $parent->facility_role !== 'Container') {
                    return response()->json([
                        'message' => 'The selected parent facility must be a Container building.',
                    ], 422);
                }

                $parentId = $parent->facility_id;
            }

            // Room cannot be a parent; only Rooms can have a parent
            if ($data['facility_role'] !== 'Room' && !empty($data['parent_facility_id'])) {
                return response()->json([
                    'message' => 'Only Rooms can have a parent facility.',
                ], 422);
            }

            $facility = \DB::transaction(function () use ($data, $parentId) {
                $facility = Facility::create([
                    'facility_name' => $data['facility_name'],
                    'facility_role' => $data['facility_role'],
                    'description' => $data['description'] ?? null,
                    'location_note' => $data['location_note'] ?? null,
                    'capacity' => $data['capacity'],
                    'category_id' => $data['category_id'],
                    'subcategory_id' => $data['subcategory_id'] ?? null,
                    'department_id' => $data['departments'][0],
                    'location_type' => $data['location_type'],
                    'base_fee' => $data['base_fee'],
                    'rate_type' => $data['rate_type'],
                    'status_id' => $data['status_id'],
                    'parent_facility_id' => $parentId,
                    'floor_level' => $data['floor_level'] ?? null,
                    'facility_code' => $data['facility_code'] ?? null,
                    'total_levels' => $data['total_levels'] ?? null,
                    'created_by' => $data['created_by'],
                ]);

                $facility->departments()->attach($data['departments']);

                return $facility;
            });

            \Log::info('Facility created successfully', [
                'facility_id' => $facility->facility_id,
                'created_by' => $data['created_by'],
                'departments' => $data['departments'],
                'facility_role' => $data['facility_role'],
            ]);

            return response()->json([
                'message' => 'Facility created successfully!',
                'data' => [
                    'facility_id' => $facility->facility_id,
                    'facility_name' => $facility->facility_name,
                    'facility_role' => $facility->facility_role,
                    'departments' => $facility->departments,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error creating facility', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'token']),
            ]);

            return response()->json([
                'message' => 'Failed to create facility',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

        /**
     * Batch-create a Container facility plus its Rooms in a single transaction.
     * The Main facility is always a Container here (only Container allows rooms).
     * Each room inherits departments from the container unless explicitly provided.
     */
    public function batchStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'facility'   => 'required|array',
                'facility.*' => 'required',
                'rooms'      => 'present|array',
                'rooms.*.facility_name' => 'required|string|max:50',
                'rooms.*.facility_code' => 'nullable|string|max:20',
            ]);

            $facilityData = $validated['facility'];
            $roomsData    = $validated['rooms'] ?? [];

            // ---- Validate main facility payload (reuses the same rules as store) ----
            $main = \Validator::make($facilityData, [
                'facility_name' => [
                    'required', 'string', 'max:50',
                    function ($attribute, $value, $fail) {
                        if (Facility::whereRaw('LOWER(facility_name) = ?', [mb_strtolower($value)])->exists()) {
                            $fail('Facility name is already taken.');
                        }
                    }
                ],
                'facility_code' => [
                    'nullable', 'string', 'max:20',
                    function ($attribute, $value, $fail) {
                        if ($value && Facility::whereRaw('LOWER(facility_code) = ?', [mb_strtolower($value)])->exists()) {
                            $fail('Facility code is already taken.');
                        }
                    }
                ],
                'description'    => 'nullable|string|max:250',
                'location_note'  => 'nullable|string|max:200',
                'capacity'       => 'required|integer|min:1',
                'category_id'    => 'required|exists:facility_categories,category_id',
                'subcategory_id' => 'nullable|exists:facility_subcategories,subcategory_id',
                'departments'    => 'required|array|min:1',
                'departments.*'  => 'exists:departments,department_id',
                'location_type'  => 'required|in:Indoors,Outdoors',
                'base_fee'       => 'required|numeric|min:0',
                'rate_type'      => 'required|in:Per Hour,Per Event',
                'status_id'      => 'required|exists:availability_statuses,status_id',
                'floor_level'    => 'nullable|integer|min:1',
                'total_levels'   => 'nullable|integer|min:1',
                'facility_role'  => 'required|in:Container',
                'created_by'     => 'required|exists:admins,admin_id',
            ])->validate();

            // ---- Validate each room ----
            $roomRules = [
                'facility_name' => [
                    'required', 'string', 'max:50',
                    function ($attribute, $value, $fail) {
                        if (Facility::whereRaw('LOWER(facility_name) = ?', [mb_strtolower($value)])->exists()) {
                            $fail('Facility name is already taken.');
                        }
                    }
                ],
                'facility_code' => [
                    'nullable', 'string', 'max:20',
                    function ($attribute, $value, $fail) {
                        if ($value && Facility::whereRaw('LOWER(facility_code) = ?', [mb_strtolower($value)])->exists()) {
                            $fail('Facility code is already taken.');
                        }
                    }
                ],
                'description'     => 'nullable|string|max:250',
                'location_note'   => 'nullable|string|max:200',
                'capacity'        => 'required|integer|min:1',
                'category_id'     => 'required|exists:facility_categories,category_id',
                'subcategory_id'  => 'nullable|exists:facility_subcategories,subcategory_id',
                'departments'     => 'nullable|array',
                'departments.*'   => 'exists:departments,department_id',
                'location_type'   => 'required|in:Indoors,Outdoors',
                'base_fee'        => 'required|numeric|min:0',
                'rate_type'       => 'required|in:Per Hour,Per Event',
                'status_id'       => 'required|exists:availability_statuses,status_id',
                'floor_level'     => 'nullable|integer|min:1',
                'total_levels'    => 'nullable|integer|min:1',
            ];
            $validatedRooms = [];
            foreach ($roomsData as $index => $room) {
                $validatedRooms[$index] = \Validator::make($room, $roomRules)->validate();
            }

            // ---- Atomic write ----
            $result = \DB::transaction(function () use ($main, $validatedRooms) {
                $container = Facility::create([
                    'facility_name'      => $main['facility_name'],
                    'facility_role'      => 'Container',
                    'description'        => $main['description'] ?? null,
                    'location_note'      => $main['location_note'] ?? null,
                    'capacity'           => $main['capacity'],
                    'category_id'        => $main['category_id'],
                    'subcategory_id'     => $main['subcategory_id'] ?? null,
                    'location_type'      => $main['location_type'],
                    'base_fee'           => $main['base_fee'],
                    'rate_type'          => $main['rate_type'],
                    'status_id'          => $main['status_id'],
                    'parent_facility_id' => null,
                    'floor_level'        => $main['floor_level'] ?? null,
                    'facility_code'      => $main['facility_code'] ?? null,
                    'total_levels'       => $main['total_levels'] ?? null,
                    'created_by'         => $main['created_by'],
                ]);

                $container->departments()->attach($main['departments']);

                $createdRooms = [];
                foreach ($validatedRooms as $room) {
                    $roomDepartments = !empty($room['departments'])
                        ? $room['departments']
                        : $main['departments']; // inherit from container

                    $createdRoom = Facility::create([
                        'facility_name'      => $room['facility_name'],
                        'facility_role'      => 'Room',
                        'description'        => $room['description'] ?? null,
                        'location_note'      => $room['location_note'] ?? null,
                        'capacity'           => $room['capacity'],
                        'category_id'        => $room['category_id'],
                        'subcategory_id'     => $room['subcategory_id'] ?? null,
                        'location_type'      => $room['location_type'],
                        'base_fee'           => $room['base_fee'],
                        'rate_type'          => $room['rate_type'],
                        'status_id'          => $room['status_id'],
                        'parent_facility_id' => $container->facility_id,
                        'floor_level'        => $room['floor_level'] ?? null,
                        'facility_code'      => $room['facility_code'] ?? null,
                        'total_levels'       => $room['total_levels'] ?? null,
                        'created_by'         => $main['created_by'],
                    ]);

                    $createdRoom->departments()->attach($roomDepartments);

                    $createdRooms[] = [
                        'facility_id'   => $createdRoom->facility_id,
                        'facility_name' => $createdRoom->facility_name,
                    ];
                }

                return ['container' => $container, 'rooms' => $createdRooms];
            });

            \Log::info('Batch facility creation succeeded', [
                'container_id' => $result['container']->facility_id,
                'room_count'   => count($result['rooms']),
                'created_by'   => $main['created_by'],
            ]);

            return response()->json([
                'message' => 'Facility and rooms created successfully!',
                'data'    => [
                    'facility_id'   => $result['container']->facility_id,
                    'facility_name' => $result['container']->facility_name,
                    'facility_role' => 'Container',
                    'rooms'         => $result['rooms'],
                    'departments'   => $result['container']->departments,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error in batch facility creation', [
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'token']),
            ]);

            return response()->json([
                'message' => 'Failed to create facility batch',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ----- Edit - Show edit form ----- //
    public function edit(Request $request)
    {
        $facilityId = $request->query('id');

        if (!$facilityId) {
            return redirect('/admin/manage-facilities')->with('error', 'No facility ID provided');
        }

        return view('admin.edit-facility', ['facilityId' => $facilityId]);
    }

    // ----- Update - Save facility changes ----- //
    public function update(Request $request, $id)
    {
        $facility = Facility::findOrFail($id);

        $data = $request->validate([
            'facility_name' => [
                'required', 'string', 'max:50',
                function ($attribute, $value, $fail) use ($id) {
                    if (Facility::whereRaw('LOWER(facility_name) = ?', [mb_strtolower($value)])->where('facility_id', '!=', $id)->exists()) {
                        $fail('Facility name is already taken.');
                    }
                }
            ],
            'facility_code' => [
                'nullable', 'string', 'max:20',
                function ($attribute, $value, $fail) use ($id) {
                    if ($value && Facility::whereRaw('LOWER(facility_code) = ?', [mb_strtolower($value)])->where('facility_id', '!=', $id)->exists()) {
                        $fail('Facility code is already taken.');
                    }
                }
            ],
            'description' => 'nullable|string|max:250',
            'location_note' => 'nullable|string|max:200',
            'capacity' => 'required|integer|min:1',
            'category_id' => 'required|exists:facility_categories,category_id',
            'subcategory_id' => 'nullable|exists:facility_subcategories,subcategory_id',
            'departments' => 'required|array|min:1',
            'departments.*' => 'exists:departments,department_id',
            'location_type' => 'required|in:Indoors,Outdoors',
            'base_fee' => 'required|numeric|min:0',
            'rate_type' => 'required|in:Per Hour,Per Event',
            'status_id' => 'required|exists:availability_statuses,status_id',
            'parent_facility_id' => 'nullable|exists:facilities,facility_id',
            'floor_level' => 'nullable|integer|min:1',
            'total_levels' => 'nullable|integer|min:1',
            'facility_role' => 'required|in:Venue,Container,Room',
        ]);
        $parentId = null;

        if ($data['facility_role'] === 'Room') {
            if (empty($data['parent_facility_id'])) {
                return response()->json([
                    'message' => 'A parent container building is required when creating a Room.',
                ], 422);
            }

            $parent = Facility::find($data['parent_facility_id']);
            if (!$parent || $parent->facility_role !== 'Container' || $parent->facility_id == $facility->facility_id) {
                return response()->json([
                    'message' => 'The selected parent facility must be a Container building and cannot be itself.',
                ], 422);
            }

            $parentId = $parent->facility_id;
        }

        if ($data['facility_role'] !== 'Room' && !empty($data['parent_facility_id'])) {
            return response()->json([
                'message' => 'Only Rooms can have a parent facility.',
            ], 422);
        }

        $user = auth()->user();

        $facility->update([
            'facility_name' => $data['facility_name'],
            'facility_role' => $data['facility_role'],
            'description' => $data['description'] ?? null,
            'location_note' => $data['location_note'] ?? null,
            'capacity' => $data['capacity'],
            'category_id' => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'department_id' => $data['departments'][0],
            'location_type' => $data['location_type'],
            'base_fee' => $data['base_fee'],
            'rate_type' => $data['rate_type'],
            'status_id' => $data['status_id'],
            'parent_facility_id' => $parentId,
            'floor_level' => $data['floor_level'] ?? null,
            'facility_code' => $data['facility_code'] ?? null,
            'total_levels' => $data['total_levels'] ?? null,
            'updated_by' => $user->admin_id,
        ]);

        $facility->departments()->sync($data['departments']);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Facility updated successfully!',
                'data' => $facility->fresh(['departments']),
            ]);
        }

        return redirect()->route('admin.manage-facilities')
            ->with('success', 'Facility updated successfully!');
    }

    /**
     * Mass assign a department to multiple facilities (updates the department_facilities pivot).
     */
    public function massAssignDepartment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'facility_ids' => 'required|array|min:1',
                'facility_ids.*' => 'exists:facilities,facility_id',
                'department_id' => 'required|exists:departments,department_id',
            ]);

            $facilityIds = $validated['facility_ids'];
            $departmentId = $validated['department_id'];

            $results = [
                'success' => [],
                'failed' => []
            ];

            foreach ($facilityIds as $facilityId) {
                try {
                    $facility = Facility::findOrFail($facilityId);

                    // Replace all existing departments with the new one
                    $facility->departments()->sync([$departmentId]);

                    $results['success'][] = [
                        'facility_id' => $facilityId,
                        'facility_name' => $facility->facility_name
                    ];

                } catch (\Exception $e) {
                    \Log::error('Failed to assign department to facility', [
                        'facility_id' => $facilityId,
                        'department_id' => $departmentId,
                        'error' => $e->getMessage()
                    ]);

                    $results['failed'][] = [
                        'facility_id' => $facilityId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $successCount = count($results['success']);
            $failedCount = count($results['failed']);

            if ($successCount === 0) {
                $message = "Failed to assign department to any facilities.";
            } else if ($failedCount === 0) {
                $message = "Successfully assigned department to " . ($successCount === 1 ? "1 facility" : "{$successCount} facilities");
            } else {
                $message = "Assigned department to {$successCount} " . ($successCount === 1 ? "facility" : "facilities") .
                    ", failed for {$failedCount} " . ($failedCount === 1 ? "facility" : "facilities");
            }

            return response()->json([
                'message' => $message,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in mass department assignment for facilities', [
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

    // ----- Destroy - Delete facility ----- //

    public function destroy($facilityId)
    {
        $facility = Facility::with(['images'])->findOrFail($facilityId);

        $user = auth()->user();

        $facility->update(['deleted_by' => $user->admin_id]);

        foreach ($facility->images as $image) {
            $relativePath = str_replace('/storage/', '', $image->image_url);
            if (!empty($relativePath) && !str_contains($relativePath, 'placeholder-image')) {
                Storage::disk('public')->delete($relativePath);
            }
            $image->delete();
        }

        $facility->delete();

        return response()->json([
            'message' => 'Facility and associated images deleted successfully!',
            'facility_id' => $facilityId,
        ], 200);
    }


    // ----- Show - View single facility (optional) ----- //
    public function show($id)
    {
        try {
            $facility = Facility::with([
                'category',
                'subcategory',
                'status',
                'departments',
                'images'
            ])->findOrFail($id);

            return response()->json([
                'data' => [
                    'facility_id' => $facility->facility_id,
                    'facility_name' => $facility->facility_name,
                    'facility_code' => $facility->facility_code,
                    'facility_role' => $facility->facility_role,
                    'parent_facility_id' => $facility->parent_facility_id,
                    'category_id' => $facility->category_id,
                    'subcategory_id' => $facility->subcategory_id,
                    'capacity' => $facility->capacity,
                    'floor_level' => $facility->floor_level,
                    'total_levels' => $facility->total_levels,
                    'base_fee' => $facility->base_fee,
                    'rate_type' => $facility->rate_type,
                    'location_type' => $facility->location_type,
                    'description' => $facility->description,
                    'location_note' => $facility->location_note,
                    'status_id' => $facility->status_id,
                    'department_ids' => $facility->departments->pluck('department_id')->toArray(),
                    'images' => $facility->images->map(fn($img) => [
                        'image_id' => $img->image_id,
                        'image_url' => $img->image_url,
                        'image_type' => $img->image_type,
                        'sort_order' => $img->sort_order,
                    ])->toArray(),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching facility: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch facility',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ----- Upload Facility Image ----- //
    public function uploadImage(Request $request, $facilityId): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'description' => 'nullable|string|max:80',
            'image_type' => 'required|in:Primary,Secondary',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $facility = Facility::findOrFail($facilityId);

        $existingCount = $facility->images()->count();

        $path = $request->file('image')->store("facility-photos/{$facility->facility_id}", 'public');

        $image = $facility->images()->create([
            'image_url' => '/storage/' . $path,
            'image_type' => $validated['image_type'],
            'description' => $validated['description'] ?? 'Facility photo',
            'sort_order' => $validated['sort_order'] ?? $existingCount,
        ]);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'image_id' => $image->image_id,
            'image_url' => $image->image_url,
            'image_type' => $image->image_type,
            'sort_order' => $image->sort_order,
        ]);
    }

    public function reorderImages(Request $request, $facilityId): JsonResponse
    {
        $validated = $request->validate([
            'images' => 'required|array|min:1',
            'images.*.image_id' => 'required|integer|exists:facility_images,image_id',
            'images.*.sort_order' => 'required|integer|min:0',
            'images.*.image_type' => 'required|in:Primary,Secondary',
        ]);

        $facility = Facility::findOrFail($facilityId);

        \DB::transaction(function () use ($facility, $validated) {
            foreach ($validated['images'] as $row) {
                $facility->images()
                    ->where('image_id', $row['image_id'])
                    ->update([
                        'sort_order' => $row['sort_order'],
                        'image_type' => $row['image_type'],
                    ]);
            }
        });

        return response()->json([
            'message' => 'Images reordered successfully',
            'images' => $facility->images()->orderBy('sort_order')->get(),
        ]);
    }

    // ----- Delete Facility Image ----- //
    public function deleteImage($facilityId, $imageId): JsonResponse
    {
        try {
            $facility = Facility::findOrFail($facilityId);
            $image = $facility->images()->findOrFail($imageId);

            // Delete file from storage, unless it's the default placeholder
            $relativePath = str_replace('/storage/', '', $image->image_url);
            if (!empty($relativePath) && !str_contains($relativePath, 'placeholder-image')) {
                Storage::disk('public')->delete($relativePath);
            }

            $image->delete();

            return response()->json(['message' => 'Image deleted successfully']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Image or facility not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Error in deleteImage', [
                'error' => $e->getMessage(),
                'facilityId' => $facilityId,
                'imageId' => $imageId,
            ]);
            return response()->json([
                'message' => 'Failed to delete image: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function reorderImageRecords(Facility $facility): void
    {
        $images = $facility->images()->orderBy('sort_order')->get();
        foreach ($images as $index => $image) {
            $image->update(['sort_order' => $index + 1]);
        }
    }


}