<?php

namespace App\Http\Controllers\Dropdowns;

use App\Http\Controllers\Controller;
use App\Models\LookupTables\FacilityCategory;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class FacilityCategoryController extends Controller
{
    public function index()
    {
        return response()->json(FacilityCategory::all());
    }

    public function indexWithSubcategories()
    {
        $categories = FacilityCategory::with('subcategories')->get();

        return response()->json($categories);
    }
/**
 * Get venues (Buildings, Outside Spaces, Sports Venues) - EXCLUDING ROOMS AND BUILDINGS WITH CHILDREN
 */
public function getVenues(Request $request)
{
    try {
        $query = Facility::with(['category', 'subcategory', 'status', 'images'])
            ->whereIn('category_id', [1, 4, 5])
            ->whereNull('parent_facility_id')
            ->whereDoesntHave('childFacilities');

        // === HANDLE MULTIPLE CATEGORIES ===
        if ($request->has('categories')) {
            $categories = is_array($request->categories) ? $request->categories : explode(',', $request->categories);
            if (!empty($categories)) {
                $query->whereIn('category_id', $categories);
            }
        } elseif ($request->has('category') && !empty($request->category)) {
            $query->where('category_id', $request->category);
        }

        // === HANDLE MULTIPLE SUBCATEGORIES ===
        if ($request->has('subcategories')) {
            $subcategories = is_array($request->subcategories) ? $request->subcategories : explode(',', $request->subcategories);
            if (!empty($subcategories)) {
                $query->whereIn('subcategory_id', $subcategories);
            }
        } elseif ($request->has('subcategory') && !empty($request->subcategory)) {
            $query->where('subcategory_id', $request->subcategory);
        }

        // Apply status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status_id', $request->status);
        }

        // Add search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('facility_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        
        $facilities = $query->orderBy('facility_name')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $facilities->items(),
            'current_page' => $facilities->currentPage(),
            'last_page' => $facilities->lastPage(),
            'per_page' => $facilities->perPage(),
            'total' => $facilities->total(),
            'next_page_url' => $facilities->nextPageUrl(),
            'prev_page_url' => $facilities->previousPageUrl(),
        ]);

    } catch (\Exception $e) {
        \Log::error('Get venues error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch venues'
        ], 500);
    }
}

/**
 * Get rooms (Campus Rooms, Residencies/Dorms)
 */
public function getRooms(Request $request)
{
    try {
        $query = Facility::with(['category', 'subcategory', 'status', 'department', 'images', 'parentFacility'])
            ->where(function (Builder $query) {
                $query->whereNotNull('parent_facility_id')
                    ->whereIn('category_id', [2, 3]);
            });

        // === HANDLE MULTIPLE CATEGORIES ===
        if ($request->has('categories')) {
            $categories = is_array($request->categories) ? $request->categories : explode(',', $request->categories);
            if (!empty($categories)) {
                $query->whereIn('category_id', $categories);
            }
        } elseif ($request->has('category') && !empty($request->category)) {
            $query->where('category_id', $request->category);
        }

        // === HANDLE MULTIPLE SUBCATEGORIES ===
        if ($request->has('subcategories')) {
            $subcategories = is_array($request->subcategories) ? $request->subcategories : explode(',', $request->subcategories);
            if (!empty($subcategories)) {
                $query->whereIn('subcategory_id', $subcategories);
            }
        } elseif ($request->has('subcategory') && !empty($request->subcategory)) {
            $query->where('subcategory_id', $request->subcategory);
        }

        // Apply status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status_id', $request->status);
        }

        // === HANDLE MULTIPLE BUILDINGS ===
        if ($request->has('buildings')) {
            $buildings = is_array($request->buildings) ? $request->buildings : explode(',', $request->buildings);
            if (!empty($buildings)) {
                $query->whereIn('parent_facility_id', $buildings);
            }
        } elseif ($request->has('building') && !empty($request->building)) {
            $query->where('parent_facility_id', $request->building);
        }

        // Add search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('facility_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        
        $facilities = $query->orderBy('facility_name')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $facilities->items(),
            'current_page' => $facilities->currentPage(),
            'last_page' => $facilities->lastPage(),
            'per_page' => $facilities->perPage(),
            'total' => $facilities->total(),
            'next_page_url' => $facilities->nextPageUrl(),
            'prev_page_url' => $facilities->previousPageUrl(),
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Get rooms error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch rooms'
        ], 500);
    }
}

/**
 * Get facilities with advanced filtering (supports multiple categories/subcategories)
 */
public function getFilteredFacilities(Request $request)
{
    try {
        $query = Facility::with(['category', 'subcategory', 'status', 'images']);
        
        // Determine if we're getting venues or rooms based on parameters
        $type = $request->get('type', 'venues');
        
        if ($type === 'venues') {
            $query->whereIn('category_id', [1, 4, 5])
                  ->whereNull('parent_facility_id')
                  ->whereDoesntHave('childFacilities');
        } else {
            $query->whereIn('category_id', [2, 3])
                  ->whereNotNull('parent_facility_id');
        }
        
        // Handle multiple category IDs
        if ($request->has('categories') && is_array($request->categories)) {
            $query->whereIn('category_id', $request->categories);
        } elseif ($request->has('category') && !empty($request->category)) {
            $query->where('category_id', $request->category);
        }
        
        // Handle multiple subcategory IDs
        if ($request->has('subcategories') && is_array($request->subcategories)) {
            $query->whereIn('subcategory_id', $request->subcategories);
        } elseif ($request->has('subcategory') && !empty($request->subcategory)) {
            $query->where('subcategory_id', $request->subcategory);
        }
        
        // Apply status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status_id', $request->status);
        }
        
        // Apply building filter for rooms
        if ($type === 'rooms' && $request->has('building') && !empty($request->building)) {
            $query->where('parent_facility_id', $request->building);
        }
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('facility_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        
        $facilities = $query->orderBy('facility_name')->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'success' => true,
            'data' => $facilities->items(),
            'current_page' => $facilities->currentPage(),
            'last_page' => $facilities->lastPage(),
            'per_page' => $facilities->perPage(),
            'total' => $facilities->total(),
            'next_page_url' => $facilities->nextPageUrl(),
            'prev_page_url' => $facilities->previousPageUrl(),
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Get filtered facilities error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch facilities'
        ], 500);
    }
}

public function getVenuesCount(Request $request)
{
    $query = Facility::whereIn('category_id', [1, 4, 5])
        ->whereNull('parent_facility_id')
        ->whereDoesntHave('childFacilities');
    
    return response()->json([
        'success' => true,
        'total' => $query->count()
    ]);
}
    /**
     * Get venue categories (filtered)
     */
    public function getVenueCategories()
    {
        $categories = FacilityCategory::with([
            'subcategories' => function ($query) {
                // Only load subcategories that belong to these categories
                $query->whereIn('category_id', [1, 4, 5]);
            }
        ])
            ->whereIn('category_id', [1, 4, 5]) // Buildings, Outside Spaces, Sports Venues
            ->get();

        return response()->json($categories);
    }

/**
 * Get room categories (filtered to only IDs 2 and 3)
 */
public function getRoomCategories()
{
    $categories = FacilityCategory::with([
        'subcategories' => function ($query) {
            // Only load subcategories that belong to categories 2 and 3
            $query->whereIn('category_id', [2, 3]);
        }
    ])
        ->whereIn('category_id', [2, 3]) // Campus Rooms, Residencies/Dorms only
        ->get();

    return response()->json($categories);
}

    /**
 * Get parent buildings for rooms filtering
 */
public function getParentBuildingsForRooms()
{
    $buildings = Facility::whereIn('category_id', [1, 4, 5]) // Buildings, Outside Spaces, Sports Venues
        ->whereNull('parent_facility_id')
        ->whereHas('childFacilities', function($query) {
            $query->whereIn('category_id', [2, 3]); // Only buildings that have rooms/dorms
        })
        ->select('facility_id', 'facility_name', 'facility_code')
        ->orderBy('facility_name')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $buildings
    ]);
}

    /**
     * Get a single facility by ID
     */
    public function show($id)
    {
        $facility = Facility::with(['category', 'subcategory', 'status', 'department', 'images', 'parentFacility'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $facility
        ]);
    }

    /**
     * Get buildings list (for filtering rooms by building)
     */
    public function getBuildings()
    {
        $buildings = Facility::whereIn('category_id', [1, 4, 5])
            ->whereNull('parent_facility_id')
            ->select('facility_id', 'facility_name', 'facility_code')
            ->orderBy('facility_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $buildings
        ]);
    }
}
