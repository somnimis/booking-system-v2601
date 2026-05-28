<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RequisitionApproval;
use App\Models\RequestedFacility;
use App\Models\RequestedEquipment;
use App\Models\RequisitionFee;
use App\Models\FormStatus;
use App\Models\CompletedTransaction;
use App\Models\RequisitionForm;
use App\Models\RequisitionComment;
use App\Services\ApprovalChainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/* AdminApprovalController — Summary Documentation

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


class AdminApprovalController extends Controller
{

    protected $approvalChainService;

    public function __construct(ApprovalChainService $approvalChainService)
    {
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

            $result = $this->approvalChainService->processAction(
                $requestId,
                $adminId,
                $action,
                $request->input('remarks', null)
            );

            if (!$result['success']) {
                return response()->json(['error' => $result['message']], 404);
            }

            // Create comment record for activity timeline
            $commentText = ucfirst($action) . " this request" . ($request->input('remarks') ? ": " . $request->input('remarks') : "");
            RequisitionComment::create([
                'request_id' => $requestId,
                'admin_id' => $adminId,
                'comment' => $commentText
            ]);

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
    public function cancelRequestPublic($requestId)
    {
        try {
            \Log::info('Public cancellation request received', ['request_id' => $requestId]);

            $form = RequisitionForm::findOrFail($requestId);

            // Check if the request can be cancelled (only certain statuses)
            $cancellableStatuses = ['Pending Approval', 'Awaiting Payment', 'Scheduled'];
            if (!in_array($form->formStatus->status_name, $cancellableStatuses)) {
                return response()->json([
                    'error' => 'Cannot cancel request',
                    'details' => 'This request cannot be cancelled in its current status'
                ], 422);
            }

            DB::beginTransaction();

            // Update the requisition form
            $form->status_id = FormStatus::where('status_name', 'Cancelled')->first()->status_id;
            $form->is_closed = true;
            $form->closed_by = null; // No admin since it's public cancellation
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

            \Log::info('Request cancelled successfully via public route', ['request_id' => $requestId]);

            return response()->json([
                'message' => 'Request cancelled successfully',
                'request_id' => $requestId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to cancel request via public route', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to cancel request',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Rename the existing cancel method for admin use
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


    public function closeForm($requestId)
    {
        try {
            $admin = auth()->user();

            $form = RequisitionForm::findOrFail($requestId);

            $form->is_closed = true;
            $form->closed_at = now();
            $form->closed_by = $admin->admin_id;
            $form->status_id = FormStatus::where('status_name', 'Completed')->first()->status_id;
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
                'message' => 'Form closed successfully',
                'form' => $form
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to close form',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function markReturned(Request $request, $requestId)
    {
        try {
            $validatedData = $request->validate([
                'is_late' => 'required|boolean',
                'late_penalty_fee' => 'required_if:is_late,true|numeric|min:0'
            ]);

            $form = RequisitionForm::findOrFail($requestId);

            $form->returned_at = now();
            $form->is_late = $validatedData['is_late'];

            if ($validatedData['is_late']) {
                $form->late_penalty_fee = $validatedData['late_penalty_fee'];
            }

            // Update status based on return time
            if ($validatedData['is_late']) {
                $form->status_id = FormStatus::where('status_name', 'Late Return')->first()->status_id;
            } else {
                $form->status_id = FormStatus::where('status_name', 'Returned')->first()->status_id;
            }

            // Recalculate approved fee
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->calculateApprovedFee($form);
            $form->approved_fee = $approvedFee;
            $form->save();

            return response()->json([
                'message' => 'Equipment marked as returned',
                'is_late' => $form->is_late,
                'late_penalty_fee' => $form->late_penalty_fee,
                'updated_approved_fee' => $approvedFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to mark equipment as returned',
                'details' => $e->getMessage()
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
                $this->sendLatePenaltyEmail($form);
            }

            // Recalculate approved fee after status change
            $form->load(['requestedFacilities', 'requestedEquipment', 'requisitionFees']);
            $approvedFee = $this->calculateApprovedFee($form);
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

    private function sendLatePenaltyEmail($form)
    {
        try {
            $userName = $form->first_name . ' ' . $form->last_name;
            $userEmail = $form->email;

            $emailData = [
                'first_name' => $form->first_name,
                'last_name' => $form->last_name,
                'penalty_fee' => $form->late_penalty_fee
            ];

            \Log::debug('Sending late penalty email', [
                'recipient' => $userEmail,
                'request_id' => $form->request_id,
                'penalty_fee' => $form->late_penalty_fee
            ]);

            \Mail::send('emails.booking-late', $emailData, function ($message) use ($userEmail, $userName) {
                $message->to($userEmail, $userName)
                    ->subject('Late Penalty Notice - Central Philippine University');
            });

            \Log::debug('Late penalty email sent successfully', [
                'recipient' => $userEmail,
                'request_id' => $form->request_id
            ]);
        } catch (\Exception $emailError) {
            \Log::error('Failed to send late penalty email', [
                'request_id' => $form->request_id,
                'error' => $emailError->getMessage(),
                'recipient' => $form->email,
                'trace' => $emailError->getTraceAsString()
            ]);
        }
    }
    // Calculate & Finalize fees //

    // Add better error logging to the calculateBaseFees method
    private function calculateBaseFees($form)
    {
        try {
            \Log::debug('Calculating base fees', [
                'request_id' => $form->request_id,
                'facilities_count' => $form->requestedFacilities->count(),
                'equipment_count' => $form->requestedEquipment->count(),
                'all_day' => $form->all_day ?? false
            ]);

            // Calculate duration in hours based on all_day flag
            $durationInHours = $this->calculateDurationHours($form);

            // Calculate facility fees with rate_type logic
            $facilityFees = $form->requestedFacilities->sum(function ($facility) use ($form, $durationInHours) {
                if ($facility->is_waived) {
                    return 0;
                }

                $fee = $facility->facility->base_fee;

                // Check if rate_type is "Per Hour" and calculate based on duration
                if ($facility->facility->rate_type === 'Per Hour') {
                    try {
                        $total = $fee * $durationInHours;

                        \Log::debug('Per Hour facility calculation', [
                            'facility_id' => $facility->facility_id,
                            'base_fee' => $fee,
                            'duration_hours' => $durationInHours,
                            'all_day' => $form->all_day ?? false,
                            'total' => $total
                        ]);

                        return $total;
                    } catch (\Exception $e) {
                        \Log::error('Error calculating per hour facility fee', [
                            'facility_id' => $facility->facility_id,
                            'error' => $e->getMessage()
                        ]);
                        return $fee; // Fallback to base fee
                    }
                }

                // For "Per Event" or any other rate type, return the base fee
                return $fee;
            });

            // Calculate equipment fees with rate_type logic
            $equipmentFees = $form->requestedEquipment->sum(function ($equipment) use ($form, $durationInHours) {
                if ($equipment->is_waived) {
                    return 0;
                }

                $fee = $equipment->equipment->base_fee;

                // Check if rate_type is "Per Hour" and calculate based on duration
                if ($equipment->equipment->rate_type === 'Per Hour') {
                    try {
                        $total = ($fee * $durationInHours) * $equipment->quantity;

                        \Log::debug('Per Hour equipment calculation', [
                            'equipment_id' => $equipment->equipment_id,
                            'base_fee' => $fee,
                            'quantity' => $equipment->quantity,
                            'duration_hours' => $durationInHours,
                            'all_day' => $form->all_day ?? false,
                            'total' => $total
                        ]);

                        return $total;
                    } catch (\Exception $e) {
                        \Log::error('Error calculating per hour equipment fee', [
                            'equipment_id' => $equipment->equipment_id,
                            'error' => $e->getMessage()
                        ]);
                        return $fee * $equipment->quantity; // Fallback to base fee
                    }
                }

                // For "Per Event" or any other rate type, return the base fee multiplied by quantity
                return $fee * $equipment->quantity;
            });

            $total = $facilityFees + $equipmentFees;

            \Log::debug('Base fees calculation completed', [
                'request_id' => $form->request_id,
                'facility_fees' => $facilityFees,
                'equipment_fees' => $equipmentFees,
                'total_base_fees' => $total,
                'duration_hours' => $durationInHours,
                'all_day' => $form->all_day ?? false
            ]);

            return $total;
        } catch (\Exception $e) {
            \Log::error('Error in calculateBaseFees', [
                'request_id' => $form->request_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 0; // Return 0 on error to prevent calculation issues
        }
    }

    /**
     * Helper method to calculate duration in hours based on all_day flag
     */
    private function calculateDurationHours($form)
    {
        if ($form->all_day) {
            // For all-day events: count days × 8 hours per day
            $startDate = Carbon::parse($form->start_date);
            $endDate = Carbon::parse($form->end_date);
            $days = $startDate->diffInDays($endDate) + 1; // +1 to include both start and end days
            $hours = $days * 8; // Standard 8-hour day

            \Log::debug('All-day duration calculation', [
                'start_date' => $form->start_date,
                'end_date' => $form->end_date,
                'days' => $days,
                'hours' => $hours
            ]);

            return $hours;
        } else {
            // For time-based events: calculate exact hours
            try {
                $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
                $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
                $hours = $startDateTime->diffInHours($endDateTime);

                // Ensure minimum 1 hour
                $hours = max(1, $hours);

                \Log::debug('Time-based duration calculation', [
                    'start' => $form->start_date . ' ' . $form->start_time,
                    'end' => $form->end_date . ' ' . $form->end_time,
                    'hours' => $hours
                ]);

                return $hours;
            } catch (\Exception $e) {
                \Log::error('Error calculating time-based duration', [
                    'error' => $e->getMessage(),
                    'start_date' => $form->start_date,
                    'start_time' => $form->start_time,
                    'end_date' => $form->end_date,
                    'end_time' => $form->end_time
                ]);
                return 1; // Fallback to 1 hour
            }
        }
    }

    private function calculateTentativeFee($requestId)
    {
        $form = RequisitionForm::with(['requestedFacilities.facility', 'requestedEquipment.equipment'])
            ->findOrFail($requestId);

        $waivers = session()->get('pending_waivers', [])[$requestId] ?? [];

        // Calculate duration in hours based on all_day flag
        $durationInHours = $this->calculateDurationHours($form);

        // Calculate facility fees with rate type and all-day support
        $facilityFees = $form->requestedFacilities->reduce(function ($carry, $facility) use ($waivers, $form, $durationInHours) {
            $isWaived = $waivers['facility'][$facility->requested_facility_id] ?? $facility->is_waived;

            if ($isWaived) {
                return $carry + 0;
            }

            $fee = $facility->facility->base_fee;

            // Apply hourly rate if applicable
            if ($facility->facility->rate_type === 'Per Hour') {
                return $carry + ($fee * $durationInHours);
            }

            // Per Event rate
            return $carry + $fee;
        }, 0);

        // Calculate equipment fees with rate type, quantity, and all-day support
        $equipmentFees = $form->requestedEquipment->reduce(function ($carry, $equipment) use ($waivers, $form, $durationInHours) {
            $isWaived = $waivers['equipment'][$equipment->requested_equipment_id] ?? $equipment->is_waived;

            if ($isWaived) {
                return $carry + 0;
            }

            $fee = $equipment->equipment->base_fee;
            $quantity = $equipment->quantity;

            // Apply hourly rate if applicable
            if ($equipment->equipment->rate_type === 'Per Hour') {
                return $carry + (($fee * $durationInHours) * $quantity);
            }

            // Per Event rate
            return $carry + ($fee * $quantity);
        }, 0);

        // Add late penalty if applicable
        $latePenalty = $form->is_late ? $form->late_penalty_fee : 0;

        $total = $facilityFees + $equipmentFees + $latePenalty;

        \Log::debug('Tentative fee calculated', [
            'request_id' => $requestId,
            'facility_fees' => $facilityFees,
            'equipment_fees' => $equipmentFees,
            'late_penalty' => $latePenalty,
            'duration_hours' => $durationInHours,
            'all_day' => $form->all_day ?? false,
            'total' => $total
        ]);

        return $total;
    }

    private function calculateApprovedFee($form)
    {
        $baseFees = $this->calculateBaseFees($form);
        $additionalFees = $this->calculateAdditionalFees($form);
        $discounts = $this->calculateTotalDiscounts($form, $baseFees + $additionalFees);

        $approvedFee = $baseFees + $additionalFees - $discounts;

        if ($form->is_late) {
            $approvedFee += $form->late_penalty_fee;
        }

        // Ensure fee doesn't go negative
        return max(0, $approvedFee);
    }

    private function calculateAdditionalFees($form)
    {
        // Sum only positive fee amounts (additional fees)
        return $form->requisitionFees->sum(function ($fee) {
            return max(0, (float) $fee->fee_amount);
        });
    }

    private function calculateTotalDiscounts($form, $subtotal)
    {
        $totalDiscount = 0;

        foreach ($form->requisitionFees as $fee) {
            $discountAmount = (float) $fee->discount_amount;

            if ($discountAmount > 0) {
                if ($fee->discount_type === 'Percentage') {
                    // Calculate percentage discount based on subtotal
                    $percentageDiscount = ($discountAmount / 100) * $subtotal;
                    $totalDiscount += $percentageDiscount;
                } else {
                    // Fixed discount
                    $totalDiscount += $discountAmount;
                }
            }
        }

        return $totalDiscount;
    }

    public function generateOfficialReceipt($requestId)
    {
        try {
            \Log::debug('=== GENERATE OFFICIAL RECEIPT CALLED ===', [
                'request_id' => $requestId,
                'full_url' => request()->fullUrl(),
                'method' => request()->method()
            ]);

            $form = RequisitionForm::with([
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'purpose',
                'requisitionFees',
                'formStatus',
                'requisitionApprovals.approvedBy'
            ])->findOrFail($requestId);

            // Check if official receipt number exists
            if (empty($form->official_receipt_num)) {
                abort(404, 'Official receipt not generated yet');
            }

            // Calculate total fee
            $totalFee = $form->approved_fee;

            // Format schedule based on all_day flag
            if ($form->all_day) {
                $startDateFormatted = Carbon::parse($form->start_date)->format('F j, Y');
                $endDateFormatted = Carbon::parse($form->end_date)->format('F j, Y');

                if ($form->start_date === $form->end_date) {
                    $scheduleString = $startDateFormatted . ' (All Day)';
                    $startSchedule = $startDateFormatted . ' (All Day)';
                    $endSchedule = $endDateFormatted . ' (All Day)';
                } else {
                    $scheduleString = $startDateFormatted . ' — ' . $endDateFormatted . ' (All Day)';
                    $startSchedule = $startDateFormatted . ' (All Day)';
                    $endSchedule = $endDateFormatted . ' (All Day)';
                }
            } else {
                $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
                $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);

                $startDateFormatted = $startDateTime->format('F j, Y');
                $endDateFormatted = $endDateTime->format('F j, Y');
                $startTimeFormatted = $startDateTime->format('g:i A');
                $endTimeFormatted = $endDateTime->format('g:i A');

                if ($form->start_date === $form->end_date) {
                    $scheduleString = $startDateFormatted . ' — ' . $startTimeFormatted . ' to ' . $endTimeFormatted;
                    $startSchedule = $startDateFormatted . ' — ' . $startTimeFormatted;
                    $endSchedule = $endDateFormatted . ' — ' . $endTimeFormatted;
                } else {
                    $scheduleString = $startDateFormatted . ' ' . $startTimeFormatted . ' — ' .
                        $endDateFormatted . ' ' . $endTimeFormatted;
                    $startSchedule = $startDateFormatted . ' — ' . $startTimeFormatted;
                    $endSchedule = $endDateFormatted . ' — ' . $endTimeFormatted;
                }
            }

            // Get all admins who approved this request with their approval dates
            $approvingAdmins = $form->requisitionApprovals
                ->whereNotNull('approved_by')
                ->map(function ($approval) {
                    return [
                        'admin' => $approval->approvedBy,
                        'date_approved' => $approval->date_updated ? Carbon::parse($approval->date_updated)->format('M j, Y') : 'N/A'
                    ];
                })
                ->filter(function ($item) {
                    return !is_null($item['admin']);
                })
                ->unique(function ($item) {
                    return $item['admin']->admin_id;
                });

            // Prepare receipt data
            $receiptData = [
                'official_receipt_num' => $form->official_receipt_num,
                'user_name' => $form->first_name . ' ' . $form->last_name,
                'user_email' => $form->email,
                'organization_name' => $form->organization_name,
                'contact_number' => $form->contact_number,
                'request_id' => $form->request_id,
                'facility_name' => $form->requestedFacilities->first()->facility->facility_name ?? 'N/A',
                'purpose' => $form->purpose->purpose_name,
                'num_participants' => $form->num_participants,
                'total_fee' => $totalFee,
                'issued_date' => $form->updated_at->format('F j, Y'),
                // Raw values
                'start_date' => $form->start_date,
                'end_date' => $form->end_date,
                'start_time' => $form->start_time,
                'end_time' => $form->end_time,
                'all_day' => $form->all_day,
                // Formatted values
                'formatted_start_date' => $startDateFormatted ?? null,
                'formatted_end_date' => $endDateFormatted ?? null,
                'formatted_start_time' => $form->all_day ? 'All Day' : ($startTimeFormatted ?? null),
                'formatted_end_time' => $form->all_day ? 'All Day' : ($endTimeFormatted ?? null),
                'schedule' => $scheduleString,
                'start_schedule' => $startSchedule,
                'end_schedule' => $endSchedule,
                'fee_breakdown' => $this->getFeeBreakdown($form),
                'approving_admins' => $approvingAdmins->map(function ($item) {
                    return [
                        'name' => $item['admin']->first_name . ' ' . $item['admin']->last_name,
                        'title' => $item['admin']->title ?? 'Administrator',
                        'signature_url' => $item['admin']->signature_url,
                        'date_approved' => $item['date_approved']
                    ];
                })->toArray(),
                'is_multi_day' => $form->start_date !== $form->end_date
            ];

            \Log::debug('Official receipt data prepared', [
                'request_id' => $requestId,
                'all_day' => $form->all_day,
                'schedule' => $scheduleString,
                'approving_admins_count' => count($receiptData['approving_admins'])
            ]);

            return view('public.official-receipt', compact('receiptData'));
        } catch (\Exception $e) {
            \Log::error('Failed to generate official receipt', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            abort(404, 'Receipt not found');
        }
    }

    private function getFeeBreakdown($form)
    {
        $breakdown = [];

        // Add facility fees
        foreach ($form->requestedFacilities as $facility) {
            if (!$facility->is_waived) {
                $breakdown[] = [
                    'description' => $facility->facility->facility_name . ' Rental',
                    'amount' => $facility->facility->base_fee
                ];
            }
        }

        // Add equipment fees
        foreach ($form->requestedEquipment as $equipment) {
            if (!$equipment->is_waived) {
                $breakdown[] = [
                    'description' => $equipment->equipment->equipment_name . ' Rental' .
                        ($equipment->quantity > 1 ? ' (×' . $equipment->quantity . ')' : ''),
                    'amount' => $equipment->equipment->base_fee * $equipment->quantity
                ];
            }
        }

        // Add additional fees
        foreach ($form->requisitionFees as $fee) {
            if ($fee->fee_amount > 0) {
                $breakdown[] = [
                    'description' => $fee->label,
                    'amount' => $fee->fee_amount
                ];
            }
        }

        // Add late penalty if applicable
        if ($form->is_late && $form->late_penalty_fee > 0) {
            $breakdown[] = [
                'description' => 'Late Penalty Fee',
                'amount' => $form->late_penalty_fee
            ];
        }

        return $breakdown;
    }

    public function autoMarkLateForms()
    {
        try {
            \Log::info('Starting automatic late form detection');

            // Get forms that are in Ongoing status and not already marked as late
            $ongoingStatus = FormStatus::where('status_name', 'Ongoing')->first();
            $lateStatus = FormStatus::where('status_name', 'Late')->first();

            if (!$ongoingStatus || !$lateStatus) {
                \Log::error('Required statuses not found');
                return response()->json([
                    'error' => 'Required statuses not found',
                    'processed' => 0,
                    'marked_late' => 0
                ], 500);
            }

            // Use get() to retrieve actual model instances, not toBase() or similar
            $formsToMarkLate = RequisitionForm::where('status_id', $ongoingStatus->status_id)
                ->where('is_late', false)
                ->get(); // This returns a collection of RequisitionForm models

            $markedLateCount = 0;

            foreach ($formsToMarkLate as $form) {
                try {
                    // Ensure $form is a RequisitionForm model
                    if (!($form instanceof RequisitionForm)) {
                        \Log::warning('Form is not an instance of RequisitionForm', [
                            'request_id' => $form->request_id ?? 'unknown',
                            'type' => gettype($form)
                        ]);
                        continue;
                    }

                    // Calculate end datetime based on all_day flag
                    if ($form->all_day) {
                        // For all-day events: end at 23:59:59 of end_date
                        $endDateTime = Carbon::parse($form->end_date . ' 23:59:59');
                        \Log::debug('All-day event end time', [
                            'request_id' => $form->request_id,
                            'end_date' => $form->end_date,
                            'end_time' => '23:59:59',
                            'parsed_end' => $endDateTime
                        ]);
                    } else {
                        // For regular events: use end_date + end_time
                        $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
                    }

                    // Calculate grace period (4 hours)
                    $gracePeriodEnd = $endDateTime->copy()->addHours(4);

                    // Check if grace period has passed
                    if (now()->greaterThan($gracePeriodEnd)) {
                        \Log::info('Marking form as late automatically', [
                            'request_id' => $form->request_id,
                            'all_day' => $form->all_day,
                            'end_datetime' => $endDateTime,
                            'grace_period_end' => $gracePeriodEnd,
                            'current_time' => now()
                        ]);

                        // Set default penalty fee for automated detection
                        $defaultPenaltyFee = 500.00; // You can adjust this amount

                        // Update form to late status with penalty fee
                        $form->status_id = $lateStatus->status_id;
                        $form->is_late = true;
                        $form->late_penalty_fee = $defaultPenaltyFee;
                        $form->save(); // This should now work since $form is a RequisitionForm model

                        // Send AUTOMATED late penalty email
                        $this->sendAutoLatePenaltyEmail($form, $defaultPenaltyFee);

                        $markedLateCount++;

                        // Log the automatic action
                        \Log::info('Form automatically marked as late', [
                            'request_id' => $form->request_id,
                            'all_day' => $form->all_day,
                            'requester' => $form->first_name . ' ' . $form->last_name,
                            'original_end' => $endDateTime,
                            'grace_period_end' => $gracePeriodEnd,
                            'penalty_fee' => $defaultPenaltyFee,
                            'marked_late_at' => now()
                        ]);
                    } else {
                        \Log::debug('Form still within grace period', [
                            'request_id' => $form->request_id,
                            'all_day' => $form->all_day,
                            'end_datetime' => $endDateTime,
                            'grace_period_end' => $gracePeriodEnd,
                            'remaining_minutes' => now()->diffInMinutes($gracePeriodEnd, false)
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Error processing form for late marking', [
                        'request_id' => $form->request_id ?? 'unknown',
                        'all_day' => $form->all_day ?? false,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue;
                }
            }

            \Log::info('Automatic late form detection completed', [
                'processed' => $formsToMarkLate->count(),
                'marked_late' => $markedLateCount,
                'still_in_grace_period' => $formsToMarkLate->count() - $markedLateCount
            ]);

            return response()->json([
                'message' => 'Automatic late detection completed',
                'processed' => $formsToMarkLate->count(),
                'marked_late' => $markedLateCount
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to automatically mark late forms', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to automatically mark late forms',
                'details' => $e->getMessage(),
                'processed' => 0,
                'marked_late' => 0
            ], 500);
        }
    }

    private function sendAutoLatePenaltyEmail($form, $penaltyFee)
    {
        try {
            $userName = $form->first_name . ' ' . $form->last_name;
            $userEmail = $form->email;

            // Calculate end datetime based on all_day flag
            if ($form->all_day) {
                // For all-day events: end at 23:59:59 of end_date
                $endDateTime = Carbon::parse($form->end_date . ' 23:59:59');
                $originalEndTimeFormatted = $endDateTime->format('F j, Y') . ' (All Day)';
                $scheduleType = 'All Day Event';
            } else {
                // For regular events: use end_date + end_time
                $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);
                $originalEndTimeFormatted = $endDateTime->format('F j, Y \a\t g:i A');
                $scheduleType = 'Timed Event';
            }

            $gracePeriodEnd = $endDateTime->copy()->addHours(4);

            // Format schedule for email display
            if ($form->all_day) {
                $startDateFormatted = Carbon::parse($form->start_date)->format('F j, Y');
                $endDateFormatted = Carbon::parse($form->end_date)->format('F j, Y');

                $fullSchedule = $form->start_date === $form->end_date
                    ? $startDateFormatted . ' (All Day)'
                    : $startDateFormatted . ' — ' . $endDateFormatted . ' (All Day)';
            } else {
                $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
                $endDateTimeForDisplay = Carbon::parse($form->end_date . ' ' . $form->end_time);

                $fullSchedule = $form->start_date === $form->end_date
                    ? $startDateTime->format('F j, Y \a\t g:i A') . ' — ' . $endDateTimeForDisplay->format('g:i A')
                    : $startDateTime->format('F j, Y \a\t g:i A') . ' — ' . $endDateTimeForDisplay->format('F j, Y \a\t g:i A');
            }

            $emailData = [
                'first_name' => $form->first_name,
                'last_name' => $form->last_name,
                'request_id' => $form->request_id,
                'penalty_fee' => number_format($penaltyFee, 2),
                'original_end_time' => $originalEndTimeFormatted,
                'grace_period_end' => $gracePeriodEnd->format('F j, Y \a\t g:i A'),
                'detected_late_time' => now()->format('F j, Y \a\t g:i A'),
                // New fields for better email templates
                'all_day' => $form->all_day,
                'schedule_type' => $scheduleType,
                'start_date' => $form->start_date,
                'end_date' => $form->end_date,
                'start_time' => $form->all_day ? 'All Day' : $form->start_time,
                'end_time' => $form->all_day ? 'All Day' : $form->end_time,
                'formatted_start_date' => Carbon::parse($form->start_date)->format('F j, Y'),
                'formatted_end_date' => Carbon::parse($form->end_date)->format('F j, Y'),
                'formatted_start_time' => $form->all_day ? 'All Day' : Carbon::parse($form->start_time)->format('g:i A'),
                'formatted_end_time' => $form->all_day ? 'All Day' : Carbon::parse($form->end_time)->format('g:i A'),
                'full_schedule' => $fullSchedule,
                'purpose' => $form->purpose->purpose_name ?? 'N/A',
                'num_participants' => $form->num_participants,
                'access_code' => $form->access_code
            ];

            \Log::debug('Sending automated late penalty email', [
                'recipient' => $userEmail,
                'request_id' => $form->request_id,
                'all_day' => $form->all_day,
                'penalty_fee' => $penaltyFee,
                'schedule' => $fullSchedule
            ]);

            \Mail::send('emails.booking-late-auto', $emailData, function ($message) use ($userEmail, $userName) {
                $message->to($userEmail, $userName)
                    ->subject('Automatic Late Penalty Notice - Central Philippine University');
            });

            \Log::debug('Automated late penalty email sent successfully', [
                'recipient' => $userEmail,
                'request_id' => $form->request_id,
                'all_day' => $form->all_day
            ]);
        } catch (\Exception $emailError) {
            \Log::error('Failed to send automated late penalty email', [
                'request_id' => $form->request_id,
                'all_day' => $form->all_day ?? false,
                'error' => $emailError->getMessage(),
                'recipient' => $form->email,
                'trace' => $emailError->getTraceAsString()
            ]);
        }
    }

    public function autoMarkOngoingForms()
    {
        try {
            \Log::info('Starting automatic ongoing form detection');

            // Get forms that are in Scheduled status
            $scheduledStatus = FormStatus::where('status_name', 'Scheduled')->first();
            $ongoingStatus = FormStatus::where('status_name', 'Ongoing')->first();

            if (!$scheduledStatus || !$ongoingStatus) {
                \Log::error('Required statuses not found');
                return response()->json([
                    'error' => 'Required statuses not found',
                    'processed' => 0,
                    'marked_ongoing' => 0
                ], 500);
            }

            $formsToMarkOngoing = RequisitionForm::where('status_id', $scheduledStatus->status_id)
                ->get();

            $markedOngoingCount = 0;

            foreach ($formsToMarkOngoing as $form) {
                try {
                    // Calculate start datetime based on all_day flag
                    if ($form->all_day) {
                        // For all-day events: start at 00:00:00 of start_date
                        $startDateTime = Carbon::parse($form->start_date . ' 00:00:00');
                        \Log::debug('All-day event start time', [
                            'request_id' => $form->request_id,
                            'start_date' => $form->start_date,
                            'start_time' => '00:00:00',
                            'parsed_start' => $startDateTime
                        ]);
                    } else {
                        // For regular events: use start_date + start_time
                        $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
                    }

                    // Check if start time has begun (current time is equal to or after start time)
                    if (now()->greaterThanOrEqualTo($startDateTime)) {
                        \Log::info('Marking form as ongoing automatically', [
                            'request_id' => $form->request_id,
                            'all_day' => $form->all_day,
                            'start_datetime' => $startDateTime,
                            'current_time' => now()
                        ]);

                        // Update form to ongoing status
                        $form->status_id = $ongoingStatus->status_id;
                        $form->save();

                        // Optional: Send notification email for status change
                        $this->sendOngoingStatusEmail($form);

                        $markedOngoingCount++;

                        // Log the automatic action
                        \Log::info('Form automatically marked as ongoing', [
                            'request_id' => $form->request_id,
                            'all_day' => $form->all_day,
                            'requester' => $form->first_name . ' ' . $form->last_name,
                            'original_start' => $startDateTime,
                            'marked_ongoing_at' => now()
                        ]);
                    } else {
                        \Log::debug('Form not yet ready for ongoing status', [
                            'request_id' => $form->request_id,
                            'all_day' => $form->all_day,
                            'start_datetime' => $startDateTime,
                            'minutes_until_start' => now()->diffInMinutes($startDateTime, false)
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Error processing form for ongoing marking', [
                        'request_id' => $form->request_id,
                        'all_day' => $form->all_day ?? false,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue;
                }
            }

            \Log::info('Automatic ongoing form detection completed', [
                'processed' => $formsToMarkOngoing->count(),
                'marked_ongoing' => $markedOngoingCount,
                'not_yet_started' => $formsToMarkOngoing->count() - $markedOngoingCount
            ]);

            return response()->json([
                'message' => 'Automatic ongoing detection completed',
                'processed' => $formsToMarkOngoing->count(),
                'marked_ongoing' => $markedOngoingCount
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to automatically mark ongoing forms', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to automatically mark ongoing forms',
                'details' => $e->getMessage(),
                'processed' => 0,
                'marked_ongoing' => 0
            ], 500);
        }
    }

    // Email notification method for ongoing status
    private function sendOngoingStatusEmail($form)
    {
        try {
            $userName = $form->first_name . ' ' . $form->last_name;
            $userEmail = $form->email;

            // Format schedule based on all_day flag
            if ($form->all_day) {
                $startDateTime = Carbon::parse($form->start_date . ' 00:00:00');
                $endDateTime = Carbon::parse($form->end_date . ' 23:59:59');

                $formattedStartTime = $startDateTime->format('F j, Y') . ' (All Day)';
                $formattedEndTime = $endDateTime->format('F j, Y') . ' (All Day)';

                $fullSchedule = $form->start_date === $form->end_date
                    ? $formattedStartTime
                    : $startDateTime->format('F j, Y') . ' — ' . $endDateTime->format('F j, Y') . ' (All Day)';

                $scheduleType = 'All Day Event';
            } else {
                $startDateTime = Carbon::parse($form->start_date . ' ' . $form->start_time);
                $endDateTime = Carbon::parse($form->end_date . ' ' . $form->end_time);

                $formattedStartTime = $startDateTime->format('F j, Y \a\t g:i A');
                $formattedEndTime = $endDateTime->format('F j, Y \a\t g:i A');

                $fullSchedule = $form->start_date === $form->end_date
                    ? $startDateTime->format('F j, Y \a\t g:i A') . ' — ' . $endDateTime->format('g:i A')
                    : $startDateTime->format('F j, Y \a\t g:i A') . ' — ' . $endDateTime->format('F j, Y \a\t g:i A');

                $scheduleType = 'Timed Event';
            }

            // Get facility names
            $facilityNames = $form->requestedFacilities->map(function ($facility) {
                return $facility->facility->facility_name;
            })->filter()->implode(', ');

            // Get equipment names with quantities
            $equipmentList = $form->requestedEquipment->map(function ($equipment) {
                $name = $equipment->equipment->equipment_name ?? 'Unknown Equipment';
                $quantity = $equipment->quantity > 1 ? " (×{$equipment->quantity})" : '';
                return $name . $quantity;
            })->filter()->implode(', ');

            $emailData = [
                'first_name' => $form->first_name,
                'last_name' => $form->last_name,
                'request_id' => $form->request_id,
                // Raw values
                'start_date' => $form->start_date,
                'end_date' => $form->end_date,
                'start_time' => $form->start_time,
                'end_time' => $form->end_time,
                'all_day' => $form->all_day,
                // Formatted values
                'formatted_start_time' => $formattedStartTime,
                'formatted_end_time' => $formattedEndTime,
                'full_schedule' => $fullSchedule,
                'schedule_type' => $scheduleType,
                'facilities' => $facilityNames ?: 'No facilities booked',
                'equipment' => $equipmentList ?: 'No equipment booked',
                'purpose' => $form->purpose->purpose_name ?? 'N/A',
                'num_participants' => $form->num_participants,
                'access_code' => $form->access_code,
                'event_title' => $form->event_title ?? 'Booking #' . $form->request_id,
                'event_details' => $form->event_details,
                'is_multi_day' => $form->start_date !== $form->end_date
            ];

            \Log::debug('Sending ongoing status email', [
                'recipient' => $userEmail,
                'request_id' => $form->request_id,
                'all_day' => $form->all_day,
                'schedule' => $fullSchedule
            ]);

            \Mail::send('emails.booking-ongoing', $emailData, function ($message) use ($userEmail, $userName) {
                $message->to($userEmail, $userName)
                    ->subject('Your Booking is Now Ongoing - Central Philippine University');
            });

            \Log::debug('Ongoing status email sent successfully', [
                'recipient' => $userEmail,
                'request_id' => $form->request_id,
                'all_day' => $form->all_day
            ]);
        } catch (\Exception $emailError) {
            \Log::error('Failed to send ongoing status email', [
                'request_id' => $form->request_id,
                'all_day' => $form->all_day ?? false,
                'error' => $emailError->getMessage(),
                'recipient' => $form->email,
                'trace' => $emailError->getTraceAsString()
            ]);
        }
    }

    public function autoUpdateAllStatuses()
    {
        try {
            \Log::info('Starting automatic status updates for all forms');

            // Run both automated methods
            $ongoingResult = $this->autoMarkOngoingForms();
            $lateResult = $this->autoMarkLateForms();

            return response()->json([
                'message' => 'Automatic status updates completed',
                'ongoing_forms' => json_decode($ongoingResult->getContent(), true),
                'late_forms' => json_decode($lateResult->getContent(), true)
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to run automatic status updates', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to run automatic status updates',
                'details' => $e->getMessage()
            ], 500);
        }
    }


}
