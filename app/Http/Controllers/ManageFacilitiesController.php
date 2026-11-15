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
            // Apply filters per-query so each role gets the same conditions
            $venuesQuery = Facility::with(['category', 'subcategory', 'status', 'images', 'parentFacility'])
                ->where('facility_role', 'Venue');
            $this->applyFilters($venuesQuery, $request);

            $containersQuery = Facility::with(['category', 'subcategory', 'status', 'images', 'parentFacility', 'childFacilities'])
                ->where('facility_role', 'Container');
            $this->applyFilters($containersQuery, $request);

            $roomsQuery = Facility::with(['category', 'subcategory', 'status', 'images', 'parentFacility'])
                ->where('facility_role', 'Room');
            $this->applyFilters($roomsQuery, $request);

            $venues = $venuesQuery->orderBy('facility_name')
                ->paginate(self::ACCORDION_LIMIT, ['*'], 'venues_page');
            $containers = $containersQuery->orderBy('facility_name')
                ->paginate(self::ACCORDION_LIMIT, ['*'], 'containers_page');
            $rooms = $roomsQuery->orderBy('facility_name')
                ->paginate(self::ACCORDION_LIMIT, ['*'], 'rooms_page');

            // Filter metadata
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
                    'venues' => $venues->getCollection()->map(fn($f) => $this->formatFacility($f))->values()->all(),
                    'containers' => $containers->getCollection()->map(fn($f) => $this->formatFacility($f))->values()->all(),
                    'rooms' => $rooms->getCollection()->map(fn($f) => $this->formatFacility($f))->values()->all(),
                ],
                'pagination' => [
                    'venues' => ['current_page' => $venues->currentPage(), 'last_page' => $venues->lastPage()],
                    'containers' => ['current_page' => $containers->currentPage(), 'last_page' => $containers->lastPage()],
                    'rooms' => ['current_page' => $rooms->currentPage(), 'last_page' => $rooms->lastPage()],
                ],
                'filters' => [
                    'statuses' => $statuses,
                    'categories' => $categories,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('ManageFacilitiesController@index failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch facilities data'], 500);
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
            $section = $request->query('section'); // 'venues', 'containers', 'rooms'
            if (!in_array($section, ['venues', 'containers', 'rooms'], true)) {
                return response()->json(['success' => false, 'message' => 'Invalid section'], 422);
            }

            $roleMap = [
                'venues' => 'Venue',
                'containers' => 'Container',
                'rooms' => 'Room'
            ];

            $query = Facility::with(['category', 'subcategory', 'status', 'images', 'parentFacility', 'childFacilities'])
                ->where('facility_role', $roleMap[$section]);

            $this->applyFilters($query, $request);

            $paginated = $query->orderBy('facility_name')
                ->paginate(self::ACCORDION_LIMIT);

            return response()->json([
                'success' => true,
                'data' => $paginated->getCollection()->map(fn($f) => $this->formatFacility($f))->values()->all(),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'total' => $paginated->total(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('ManageFacilitiesController@section failed', ['error' => $e->getMessage()]);
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
        $primary = $facility->images->firstWhere('image_type', 'Primary')
            ?? $facility->images->sortBy('sort_order')->first();

        // NEW: Inherit container image if it's a room and has no image
        $primaryImageUrl = $primary?->image_url;
        if (!$primaryImageUrl && $facility->facility_role === 'Room' && $facility->parentFacility) {
            $parentPrimary = $facility->parentFacility->images->firstWhere('image_type', 'Primary')
                ?? $facility->parentFacility->images->sortBy('sort_order')->first();
            $primaryImageUrl = $parentPrimary?->image_url;
        }

        $childrenCount = $facility->childFacilities->count();
        $buildingType = strtolower($facility->facility_role);

        return [
            'facility_id' => $facility->facility_id,
            'parent_facility_id' => $facility->parent_facility_id,
            'parent_facility_name' => $facility->parentFacility?->facility_name, // Added for Rooms tab
            'facility_name' => $facility->facility_name,
            'facility_code' => $facility->facility_code,
            'description' => $facility->description,
            'capacity' => $facility->capacity,
            'base_fee' => $facility->facility_role === 'Container' ? null : $facility->base_fee,
            'rate_type' => $facility->facility_role === 'Container' ? null : $facility->rate_type,
            'building_type' => $buildingType,
            'children_count' => $childrenCount,

            'category_id' => $facility->category_id,
            'subcategory_id' => $facility->subcategory_id,
            'status_id' => $facility->status_id,
            'category_name' => $facility->category?->category_name ?? 'Uncategorized',
            'subcategory_name' => $facility->subcategory?->subcategory_name,
            'status_name' => $facility->status?->status_name,
            'status_color_code' => $facility->status?->color_code,

            'primary_image_url' => $primaryImageUrl,
        ];
    }
}