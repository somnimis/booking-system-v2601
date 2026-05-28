<?php

namespace App\Http\Controllers;

use App\Models\RequisitionForm;
use App\Models\Feedback;
use App\Models\Admin;
use App\Models\RequestedFacility;
use App\Models\RequestedEquipment;
use App\Models\RequisitionComment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
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
            
            $isHeadAdmin = $admin->role_id === 1;
            $managedDepartmentIds = $admin->departments()->pluck('departments.department_id')->toArray();
            
            // 1. Get pending approvals (max 3, no pagination)
            $pendingApprovals = $this->getPendingApprovals($isHeadAdmin, $managedDepartmentIds);
            
            // 2. Get latest feedback (max 4, no pagination)
            $latestFeedback = $this->getLatestFeedback();
            
            // 3. Get stats counts
            $stats = $this->getDashboardStats($isHeadAdmin, $managedDepartmentIds);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'pending_approvals' => $pendingApprovals,
                    'latest_feedback' => $latestFeedback,
                    'stats' => $stats
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Dashboard API error: ' . $e->getMessage());
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
            
            $isHeadAdmin = $admin->role_id === 1;
            $managedDepartmentIds = $admin->departments()->pluck('departments.department_id')->toArray();
            
            $page = $request->input('page', 1);
            $perPage = 5; // MAX 5 per page
            
            $today = Carbon::today()->format('Y-m-d');
            
            $query = RequisitionForm::where('status_id', 3) // Reserved status
                ->where(function ($q) use ($today) {
                    $q->whereDate('start_date', '<=', $today)
                      ->whereDate('end_date', '>=', $today);
                })
                ->with(['requestedFacilities.facility', 'requestedEquipment.equipment'])
                ->select([
                    'request_id', 'first_name', 'last_name', 'organization_name',
                    'event_title', 'start_date', 'end_date', 'start_time', 'end_time'
                ])
                ->orderBy('start_time', 'asc');
            
            // Apply department filtering
            if (!$isHeadAdmin && !empty($managedDepartmentIds)) {
                $query->where(function ($q) use ($managedDepartmentIds) {
                    $q->whereHas('requestedFacilities.facility', function ($sq) use ($managedDepartmentIds) {
                        $sq->whereIn('managed_by', $managedDepartmentIds);
                    })->orWhereHas('requestedEquipment.equipment', function ($sq) use ($managedDepartmentIds) {
                        $sq->whereIn('managed_by', $managedDepartmentIds);
                    });
                });
            } elseif (!$isHeadAdmin && empty($managedDepartmentIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0,
                        'data' => []
                    ]
                ]);
            }
            
            $reservations = $query->paginate($perPage, ['*'], 'page', $page);
            
            $mappedData = $reservations->map(function ($reservation) {
                $locations = collect();
                
                foreach ($reservation->requestedFacilities as $facility) {
                    $locations->push($facility->facility->facility_name);
                }
                
                $equipmentNames = $reservation->requestedEquipment->take(2)->map(function ($eq) {
                    return $eq->equipment->equipment_name;
                });
                $locations = $locations->merge($equipmentNames);
                
                return [
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
            
            return response()->json([
                'success' => true,
                'data' => [
                    'current_page' => $reservations->currentPage(),
                    'last_page' => $reservations->lastPage(),
                    'per_page' => $reservations->perPage(),
                    'total' => $reservations->total(),
                    'data' => $mappedData
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Today Events API error: ' . $e->getMessage());
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
            $perPage = 3; // MAX 3 per page
            
            $comments = RequisitionComment::with(['admin', 'requisitionForm' => function ($q) {
                    $q->select('request_id', 'event_title', 'first_name', 'last_name');
                }])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
            
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
                
                return [
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
            
            return response()->json([
                'success' => true,
                'data' => [
                    'current_page' => $comments->currentPage(),
                    'last_page' => $comments->lastPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                    'data' => $activities
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Activity Timeline API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load activity timeline'
            ], 500);
        }
    }
    
    /**
     * Get pending approvals (MAX 3, no pagination)
     */
    private function getPendingApprovals($isHeadAdmin, $managedDepartmentIds)
    {
        $query = RequisitionForm::where('status_id', 1)
            ->with(['formStatus', 'purpose'])
            ->select([
                'request_id', 'first_name', 'last_name', 'organization_name',
                'event_title', 'start_date', 'end_date', 'created_at', 'status_id'
            ])
            ->orderBy('created_at', 'asc')
            ->limit(3);
        
        if (!$isHeadAdmin && !empty($managedDepartmentIds)) {
            $query->where(function ($q) use ($managedDepartmentIds) {
                $q->whereHas('requestedFacilities.facility', function ($sq) use ($managedDepartmentIds) {
                    $sq->whereIn('managed_by', $managedDepartmentIds);
                })->orWhereHas('requestedEquipment.equipment', function ($sq) use ($managedDepartmentIds) {
                    $sq->whereIn('managed_by', $managedDepartmentIds);
                });
            });
        } elseif (!$isHeadAdmin && empty($managedDepartmentIds)) {
            return [];
        }
        
        $forms = $query->get();
        
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
        });
    }
    
    /**
     * Get latest feedback (MAX 4, no pagination)
     */
    private function getLatestFeedback()
    {
        $feedback = Feedback::with(['requisitionForm' => function ($q) {
                $q->select('request_id', 'first_name', 'last_name', 'organization_name');
            }])
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
        });
    }
    
    /**
     * Get dashboard statistics
     */
    private function getDashboardStats($isHeadAdmin, $managedDepartmentIds)
    {
        $pendingQuery = RequisitionForm::where('status_id', 1);
        $reservedQuery = RequisitionForm::where('status_id', 3);
        $awaitingPaymentQuery = RequisitionForm::where('status_id', 2);
        $paymentSubmittedQuery = RequisitionForm::where('status_id', 7);
        
        if (!$isHeadAdmin && !empty($managedDepartmentIds)) {
            $filterCallback = function ($query) use ($managedDepartmentIds) {
                $query->where(function ($subQuery) use ($managedDepartmentIds) {
                    $subQuery->whereHas('requestedFacilities.facility', function ($q) use ($managedDepartmentIds) {
                        $q->whereIn('managed_by', $managedDepartmentIds);
                    })->orWhereHas('requestedEquipment.equipment', function ($q) use ($managedDepartmentIds) {
                        $q->whereIn('managed_by', $managedDepartmentIds);
                    });
                });
            };
            
            $pendingQuery->where($filterCallback);
            $reservedQuery->where($filterCallback);
            $awaitingPaymentQuery->where($filterCallback);
            $paymentSubmittedQuery->where($filterCallback);
        } elseif (!$isHeadAdmin && empty($managedDepartmentIds)) {
            return [
                'pending_count' => 0,
                'reserved_count' => 0,
                'awaiting_payment_count' => 0,
                'payment_submitted_count' => 0,
                'feedback_count' => 0
            ];
        }
        
        return [
            'pending_count' => $pendingQuery->count(),
            'reserved_count' => $reservedQuery->count(),
            'awaiting_payment_count' => $awaitingPaymentQuery->count(),
            'payment_submitted_count' => $paymentSubmittedQuery->count(),
            'feedback_count' => Feedback::count()
        ];
    }
    
    /**
     * Calculate urgency based on how long the request has been waiting
     */
    private function calculateUrgency($createdAt)
    {
        $days = $createdAt->diffInDays(now());
        
        if ($days >= 7) return 'urgent';
        if ($days >= 5) return 'high';
        if ($days >= 3) return 'medium';
        return 'normal';
    }
}