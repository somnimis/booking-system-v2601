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
use App\Models\EquipmentTransaction;
use App\Models\RequisitionApproval;
use App\Models\FormStatus;

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

        // Role-aware stat cards: signatories get personal workload cards,
        // system admin gets oversight cards, other roles get none (hidden in UI).
        $roleStats = null;
        if (in_array((int) $admin->role_id, Admin::APPROVER_ROLE_IDS, true)) {
            $roleStats = $this->getSignatoryStats($admin);
        } elseif ((int) $admin->role_id === Admin::ROLE_SYSTEM_ADMIN) {
            $roleStats = $this->getSystemAdminStats();
        }

        return [
            'pending_approvals' => $this->getPendingApprovals($isHeadAdmin, $departmentIds),
            'latest_feedback' => $this->getLatestFeedback(),
            'stats' => $this->getDashboardStats($isHeadAdmin, $departmentIds),
            'ops_stats' => $this->getOpsStats(),
            'role_stats' => $roleStats, // null for role 4
        ];
    }

    /**
     * Operational stats for the dashboard top row.
     *
     * These are org-wide metrics (not department-filtered) since they describe
     * system-wide activity, not an admin's personal work queue.
     */
    public function getOpsStats(): array
    {
        return Cache::remember(self::CACHE_KEY_PREFIX . 'ops_stats', self::CACHE_DURATION, function () {
            $today = Carbon::today();
            $weekEnd = Carbon::today()->addDays(7);

            $todayBookings = RequisitionForm::where('status_id', 4)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->count();

            $weekBookings = RequisitionForm::where('status_id', 4)
                ->whereDate('start_date', '>=', $today)
                ->whereDate('start_date', '<=', $weekEnd)
                ->count();

            $overdueReturns = EquipmentTransaction::whereNull('returned_at')
                ->whereNotNull('expected_return_at')
                ->where('expected_return_at', '<', now())
                ->count();

            $satisfactionAvg = (float) (Feedback::whereNotNull('system_performance')
                ->where('created_at', '>=', now()->subDays(30))
                ->avg('system_performance') ?? 0);

            $satisfactionCount = Feedback::whereNotNull('system_performance')
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            return [
                'today_bookings' => $todayBookings,
                'week_bookings' => $weekBookings,
                'overdue_returns' => $overdueReturns,
                'satisfaction' => [
                    'value' => round($satisfactionAvg, 2),
                    'label' => $satisfactionAvg > 0
                        ? Feedback::averageToTier($satisfactionAvg)
                        : 'No data',
                    'count' => $satisfactionCount,
                ],
            ];
        });
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
                ->whereIn('status_id', [1, 2, 3, 4])
                ->groupBy('status_id')
                ->pluck('total', 'status_id')
                ->toArray();

            // Feedback count (separate query as it's on a different table)
            $feedbackCount = Feedback::count();

            return [
                'pending_count' => $statusCounts[1] ?? 0,           // Pending Approval
                'awaiting_payment_count' => $statusCounts[2] ?? 0,  // Awaiting Payment
                'verifying_count' => $statusCounts[3] ?? 0,         // Verifying Payment
                'reserved_count' => $statusCounts[4] ?? 0,          // Reserved
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
                'request_id',
                'first_name',
                'last_name',
                'organization_name',
                'event_title',
                'start_date',
                'end_date',
                'created_at',
                'status_id'
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

        $query = RequisitionForm::where('status_id', 4)
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
                'request_id',
                'first_name',
                'last_name',
                'organization_name',
                'event_title',
                'start_date',
                'end_date',
                'start_time',
                'end_time'
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

                if ($item->system_performance)
                    $ratings[] = 'System: ' . ucfirst($item->system_performance);
                if ($item->booking_experience)
                    $ratings[] = 'Booking: ' . ucfirst($item->booking_experience);
                if ($item->ease_of_use)
                    $ratings[] = 'Ease: ' . ucfirst($item->ease_of_use);

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
            self::CACHE_KEY_PREFIX . 'ops_stats',
            self::CACHE_KEY_PREFIX . 'system_admin_stats',
            self::CACHE_KEY_PREFIX . 'signatory_stats_' . $admin->admin_id,
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

        if ($days >= 7)
            return 'urgent';
        if ($days >= 5)
            return 'high';
        if ($days >= 3)
            return 'medium';
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
            'verifying_count' => 0,
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

    /**
     * Personal stats for signatory roles (2, 3, 5).
     *
     * All counts are scoped strictly to the logged-in admin (admin_id = me).
     * Scope mirrors ReservationListingsController::paginatedActionableRequests
     * so the card numbers match the "Needs My Action" list page.
     */
    public function getSignatoryStats(Admin $admin): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . 'signatory_stats_' . $admin->admin_id;

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($admin) {
            $verifyingStatusId = FormStatus::where('status_name', 'Verifying Payment')
                ->value('status_id');

            // -- Needs My Review: active-stage pending rows for this admin --
            $needsReview = $this->actionableFormsQuery($admin, $verifyingStatusId)->count();

            // -- Due This Week: same scope, event starts within 7 days --
            $today = Carbon::today();
            $dueThisWeek = $this->actionableFormsQuery($admin, $verifyingStatusId)
                ->whereDate('start_date', '>=', $today)
                ->whereDate('start_date', '<=', $today->copy()->addDays(7))
                ->count();

            // -- Overdue Tasks: pending approval rows for this admin older than 3 days --
            // Measures time waiting regardless of stage, per product decision.
            $overdueTasks = RequisitionApproval::where('admin_id', $admin->admin_id)
                ->where('status', 'Pending')
                ->where('created_at', '<', now()->subDays(3))
                ->count();

            // -- Approved This Week: actions I actually took in the last 7 days --
            $approvedThisWeek = RequisitionApproval::where('acted_by', $admin->admin_id)
                ->where('status', 'Approved')
                ->where('acted_at', '>=', now()->subDays(7))
                ->count();

            return [
                'needs_review' => $needsReview,
                'due_this_week' => $dueThisWeek,
                'overdue_tasks' => $overdueTasks,
                'approved_this_week' => $approvedThisWeek,
            ];
        });
    }

    /**
     * Oversight stats for System Administrator (role 1).
     *
     * Role 1 does not approve/reject — it overrides Verifying Payment forms
     * and monitors the pipeline. Cards reflect that remit.
     *
     * Edge cases handled:
     * - Forms closed/cancelled but still carrying an active status are excluded.
     * - "Awaiting Final Approval" uses whereHas on the requisitionApprovals
     *   relationship (via a subquery) rather than distinct+count, so multiple
     *   Stage-2 approvers per form never inflate the count.
     * - Missing 'Verifying Payment' FormStatus row returns 0 instead of throwing.
     */
    public function getSystemAdminStats(): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . 'system_admin_stats';

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            $verifyingStatusId = FormStatus::where('status_name', 'Verifying Payment')
                ->value('status_id');

            // Forms in Verifying Payment awaiting signatory receipt review.
            $verifyingPayment = $verifyingStatusId
                ? RequisitionForm::where('status_id', $verifyingStatusId)
                    ->where('is_closed', false)
                    ->count()
                : 0;

            // Stage-2 bottleneck: forms still waiting on a Final Approving Officer.
            // Subquery keeps multiple Stage-2 approvers from double-counting the form.
            // Fee auto-locks when Stage 2 clears (see ApprovalChainService::moveToNextStage).
            $awaitingFinalization = RequisitionForm::whereHas('requisitionApprovals', function ($q) {
                $q->where('stage', 2)->where('status', 'Pending');
            })->count();

            // Intake this week: forms still at Pending Approval (status_id = 1)
            // submitted within the last 7 days. Excludes everything that has
            // moved past stage 1 or was closed/rejected.
            $pendingThisWeek = RequisitionForm::where('status_id', 1)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            // Today's active events.
            $today = Carbon::today();
            $todayBookings = RequisitionForm::where('status_id', 4)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->count();

            return [
                'today_bookings'        => $todayBookings,
                'pending_this_week'     => $pendingThisWeek,
                'awaiting_finalization' => $awaitingFinalization,
                'verifying_payment'     => $verifyingPayment,
            ];
        });
    }

    /**
     * Build the query that mirrors "actionable requests" for an approver.
     *
     * Kept in the service so the dashboard cards and the actionable list
     * page share identical semantics. If the list page ever drifts, update
     * this method too (or refactor the controller to call it).
     */
    private function actionableFormsQuery(Admin $admin, ?int $verifyingStatusId)
    {
        return RequisitionForm::query()
            ->whereHas('requisitionApprovals', function ($q) use ($admin, $verifyingStatusId) {
                $q->where('admin_id', $admin->admin_id)
                    ->where('status', 'Pending')
                    ->where(function ($stageQ) use ($verifyingStatusId) {
                        // Stage 1: this row is stage 1
                        $stageQ->where('stage', 1)
                            // Stage 2: no pending stage-1 rows exist
                            ->orWhere(function ($q2) {
                            $q2->where('stage', 2)
                                ->whereNotExists(function ($sub) {
                                    $sub->select(DB::raw(1))
                                        ->from('requisition_approvals as ra1')
                                        ->whereColumn('ra1.request_id', 'requisition_approvals.request_id')
                                        ->where('ra1.stage', 1)
                                        ->where('ra1.status', 'Pending');
                                });
                        })
                            // Stage 3: form is Verifying Payment, no pending stage 1/2 rows
                            ->orWhere(function ($q3) use ($verifyingStatusId) {
                            $q3->where('stage', 3)
                                ->whereExists(function ($sub) use ($verifyingStatusId) {
                                    $sub->select(DB::raw(1))
                                        ->from('requisition_forms as rf')
                                        ->whereColumn('rf.request_id', 'requisition_approvals.request_id')
                                        ->where('rf.status_id', $verifyingStatusId);
                                })
                                ->whereNotExists(function ($sub) {
                                    $sub->select(DB::raw(1))
                                        ->from('requisition_approvals as ra2')
                                        ->whereColumn('ra2.request_id', 'requisition_approvals.request_id')
                                        ->whereIn('ra2.stage', [1, 2])
                                        ->where('ra2.status', 'Pending');
                                });
                        });
                    });
            });
    }

}