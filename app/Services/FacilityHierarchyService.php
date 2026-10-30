<?php

namespace App\Services;

use App\Models\Facility;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FacilityHierarchyService
{
    /**
     * Get paginated standalone venues (Top-level, no children).
     */
    public function getVenues(int $perPage, int $page, ?int $facilityId): LengthAwarePaginator
    {
        $query = Facility::select('facility_id', 'facility_name', 'capacity', 'base_fee')
            ->where('status_id', 1)
            ->whereNull('parent_facility_id')
            ->whereDoesntHave('childFacilities');

        if ($facilityId) {
            $query->where('facility_id', $facilityId);
        }

        return $query->orderBy('facility_name')->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get paginated buildings (Top-level, with children).
     * Eager-loads all children, but the JS will render them.
     */
    public function getBuildingsWithChildren(int $perPage, int $page, ?int $parentId): LengthAwarePaginator
    {
        $query = Facility::select('facility_id', 'facility_name', 'capacity', 'base_fee')
            ->where('status_id', 1)
            ->whereNull('parent_facility_id')
            ->whereHas('childFacilities', function ($q) {
                $q->where('status_id', 1);
            });

        if ($parentId) {
            $query->where('facility_id', $parentId);
        }

        return $query
            ->with(['childFacilities' => function ($q) {
                $q->select('facility_id', 'facility_name', 'capacity', 'base_fee', 'parent_facility_id')
                  ->where('status_id', 1)
                  ->orderBy('facility_name');
            }])
            ->orderBy('facility_name')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get a single room's data (used when a specific room is selected).
     */
    public function getSingleRoom(int $facilityId): array
    {
        $room = Facility::select('facility_id', 'facility_name', 'capacity', 'base_fee', 'parent_facility_id')
            ->where('status_id', 1)
            ->where('facility_id', $facilityId)
            ->first();

        if (!$room) return [];

        $parent = Facility::select('facility_id', 'facility_name', 'capacity', 'base_fee')
            ->where('facility_id', $room->parent_facility_id)
            ->first();

        return [
            'facility_id' => $parent->facility_id,
            'facility_name' => $parent->facility_name,
            'capacity' => $parent->capacity,
            'base_fee' => $parent->base_fee,
            'is_parent' => true,
            'children' => [[
                'facility_id' => $room->facility_id,
                'facility_name' => $room->facility_name,
                'capacity' => $room->capacity,
                'base_fee' => $room->base_fee,
                'is_parent' => false,
                'parent_facility_id' => $room->parent_facility_id,
            ]]
        ];
    }

    /**
     * Helper to standardize paginator meta output.
     */
    public function getPaginatorMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
        ];
    }
}