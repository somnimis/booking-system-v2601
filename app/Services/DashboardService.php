<?php
// app/Services/DashboardService.php

namespace App\Services;

use App\Models\Admin;
use App\Models\Feedback;
use App\Models\RequisitionForm;
use App\Models\RequisitionComment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardService
{
    /**
     * Cache duration in seconds for static dashboard sections
     */
    private const CACHE_DURATION = 60;
    private const CACHE_KEY_PREFIX = 'dashboard_';

    /**
     * Get complete dashboard data (static sections only)
     */
    public function getDashboardData(Admin $admin): array
    {
        $departmentIds = $this->getManagedDepartmentIds($admin);
        $isHeadAdmin = $admin->role_id === 1;

        return [
            'pending_approvals' => $this->getPendingApprovals($isHeadAdmin, $departmentIds),
            'latest_feedback' => $this->getLatestFeedback(),
            'stats' => $this->getDashboardStats($isHeadAdmin, $departmentIds),
        ];
    }

    /**
     * Get dashboard statistics with optimized single query
     */
    public function getDashboardStats(bool $isHeadAdmin, array $departmentIds): array
    {
        $cacheKey = $this->getCacheKey('stats', $isHeadAdmin, $departmentIds);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($isHeadAdmin, $departmentIds) {
            $baseQuery = RequisitionForm::query();

            if (!$isHeadAdmin) {
                if (empty($departmentIds)) {
                    return $this->getEmptyStats();
                }
                $baseQuery->visibleToAdmin($departmentIds);
            }

            // Single aggregated query for all status counts
            $statusCounts = $baseQuery
                ->select('status_id', DB::raw('count(*) as total'))
                ->whereIn('status_id', [1, 2, 3, 7])
                ->groupBy('status_id')
                ->pluck('total', 'status_id')
                ->toArray();

            // Feedback count (separate query as it's on a different table)
            $feedbackCount = Feedback::count();

            return [
                'pending_count' => $statusCounts[1] ?? 0,
                'awaiting_payment_count' => $statusCounts[2] ?? 0,
                'reserved_count' => $statusCounts[3] ?? 0,
                'payment_submitted_count' => $statusCounts[7] ?? 0,
                'feedback_count' => $feedbackCount,
            ];
        });
    }

    /**
     * Get pending approvals (max 3)
     */
    public function getPendingApprovals(bool $isHeadAdmin, array $departmentIds): array
    {
        $cacheKey = $this->getCacheKey('pending', $isHeadAdmin, $departmentIds);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($isHeadAdmin, $departmentIds) {
            $query = RequisitionForm::with([
                'formStatus',
                'purpose'
            ])
            ->where('status_id', 1)
            ->orderBy('created_at', 'asc')
            ->limit(3);

            if (!$isHeadAdmin) {
                if (empty($departmentIds)) {
                    return [];
                }
                $query->visibleToAdmin($departmentIds);
            }

            $forms = $query->get([
                'request_id', 'first_name', 'last_name', 'organization_name',
                'event_title', 'start_date', 'end_date', 'created_at', 'status_id'
            ]);

            return $forms->map(function ($form) {
                return [
                    'request_id' => $form->request_id,
                    'requester_name' => trim($form->first_name . ' ' . $form->last_name),
                    'organization' => $form->organization_name ?? 'No Organization',
                    'event_title' => $form->event_title ?? 'Untitled Event',
                    'start_date' => Carbon::parse($form->start_date)->format('M d, Y'),
                    'created_at' => $form->created_at->diffForHumans(),
                    'urgency' => $this->calculateUrgency($form->created_at)
                ];
            })->toArray();
        });
    }

    /**
     * Get today's events with pagination
     */
    public function getTodayEvents(Admin $admin, int $page, int $perPage = 5): LengthAwarePaginator
    {
        $isHeadAdmin = $admin->role_id === 1;
        $departmentIds = $this->getManagedDepartmentIds($admin);

        $today = Carbon::today()->format('Y-m-d');

        $query = RequisitionForm::where('status_id', 3)
            ->where(function ($q) use ($today) {
                $q->whereDate('start_date', '<=', $today)
                  ->whereDate('end_date', '>=', $today);
            })
            ->with([
                'requestedFacilities.facility' => function ($q) {
                    $q->select('facility_id', 'facility_name', 'managed_by');
                },
                'requestedEquipment.equipment' => function ($q) {
                    $q->select('equipment_id', 'equipment_name', 'managed_by');
                }
            ])
            ->select([
                'request_id', 'first_name', 'last_name', 'organization_name',
                'event_title', 'start_date', 'end_date', 'start_time', 'end_time'
            ])
            ->orderBy('start_time', 'asc');

        // Apply department filtering
        if (!$isHeadAdmin) {
            if (empty($departmentIds)) {
                return new LengthAwarePaginator([], 0, $perPage, $page);
            }
            $query->visibleToAdmin($departmentIds);
        }

        $reservations = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform the results
        $mappedData = $reservations->map(function ($reservation) {
            $locations = collect();

            foreach ($reservation->requestedFacilities as $facility) {
                $locations->push($facility->facility->facility_name);
            }

            $equipmentNames = $reservation->requestedEquipment->take(2)->map(function ($eq) {
                return $eq->equipment->equipment_name;
            });
            $locations = $locations->merge($equipmentNames);

            return (object) [
                'request_id' => $reservation->request_id,
                'requester_name' => trim($reservation->first_name . ' ' . $reservation->last_name),
                'organization' => $reservation->organization_name ?? 'No Organization',
                'event_title' => $reservation->event_title ?? 'Untitled Event',
                'locations' => $locations->take(3)->values(),
                'location_count' => $locations->count(),
                'time' => date('g:i A', strtotime($reservation->start_time)),
                'start_date' => $reservation->start_date,
                'end_date' => $reservation->end_date
            ];
        });

        return new LengthAwarePaginator(
            $mappedData,
            $reservations->total(),
            $reservations->perPage(),
            $reservations->currentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    /**
     * Get activity timeline with pagination
     */
    public function getActivityTimeline(Admin $admin, int $page, int $perPage = 3): LengthAwarePaginator
    {
        $query = RequisitionComment::with([
            'admin' => function ($q) {
                $q->select('admin_id', 'first_name', 'last_name');
            },
            'requisitionForm' => function ($q) {
                $q->select('request_id', 'event_title', 'first_name', 'last_name');
            }
        ])
        ->orderBy('created_at', 'desc');

        $comments = $query->paginate($perPage, ['*'], 'page', $page);

        $activities = $comments->map(function ($comment) {
            $adminName = $comment->admin
                ? trim($comment->admin->first_name . ' ' . $comment->admin->last_name)
                : 'Unknown Admin';

            $requestId = $comment->request_id;
            $eventTitle = $comment->requisitionForm
                ? ($comment->requisitionForm->event_title ?? 'Untitled Event')
                : 'Unknown Event';

            $commentText = $comment->comment ?? 'No comment';
            $truncatedComment = strlen($commentText) > 100
                ? substr($commentText, 0, 100) . '...'
                : $commentText;

            return (object) [
                'activity_id' => $comment->comment_id,
                'request_id' => $requestId,
                'request_number' => str_pad($requestId, 4, '0', STR_PAD_LEFT),
                'event_title' => $eventTitle,
                'admin_name' => $adminName,
                'action_type' => 'added a remark',
                'comment' => $truncatedComment,
                'full_comment' => $comment->comment,
                'time_ago' => $comment->created_at->diffForHumans(),
                'created_at' => $comment->created_at->toIso8601String()
            ];
        });

        return new LengthAwarePaginator(
            $activities,
            $comments->total(),
            $comments->perPage(),
            $comments->currentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    /**
     * Get latest feedback (max 4)
     */
    public function getLatestFeedback(): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . 'feedback';

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            $feedback = Feedback::with([
                'requisitionForm' => function ($q) {
                    $q->select('request_id', 'first_name', 'last_name', 'organization_name');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

            return $feedback->map(function ($item) {
                $ratings = [];

                if ($item->system_performance) $ratings[] = 'System: ' . ucfirst($item->system_performance);
                if ($item->booking_experience) $ratings[] = 'Booking: ' . ucfirst($item->booking_experience);
                if ($item->ease_of_use) $ratings[] = 'Ease: ' . ucfirst($item->ease_of_use);

                return [
                    'feedback_id' => $item->feedback_id,
                    'email' => $item->email ?? 'Anonymous',
                    'request_id' => $item->request_id,
                    'requester_name' => $item->requisitionForm
                        ? trim($item->requisitionForm->first_name . ' ' . $item->requisitionForm->last_name)
                        : 'Unknown',
                    'ratings_summary' => implode(' • ', array_slice($ratings, 0, 2)),
                    'additional_feedback' => $item->additional_feedback,
                    'created_at' => $item->created_at->diffForHumans()
                ];
            })->toArray();
        });
    }

    /**
     * Get managed department IDs with caching
     */
    public function getManagedDepartmentIds(Admin $admin): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . 'departments_' . $admin->admin_id;

        return Cache::remember($cacheKey, self::CACHE_DURATION * 5, function () use ($admin) {
            return $admin->departments()
                ->pluck('departments.department_id')
                ->toArray();
        });
    }

    /**
     * Clear all dashboard caches for an admin
     */
    public function clearDashboardCache(Admin $admin): void
    {
        $patterns = [
            self::CACHE_KEY_PREFIX . 'stats_*',
            self::CACHE_KEY_PREFIX . 'pending_*',
            self::CACHE_KEY_PREFIX . 'departments_' . $admin->admin_id,
            self::CACHE_KEY_PREFIX . 'feedback',
        ];

        foreach ($patterns as $pattern) {
            Cache::delete($pattern);
        }
    }

    /**
     * Calculate urgency based on how long the request has been waiting
     */
    private function calculateUrgency($createdAt): string
    {
        $days = $createdAt->diffInDays(now());

        if ($days >= 7) return 'urgent';
        if ($days >= 5) return 'high';
        if ($days >= 3) return 'medium';
        return 'normal';
    }

    /**
     * Get empty stats array
     */
    private function getEmptyStats(): array
    {
        return [
            'pending_count' => 0,
            'reserved_count' => 0,
            'awaiting_payment_count' => 0,
            'payment_submitted_count' => 0,
            'feedback_count' => 0
        ];
    }

    /**
     * Generate cache key
     */
    private function getCacheKey(string $section, bool $isHeadAdmin, array $departmentIds): string
    {
        $identifier = $isHeadAdmin ? 'head' : implode('_', $departmentIds);
        return self::CACHE_KEY_PREFIX . $section . '_' . $identifier;
    }
}