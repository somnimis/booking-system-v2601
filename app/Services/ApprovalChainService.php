<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\RequisitionApproval;
use App\Models\RequisitionForm;
use App\Models\FormStatus;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class ApprovalChainService
{
    /**
     * Create approval chain records for a requisition
     */
    public function createApprovalChain($requisitionForm)
    {
        // Get all unique admins who need to approve in Stage 1 (via departments)
        $stage1AdminIds = collect();

        // 1. Facility managers from requested facilities (managed_by = department_id)
        $facilityDepartmentIds = $requisitionForm->requestedFacilities
            ->pluck('facility.managed_by')
            ->filter()
            ->unique();

        foreach ($facilityDepartmentIds as $deptId) {
            $admins = Admin::whereHas('departments', function ($q) use ($deptId) {
                $q->where('departments.department_id', $deptId);
            })->where('role_id', 3)->pluck('admin_id');
            $stage1AdminIds = $stage1AdminIds->merge($admins);
        }

        // 2. Equipment managers from requested equipment (managed_by = department_id)
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

        // 3. Service managers from requested services (managed_by = department_id)
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

        // 4. Purpose signatory (routes_to = department_id)
        if ($requisitionForm->purpose && $requisitionForm->purpose->routes_to) {
            $admins = Admin::whereHas('departments', function ($q) use ($requisitionForm) {
                $q->where('departments.department_id', $requisitionForm->purpose->routes_to);
            })->where('role_id', 3)->pluck('admin_id');
            $stage1AdminIds = $stage1AdminIds->merge($admins);
        }

        // Remove duplicates
        $stage1AdminIds = $stage1AdminIds->unique()->values();

        // Stage 2: Final Approving Officers (role_id = 2)
        $stage2AdminIds = Admin::where('role_id', 2)->pluck('admin_id');

        // Stage 3: Issuing Officers (role_id = 5)
        $stage3AdminIds = Admin::where('role_id', 5)->pluck('admin_id');

        // Create approval records (deduplicate within loop)
        $createdCount = 0;
        $processedAdmins = [];

        foreach ($stage1AdminIds as $adminId) {
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

        if ($stage1AdminIds->isEmpty()) {
            $this->moveToNextStage($requisitionForm->request_id, 1);
        }

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
     * Move to the next stage in the approval chain
     */
    public function moveToNextStage($requestId, $currentStage)
    {
        $nextStage = $currentStage + 1;

        // Check if there are any pending approvals in the next stage
        $nextStageApprovals = RequisitionApproval::where('request_id', $requestId)
            ->where('stage', $nextStage)
            ->where('status', 'Pending')
            ->get();

        // If no pending approvals in next stage and we're not beyond stage 3, skip to next
        if ($nextStageApprovals->isEmpty() && $nextStage <= 3) {
            Log::info('No pending approvals in stage ' . $nextStage . ', skipping to next stage', [
                'request_id' => $requestId,
                'skipped_stage' => $nextStage
            ]);
            $this->moveToNextStage($requestId, $nextStage);
            return;
        }

        // After Stage 3 is completed
        if ($nextStage > 3) {
            // Stage 3 completed! Move to Stage 4: Payment Assessment
            $awaitingPaymentStatus = FormStatus::where('status_name', 'Awaiting Payment')->first();

            if ($awaitingPaymentStatus) {
                $requisition = RequisitionForm::find($requestId);
                $requisition->status_id = $awaitingPaymentStatus->status_id;
                $requisition->save();

                // Notify Head Administrator (role_id = 1) for payment assessment
                $this->notifyHeadAdminForPaymentAssessment($requestId);

                // Schedule payment reminders (Day 3 warning, Day 5 auto-cancel)
                $this->schedulePaymentReminders($requestId);

                Log::info('Stage 3 completed - Moving to Stage 4 (Payment Assessment)', [
                    'request_id' => $requestId,
                    'new_status' => 'Awaiting Payment'
                ]);
            }
            return;
        }

        // Notify all admins in the next stage
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
     * Process an approval action (approve or reject)
     */
    public function processAction($requestId, $adminId, $action, $remarks = null)
    {
        // Find the pending approval record for this admin and request
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

        // Update the approval record
        $approval->update([
            'acted_by' => $adminId,
            'acted_at' => now(),
            'status' => ucfirst($action),
            'remarks' => $remarks,
            'date_updated' => now()
        ]);

        // Create comment record for activity timeline
        $commentText = ucfirst($action) . " this request (Stage {$approval->stage})" . ($remarks ? ": " . $remarks : "");

        // Note: You'll need to inject RequisitionComment model or handle this differently
        // RequisitionComment::create([...]);

        // Only proceed to next stage if approved
        if ($action === 'approve') {
            // Check if all approvals in current stage are completed
            $currentStage = $approval->stage;
            $pendingInStage = RequisitionApproval::where('request_id', $requestId)
                ->where('stage', $currentStage)
                ->where('status', 'Pending')
                ->count();

            // If no pending approvals in current stage, move to next stage
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
     * Notify an admin about approval required
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
     * Notify Head Administrators for payment assessment
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
     * Schedule payment reminders (to be implemented with queue system)
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