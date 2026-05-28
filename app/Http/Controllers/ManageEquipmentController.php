<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ManageEquipmentController
 *
 * Dedicated, merged controller for the manage-equipment admin page.
 * Consolidates multiple API calls on page load:
 *   - GET /api/equipment (admin version with pagination)
 *   - GET /api/availability-statuses
 *   - GET /api/equipment-categories
 *
 * Now resolved in a single request:
 *   GET /api/admin/manage-equipment
 *
 * Supports:
 *   - Server-side pagination (12 per page)
 *   - Filter by status_id
 *   - Filter by category_id
 *   - Search by equipment_name
 *   - Returns filter metadata (statuses, categories) in first-page response
 */
class ManageEquipmentController extends Controller
{
    // Number of equipment items returned per page
    private const PER_PAGE = 12;

    /**
     * GET /api/admin/manage-equipment
     *
     * Returns paginated equipment plus filter metadata (statuses, categories).
     * Filter metadata is always included so the frontend can populate dropdowns
     * without an extra round-trip.
     *
     * Query Parameters:
     *   page          int     Page number (default: 1)
     *   status_id     int     Filter by availability_status.status_id
     *   category_id   int     Filter by equipment_categories.category_id
     *   search        string  Partial match on equipment_name
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // ── Build the equipment query ─────────────────────────────────
            $query = Equipment::with([
                'category',
                'status',
                'department',
                'images',
                'items' => function ($query) {
                    $query->where('status_id', '!=', 5); // Exclude deleted items
                },
                'items.condition'
            ]);

            // Filter: status
            if ($request->filled('status_id')) {
                $query->where('status_id', (int) $request->status_id);
            }

            // Filter: category
            if ($request->filled('category_id')) {
                $query->where('category_id', (int) $request->category_id);
            }

            // Filter: search
            if ($request->filled('search')) {
                $term = $request->search;
                $query->where('equipment_name', 'LIKE', "%{$term}%");
            }

            // ── Paginate ───────────────────────────────────────────────────
            $paginated = $query
                ->orderBy('equipment_name')
                ->paginate(self::PER_PAGE);

            // ── Format equipment rows with calculated quantities ──────────
            $equipment = $paginated->getCollection()->map(
                fn($e) => $this->formatEquipment($e)
            );

            // ── Filter metadata (always returned) ─────────────────────────
            $statuses = DB::table('availability_statuses')
                ->select('status_id', 'status_name')
                ->orderBy('status_name')
                ->get();

            $categories = DB::table('equipment_categories')
                ->select('category_id', 'category_name')
                ->orderBy('category_name')
                ->get();

            return response()->json([
                'success' => true,

                // Paginated equipment data
                'data' => $equipment,

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
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format a single Equipment model into the shape expected by the blade.
     */
    private function formatEquipment(Equipment $equipment): array
    {
        // Calculate available quantity based on items with status_id = 1 
        // and condition_id in [1, 2, 3] (Good, Fair, Like New)
        $availableCount = $equipment->items
            ->filter(function ($item) {
                return $item->status_id == 1 && in_array($item->condition_id, [1, 2, 3]);
            })
            ->count();

        $totalCount = $equipment->items->count();

        // Find primary image
        $primaryImage = "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png";
        
        if ($equipment->images && $equipment->images->count() > 0) {
            $validImages = $equipment->images->filter(function ($img) {
                return $img->image_url && trim($img->image_url) !== '';
            });
            
            if ($validImages->count() > 0) {
                $sortOrder1Image = $validImages->firstWhere('sort_order', 1);
                $primaryTypeImage = $validImages->firstWhere('image_type', 'Primary');
                
                $primaryImage = $sortOrder1Image->image_url ?? 
                    $primaryTypeImage->image_url ?? 
                    $validImages->first()->image_url ?? 
                    $primaryImage;
            }
        }

        return [
            'equipment_id' => $equipment->equipment_id,
            'equipment_name' => $equipment->equipment_name,
            'description' => $equipment->description,
            'brand' => $equipment->brand,
            'storage_location' => $equipment->storage_location,
            'base_fee' => $equipment->base_fee,
            'rate_type' => $equipment->rate_type,
            'maximum_rental_hour' => $equipment->maximum_rental_hour,
            
            // Category data
            'category' => [
                'category_id' => $equipment->category_id,
                'category_name' => $equipment->category->category_name ?? 'Uncategorized',
            ],
            
            // Status data
            'status' => [
                'status_id' => $equipment->status_id,
                'status_name' => $equipment->status->status_name ?? 'Unknown',
                'color_code' => $equipment->status->color_code ?? '#6c757d',
            ],
            
            // Department data (primary department for backward compatibility)
            'department' => $equipment->department ? [
                'department_id' => $equipment->department_id,
                'department_name' => $equipment->department->department_name,
            ] : null,
            
            // All departments (for future use)
            'departments' => $equipment->departments->map(fn($dept) => [
                'department_id' => $dept->department_id,
                'department_name' => $dept->department_name,
            ]),
            
            // Quantities
            'available_quantity' => $availableCount,
            'total_quantity' => $totalCount,
            
            // Images
            'images' => $equipment->images->map(fn($img) => [
                'image_id' => $img->image_id,
                'image_url' => $img->image_url,
                'image_type' => $img->image_type,
                'sort_order' => $img->sort_order,
                'description' => $img->description,
                'cloudinary_public_id' => $img->cloudinary_public_id,
            ])->values()->toArray(),
            
            // Items (for reference)
            'items' => $equipment->items->map(fn($item) => [
                'item_id' => $item->item_id,
                'item_name' => $item->item_name,
                'condition_id' => $item->condition_id,
                'condition_name' => $item->condition->condition_name ?? null,
                'barcode_number' => $item->barcode_number,
                'status_id' => $item->status_id,
            ]),
            
            'created_at' => $equipment->created_at,
            'updated_at' => $equipment->updated_at,
        ];
    }
}