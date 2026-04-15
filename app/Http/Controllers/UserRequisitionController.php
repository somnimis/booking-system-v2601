<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FormStatus;
use App\Models\RequisitionForm;
use App\Models\CompletedTransaction;
use App\Services\RequisitionFormatterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class UserRequisitionController extends Controller
{
    protected $formatter;

    public function __construct(
        RequisitionFormatterService $formatter,
    ) {
        $this->formatter = $formatter;
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

        public function uploadPaymentReceipt(Request $request, $requestId)
    {
        try {
            \Log::info('Payment receipt upload attempt', [
                'request_id' => $requestId,
                'has_receipt_url' => !empty($request->receipt_url),
                'has_public_id' => !empty($request->public_id)
            ]);

            $validatedData = $request->validate([
                'receipt_url' => 'required|url',
                'public_id' => 'required|string'
            ]);

            $form = RequisitionForm::findOrFail($requestId);

            // Check if form is in correct status for payment
            if ($form->status_id !== FormStatus::where('status_name', 'Awaiting Payment')->first()->status_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is not awaiting payment.'
                ], 422);
            }

            // Update the form with receipt details
            $form->proof_of_payment_url = $validatedData['receipt_url'];
            $form->proof_of_payment_public_id = $validatedData['public_id'];
            $form->save();

            \Log::info('Payment receipt uploaded successfully', [
                'request_id' => $requestId,
                'receipt_url' => $validatedData['receipt_url']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Receipt uploaded successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Receipt upload validation failed', [
                'request_id' => $requestId,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to upload payment receipt', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload receipt: ' . $e->getMessage()
            ], 500);
        }
    }
}