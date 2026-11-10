<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentItem;
use App\Models\LookupTables\EquipmentCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ManageEquipmentController
 *
 * Admin-facing endpoints for the manage-equipment page.
 *
 *   GET /api/admin/manage-equipment
 *       → top-level equipment list (no pagination, capped at 10 per section)
 *       → filters: statuses + categories
 *       → applies status_id / category_id filters
 *
 *   GET /api/admin/manage-equipment/section?section=equipment&page=N
 *       → paginated "load more" for the equipment accordion
 *
 *   GET /api/admin/manage-equipment/search?q=...
 *       → mixed flat results (equipment + items), ignores filters (orthogonal)
 *       → item results include parent equipment info
 *
 *   GET /api/admin/equipment/{equipmentId}/items-list?page=N
 *       → paginated items for one equipment (lazy expand in accordion)
 */
class ManageEquipmentController extends Controller
{
    private const ACCORDION_LIMIT = 10;
    private const CHILDREN_PER_PAGE = 10;
    private const SEARCH_LIMIT = 50;

    /**
     * Accordion data — top-level equipment with computed item counts and
     * primary images. No pagination on the initial load; capped at 10.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Equipment::with(['category', 'status', 'images'])
                ->withCount([
                    'items as items_count' => fn($q) => $q->where('status_id', '!=', 5),
                ]);

            $this->applyFilters($query, $request);

            $total = (clone $query)->count();

            /** @var \Illuminate\Database\Eloquent\Collection<int, Equipment> $rows */
            $rows = $query->orderBy('equipment_name')
                ->limit(self::ACCORDION_LIMIT)
                ->get();

            // Also surface items whose OWN status matches the filter (when filtered),
            // so the admin sees exactly what matched inside the parent.
            $matchingItems = [];
            if ($request->filled('status_id')) {
                $statusId = (int) $request->status_id;

                /** @var \Illuminate\Database\Eloquent\Collection<int, EquipmentItem> $itemsCollection */
                $itemsCollection = EquipmentItem::with(['condition', 'status', 'equipment.category', 'equipment.images'])
                    ->where('status_id', $statusId)
                    ->where('status_id', '!=', 5)
                    ->orderBy('item_name')
                    ->limit(self::ACCORDION_LIMIT)
                    ->get();

                $matchingItems = $itemsCollection
                    ->map(fn($i) => $this->formatItem($i))
                    ->values()
                    ->all();
            }

            // Filter metadata
            $statuses = DB::table('availability_statuses')
                ->select('status_id', 'status_name', 'color_code')
                ->orderBy('status_name')
                ->get();

            $categories = EquipmentCategory::orderBy('category_name')
                ->get(['category_id', 'category_name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'equipment' => $rows->map(fn($e) => $this->formatEquipment($e))->values()->all(),
                    'equipment_total' => $total,
                    'matching_items' => $matchingItems,
                ],
                'filters' => [
                    'statuses' => $statuses,
                    'categories' => $categories,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('ManageEquipmentController@index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch equipment data',
            ], 500);
        }
    }

    /**
     * Paginated loader for the equipment accordion ("Load more" button).
     */
    public function section(Request $request): JsonResponse
    {
        try {
            $query = Equipment::with(['category', 'status', 'images'])
                ->withCount([
                    'items as items_count' => fn($q) => $q->where('status_id', '!=', 5),
                ]);

            $this->applyFilters($query, $request);

            $paginated = $query->orderBy('equipment_name')
                ->paginate(self::ACCORDION_LIMIT);

            /** @var \Illuminate\Database\Eloquent\Collection<int, Equipment> $rows */
            $rows = $paginated->getCollection();

            // Matching items when status filter active (mirrors index)
            $matchingItems = [];
            if ($request->filled('status_id')) {
                $statusId = (int) $request->status_id;

                /** @var \Illuminate\Database\Eloquent\Collection<int, EquipmentItem> $itemsCollection */
                $itemsCollection = EquipmentItem::with(['condition', 'status', 'equipment.category', 'equipment.images'])
                    ->where('status_id', $statusId)
                    ->where('status_id', '!=', 5)
                    ->orderBy('item_name')
                    ->limit(self::ACCORDION_LIMIT)
                    ->get();

                $matchingItems = $itemsCollection
                    ->map(fn($i) => $this->formatItem($i))
                    ->values()
                    ->all();
            }

            return response()->json([
                'success' => true,
                'data' => $rows->map(fn($e) => $this->formatEquipment($e))->values()->all(),
                'matching_items' => $matchingItems,
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'total' => $paginated->total(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('ManageEquipmentController@section failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to load section'], 500);
        }
    }


    /**
     * Orthogonal search — matches equipment name / brand / item name / item barcode.
     * Returns flat mixed results: { result_type: 'equipment' | 'item', ... }
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $term = trim((string) $request->query('q', ''));
            if ($term === '') {
                return response()->json(['success' => true, 'results' => []]);
            }

            $like = "%{$term}%";

            // Equipment matches (by name or brand)
            /** @var \Illuminate\Database\Eloquent\Collection<int, Equipment> $equipmentMatches */
            $equipmentMatches = Equipment::with(['category', 'status', 'images'])
                ->withCount([
                    'items as items_count' => fn($q) => $q->where('status_id', '!=', 5),
                ])
                ->where(function ($q) use ($like) {
                    $q->where('equipment_name', 'ILIKE', $like)
                        ->orWhere('brand', 'ILIKE', $like);
                })
                ->orderBy('equipment_name')
                ->limit(self::SEARCH_LIMIT)
                ->get();

            $equipmentIds = $equipmentMatches->pluck('equipment_id')->all();

            // Item matches (name or barcode), excluding items whose parent is already matched
            /** @var \Illuminate\Database\Eloquent\Collection<int, EquipmentItem> $itemMatches */
            $itemMatches = EquipmentItem::with(['equipment.category', 'equipment.status', 'equipment.images', 'condition', 'status'])
                ->where('status_id', '!=', 5)
                ->where(function ($q) use ($like) {
                    $q->where('item_name', 'ILIKE', $like)
                        ->orWhere('barcode_number', 'ILIKE', $like);
                })
                ->when(!empty($equipmentIds), fn($q) => $q->whereNotIn('equipment_id', $equipmentIds))
                ->orderBy('item_name')
                ->limit(self::SEARCH_LIMIT)
                ->get();

            // Merge into flat results
            $results = [];

            foreach ($equipmentMatches as $eq) {
                $results[] = [
                    'result_type' => 'equipment',
                    'equipment' => $this->formatEquipment($eq),
                ];
            }

            foreach ($itemMatches as $it) {
                $results[] = [
                    'result_type' => 'item',
                    'item' => $this->formatItem($it),
                ];
            }

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            \Log::error('ManageEquipmentController@search failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Search failed'], 500);
        }
    }

    /**
     * Paginated items for one equipment — used when expanding in the accordion.
     */
    public function items(Request $request, int $equipmentId): JsonResponse
    {
        try {
            $query = EquipmentItem::with(['condition', 'status'])
                ->where('equipment_id', $equipmentId)
                ->where('status_id', '!=', 5);

            $paginated = $query->orderBy('item_name')
                ->paginate(self::CHILDREN_PER_PAGE);

            /** @var \Illuminate\Database\Eloquent\Collection<int, EquipmentItem> $rows */
            $rows = $paginated->getCollection();

            // Need equipment's primary image for item fallback
            $equipment = Equipment::with('images')->find($equipmentId);
            $fallbackImg = $equipment ? $this->primaryImageUrl($equipment) : null;

            return response()->json([
                'success' => true,
                'data' => $rows->map(fn($i) => $this->formatItem($i, $fallbackImg))->values()->all(),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'total' => $paginated->total(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('ManageEquipmentController@items failed', [
                'equipment_id' => $equipmentId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch items'], 500);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status_id')) {
            $query->where('status_id', (int) $request->status_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }
    }

    /**
     * Resolve primary image URL from images collection (schema: image_type === 'Primary', sort_order = 1).
     */
    private function primaryImageUrl(Equipment $equipment): ?string
    {
        $images = $equipment->images ?? collect();
        if ($images->isEmpty())
            return null;

        $valid = $images->filter(fn($img) => $img->image_url && trim($img->image_url) !== '');
        if ($valid->isEmpty())
            return null;

        $bySort = $valid->firstWhere('sort_order', 1);
        $byType = $valid->firstWhere('image_type', 'Primary');
        return $bySort->image_url ?? $byType->image_url ?? $valid->first()->image_url ?? null;
    }

    private function formatEquipment(Equipment $equipment): array
    {
        return [
            'equipment_id' => $equipment->equipment_id,
            'equipment_name' => $equipment->equipment_name,
            'description' => $equipment->description,
            'brand' => $equipment->brand,
            'storage_location' => $equipment->storage_location,
            'base_fee' => $equipment->base_fee,
            'rate_type' => $equipment->rate_type,
            'category_id' => $equipment->category_id,
            'category_name' => $equipment->category?->category_name ?? 'Uncategorized',
            'status_id' => $equipment->status_id,
            'status_name' => $equipment->status?->status_name,
            'status_color_code' => $equipment->status?->color_code,
            'items_count' => $equipment->items_count ?? 0,
            'primary_image_url' => $this->primaryImageUrl($equipment),
        ];
    }

    /**
     * @param  string|null  $fallbackImage  Equipment's primary image (for items missing their own)
     */
    private function formatItem(EquipmentItem $item, ?string $fallbackImage = null): array
    {
        $imageUrl = $item->image_url && trim($item->image_url) !== '' ? $item->image_url : null;
        if (!$imageUrl && $fallbackImage)
            $imageUrl = $fallbackImage;
        if (!$imageUrl && $item->equipment)
            $imageUrl = $this->primaryImageUrl($item->equipment);

        return [
            'item_id' => $item->item_id,
            'equipment_id' => $item->equipment_id,
            'item_name' => $item->item_name,
            'brand' => $item->brand,
            'barcode_number' => $item->barcode_number,
            'item_notes' => $item->item_notes,
            'condition_id' => $item->condition_id,
            'condition_name' => $item->condition?->condition_name,
            'status_id' => $item->status_id,
            'status_name' => $item->status?->status_name,
            'status_color_code' => $item->status?->color_code,
            'primary_image_url' => $imageUrl,
            // Parent equipment info (used by search overlay to show context)
            'equipment_name' => $item->equipment?->equipment_name,
            'category_name' => $item->equipment?->category?->category_name,
        ];
    }
}