<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EquipmentTransaction;
use App\Models\EquipmentItem;
use App\Models\RequestedEquipment;
use App\Models\RequisitionForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class EquipmentTransactionController extends Controller
{
    /**
     * Store a new equipment transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
   public function store(Request $request): JsonResponse
{
    // Validate the request based on transaction type
    $validator = Validator::make($request->all(), [
        'barcode' => 'required|string|exists:equipment_items,barcode_number',
        'transaction_type' => 'required|in:release,return,update',

        // Common fields
        'condition_id' => 'nullable|exists:conditions,condition_id',
        'notes' => 'nullable|string|max:1000',

        // Release specific
        'request_id' => 'required_if:transaction_type,release|exists:requisition_forms,request_id',
        'facility_id' => 'nullable|exists:facilities,facility_id',
        'destination_name' => 'nullable|string|max:255',

        // Return specific
        'apply_late_fee' => 'nullable|boolean',
        'late_fee_amount' => 'required_if:apply_late_fee,true|numeric|min:0|nullable',

        // Update specific - FIXED: Use availability_statuses table, not form_statuses
        'status_id' => 'required_if:transaction_type,update|exists:availability_statuses,status_id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    // Find the equipment item by barcode
    $equipmentItem = EquipmentItem::where('barcode_number', $request->barcode)
        ->with(['equipment', 'condition'])
        ->firstOrFail();

    try {
        DB::beginTransaction();

        // Determine which requested_equipment_id to use
        $requestedEquipmentId = null;
        if ($request->transaction_type === 'release' && $request->request_id) {
            // Find the requested equipment record for this equipment and request
            $requestedEquipment = RequestedEquipment::where('request_id', $request->request_id)
                ->where('equipment_id', $equipmentItem->equipment_id)
                ->first();

            if (!$requestedEquipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'This equipment is not part of the selected requisition.'
                ], 422);
            }

            $requestedEquipmentId = $requestedEquipment->requested_equipment_id;
        }

        // Prepare transaction data
        $transactionData = [
            'item_id' => $equipmentItem->item_id,
            'request_id' => $request->transaction_type === 'update' ? null : $request->request_id,
            'requested_equipment_id' => $requestedEquipmentId,
            'condition_id' => $request->condition_id ?? $equipmentItem->condition_id,
            'status_id' => 1, // Default to active status for transactions
        ];

        // Handle different transaction types
        switch ($request->transaction_type) {
            case 'release':
                $transactionData = array_merge($transactionData, [
                    'released_at' => now(),
                    'released_by' => Auth::id(),
                    'facility_id' => $request->facility_id,
                    'destination_name' => $request->destination_name,
                    'release_notes' => $request->notes,
                ]);

                // Update equipment item status to 'In Use' (availability_statuses)
                $equipmentItem->status_id = 2; // Assuming 2 = 'In Use' or 'Reserved'
                $equipmentItem->save();
                break;

            case 'return':
                // Find the most recent release transaction for this item
                $releaseTransaction = EquipmentTransaction::where('item_id', $equipmentItem->item_id)
                    ->whereNotNull('released_at')
                    ->whereNull('returned_at')
                    ->latest()
                    ->first();

                $transactionData = array_merge($transactionData, [
                    'returned_at' => now(),
                    'returned_by' => Auth::id(),
                    'return_notes' => $request->notes,
                    'status_id' => 3, // Completed status
                ]);

                // If this is a return, link to the original release transaction
                if ($releaseTransaction) {
                    $transactionData['request_id'] = $releaseTransaction->request_id;
                    $transactionData['requested_equipment_id'] = $releaseTransaction->requested_equipment_id;
                    $transactionData['facility_id'] = $releaseTransaction->facility_id;
                    $transactionData['destination_name'] = $releaseTransaction->destination_name;
                }

                // Update equipment item status back to 'Available'
                $equipmentItem->status_id = 1; // Assuming 1 = 'Available'
                $equipmentItem->save();

                // Handle late fee if applicable
                if ($request->apply_late_fee && $transactionData['request_id']) {
                    $requisition = RequisitionForm::find($transactionData['request_id']);
                    if ($requisition) {
                        $requisition->late_penalty_fee = $request->late_fee_amount ?? $requisition->late_penalty_fee;
                        $requisition->is_late = true;
                        $requisition->save();
                    }
                }
                break;

            case 'update':
                // For update, we don't need request_id or timestamps
                $transactionData = array_merge($transactionData, [
                    'release_notes' => $request->notes,
                    'request_id' => null,
                    'requested_equipment_id' => null,
                ]);

                // Update equipment item with new condition/status
                if ($request->condition_id) {
                    $equipmentItem->condition_id = $request->condition_id;
                }
                if ($request->status_id) {
                    $equipmentItem->status_id = $request->status_id;
                }
                $equipmentItem->save();
                break;
        }

        // Create the transaction record
        $transaction = EquipmentTransaction::create($transactionData);

        DB::commit();

        // Load relationships for response
        $transaction->load([
            'equipmentItem.equipment',
            'equipmentItem.condition',
            'requisitionForm',
            'facility',
            'releasedBy',
            'returnedBy'
        ]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->transaction_type) . ' transaction completed successfully.',
            'data' => $transaction
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to process transaction.',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Look up equipment by barcode.
     *
     * @param  string  $barcode
     * @return \Illuminate\Http\JsonResponse
     */
    public function lookupByBarcode($barcode): JsonResponse
    {
        $equipmentItem = EquipmentItem::with([
            'equipment',
            'condition',
            'status',
            'transactions' => function ($query) {
                $query->latest()->limit(5);
            }
        ])
            ->where('barcode_number', $barcode)
            ->first();

        if (!$equipmentItem) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found with this barcode.'
            ], 404);
        }

        // Get active requisitions for dropdown
        $activeRequisitions = RequisitionForm::whereIn('status_id', [2, 3]) // Pending, Approved
            ->with('purpose')
            ->latest()
            ->limit(10)
            ->get(['request_id', 'first_name', 'last_name', 'organization_name', 'start_date']);

        // Format requisitions for select dropdown
        $formattedRequisitions = $activeRequisitions->map(function ($req) {
            $requester = $req->organization_name ?? $req->first_name . ' ' . $req->last_name;
            return [
                'id' => $req->request_id,
                'text' => "R-{$req->request_id} - {$requester} (" . $req->start_date->format('M d, Y') . ")"
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'item' => $equipmentItem,
                'active_requisitions' => $formattedRequisitions
            ]
        ]);
    }

/**
 * Get ongoing transactions for the list view.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
public function getOngoingTransactions(Request $request): JsonResponse
{
    $transactions = EquipmentTransaction::with([
        'equipmentItem' => function ($query) {
            $query->with(['equipment', 'condition']);
        },
        'requisitionForm' => function ($query) {
            $query->select('request_id', 'first_name', 'last_name', 'organization_name', 'start_date', 'end_date');
        },
        'facility',
        'releasedBy',
        'returnedBy',
        'condition',
        'status'
    ])
    ->where(function ($query) {
        // Active transactions (released but not returned) OR recent transactions
        $query->whereNotNull('released_at')
              ->whereNull('returned_at')
              ->orWhere('created_at', '>=', now()->subDay());
    })
    ->latest()
    ->paginate(20);

    // Transform the data to match the UI requirements
    $transformed = $transactions->through(function ($transaction) {
        // Get equipment name from the equipment relationship
        $equipmentName = $transaction->equipmentItem?->equipment?->equipment_name ?? 'Unknown Equipment';
        $itemName = $transaction->equipmentItem?->item_name ?? '';
        
        // Build full item name
        $fullItemName = $itemName ? $equipmentName . ' - ' . $itemName : $equipmentName;
        
        // Get requester name
        $requester = 'N/A';
        if ($transaction->requisitionForm) {
            $requester = $transaction->requisitionForm->organization_name ?? 
                        trim(($transaction->requisitionForm->first_name ?? '') . ' ' . ($transaction->requisitionForm->last_name ?? ''));
        }
        
        // Get destination
        $destination = $transaction->facility?->facility_name ?? 
                      $transaction->destination_name ?? 
                      'N/A';
        
        // Get condition info - check both transaction condition and item condition
        $condition = $transaction->condition ?? $transaction->equipmentItem?->condition;
        $conditionName = $condition?->condition_name ?? 'Unknown';
        $conditionColor = $condition?->color_code ?? '#6c757d';
        
        // Determine status type and name
        $statusType = $this->getTransactionStatusType($transaction);
        $statusName = $this->getTransactionStatusName($transaction);
        
        // Check if overdue
        $isLate = false;
        if ($transaction->isActive() && $transaction->requisitionForm && $transaction->requisitionForm->end_date) {
            $isLate = now()->gt($transaction->requisitionForm->end_date);
        }
        
        return [
            'id' => $transaction->id,
            'item_name' => $fullItemName,
            'item_image' => $transaction->equipmentItem?->image_url,
            'request_id' => $transaction->request_id,
            'requester' => $requester,
            'destination' => $destination,
            'condition' => [
                'name' => $conditionName,
                'color_code' => $conditionColor
            ],
            'status' => [
                'name' => $statusName,
                'type' => $statusType
            ],
            'released_at' => $transaction->released_at ? $transaction->released_at->diffForHumans() : null,
            'returned_at' => $transaction->returned_at ? $transaction->returned_at->diffForHumans() : null,
            'is_late' => $isLate,
            'duration_days' => $transaction->duration_in_days
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $transformed
    ]);
}

/**
 * Get transaction status type for badge styling.
 */
private function getTransactionStatusType(EquipmentTransaction $transaction): string
{
    if ($transaction->isReturned()) {
        return 'success';
    }
    
    if ($transaction->isReleased()) {
        // Check if overdue
        if ($transaction->requisitionForm && now()->gt($transaction->requisitionForm->end_date)) {
            return 'danger';
        }
        return 'primary';
    }
    
    return 'warning';
}

/**
 * Get transaction status display name.
 */
private function getTransactionStatusName(EquipmentTransaction $transaction): string
{
    if ($transaction->isReturned()) {
        return 'Returned';
    }
    
    if ($transaction->isReleased()) {
        // Check if overdue
        if ($transaction->requisitionForm && now()->gt($transaction->requisitionForm->end_date)) {
            return 'Overdue';
        }
        return 'In Use';
    }
    
    return 'Pending';
}
}