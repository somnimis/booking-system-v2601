<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get initial dashboard data (static content only - no paginated sections)
     * This ensures fastest initial load
     */
    public function getDashboardData(Request $request)
    {
        try {
            /** @var Admin $admin */
            $admin = $request->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $data = $this->dashboardService->getDashboardData($admin);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard API error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data'
            ], 500);
        }
    }

    /**
     * Get today's events with pagination (MAX 5 per page) - LAZY LOADED
     */
    public function getTodayEventsPaginated(Request $request)
    {
        try {
            /** @var Admin $admin */
            $admin = $request->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $page = $request->input('page', 1);
            $perPage = 5;

            $reservations = $this->dashboardService->getTodayEvents($admin, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'current_page' => $reservations->currentPage(),
                    'last_page' => $reservations->lastPage(),
                    'per_page' => $reservations->perPage(),
                    'total' => $reservations->total(),
                    'data' => $reservations->items()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Today Events API error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load today\'s events'
            ], 500);
        }
    }

    /**
     * Get activity timeline with pagination (MAX 3 per page) - LAZY LOADED
     */
    public function getActivityTimelinePaginated(Request $request)
    {
        try {
            /** @var Admin $admin */
            $admin = $request->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $page = $request->input('page', 1);
            $perPage = 3;

            $activities = $this->dashboardService->getActivityTimeline($admin, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'current_page' => $activities->currentPage(),
                    'last_page' => $activities->lastPage(),
                    'per_page' => $activities->perPage(),
                    'total' => $activities->total(),
                    'data' => $activities->items()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Activity Timeline API error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load activity timeline'
            ], 500);
        }
    }
}