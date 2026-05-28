<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ManageFacilitiesController
 *
 * Dedicated, merged controller for the manage-facilities admin page.
 * Consolidates what were previously 3 separate API calls on page load:
 *   - GET /api/facilities          (all facilities, no pagination)
 *   - GET /api/availability-statuses
 *   - GET /api/facility-categories
 *
 * Now resolved in a single request:
 *   GET /api/admin/manage-facilities
 *
 * Supports:
 *   - Server-side pagination (12 per page)
 *   - Filter by status_id
 *   - Filter by category_id
 *   - Filter by parent_facility_id (buildings)
 *   - Search by facility_name
 *   - Returns filter metadata (statuses, categories, parent buildings) in first-page response
 */
class ManageFacilitiesController extends Controller
{
    // Number of facilities returned per page
    private const PER_PAGE = 12;


    /**
     * GET /api/admin/manage-facilities
     *
     * Returns paginated facilities plus filter metadata (statuses, categories,
     * parent buildings). Filter metadata is always included so the frontend
     * can populate dropdowns without an extra round-trip.
     *
     * Query Parameters:
     *   page          int     Page number (default: 1)
     *   status_id     int     Filter by availability_status.status_id
     *   category_id   int     Filter by facility_categories.category_id
     *   parent_id     int     Filter by parent_facility_id (0 = top-level only)
     *   search        string  Partial match on facility_name
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // ── Build the facilities query ─────────────────────────────────
            $query = Facility::with([
                'category',
                'subcategory',
                'status',
                'images',
            ]);

            // Filter: status
            if ($request->filled('status_id')) {
                $query->where('status_id', (int) $request->status_id);
            }

            // Filter: category
            if ($request->filled('category_id')) {
                $query->where('category_id', (int) $request->category_id);
            }

            // Filter: building (parent_facility_id)
            // parent_id=0  → top-level facilities only (parent_facility_id IS NULL)
            // parent_id=N  → children of building N
            if ($request->filled('parent_id')) {
                $parentId = (int) $request->parent_id;
                if ($parentId === 0) {
                    $query->whereNull('parent_facility_id');
                } else {
                    $query->where('parent_facility_id', $parentId);
                }
            }

            // Filter: search
            if ($request->filled('search')) {
                $term = $request->search;
                $query->where('facility_name', 'LIKE', "%{$term}%");
            }

            // ── Paginate ───────────────────────────────────────────────────
            $paginated = $query
                ->orderBy('facility_name')
                ->paginate(self::PER_PAGE);

            // ── Format facility rows (no department data) ──────────────────
            $facilities = $paginated->getCollection()->map(
                fn($f) => $this->formatFacility($f)
            );

            // ── Filter metadata (always returned) ─────────────────────────
            // Statuses – read directly from the pivot table already loaded
            // by the relationship; avoids a separate model import.
            $statuses = \DB::table('availability_statuses')
                ->select('status_id', 'status_name')
                ->orderBy('status_name')
                ->get();

            $categories = \DB::table('facility_categories')
                ->select('category_id', 'category_name')
                ->orderBy('category_name')
                ->get();

            // Parent buildings: facilities where parent_facility_id IS NULL
            // AND that have at least one child facility (rooms/spaces under them)
            $parentBuildings = Facility::whereNull('parent_facility_id')
                ->whereHas('childFacilities') // Changed from 'children' to 'childFacilities'
                ->select('facility_id', 'facility_name')
                ->orderBy('facility_name')
                ->get()
                ->map(fn($b) => [
                    'facility_id' => $b->facility_id,
                    'facility_name' => $b->facility_name,
                ]);

            return response()->json([
                'success' => true,

                // Paginated facility data
                'data' => $facilities,

                // Pagination meta
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'from' => $paginated->firstItem(),
                    'to' => $paginated->lastItem(),
                ],

                // Filter dropdowns – always returned so the blade only ever
                // needs one API call to bootstrap the entire page.
                'filters' => [
                    'statuses' => $statuses,
                    'categories' => $categories,
                    'parent_buildings' => $parentBuildings,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('ManageFacilitiesController@index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch facilities data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format a single Facility model into the shape expected by the blade.
     * Department data is intentionally excluded.
     */
    private function formatFacility(Facility $facility): array
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

            // Resolved relationship fields
            'category_id' => $facility->category_id,
            'status_id' => $facility->status_id,
            'category_name' => $facility->category?->category_name ?? 'Uncategorized',
            'subcategory_name' => $facility->subcategory?->subcategory_name,
            'status_name' => $facility->status?->status_name,

            // Images
            'images' => $facility->images->map(fn($img) => [
                'image_url' => $img->image_url,
                'is_primary' => $img->is_primary ?? false,
            ])->values()->toArray(),
        ];
    }
}