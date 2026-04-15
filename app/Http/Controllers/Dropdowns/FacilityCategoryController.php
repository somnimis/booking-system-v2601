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
 * Get venues (Buildings, Outside Spaces, Sports Venues) - EXCLUDING ROOMS
 */
public function getVenues(Request $request)
{
    try {
        // Build the query - get venues but EXCLUDE rooms
        $query = Facility::with(['category', 'subcategory', 'status', 'images'])
            ->whereIn('category_id', [1, 4, 5]) // Only venue categories
            ->whereNull('parent_facility_id'); // Exclude rooms (which have parent)

        // Apply filters
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('subcategory')) {
            $query->where('subcategory_id', $request->subcategory);
        }

        if ($request->has('status')) {
            $query->where('status_id', $request->status);
        }

        // Order and paginate
        $facilities = $query->orderBy('facility_name')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $facilities
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
        $facilities = Facility::with(['category', 'subcategory', 'status', 'department', 'images', 'parentFacility'])
            ->where(function (Builder $query) {
                // Rooms: Campus Rooms (2), Residencies/Dorms (3)
                $query->whereIn('category_id', [2, 3])
                      ->orWhereNotNull('parent_facility_id'); // Include rooms within buildings
            })
            ->when($request->has('category'), function ($query) use ($request) {
                $query->where('category_id', $request->category);
            })
            ->when($request->has('subcategory'), function ($query) use ($request) {
                $query->where('subcategory_id', $request->subcategory);
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status_id', $request->status);
            })
            ->when($request->has('building'), function ($query) use ($request) {
                $query->whereHas('parent', function ($q) use ($request) {
                    $q->where('facility_id', $request->building);
                });
            })
            ->orderBy('facility_name')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $facilities
        ]);
    }

    /**
     * Get venue categories (filtered)
     */
    public function getVenueCategories()
    {
        $categories = FacilityCategory::with(['subcategories' => function ($query) {
                // Only load subcategories that belong to these categories
                $query->whereIn('category_id', [1, 4, 5]);
            }])
            ->whereIn('category_id', [1, 4, 5]) // Buildings, Outside Spaces, Sports Venues
            ->get();

        return response()->json($categories);
    }

    /**
     * Get room categories (filtered)
     */
    public function getRoomCategories()
    {
        $categories = FacilityCategory::with(['subcategories' => function ($query) {
                // Only load subcategories that belong to these categories
                $query->whereIn('category_id', [2, 3]);
            }])
            ->whereIn('category_id', [2, 3]) // Campus Rooms, Residencies/Dorms
            ->get();

        return response()->json($categories);
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
