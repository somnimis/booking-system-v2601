<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FormStatus;
use App\Models\RequisitionForm;
use App\Models\CompletedTransaction;
use App\Services\RequisitionFormatterService;
use App\Services\NotificationService;
use App\Services\UserBookingService;
use App\Models\RequisitionApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class UserRequisitionController extends Controller
{
    protected $formatter;
    protected $notificationService;
    protected $bookings;

    public function __construct(
        RequisitionFormatterService $formatter,
        NotificationService $notificationService,
        UserBookingService $bookings
    ) {
        $this->formatter = $formatter;
        $this->notificationService = $notificationService;
        $this->bookings = $bookings;
    }

    public function getFormByAccessCode($accessCode)
    {
        try {
            $form = RequisitionForm::with([
                'formStatus:status_id,status_name,color_code',
                'requestedFacilities.facility:facility_id,facility_name,base_fee,rate_type',
                'requestedEquipment.equipment:equipment_id,equipment_name,base_fee,rate_type',
                'purpose:purpose_id,purpose_name',
                'requisitionFees',
            ])->where('access_code', $accessCode)->firstOrFail();

            return response()->json($this->formatter->formatPublicForm($form));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Form not found',
                'details' => $e->getMessage(),
            ], 404);
        }
    }

    /** Search requisitions by access code or email; returns pre-rendered card HTML. */
    public function searchForms(Request $request)
    {
        $request->validate(['q' => 'required|string|max:255']);
        return response()->json($this->bookings->search($request->input('q')));
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

    /**
     * Handle payment receipt upload (server-side storage on 'public' disk).
     * Validates form is in Awaiting Payment, stores file, advances to Verifying Payment,
     * and notifies stage-3 approvers.
     */
    public function uploadPaymentReceipt(Request $request, $requestId)
    {
        $request->validate([
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ]);

        $form = RequisitionForm::findOrFail($requestId);

        $awaiting = FormStatus::where('status_name', 'Awaiting Payment')->first();
        if (!$awaiting || $form->status_id !== $awaiting->status_id) {
            return response()->json([
                'success' => false,
                'message' => 'This request is not awaiting payment.',
            ], 422);
        }

        try {
            // Store on public disk: storage/app/public/receipts/{request_id}_{uuid}.{ext}
            $path = $request->file('receipt')->store('receipts', 'public');

            DB::transaction(function () use ($form, $path) {
                $form->proof_of_payment_url = '/storage/' . $path;
                $form->proof_of_payment_public_id = $path; // local path acts as identifier
                $form->status_id = FormStatus::where('status_name', 'Verifying Payment')->value('status_id');
                $form->save();
            });

            $stage3 = RequisitionApproval::where('request_id', $requestId)
                ->where('stage', 3)
                ->where('status', 'Pending')
                ->pluck('admin_id');

            if ($stage3->isNotEmpty()) {
                $this->notificationService->notifyStage3Ready($form, $stage3);
            }

            return response()->json(['success' => true, 'message' => 'Receipt uploaded successfully.']);
        } catch (\Exception $e) {
            \Log::error('Receipt upload failed', ['request_id' => $requestId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Upload failed. Please try again.'], 500);
        }
    }

    public function showVerifyPage()
    {
        return view('public.verify');
    }

    public function verifyPermit(Request $request)
    {
        $request->validate(['control_num' => 'required|string']);
        $form = RequisitionForm::with('formStatus')
            ->where('control_num', trim($request->control_num))
            ->first();

        if (!$form) {
            return back()->with('error', 'No matching permit found. This document may be forged.');
        }

        if (in_array($form->formStatus->status_name, ['Cancelled', 'Rejected'])) {
            return back()->with('error', 'WARNING: This permit has been ' . $form->formStatus->status_name . ' in the system.');
        }

        return back()->with('success', 'Valid Permit Found!')->with('permit', $form);
    }

}