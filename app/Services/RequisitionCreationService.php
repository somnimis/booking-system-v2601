<?php

namespace App\Services;

use App\Models\RequisitionForm;
use App\Models\RequestedFacility;
use App\Models\RequestedEquipment;
use App\Models\RequestedService;
use App\Models\Facility;
use App\Models\Equipment;
use App\Models\ExtraService;
use App\Models\FormStatus;
use App\Models\RequisitionComment;
use App\Exceptions\BookingConflictException;
use App\Http\Requests\RequisitionSubmitRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RequisitionCreationService
 * ----------------------------------------------------------------------------
 * Single source of truth for creating requisitions (public + admin).
 *
 * Pipeline (both paths):
 *   1. Availability check (all items) -> BookingConflictException on hard conflicts
 *   2. Create RequisitionForm (no tentative_fee yet)
 *   3. Persist children with fee_snapshot (frozen price at submission)
 *   4. Reload relations, compute authoritative tentative_fee -> update
 *   5. Build approval chain (skipped when finalized/admin)
 *   6. Send notifications
 *   7. Clear submission session
 *
 * Admin path (`createFromPayload`) additionally:
 *   - Sets caller-supplied status_id (bypasses Pending Approval)
 *   - Sets is_finalized / finalized_at / finalized_by
 *   - Inserts an admin RequisitionComment
 *   - SKIPS approval chain creation (finalized = no workflow)
 * ----------------------------------------------------------------------------
 */
class RequisitionCreationService
{
    public function __construct(
        protected FeeCalculatorService $feeCalculator,
        protected CheckAvailabilityService $availabilityChecker,
        protected ApprovalChainService $approvalChainService,
        protected NotificationService $notificationService,
        protected AccessCodeService $accessCodeService,
    ) {
    }

    // ----- Public entry point (session cart) ----- //

    public function createFromSession(RequisitionSubmitRequest $request): RequisitionForm
    {
        $items = session('selected_items', []);
        if (empty($items)) {
            throw new \Exception('Your booking cart is empty. Add items before submitting.');
        }

        $data = $this->normalizeRequestData($request->validated());
        $data['items'] = $items;

        // Persist request_info to session for downstream UI consistency (unchanged behavior)
        session(['request_info' => $data]);

        return $this->create($data, null);
    }

    // ----- Admin entry point (payload) ----- //

    /**
     * @param  array  $data   Must match RequisitionSubmitRequest shape + items[] + status_id
     * @param  int    $adminId
     */
    public function createFromPayload(array $data, int $adminId): RequisitionForm
    {
        $data['items'] = $data['items'] ?? [];
        $finalize = [
            'admin_id' => $adminId,
            'status_id' => $data['status_id'],
            'comment' => 'Admin created this reservation manually',
        ];

        return $this->create($data, $finalize);
    }

    // ------------------------------------------------------------------------
    // Core pipeline
    // ------------------------------------------------------------------------

    protected function create(array $data, ?array $finalize): RequisitionForm
    {
        DB::beginTransaction();

        try {
            $this->validateAvailability($data);

            $requisitionForm = $this->createRequisition($data, $finalize);

            $this->saveChildren($requisitionForm, $data['items']);

            $requisitionForm->load([
                'requestedFacilities.facility',
                'requestedEquipment.equipment',
                'requestedServices.service',
            ]);

            // Authoritative fee — computed from persisted rows, not from cart claims.
            $tentativeFee = $this->feeCalculator->calculateBaseFee($requisitionForm);
            $requisitionForm->update([
                'tentative_fee' => round($tentativeFee, 2),
                'approved_fee' => round($tentativeFee, 2),
            ]);

            // Approval chain: Skip if this was finalized immediately (Reserved status bypass).
            // Public forms and Admin (Pending Approval) forms will have is_finalized = false.
            if (!$requisitionForm->is_finalized) {
                $this->approvalChainService->createApprovalChain($requisitionForm);
            }

            // Admin comment record (non-critical; don't fail the whole txn).
            if ($finalize !== null) {
                $this->addAdminComment($requisitionForm->request_id, $finalize['admin_id'], $finalize['comment']);
            }

            DB::commit();

            $this->sendNotifications($requisitionForm);
            $this->clearSubmissionSession();

            return $requisitionForm;
        } catch (BookingConflictException $e) {
            DB::rollBack();
            throw $e; // Controllers map this to 409
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Requisition creation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    // ------------------------------------------------------------------------
    // Normalizers
    // ------------------------------------------------------------------------

    /**
     * Turn validated FormRequest into the common `$data` shape used by both paths.
     */
    protected function normalizeRequestData(array $validated): array
    {
        $allDay = (bool) ($validated['all_day'] ?? false);

        return [
            'user_type' => $validated['user_type'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'school_id' => $validated['school_id'] ?? null,
            'organization_name' => $validated['organization_name'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'num_participants' => $validated['num_participants'],
            'purpose_id' => $validated['purpose_id'],
            'additional_requests' => $validated['additional_requests'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'start_time' => $allDay ? '00:00:00' : $validated['start_time'],
            'end_time' => $allDay ? '00:00:00' : $validated['end_time'],
            'all_day' => $allDay,
            'cleanup_hours' => $validated['cleanup_hours'] ?? 4,
            'event_title' => $validated['event_title'],
            'event_details' => $validated['event_details'] ?? null,
            'num_tables' => $validated['num_tables'] ?? 0,
            'num_chairs' => $validated['num_chairs'] ?? 0,
            'num_microphones' => $validated['num_microphones'] ?? 0,
            'event_documents_url' => $validated['event_documents_url'] ?? null,
        ];
    }

    // ------------------------------------------------------------------------
    // Availability
    // ------------------------------------------------------------------------

    /**
     * Normalize items to CheckAvailabilityService::checkAll() contract,
     * throw BookingConflictException if hard conflicts exist.
     *
     * @throws BookingConflictException
     */
    protected function validateAvailability(array $data): void
    {
        $items = [];
        foreach ($data['items'] as $item) {
            if ($item['type'] === 'facility') {
                $items[] = ['type' => 'facility', 'facility_id' => $item['facility_id']];
            } elseif ($item['type'] === 'equipment') {
                $items[] = [
                    'type' => 'equipment',
                    'equipment_id' => $item['equipment_id'],
                    'quantity' => $item['quantity'] ?? 1,
                ];
            } elseif ($item['type'] === 'service') {
                $items[] = ['type' => 'service', 'service_id' => $item['service_id']];
            }
        }

        $result = $this->availabilityChecker->checkAll([
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'all_day' => $data['all_day'],
            'cleanup_hours' => $data['cleanup_hours'],
            'items' => $items,
        ]);

        if (!empty($result['hard_conflicts'])) {
            throw new BookingConflictException($result['hard_conflicts']);
        }
    }

    // ------------------------------------------------------------------------
    // Persistence
    // ------------------------------------------------------------------------

    protected function createRequisition(array $data, ?array $finalize): RequisitionForm
    {
        $statusId = $finalize['status_id']
            ?? FormStatus::where('status_name', FormStatus::PENDING_APPROVAL)->value('status_id');

        // Determine the status name to apply bypass rules
        $statusName = FormStatus::where('status_id', $statusId)->value('status_name');

        $attrs = [
            'user_type' => $data['user_type'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'school_id' => $data['school_id'],
            'organization_name' => $data['organization_name'],
            'contact_number' => $data['contact_number'],
            'num_participants' => $data['num_participants'],
            'purpose_id' => $data['purpose_id'],
            'additional_requests' => $data['additional_requests'],
            'event_title' => $data['event_title'],
            'event_details' => $data['event_details'],
            'num_tables' => $data['num_tables'],
            'num_chairs' => $data['num_chairs'],
            'num_microphones' => $data['num_microphones'],
            'event_documents_url' => $data['event_documents_url'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'all_day' => $data['all_day'],
            'cleanup_hours' => $data['cleanup_hours'],
            'access_code' => $this->accessCodeService->generateUniqueAccessCode(),
            'status_id' => $statusId,
            'is_admin_created' => $finalize !== null,
            'created_by' => $finalize['admin_id'] ?? null,
        ];

        // Admin finalize fields
        if ($finalize !== null) {
            if ($statusName === FormStatus::RESERVED) {
                // Reserved: immediately finalized, bypasses workflow, finalized_by stays null
                $attrs['is_finalized'] = true;
                $attrs['finalized_at'] = now();
                $attrs['finalized_by'] = null;
            } else {
                // Pending Approval: Not finalized, acts like normal submission
                $attrs['is_finalized'] = false;
                $attrs['finalized_at'] = null;
                $attrs['finalized_by'] = null;
            }
        }

        return RequisitionForm::create($attrs);
    }

    /**
     * Persist children with fee_snapshot (frozen price at submission).
     */
    protected function saveChildren(RequisitionForm $form, array $items): void
    {
        $facilityIds = collect($items)->where('type', 'facility')->pluck('facility_id');
        $equipmentIds = collect($items)->where('type', 'equipment')->pluck('equipment_id');
        $serviceIds = collect($items)->where('type', 'service')->pluck('service_id');

        $facilities = Facility::whereIn('facility_id', $facilityIds)->get()->keyBy('facility_id');
        $equipment = Equipment::whereIn('equipment_id', $equipmentIds)->get()->keyBy('equipment_id');
        $services = ExtraService::whereIn('service_id', $serviceIds)->get()->keyBy('service_id');

        foreach ($items as $item) {
            if ($item['type'] === 'facility') {
                RequestedFacility::create([
                    'request_id' => $form->request_id,
                    'facility_id' => $item['facility_id'],
                    'fee_snapshot' => $facilities->get($item['facility_id'])?->base_fee,
                    'is_waived' => false,
                ]);
            } elseif ($item['type'] === 'equipment') {
                RequestedEquipment::create([
                    'request_id' => $form->request_id,
                    'equipment_id' => $item['equipment_id'],
                    'quantity' => $item['quantity'] ?? 1,
                    'fee_snapshot' => $equipment->get($item['equipment_id'])?->base_fee,
                    'is_waived' => false,
                ]);
            } elseif ($item['type'] === 'service') {
                RequestedService::create([
                    'request_id' => $form->request_id,
                    'service_id' => $item['service_id'],
                    'fee_snapshot' => $services->get($item['service_id'])?->service_fee,
                    'is_waived' => false,
                ]);
            }
        }
    }

    protected function addAdminComment(int $requestId, int $adminId, string $comment): void
    {
        try {
            RequisitionComment::create([
                'request_id' => $requestId,
                'admin_id' => $adminId,
                'comment' => $comment,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add admin comment', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ------------------------------------------------------------------------
    // Side effects
    // ------------------------------------------------------------------------

    protected function sendNotifications(RequisitionForm $form): void
    {
        try {
            $this->notificationService->sendConfirmationEmail($form);
        } catch (\Exception $e) {
            Log::error('Confirmation email failed: ' . $e->getMessage());
        }

        try {
            $this->notificationService->sendAdminApprovalEmails($form);
        } catch (\Exception $e) {
            Log::error('Admin approval emails failed: ' . $e->getMessage());
        }
    }

    protected function clearSubmissionSession(): void
    {
        session()->forget(['request_info', 'selected_items', 'fee_summary', 'temp_uploads', 'extra_services']);
    }
}