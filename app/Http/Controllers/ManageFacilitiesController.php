<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\LookupTables\FacilityCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ManageFacilitiesController
 *
 * Admin-facing endpoints for the manage-facilities page.
 *
 *   GET /api/admin/manage-facilities
 *       → accordion data: { venues: [], buildings: [] } (top-level only)
 *       → filters: statuses + categories (with nested subcategories)
 *       → applies status_id / category_id / subcategory_id filters
 *
 *   GET /api/admin/manage-facilities/search?q=...
 *       → flat search results, ignores all filters (orthogonal)
 *
 *   GET /api/admin/facilities/{buildingId}/children?page=N
 *       → paginated rooms for one building (lazy-loaded on expand)
 *
 * Building type is derived server-side:
 *   parent_facility_id IS NULL + has children  → 'building' (container)
 *   parent_facility_id IS NULL + no children   → 'venue'    (standalone)
 *   parent_facility_id NOT NULL                → 'room'
 */
class ManageFacilitiesController extends Controller
{
    private const CHILDREN_PER_PAGE = 10;
    private const SEARCH_LIMIT = 50;
    private const ACCORDION_LIMIT = 10;

    /**
     * Accordion data for the manage-facilities page.
     * Top-level only: standalone venues (no children) + buildings (with children).
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Facility::with(['category', 'subcategory', 'status', 'images', 'childFacilities'])
                ->whereNull('parent_facility_id');

            $this->applyFilters($query, $request);

            // Cap each section independently. Venues = no children; Buildings = has children.
            // Also return total counts so the blade can show "X of Y" or "Load more" if desired.
            $venuesQuery = (clone $query)->whereDoesntHave('childFacilities');
            $buildingsQuery = (clone $query)->whereHas('childFacilities');

            $venuesTotal = (clone $venuesQuery)->count();
            $buildingsTotal = (clone $buildingsQuery)->count();

            /** @var \Illuminate\Database\Eloquent\Collection<int, Facility> $venueModels */
            $venueModels = $venuesQuery->orderBy('facility_name')->limit(self::ACCORDION_LIMIT)->get();

            /** @var \Illuminate\Database\Eloquent\Collection<int, Facility> $buildingModels */
            $buildingModels = $buildingsQuery->orderBy('facility_name')->limit(self::ACCORDION_LIMIT)->get();

            $venues = $venueModels->map(fn($f) => $this->formatFacility($f))->values()->all();
            $buildings = $buildingModels->map(fn($f) => $this->formatFacility($f))->values()->all();

            // Filter metadata (statuses + categories with nested subcategories)
            $statuses = \DB::table('availability_statuses')
                ->select('status_id', 'status_name')
                ->orderBy('status_name')
                ->get();

            $categories = FacilityCategory::with('subcategories')
                ->orderBy('category_name')
                ->get()
                ->map(fn($c) => [
                    'category_id' => $c->category_id,
                    'category_name' => $c->category_name,
                    'subcategories' => $c->subcategories->map(fn($s) => [
                        'subcategory_id' => $s->subcategory_id,
                        'subcategory_name' => $s->subcategory_name,
                    ])->values(),
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'venues' => $venues,
                    'buildings' => $buildings,
                    'venues_total' => $venuesTotal,
                    'buildings_total' => $buildingsTotal,
                ],
                'filters' => [
                    'statuses' => $statuses,
                    'categories' => $categories,
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
            ], 500);
        }
    }

    /**
     * Paginated loader for a single accordion section (venues OR buildings).
     * Used by the "Load more" button in the manage-facilities blade.
     *
     * Query params:
     *   - section  : "venues" | "buildings"  (required)
     *   - page     : int (default 1)
     *   - status_id / category_id / subcategory_id (same filters as index)
     */
    public function section(Request $request): JsonResponse
    {
        try {
            $section = $request->query('section');
            if (!in_array($section, ['venues', 'buildings'], true)) {
                return response()->json(['success' => false, 'message' => 'Invalid section'], 422);
            }

            $query = Facility::with(['category', 'subcategory', 'status', 'images', 'childFacilities'])
                ->whereNull('parent_facility_id');

            $this->applyFilters($query, $request);

            if ($section === 'venues') {
                $query->whereDoesntHave('childFacilities');
            } else {
                $query->whereHas('childFacilities');
            }

            $paginated = $query->orderBy('facility_name')
                ->paginate(self::ACCORDION_LIMIT);

            /** @var \Illuminate\Database\Eloquent\Collection<int, Facility> $rows */
            $rows = $paginated->getCollection();

            return response()->json([
                'success' => true,
                'data' => $rows->map(fn($f) => $this->formatFacility($f))->values()->all(),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'total' => $paginated->total(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('ManageFacilitiesController@section failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to load section'], 500);
        }
    }

    /**
     * Flat search — orthogonal to accordion & filters.
     * Returns up to SEARCH_LIMIT facility_name or facility_code matches, alphabetical.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $term = trim((string) $request->query('q', ''));

            if ($term === '') {
                return response()->json(['success' => true, 'results' => []]);
            }

            /** @var \Illuminate\Database\Eloquent\Collection<int, Facility> $facilities */
            $facilities = Facility::with(['category', 'subcategory', 'status', 'images', 'childFacilities'])
                ->where(function ($q) use ($term) {
                    $q->where('facility_name', 'ILIKE', "%{$term}%")
                        ->orWhere('facility_code', 'ILIKE', "%{$term}%");
                })
                ->orderBy('facility_name')
                ->limit(self::SEARCH_LIMIT)
                ->get();

            return response()->json([
                'success' => true,
                'results' => $facilities->map(fn($f) => $this->formatFacility($f)),
            ]);
        } catch (\Exception $e) {
            \Log::error('ManageFacilitiesController@search failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed',
            ], 500);
        }
    }

    /**
     * Lazy-loaded rooms for a single building.
     * Only status_id and subcategory_id filters are honored here —
     * category_id would narrow rooms to the wrong set (children are rooms).
     */
    public function children(Request $request, int $buildingId): JsonResponse
    {
        try {
            $query = Facility::with(['category', 'subcategory', 'status', 'images', 'childFacilities'])
                ->where('parent_facility_id', $buildingId);

            if ($request->filled('status_id')) {
                $query->where('status_id', (int) $request->status_id);
            }
            if ($request->filled('subcategory_id')) {
                $query->where('subcategory_id', (int) $request->subcategory_id);
            }

            $paginated = $query->orderBy('facility_name')
                ->paginate(self::CHILDREN_PER_PAGE);

            return response()->json([
                'success' => true,
                'data' => $paginated->getCollection()->map(fn($f) => $this->formatFacility($f)),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'total' => $paginated->total(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('ManageFacilitiesController@children failed', [
                'building_id' => $buildingId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch rooms for this building',
            ], 500);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Apply status / category / subcategory filters.
     * Category filter is inclusive of its subcategories by schema design:
     * every facility in a subcategory already carries that subcategory's
     * parent category_id, so where('category_id', X) covers both.
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status_id')) {
            $query->where('status_id', (int) $request->status_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }
        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', (int) $request->subcategory_id);
        }
    }

    /**
     * Format a Facility into the row shape consumed by the blade.
     *
     * @param  Facility  $facility
     */
    private function formatFacility(Facility $facility): array
    {
        $primary = $facility->images
            ->firstWhere('image_type', 'Primary')
            ?? $facility->images->sortBy('sort_order')->first();

        $childrenCount = $facility->childFacilities->count();
        $isBuilding = $facility->parent_facility_id === null && $childrenCount > 0;

        if ($facility->parent_facility_id !== null) {
            $buildingType = 'room';
        } elseif ($isBuilding) {
            $buildingType = 'building';
        } else {
            $buildingType = 'venue';
        }

        return [
            'facility_id' => $facility->facility_id,
            'parent_facility_id' => $facility->parent_facility_id,
            'facility_name' => $facility->facility_name,
            'facility_code' => $facility->facility_code,
            'description' => $facility->description,
            'capacity' => $facility->capacity,
            'base_fee' => $isBuilding ? null : $facility->base_fee,
            'rate_type' => $isBuilding ? null : $facility->rate_type,
            'building_type' => $buildingType,
            'children_count' => $childrenCount,

            'category_id' => $facility->category_id,
            'subcategory_id' => $facility->subcategory_id,
            'status_id' => $facility->status_id,
            'category_name' => $facility->category?->category_name ?? 'Uncategorized',
            'subcategory_name' => $facility->subcategory?->subcategory_name,
            'status_name' => $facility->status?->status_name,
            'status_color_code' => $facility->status?->color_code,

            'primary_image_url' => $primary?->image_url,
        ];
    }
}