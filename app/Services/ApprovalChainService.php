<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\RequisitionApproval;
use App\Models\RequisitionForm;
use App\Models\FormStatus;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

/**
 * ApprovalChainService
 * 
 * Manages the multi-stage approval workflow for requisition forms in the system.
 * 
 * The approval process consists of 4 stages:
 * - Stage 1: Department-level approval (role_id = 3 - Department Managers)
 * - Stage 2: Final approval (role_id = 2 - Final Approving Officers)
 * - Stage 3: Issuing approval (role_id = 5 - Issuing Officers)
 * - Stage 4: Payment assessment (role_id = 1 - Head Administrators)
 * 
 * The service automatically determines which admins need to approve based on:
 * - Facilities requested (their managing departments)
 * - Equipment requested (their managing departments)
 * - Services requested (their managing departments)
 * - Purpose/routing department of the requisition
 * 
 * @package App\Services
 */
class ApprovalChainService
{
    /**
     * Create approval chain records for a requisition form
     * 
     * This method analyzes the requisition's requested items (facilities, equipment, services)
     * and determines all relevant department managers who must approve at Stage 1.
     * It then creates approval records for all three stages in the database.
     * 
     * Stage 1 Approving Officers (role_id = 3):
     * - Department managers from:
     *   • Facilities' managing departments (facility.managed_by)
     *   • Equipment's managing departments (equipment.managed_by)
     *   • Services' managing departments (service.managed_by)
     *   • Purpose's routing department (purpose.routes_to)
     * - Duplicates are automatically filtered out
     * 
     * Stage 2 Final Approving Officers (role_id = 2):
     * - All admins with final approval authority
     * 
     * Stage 3 Issuing Officers (role_id = 5):
     * - All admins responsible for issuing approved requests
     * 
     * @param RequisitionForm $requisitionForm The requisition form to create approvals for
     * @return int Total number of approval records created
     */
    public function createApprovalChain($requisitionForm)
    {
        // Initialize collection to store unique admin IDs for Stage 1
        $stage1AdminIds = collect();

        // 1. Collect department managers from requested facilities
        // Each facility has a 'managed_by' field indicating which department oversees it
        $facilityDepartmentIds = $requisitionForm->requestedFacilities
            ->pluck('facility.managed_by')
            ->filter()              // Remove null/empty values
            ->unique();             // Remove duplicate department IDs

        foreach ($facilityDepartmentIds as $deptId) {
            // Find admins with role_id=3 (department managers) belonging to this department
            $admins = Admin::whereHas('departments', function ($q) use ($deptId) {
                $q->where('departments.department_id', $deptId);
            })->where('role_id', 3)->pluck('admin_id');
            $stage1AdminIds = $stage1AdminIds->merge($admins);
        }

        // 2. Collect department managers from requested equipment
        $equipmentDepartmentIds = $requisitionForm->requestedEquipment
            ->pluck('equipment.managed_by')
            ->filter()
            ->unique();

        foreach ($equipmentDepartmentIds as $deptId) {
            $admins = Admin::whereHas('departments', function ($q) use ($deptId) {
                $q->where('departments.department_id', $deptId);
            })->where('role_id', 3)->pluck('admin_id');
            $stage1AdminIds = $stage1AdminIds->merge($admins);
        }

        // 3. Collect department managers from requested services
        $serviceDepartmentIds = $requisitionForm->requestedServices
            ->pluck('service.managed_by')
            ->filter()
            ->unique();

        foreach ($serviceDepartmentIds as $deptId) {
            $admins = Admin::whereHas('departments', function ($q) use ($deptId) {
                $q->where('departments.department_id', $deptId);
            })->where('role_id', 3)->pluck('admin_id');
            $stage1AdminIds = $stage1AdminIds->merge($admins);
        }

        // 4. Add the purpose signatory's department manager
        // The 'routes_to' field specifies which department should approve based on the requisition's purpose
        if ($requisitionForm->purpose && $requisitionForm->purpose->routes_to) {
            $admins = Admin::whereHas('departments', function ($q) use ($requisitionForm) {
                $q->where('departments.department_id', $requisitionForm->purpose->routes_to);
            })->where('role_id', 3)->pluck('admin_id');
            $stage1AdminIds = $stage1AdminIds->merge($admins);
        }

        // Remove any duplicate admin IDs from Stage 1 collection
        $stage1AdminIds = $stage1AdminIds->unique()->values();

        // Stage 2: Get all Final Approving Officers (role_id = 2)
        $stage2AdminIds = Admin::where('role_id', 2)->pluck('admin_id');

        // Stage 3: Get all Issuing Officers (role_id = 5)
        $stage3AdminIds = Admin::where('role_id', 5)->pluck('admin_id');

        // Create approval records for Stage 1, ensuring no duplicate admin entries
        $createdCount = 0;
        $processedAdmins = [];

        foreach ($stage1AdminIds as $adminId) {
            // Skip if this admin already processed (duplicate prevention)
            if (in_array($adminId, $processedAdmins))
                continue;
            $processedAdmins[] = $adminId;

            RequisitionApproval::create([
                'request_id' => $requisitionForm->request_id,
                'admin_id' => $adminId,
                'status' => 'Pending',
                'stage' => 1,
                'date_updated' => now()
            ]);
            $createdCount++;
        }

        // Create approval records for Stage 2
        foreach ($stage2AdminIds as $adminId) {
            RequisitionApproval::create([
                'request_id' => $requisitionForm->request_id,
                'admin_id' => $adminId,
                'status' => 'Pending',
                'stage' => 2,
                'date_updated' => now()
            ]);
            $createdCount++;
        }

        // Create approval records for Stage 3
        foreach ($stage3AdminIds as $adminId) {
            RequisitionApproval::create([
                'request_id' => $requisitionForm->request_id,
                'admin_id' => $adminId,
                'status' => 'Pending',
                'stage' => 3,
                'date_updated' => now()
            ]);
            $createdCount++;
        }

        // If no Stage 1 approvers were identified, automatically advance to Stage 2
        // This can happen for requisitions that don't require department-level approval
        if ($stage1AdminIds->isEmpty()) {
            $this->moveToNextStage($requisitionForm->request_id, 1);
        }

        // Log the approval chain creation for audit trail
        Log::info('Approval chain created', [
            'request_id' => $requisitionForm->request_id,
            'total_approvals' => $createdCount,
            'stage1_count' => count($processedAdmins),
            'stage2_count' => $stage2AdminIds->count(),
            'stage3_count' => $stage3AdminIds->count()
        ]);

        return $createdCount;
    }

    /**
     * Move the requisition to the next stage in the approval workflow
     * 
     * This method handles the transition between approval stages:
     * - Skips stages with no pending approvals (auto-advance)
     * - Notifies admins in the next stage
     * - Handles the special transition to Stage 4 (Payment Assessment)
     * 
     * Stage Transition Logic:
     * - Stage 1 → Stage 2 (if Stage 2 has pending approvals)
     * - Stage 2 → Stage 3 (if Stage 3 has pending approvals)
     * - Stage 3 → Stage 4 (Payment Assessment, handled separately)
     * 
     * @param int $requestId The requisition ID
     * @param int $currentStage The completed stage (1, 2, or 3)
     * @return void
     */
    public function moveToNextStage($requestId, $currentStage)
    {
        $nextStage = $currentStage + 1;

        // Check if there are any pending approvals in the next stage
        $nextStageApprovals = RequisitionApproval::where('request_id', $requestId)
            ->where('stage', $nextStage)
            ->where('status', 'Pending')
            ->get();

        // If no pending approvals in the next stage and we're not beyond stage 3,
        // skip this stage and move to the next one (recursive skip)
        if ($nextStageApprovals->isEmpty() && $nextStage <= 3) {
            Log::info('No pending approvals in stage ' . $nextStage . ', skipping to next stage', [
                'request_id' => $requestId,
                'skipped_stage' => $nextStage
            ]);
            $this->moveToNextStage($requestId, $nextStage);
            return;
        }

        // Handle completion of Stage 3 - transition to payment assessment
        if ($nextStage > 3) {
            // Stage 3 completed! Move to Stage 4: Payment Assessment
            $awaitingPaymentStatus = FormStatus::where('status_name', 'Awaiting Payment')->first();

            if ($awaitingPaymentStatus) {
                // Update requisition status to "Awaiting Payment"
                $requisition = RequisitionForm::find($requestId);
                $requisition->status_id = $awaitingPaymentStatus->status_id;
                $requisition->save();

                // Notify Head Administrators (role_id = 1) that payment assessment is needed
                $this->notifyHeadAdminForPaymentAssessment($requestId);

                // Schedule automated payment reminders (Day 3 warning, Day 5 auto-cancel)
                $this->schedulePaymentReminders($requestId);

                Log::info('Stage 3 completed - Moving to Stage 4 (Payment Assessment)', [
                    'request_id' => $requestId,
                    'new_status' => 'Awaiting Payment'
                ]);
            }
            return;
        }

        // Notify all admins in the next stage about their pending approvals
        $stageNames = [
            1 => 'Approving Officers',
            2 => 'Final Approving Officer',
            3 => 'Issuing Officer'
        ];

        foreach ($nextStageApprovals as $approval) {
            $this->notifyAdmin($approval->admin_id, $requestId, $nextStage, $stageNames[$nextStage]);
        }

        Log::info('Moved to stage ' . $nextStage, [
            'request_id' => $requestId,
            'stage_name' => $stageNames[$nextStage],
            'notified_admins' => $nextStageApprovals->pluck('admin_id')->toArray()
        ]);
    }

    /**
     * Process an approval or rejection action from an admin
     * 
     * This method handles the core approval workflow:
     * - Validates the approval exists and is pending
     * - Records the admin's decision (approve/reject)
     * - If approved, checks if the entire stage is complete
     * - Automatically advances to the next stage when current stage is fully approved
     * 
     * Important: Only approved actions trigger stage advancement.
     * Rejected requisitions stop the workflow (no further stages processed).
     * 
     * @param int $requestId The requisition ID
     * @param int $adminId The ID of the admin taking action
     * @param string $action The action taken ('approve' or 'reject')
     * @param string|null $remarks Optional remarks or comments from the admin
     * @return array Response with success status and message
     */
    public function processAction($requestId, $adminId, $action, $remarks = null)
    {
        // Find the pending approval record for this admin and requisition
        $approval = RequisitionApproval::where('request_id', $requestId)
            ->where('admin_id', $adminId)
            ->where('status', 'Pending')
            ->first();

        if (!$approval) {
            return [
                'success' => false,
                'message' => 'No pending approval found for this admin'
            ];
        }

        // Update the approval record with the admin's decision
        $approval->update([
            'acted_by' => $adminId,
            'acted_at' => now(),
            'status' => ucfirst($action),  // 'approve' becomes 'Approve'
            'remarks' => $remarks,
            'date_updated' => now()
        ]);

        // TODO: Create comment record for activity timeline
        // This would log the action in the requisition's activity log
        $commentText = ucfirst($action) . " this request (Stage {$approval->stage})" . ($remarks ? ": " . $remarks : "");

        // Only proceed to next stage if the action was approval
        if ($action === 'approve') {
            // Check if all approvals in the current stage are completed
            $currentStage = $approval->stage;
            $pendingInStage = RequisitionApproval::where('request_id', $requestId)
                ->where('stage', $currentStage)
                ->where('status', 'Pending')
                ->count();

            // If no pending approvals remain, move to the next stage
            if ($pendingInStage === 0) {
                $this->moveToNextStage($requestId, $currentStage);
            }
        }

        return [
            'success' => true,
            'message' => "Request {$action}d successfully",
            'approval_id' => $approval->approval_id
        ];
    }

    /**
     * Send a notification to an admin about an approval request
     * 
     * Creates a notification record in the database for the specified admin.
     * Notifications are used for:
     * - Alerting admins when their approval is required
     * - Providing context about the requisition (requestor name, ID, stage)
     * 
     * @param int $adminId The ID of the admin to notify
     * @param int $requestId The requisition ID
     * @param int $stage The approval stage number (1, 2, or 3)
     * @param string $stageName Human-readable name of the stage
     * @return void
     */
    private function notifyAdmin($adminId, $requestId, $stage, $stageName)
    {
        $requisition = RequisitionForm::find($requestId);

        Notification::create([
            'admin_id' => $adminId,
            'type' => 'approval_request',
            'message' => "Requisition #{$requisition->request_id} from {$requisition->first_name} {$requisition->last_name} requires your approval (Stage {$stage}: {$stageName}).",
            'request_id' => $requisition->request_id,
            'is_read' => false
        ]);
    }
    
    /**
     * Notify all Head Administrators that payment assessment is needed
     * 
     * This is called after Stage 3 (Issuing) is completed.
     * Head Administrators (role_id = 1) are responsible for:
     * - Reviewing the financial aspects of the requisition
     * - Confirming fund availability
     * - Approving or rejecting payment
     * 
     * @param int $requestId The requisition ID
     * @return void
     */
    private function notifyHeadAdminForPaymentAssessment($requestId)
    {
        $requisition = RequisitionForm::find($requestId);

        // Get all Head Administrators (role_id = 1)
        $headAdmins = Admin::where('role_id', 1)->get();

        foreach ($headAdmins as $admin) {
            Notification::create([
                'admin_id' => $admin->admin_id,
                'type' => 'payment_assessment',
                'message' => "Requisition #{$requisition->request_id} from {$requisition->first_name} {$requisition->last_name} is awaiting payment assessment.",
                'request_id' => $requisition->request_id,
                'is_read' => false
            ]);
        }

        Log::info('Head Administrators notified for payment assessment', [
            'request_id' => $requestId,
            'notified_admins' => $headAdmins->pluck('admin_id')->toArray()
        ]);
    }

    /**
     * Schedule automated reminders for payment completion
     * 
     * This method sets up the timeline for payment follow-up:
     * - Day 3: Send a warning notification about pending payment
     * - Day 5: Automatically cancel the requisition if payment not received
     * 
     * Note: The actual implementation requires a queue system (Laravel Queues)
     * with delayed job dispatching.
     * 
     * Payment Timeline:
     * - Day 0: Requisition approved, awaiting payment
     * - Day 3: Warning notification sent
     * - Day 5: Automatic cancellation if payment not completed
     * 
     * TODO Implementation Requirements:
     * - Create SendWarningEmailJob to send payment reminders
     * - Create AutoCancelFormJob to handle automatic cancellation
     * - Configure queue worker for delayed job processing
     * - Set up email templates for payment reminders
     * 
     * @param int $requestId The requisition ID
     * @return void
     */
    private function schedulePaymentReminders($requestId)
    {
        Log::info('Payment reminders scheduled', [
            'request_id' => $requestId,
            'day_3_warning' => now()->addDays(3)->toDateTimeString(),
            'day_5_auto_cancel' => now()->addDays(5)->toDateTimeString()
        ]);

        // TODO: Implement queue jobs for:
        // - SendWarningEmailJob::dispatch($requestId)->delay(now()->addDays(3));
        // - AutoCancelFormJob::dispatch($requestId)->delay(now()->addDays(5));
    }
}