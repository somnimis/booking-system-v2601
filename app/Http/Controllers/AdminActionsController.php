<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RequestedFacility;
use App\Models\RequestedEquipment;
use App\Models\RequisitionFee;
use App\Models\FormStatus;
use App\Models\CompletedTransaction;
use App\Models\RequisitionForm;
use App\Models\RequisitionComment;
use App\Services\FeeCalculatorService;
use App\Services\CheckAvailabilityService;
use App\Services\NotificationService;
use App\Services\ReceiptService;
use App\Services\ApprovalChainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/* AdminActionsController — Summary Documentation

This controller manages the entire admin-side approval and fee handling process
for requisition forms within the booking system. It provides endpoints for viewing,
approving, rejecting, and modifying requests, as well as managing related financial actions.

The controller includes methods to fetch pending and completed requests, allowing
admins to review requisition forms that are awaiting approval or have been finalized.
Only authorized roles such as Head Admin, Vice President of Administration, and
Approving Officer can perform approval or rejection actions. When a request is approved
or rejected, a corresponding record is created in the requisition_approvals table,
capturing details such as the admin who performed the action, remarks, and the timestamp.

It also handles the financial side of the approval process. Through dedicated methods,
admins can add fees or discounts to a requisition form, each stored in the
requisition_fees table with details like label, amount, and references to any
waived facilities or equipment. Additional methods allow specific items or entire
forms to be marked as waived, updating related database records to reflect that
charges have been removed or discounted.

Overall, the AdminApprovalController serves as the core module for managing
the administrative workflow of requisition approval, ensuring that all actions,
statuses, and fee-related transactions are properly validated, recorded, and
restricted to the appropriate user roles.
*/


class AdminActionsController extends Controller
{

    protected $feeCalculator;
    protected $availabilityChecker;
    protected $notificationService;
    protected $receiptService;
    protected $approvalChainService;

    public function __construct(FeeCalculatorService $feeCalculator, CheckAvailabilityService $availabilityChecker, NotificationService $notificationService, ReceiptService $receiptService, ApprovalChainService $approvalChainService)
    {
        $this->feeCalculator = $feeCalculator;
        $this->availabilityChecker = $availabilityChecker;
        $this->notificationService = $notificationService;
        $this->receiptService = $receiptService;
        $this->approvalChainService = $approvalChainService;
    }

    public function actionRequest(Request $request, $requestId, $action)
    {
        try {
            Log::debug('=== PROCESS APPROVAL ACTION CALLED ===', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'action' => $action
            ]);

            if (!in_array($action, ['approve', 'reject'])) {
                return response()->json(['error' => 'Invalid action'], 400);
            }

            $adminId = auth()->id();

            if (!$adminId) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            // Guard: reject attempts on closed forms. Finalized forms are
            // still acceptable — finalization locks the fee, not the workflow.
            $form = RequisitionForm::find($requestId);
            if (!$form) {
                return response()->json(['error' => 'Requisition not found'], 404);
            }
            if ($form->is_closed) {
                return response()->json(['error' => 'Form is already closed'], 422);
            }

            $result = $this->approvalChainService->processAction(
                $requestId,
                $adminId,
                $action,
                $request->input('remarks', null)
            );

            if (!$result['success']) {
                return response()->json(['error' => $result['message']], 404);
            }

            Log::debug('Approval action processed successfully', [
                'approval_id' => $result['approval_id'],
                'action' => $action
            ]);

            return response()->json([
                'message' => $result['message'],
                'approval_id' => $result['approval_id']
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to {$action} request", [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => "Failed to {$action} request",
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function addFee(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'label' => 'required|string|max:50',
                'fee_amount' => 'required|numeric|min:0.01',
                'account_num' => 'nullable|string|max:10', // Add this line
            ]);

            $admin = auth()->user();

            $fee = RequisitionFee::create([
                'request_id' => $requestId,
                'added_by' => $admin->admin_id,
                'label' => $validatedData['label'],
                'fee_amount' => $validatedData['fee_amount'],
                'discount_amount' => 0,
                'account_num' => $validatedData['account_num'] ?? null, // Add this line
            ]);

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Fee added successfully',
                'fee' => $fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add fee',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function removeFee($requestId, $feeId)
    {
        try {
            $fee = RequisitionFee::where('request_id', $requestId)
                ->where('fee_id', $feeId)
                ->firstOrFail();

            $fee->delete();

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Fee removed successfully',
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove fee',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function addDiscount(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'label' => 'required|string|max:50',
                'discount_amount' => 'required|numeric|min:0.01',
                'discount_type' => 'required|in:Fixed,Percentage',
                'account_num' => 'nullable|string|max:10', // Add this line
            ]);

            $admin = auth()->user();

            $discount = RequisitionFee::create([
                'request_id' => $requestId,
                'added_by' => $admin->admin_id,
                'label' => $validatedData['label'],
                'fee_amount' => 0,
                'discount_amount' => $validatedData['discount_amount'],
                'discount_type' => $validatedData['discount_type'],
                'account_num' => $validatedData['account_num'] ?? null, // Add this line
            ]);

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Discount added successfully',
                'discount' => $discount,
                'discount_type' => $validatedData['discount_type'],
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add discount',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function addLatePenalty(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'penalty_amount' => 'required|numeric|min:0'
            ]);

            $form = RequisitionForm::findOrFail($requestId);

            // Check if the requisition is marked as late by the system
            if (!$form->is_late) {
                return response()->json([
                    'error' => 'Cannot add late penalty',
                    'details' => 'This requisition is not marked as late by the system'
                ], 422);
            }

            $form->late_penalty_fee = $validatedData['penalty_amount'];
            $form->save();

            // Recalculate approved fee
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Late penalty added successfully',
                'penalty_amount' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add late penalty',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function removeLatePenalty(Request $request, $requestId)
    {
        try {
            $form = RequisitionForm::findOrFail($requestId);

            // Only reset the penalty fee, leave is_late status as determined by the system
            $form->late_penalty_fee = 0;
            $form->save();

            // Recalculate approved fee without penalty
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Late penalty removed successfully',
                'penalty_amount' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee,
                'is_late' => $form->is_late // Include current late status in response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove late penalty',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function waiveItems(Request $request, $requestId)
    {
        try {
            \Log::debug('Waive items request received', [
                'request_id' => $requestId,
                'waive_all' => $request->waive_all,
                'waived_facilities' => $request->waived_facilities,
                'waived_equipment' => $request->waived_equipment
            ]);

            // First, let's log all equipment for this request to see what should be valid
            $validEquipmentIds = RequestedEquipment::where('request_id', $requestId)
                ->pluck('requested_equipment_id')
                ->toArray();

            $validFacilityIds = RequestedFacility::where('request_id', $requestId)
                ->pluck('requested_facility_id')
                ->toArray();

            \Log::debug('Valid IDs for this request', [
                'valid_equipment_ids' => $validEquipmentIds,
                'valid_facility_ids' => $validFacilityIds,
                'requested_equipment' => $request->waived_equipment,
                'requested_facilities' => $request->waived_facilities
            ]);

            // Custom validation to check if items belong to this request
            $validator = Validator::make($request->all(), [
                'waive_all' => 'sometimes|boolean',
                'admin_id' => 'required|exists:admins,admin_id', // Add admin validation
                'waived_facilities' => 'sometimes|array',
                'waived_facilities.*' => [
                    function ($attribute, $value, $fail) use ($requestId, $validFacilityIds) {
                        if (!in_array($value, $validFacilityIds)) {
                            $fail("The selected facility (ID: $value) is invalid for this request. Valid facilities: " . implode(', ', $validFacilityIds));
                        }
                    }
                ],
                'waived_equipment' => 'sometimes|array',
                'waived_equipment.*' => [
                    function ($attribute, $value, $fail) use ($requestId, $validEquipmentIds) {
                        if (!in_array($value, $validEquipmentIds)) {
                            $fail("The selected equipment (ID: $value) is invalid for this request. Valid equipment: " . implode(', ', $validEquipmentIds));
                        }
                    }
                ]
            ]);

            if ($validator->fails()) {
                \Log::error('Waive items validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all(),
                    'valid_equipment_ids' => $validEquipmentIds,
                    'valid_facility_ids' => $validFacilityIds
                ]);

                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $validator->errors(),
                    'debug' => [
                        'valid_equipment_ids' => $validEquipmentIds,
                        'valid_facility_ids' => $validFacilityIds
                    ]
                ], 422);
            }
            $validatedData = $validator->validated();

            DB::beginTransaction();

            if (isset($validatedData['waive_all']) && $validatedData['waive_all']) {
                // Waive all facilities and equipment with waived_by
                RequestedFacility::where('request_id', $requestId)
                    ->update([
                        'is_waived' => true,
                        'waived_by' => $validatedData['admin_id']
                    ]);

                RequestedEquipment::where('request_id', $requestId)
                    ->update([
                        'is_waived' => true,
                        'waived_by' => $validatedData['admin_id']
                    ]);
            } else {
                // Only update waivers for specific items
                // Update facilities based on the provided list
                if (isset($validatedData['waived_facilities'])) {
                    // Waive the specified facilities with waived_by
                    RequestedFacility::where('request_id', $requestId)
                        ->whereIn('requested_facility_id', $validatedData['waived_facilities'])
                        ->update([
                            'is_waived' => true,
                            'waived_by' => $validatedData['admin_id']
                        ]);

                    // Unwaive facilities not in the list (set waived_by to null)
                    RequestedFacility::where('request_id', $requestId)
                        ->whereNotIn('requested_facility_id', $validatedData['waived_facilities'])
                        ->update([
                            'is_waived' => false,
                            'waived_by' => null
                        ]);
                } else {
                    // If no facilities specified, unwaive all facilities (set waived_by to null)
                    RequestedFacility::where('request_id', $requestId)
                        ->update([
                            'is_waived' => false,
                            'waived_by' => null
                        ]);
                }

                // Update equipment based on the provided list
                if (isset($validatedData['waived_equipment'])) {
                    // Waive the specified equipment with waived_by
                    RequestedEquipment::where('request_id', $requestId)
                        ->whereIn('requested_equipment_id', $validatedData['waived_equipment'])
                        ->update([
                            'is_waived' => true,
                            'waived_by' => $validatedData['admin_id']
                        ]);

                    // Unwaive equipment not in the list (set waived_by to null)
                    RequestedEquipment::where('request_id', $requestId)
                        ->whereNotIn('requested_equipment_id', $validatedData['waived_equipment'])
                        ->update([
                            'is_waived' => false,
                            'waived_by' => null
                        ]);
                } else {
                    // If no equipment specified, unwaive all equipment (set waived_by to null)
                    RequestedEquipment::where('request_id', $requestId)
                        ->update([
                            'is_waived' => false,
                            'waived_by' => null
                        ]);
                }
            }

            // Recalculate approved fee
            $form = RequisitionForm::with(['requestedFacilities', 'requestedEquipment', 'requisitionFees'])
                ->findOrFail($requestId);

            // Use getFeeSummary() to get both approved and base fees
            $feeSummary = $this->feeCalculator->getFeeSummary($form);
            $form->approved_fee = $feeSummary['approved_fee'];
            $form->save();

            DB::commit();

            return response()->json([
                'message' => 'Items waived successfully',
                'updated_approved_fee' => $feeSummary['approved_fee'],
                'tentative_fee' => $feeSummary['base_fee'] + ($form->is_late ? $form->late_penalty_fee : 0) // Calculate tentative fee using base_fee from summary
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to waive items', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to waive items',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new comment to a requisition form
     */
    public function addComment(Request $request, $requestId)
    {
        try {
            $admin = $request->user();

            $validated = $request->validate([
                'comment' => 'required|string|max:1000',
            ]);

            $comment = RequisitionComment::create([
                'request_id' => $requestId,
                'admin_id' => $admin->admin_id,
                'comment' => $validated['comment'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Load the admin relationship for the response
            $comment->load('admin');

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'comment' => $comment
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error adding comment', [
                'request_id' => $requestId,
                'admin_id' => $request->user()->admin_id ?? 'unknown',
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error adding comment to requisition', [
                'request_id' => $requestId,
                'admin_id' => $request->user()->admin_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment'
            ], 500);
        }
    }

    /**
     * Get all comments for a requisition form
     */
    public function getComments($requestId)
    {
        try {
            Log::info('Fetching comments', ['request_id' => $requestId]);

            $comments = RequisitionComment::where('request_id', $requestId)
                ->with('admin')
                ->orderBy('created_at', 'asc') // Change from 'desc' to 'asc'
                ->get();

            Log::debug('Comments fetched', [
                'request_id' => $requestId,
                'comment_count' => $comments->count()
            ]);

            return response()->json([
                'success' => true,
                'comments' => $comments
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching comments', [
                'request_id' => $requestId,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments'
            ], 500);
        }
    }

    public function cancelForm(Request $request, $requestId)
    {
        try {
            $adminId = auth()->id();

            if (!$adminId) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            DB::beginTransaction();

            $form = RequisitionForm::findOrFail($requestId);

            // Update the requisition form
            $form->status_id = FormStatus::where('status_name', 'Cancelled')->first()->status_id;
            $form->is_closed = true;
            $form->closed_by = $adminId;
            $form->closed_at = now();
            $form->updated_at = now();
            $form->save();

            // Create completed transaction record
            CompletedTransaction::create([
                'request_id' => $requestId,
                'official_receipt_no' => null,
                'official_receipt_url' => null,
                'official_receipt_public_id' => null
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Form cancelled successfully',
                'request_id' => $requestId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to cancel form as admin', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to cancel form',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Close a requisition form.
     *
     * Workflow: sets status to "Completed", stamps closed_at / closed_by,
     * and stores an optional closure_reason. Idempotent guard prevents
     * closing a form that's already closed.
     */
    public function closeForm(Request $request, $requestId)
    {
        try {
            $admin = auth()->user();
            if (!$admin) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            $validated = $request->validate([
                'closure_reason' => 'nullable|string|max:255',
            ]);

            $form = RequisitionForm::findOrFail($requestId);

            if ($form->is_closed) {
                return response()->json(['error' => 'Form is already closed'], 422);
            }

            $completedStatus = FormStatus::where('status_name', 'Completed')->first();
            if (!$completedStatus) {
                throw new \Exception('Completed status not found');
            }

            DB::beginTransaction();

            $form->is_closed = true;
            $form->closed_at = now();
            $form->closed_by = $admin->admin_id;
            $form->closure_reason = $validated['closure_reason'] ?? null;
            $form->status_id = $completedStatus->status_id;
            $form->save();

            if (!CompletedTransaction::where('request_id', $requestId)->exists()) {
                CompletedTransaction::create([
                    'request_id' => $requestId,
                    'official_receipt_no' => $form->official_receipt_num,
                    'official_receipt_url' => null,
                    'official_receipt_public_id' => null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Form closed successfully',
                'form' => $form,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to close form',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, $requestId)
    {
        try {
            \Log::debug('Update status request received', [
                'request_id' => $requestId,
                'new_status' => $request->status_name,
                'admin_id' => auth()->id()
            ]);

            $validatedData = $request->validate([
                'status_name' => 'required|string|in:Scheduled,Ongoing,Late,Returned,Late Return,Completed',
                'late_penalty_fee' => 'sometimes|nullable|numeric|min:0'
            ]);

            $adminId = auth()->id();
            if (!$adminId) {
                \Log::warning('Admin not authenticated during status update');
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            $form = RequisitionForm::with('formStatus')->findOrFail($requestId);

            if ($form->is_closed) {
                return response()->json(['error' => 'Form is already closed'], 422);
            }

            // VALIDATION: Can only mark as Late if current status is Ongoing
            if ($validatedData['status_name'] === 'Late') {
                $currentStatus = $form->formStatus->status_name;
                if ($currentStatus !== 'Ongoing') {
                    return response()->json([
                        'error' => 'Cannot mark as Late',
                        'details' => 'Can only mark forms as Late when they are in Ongoing status. Current status: ' . $currentStatus
                    ], 422);
                }
            }

            // Get the status ID for the selected status name
            $status = FormStatus::where('status_name', $validatedData['status_name'])->first();
            if (!$status) {
                \Log::error('Status not found', ['status_name' => $validatedData['status_name']]);
                return response()->json(['error' => 'Invalid status'], 422);
            }

            // Handle Late status specifically
            if ($validatedData['status_name'] === 'Late') {
                $form->is_late = true;

                // Set late penalty fee if provided
                if (isset($validatedData['late_penalty_fee']) && $validatedData['late_penalty_fee'] > 0) {
                    $form->late_penalty_fee = $validatedData['late_penalty_fee'];
                }
            }
            // Handle unmarking late (when changing from Late to another status)
            elseif ($form->formStatus->status_name === 'Late' && $validatedData['status_name'] !== 'Late') {
                $form->is_late = false;
                $form->late_penalty_fee = 0; // Reset penalty fee
            }

            // Update the form status
            $form->status_id = $status->status_id;

            // Additional logic based on status
            if (in_array($validatedData['status_name'], ['Returned', 'Late Return', 'Completed', 'Rejected', 'Cancelled'])) {
                $form->is_closed = true;
                $form->closed_at = now();
                $form->closed_by = $adminId;

                // Create completed transaction record for finalized statuses
                if (!CompletedTransaction::where('request_id', $requestId)->exists()) {
                    CompletedTransaction::create([
                        'request_id' => $requestId,
                        'official_receipt_no' => $form->official_receipt_no,
                        'official_receipt_url' => $form->official_receipt_url,
                        'official_receipt_public_id' => $form->official_receipt_public_id
                    ]);
                }
            }

            $form->save();

            // Send email notification if status changed to Late
            if ($validatedData['status_name'] === 'Late') {
                $this->notificationService->sendLatePenaltyEmail($form);
            }

            // Recalculate approved fee after status change
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->feeCalculator->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            \Log::info('Status updated successfully', [
                'request_id' => $requestId,
                'old_status' => $form->getOriginal('status_id'),
                'new_status' => $form->status_id,
                'is_late' => $form->is_late,
                'late_penalty_fee' => $form->late_penalty_fee,
                'admin_id' => $adminId
            ]);

            return response()->json([
                'message' => 'Status updated successfully',
                'new_status' => $validatedData['status_name'],
                'status_id' => $status->status_id,
                'color_code' => $status->color_code,
                'is_late' => $form->is_late,
                'late_penalty_fee' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Status update validation failed', [
                'request_id' => $requestId,
                'errors' => $e->errors(),
                'input_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update status', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update status',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalize a reservation: sets status to Reserved after stage-3 approval
     * and captures the official receipt number. Renamed from markAsScheduled
     * (which targeted the now-deprecated 'Scheduled' status).
     */
    public function finalizeReservation(Request $request, $requestId)
    {
        try {
            \Log::debug('Finalize reservation request received', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'official_receipt_num' => $request->official_receipt_num,
            ]);

            $validatedData = $request->validate([
                'official_receipt_num' => 'required|string|max:50|unique:requisition_forms,official_receipt_num',
                'event_title' => 'sometimes|string|max:50|nullable',
                'event_details' => 'sometimes|string|max:100|nullable',
            ]);

            $adminId = auth()->id();
            if (!$adminId) {
                return response()->json(['error' => 'Admin not authenticated'], 401);
            }

            $form = RequisitionForm::with([
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requisitionFees',
                'purpose',
                'formStatus',
            ])->findOrFail($requestId);

            if ($form->is_closed) {
                return response()->json(['error' => 'Form is already closed'], 422);
            }

            $reservedStatus = FormStatus::where('status_name', 'Reserved')->first();
            if (!$reservedStatus) {
                throw new \Exception('Reserved status not found');
            }

            $form->official_receipt_num = $validatedData['official_receipt_num'];
            $form->status_id = $reservedStatus->status_id;

            if (!empty($validatedData['event_title'])) {
                $form->event_title = $validatedData['event_title'];
            }
            if (!empty($validatedData['event_details'])) {
                $form->event_details = $validatedData['event_details'];
            }

            $form->save();

            // Send confirmation email to the requester.
            $this->notificationService->sendScheduledConfirmationEmail($form);

            \Log::info('Reservation finalized successfully', [
                'request_id' => $requestId,
                'official_receipt_num' => $form->official_receipt_num,
                'admin_id' => $adminId,
            ]);

            return response()->json([
                'message' => 'Reservation finalized successfully',
                'official_receipt_num' => $form->official_receipt_num,
                'new_status' => 'Reserved',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to finalize reservation', [
                'request_id' => $requestId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to finalize reservation',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateOfficialReceipt($requestId)
    {
        try {
            \Log::debug('=== GENERATE OFFICIAL RECEIPT CALLED ===', [
                'request_id' => $requestId,
            ]);

            $receiptData = $this->receiptService->generateReceiptData($requestId);

            return view('public.official-receipt', compact('receiptData'));

        } catch (\Exception $e) {
            \Log::error('Failed to generate official receipt', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
            abort(404, 'Receipt not found');
        }
    }

}
