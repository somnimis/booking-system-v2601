<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\RequisitionApproval;
use App\Models\RequisitionForm;
use App\Models\FormStatus;
use App\Models\DepartmentRole;
use App\Services\FeeCalculatorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * ApprovalChainService
 * 
 * Manages the multi-stage approval workflow for requisition forms in the system.
 * 
 * The approval process consists of 4 stages:
 * - Stage 1: Department-level approval (role_id = 3 - Department Managers)
 * - Stage 2: Final approval (role_id = 2 - Final Approving Officers)
 * - Stage 3: Issuing approval (role_id = 5 - Issuing Officers)
 * - Role 1 (System Administrator) is NOT a participant in any stage.
 *   Its access is observational and for emergency override only.
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
    protected NotificationService $notificationService;
    protected FeeCalculatorService $feeCalculator;

    public function __construct(
        NotificationService $notificationService,
        FeeCalculatorService $feeCalculator,
    ) {
        $this->notificationService = $notificationService;
        $this->feeCalculator = $feeCalculator;
    }

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

        // ============================================
        // Stage 1: Department Heads for all four resource types
        // ============================================
        $resourceDeptIds = collect();

        // Facilities
        $resourceDeptIds = $resourceDeptIds->merge(
            $requisitionForm->requestedFacilities
                ->map(fn($rf) => $rf->facility?->managed_by)
                ->filter()
        );

        // Equipment
        $resourceDeptIds = $resourceDeptIds->merge(
            $requisitionForm->requestedEquipment
                ->map(fn($re) => $re->equipment?->managed_by)
                ->filter()
        );

        // Services
        $resourceDeptIds = $resourceDeptIds->merge(
            $requisitionForm->requestedServices
                ->map(fn($rs) => $rs->service?->managed_by)
                ->filter()
        );

        // Purpose routing
        if ($requisitionForm->purpose?->routes_to) {
            $resourceDeptIds->push($requisitionForm->purpose->routes_to);
        }

        $resourceDeptIds = $resourceDeptIds->unique()->values();

        // Find the Department Head for each department.
        // Defensive: exclude System Administrators (role 1) even if they were
        // mistakenly assigned as Department Head. Only approver roles (2, 3, 5)
        // participate in Stage 1.
        $stage1AdminIds = DB::table('admins')
            ->join('admin_departments', 'admins.admin_id', '=', 'admin_departments.admin_id')
            ->whereIn('admin_departments.department_id', $resourceDeptIds)
            ->where('admin_departments.role_id', DepartmentRole::HEAD)
            ->whereIn('admins.role_id', Admin::APPROVER_ROLE_IDS)
            ->distinct()
            ->pluck('admins.admin_id');

        // Notify stage-1 approvers that they have a pending action.
        // Zero stage-1 approvers = skip (the form auto-advances below).
        if ($stage1AdminIds->isNotEmpty()) {
            $this->notificationService->notifyStage1Ready($requisitionForm, $stage1AdminIds);
        }

        // ============================================
        // Stage 2: Final Approving Officers
        // ============================================
        $stage2AdminIds = Admin::whereHas('role', function ($q) {
            $q->where('role_title', 'Final Approving Officer');
        })
            ->where('role_id', '!=', Admin::ROLE_SYSTEM_ADMIN) 
            ->pluck('admin_id');

        // ============================================
        // Stage 3: Issuing Officers
        // ============================================
        $stage3AdminIds = Admin::whereHas('role', function ($q) {
            $q->where('role_title', 'Issuing Officer');
        })
            ->where('role_id', '!=', Admin::ROLE_SYSTEM_ADMIN) 
            ->pluck('admin_id');

        // ============================================
        // Create approval records for Stage 1, ensuring no duplicate admin entries
        // ============================================

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

        // ============================================
        // Create approval records for Stage 2
        // ============================================

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

        // ============================================
        // Create approval records for Stage 3
        // ============================================

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
    public function moveToNextStage($requestId, $currentStage, $actingAdminId = null)
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
            $this->moveToNextStage($requestId, $nextStage, $actingAdminId);
            return;
        }

        // Stage 3 completed — readiness signal only. Workflow already
        // advanced to Awaiting Payment when stage 2 cleared.
        if ($nextStage > 3) {
            Log::info('Stage 3 completed — awaiting manual finalization', [
                'request_id' => $requestId,
            ]);
            return;
        }

        // Stage 1 → 2: notify stage-2 approvers.
        if ($currentStage === 1) {
            $form = RequisitionForm::find($requestId);
            if ($form) {
                $stage2Ids = $nextStageApprovals
                    ->where('status', 'Pending')
                    ->pluck('admin_id');
                $this->notificationService->notifyStage2Ready($form, $stage2Ids);
            }
        }

        // Stage 2 → Awaiting Payment: workflow advances AND fee is auto-locked.
        // Finalization fields are set here because "payment due" only makes
        // sense once the fee is frozen — otherwise the amount could still drift.
        if ($currentStage === 2) {
            $form = RequisitionForm::with([
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requisitionFees',
                'purpose',
            ])->find($requestId);

            if ($form) {
                $awaitingPaymentStatus = FormStatus::where('status_name', 'Awaiting Payment')->first();

                if ($awaitingPaymentStatus) {
                    // Auto-finalize: lock the fee at this moment.
                    $form->is_finalized = true;
                    $form->finalized_at = now();
                    $form->finalized_by = $actingAdminId;
                    $form->approved_fee = $this->feeCalculator->calculateApprovedFee($form);

                    // Advance workflow status.
                    $form->status_id = $awaitingPaymentStatus->status_id;
                    $form->save();

                    // Notify the requester that payment is now due.
                    $this->notificationService->sendApprovalEmail($form);

                    Log::info('Stage 2 complete — auto-finalized and advanced to Awaiting Payment', [
                        'request_id' => $requestId,
                        'finalized_by' => $form->finalized_by,
                    ]);
                }
            }
        }

        Log::info('Moved to stage ' . $nextStage, [
            'request_id' => $requestId,
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

        // Map the action verb to the stored past-tense status expected by the
        // ENUM('Pending','Approved','Rejected') column and the rest of the app.
        $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';

        $approval->update([
            'acted_by' => $adminId,
            'acted_at' => now(),
            'status' => $newStatus,
            'remarks' => $remarks,
            'date_updated' => now(),
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
                $this->moveToNextStage($requestId, $currentStage, $adminId);
            }
        }

        return [
            'success' => true,
            'message' => "Request {$action}d successfully",
            'approval_id' => $approval->approval_id
        ];
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
     * - Set up templates for payment reminders
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