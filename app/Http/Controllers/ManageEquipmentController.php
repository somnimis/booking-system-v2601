<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentItem;
use App\Models\LookupTables\EquipmentCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManageEquipmentController extends Controller
{
    private const ACCORDION_LIMIT = 10;
    private const CHILDREN_PER_PAGE = 10;
    private const SEARCH_LIMIT = 50;

    public function index(Request $request): JsonResponse
    {
        try {
            // Determine the current page for each dataset
            $equipmentPage = (int) $request->input('equipment_page', 1);
            $itemsPage = (int) $request->input('items_page', 1);

            // 1. Fetch Equipment Tab Data
            $equipmentQuery = Equipment::with(['category', 'status', 'images'])
                ->withCount(['items as items_count']);
            if ($request->filled('status_id')) {
                $equipmentQuery->where('status_id', (int) $request->status_id);
            }
            if ($request->filled('category_id')) {
                $equipmentQuery->where('category_id', (int) $request->category_id);
            }
            $equipment = $equipmentQuery->orderBy('equipment_name')
                ->paginate(self::ACCORDION_LIMIT, ['*'], 'equipment_page', $equipmentPage);

            // 2. Fetch Items Tab Data (Flat)
            $itemsQuery = EquipmentItem::with(['condition', 'status', 'equipment.category', 'equipment.images']);
            if ($request->filled('status_id')) {
                $itemsQuery->where('status_id', (int) $request->status_id);
            }
            if ($request->filled('category_id')) {
                $itemsQuery->whereHas('equipment', fn($q) => $q->where('category_id', (int) $request->category_id));
            }
            if ($request->filled('condition_id')) {
                $itemsQuery->where('condition_id', (int) $request->condition_id);
            }
            $items = $itemsQuery->orderBy('item_name')
                ->paginate(self::CHILDREN_PER_PAGE, ['*'], 'items_page', $itemsPage);

            // Metadata
            $statuses = DB::table('availability_statuses')->select('status_id', 'status_name', 'color_code')->orderBy('status_name')->get();
            $categories = EquipmentCategory::orderBy('category_name')->get(['category_id', 'category_name']);
            $conditions = \DB::table('conditions')->select('condition_id', 'condition_name')->orderBy('condition_name')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'equipment' => $equipment->getCollection()->map(fn($e) => $this->formatEquipment($e))->values()->all(),
                    'items' => $items->getCollection()->map(fn($i) => $this->formatItem($i))->values()->all(),
                ],
                'pagination' => [
                    'equipment' => ['current_page' => $equipment->currentPage(), 'last_page' => $equipment->lastPage()],
                    'items' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage()],
                ],
                'filters' => ['statuses' => $statuses, 'categories' => $categories, 'conditions' => $conditions],
            ]);
        } catch (\Exception $e) {
            \Log::error('ManageEquipmentController@index failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch data'], 500);
        }
    }
    public function section(Request $request): JsonResponse
    {
        try {
            $section = $request->query('section');
            $page = $request->input('page', 1);

            if ($section === 'equipment') {
                $query = Equipment::with(['category', 'status', 'images'])->withCount(['items as items_count']);
                if ($request->filled('status_id'))
                    $query->where('status_id', (int) $request->status_id);
                if ($request->filled('category_id'))
                    $query->where('category_id', (int) $request->category_id);
                $paginated = $query->orderBy('equipment_name')->paginate(self::ACCORDION_LIMIT, ['*'], 'page', $page);
                $data = $paginated->getCollection()->map(fn($e) => $this->formatEquipment($e))->values()->all();
            } else {
                $query = EquipmentItem::with(['condition', 'status', 'equipment.category', 'equipment.images']);
                if ($request->filled('status_id'))
                    $query->where('status_id', (int) $request->status_id);
                if ($request->filled('category_id'))
                    $query->whereHas('equipment', fn($q) => $q->where('category_id', (int) $request->category_id));
                $paginated = $query->orderBy('item_name')->paginate(self::CHILDREN_PER_PAGE, ['*'], 'page', $page);
                $data = $paginated->getCollection()->map(fn($i) => $this->formatItem($i))->values()->all();
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => ['current_page' => $paginated->currentPage(), 'last_page' => $paginated->lastPage(), 'total' => $paginated->total()],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to load section'], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $term = trim((string) $request->query('q', ''));
            if ($term === '')
                return response()->json(['success' => true, 'results' => []]);
            $like = "%{$term}%";
            $page = $request->input('page', 1);

            $equipmentMatches = Equipment::with(['category', 'status', 'images'])
                ->withCount(['items as items_count'])
                ->where(fn($q) => $q->where('equipment_name', 'ILIKE', $like)->orWhere('brand', 'ILIKE', $like))
                ->orderBy('equipment_name')->paginate(self::SEARCH_LIMIT, ['*'], 'eq_page', $page);

            $equipmentIds = $equipmentMatches->getCollection()->pluck('equipment_id')->all();

            $itemMatches = EquipmentItem::with(['equipment.category', 'equipment.images', 'condition', 'status'])
                ->where(fn($q) => $q->where('item_name', 'ILIKE', $like)->orWhere('barcode_number', 'ILIKE', $like))
                ->when(!empty($equipmentIds), fn($q) => $q->whereNotIn('equipment_id', $equipmentIds))
                ->orderBy('item_name')->paginate(self::SEARCH_LIMIT, ['*'], 'it_page', $page);

            $results = [];
            foreach ($equipmentMatches as $eq) {
                $results[] = ['result_type' => 'equipment', 'equipment' => $this->formatEquipment($eq)];
            }
            foreach ($itemMatches as $it) {
                $results[] = ['result_type' => 'item', 'item' => $this->formatItem($it)];
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => max($equipmentMatches->lastPage(), $itemMatches->lastPage())
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Search failed'], 500);
        }
    }

    public function items(Request $request, int $equipmentId): JsonResponse
    {
        try {
            $query = EquipmentItem::with(['condition', 'status'])->where('equipment_id', $equipmentId);
            $paginated = $query->orderBy('item_name')->paginate(self::CHILDREN_PER_PAGE);
            $equipment = Equipment::with('images')->find($equipmentId);
            $fallbackImg = $equipment ? $this->primaryImageUrl($equipment) : null;

            return response()->json([
                'success' => true,
                'data' => $paginated->getCollection()->map(fn($i) => $this->formatItem($i, $fallbackImg))->values()->all(),
                'pagination' => ['current_page' => $paginated->currentPage(), 'last_page' => $paginated->lastPage(), 'total' => $paginated->total()],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch items'], 500);
        }
    }

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
            'primary_image_url' => $this->getPrimaryImageUrl($equipment),
        ];
    }

    /**
     * Helper to safely extract the primary image URL.
     * Looks for image_type === 'Primary', otherwise falls back to the lowest sort_order.
     */
    private function getPrimaryImageUrl(Equipment $equipment): ?string
    {
        if (!$equipment->relationLoaded('images') || $equipment->images->isEmpty()) {
            return null;
        }

        // Try to find the designated primary image
        $primary = $equipment->images->firstWhere('image_type', 'Primary');
        
        // Fallback: if no primary is found, just grab the first image by sort order
        if (!$primary) {
            $primary = $equipment->images->sortBy('sort_order')->first();
        }

        return $primary ? $primary->image_url : null;
    }

    private function formatItem($item, ?string $fallbackImage = null): array
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
            'equipment_name' => $item->equipment?->equipment_name,
            'category_name' => $item->equipment?->category?->category_name,
        ];
    }
}