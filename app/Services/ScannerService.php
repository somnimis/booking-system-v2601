<?php
// app/Services/ScannerService.php

namespace App\Services;

use App\Models\EquipmentItem;
use App\Models\RequestedEquipment;
use App\Models\EquipmentTransaction;

class ScannerService
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Find equipment item by barcode with various matching strategies
     */
    public function findItemByBarcode(string $barcode): ?EquipmentItem
    {
        $barcode = $this->cleanBarcode($barcode);
        
        // Try exact match first
        $item = EquipmentItem::with([
            'equipment.category',
            'equipment.department',
            'equipment.status',
            'equipment.images',
            'condition'
        ])->where('barcode_number', $barcode)->first();

        if (!$item) {
            // Try partial match
            $item = EquipmentItem::with([
                'equipment.category',
                'equipment.department', 
                'equipment.status',
                'equipment.images',
                'condition'
            ])->where('barcode_number', 'like', '%' . $barcode . '%')->first();
        }

        if (!$item) {
            // Try without EQ- prefix
            $barcodeWithoutPrefix = str_replace('EQ-', '', $barcode);
            if ($barcodeWithoutPrefix !== $barcode) {
                $item = EquipmentItem::with([
                    'equipment.category',
                    'equipment.department', 
                    'equipment.status',
                    'equipment.images',
                    'condition'
                ])->where('barcode_number', 'like', '%' . $barcodeWithoutPrefix . '%')->first();
            }
        }

        return $item;
    }

    /**
     * Clean and normalize barcode
     */
    public function cleanBarcode(string $barcode): string
    {
        $barcode = trim($barcode);
        $barcode = preg_replace('/^(EQ-)+/', 'EQ-', $barcode);
        $barcode = preg_replace('/[^A-Z0-9\-]/', '', $barcode);
        
        return $barcode;
    }

    /**
     * Get current bookings for equipment
     */
    public function getCurrentBookings(int $equipmentId)
    {
        return RequestedEquipment::with([
            'requisitionForm' => function($query) {
                $query->where('is_closed', false)
                      ->where('is_finalized', true);
            }
        ])->where('equipment_id', $equipmentId)
          ->get()
          ->filter(function($requestedEquipment) {
              return $requestedEquipment->requisitionForm !== null;
          });
    }

    /**
     * Calculate stock availability
     */
    public function calculateStockAvailability(int $equipmentId, $currentBookings = null)
    {
        if (!$currentBookings) {
            $currentBookings = $this->getCurrentBookings($equipmentId);
        }

        $totalItems = EquipmentItem::where('equipment_id', $equipmentId)
                                 ->where('status_id', '!=', 5)
                                 ->count();

        $availableCount = EquipmentItem::where('equipment_id', $equipmentId)
                                     ->where('status_id', 1)
                                     ->whereIn('condition_id', [1, 2, 3])
                                     ->where('status_id', '!=', 5)
                                     ->count();

        $bookedItems = $currentBookings->sum('quantity');
        $availableStock = max(0, $availableCount - $bookedItems);

        return [
            'total_items' => $totalItems,
            'available_count' => $availableCount,
            'booked_items' => $bookedItems,
            'available_stock' => $availableStock
        ];
    }

    /**
     * Get active transaction for an item
     */
    public function getActiveTransaction(int $itemId)
    {
        return EquipmentTransaction::where('item_id', $itemId)
            ->where('status_id', 1)
            ->with(['requisitionForm', 'releasedBy', 'facility'])
            ->first();
    }

    /**
     * Validate item can be borrowed
     */
    public function validateBorrowRequest(string $barcode, int $requisitionFormId): array
    {
        $item = $this->findItemByBarcode($barcode);
        
        if (!$item) {
            return ['error' => 'Equipment item not found'];
        }

        $requestedEquipment = RequestedEquipment::where('request_id', $requisitionFormId)
            ->where('equipment_id', $item->equipment_id)
            ->first();

        if (!$requestedEquipment) {
            return ['error' => 'This equipment is not part of the selected booking'];
        }

        $activeTransaction = $this->getActiveTransaction($item->item_id);

        if ($activeTransaction) {
            return ['error' => 'This item is already in use. Please return it first.', 'transaction' => $activeTransaction];
        }

        return [
            'success' => true,
            'item' => $item,
            'requested_equipment' => $requestedEquipment
        ];
    }

    /**
     * Validate return request
     */
    public function validateReturnRequest(string $barcode): array
    {
        $item = $this->findItemByBarcode($barcode);
        
        if (!$item) {
            return ['error' => 'Equipment item not found'];
        }

        $activeTransaction = EquipmentTransaction::where('item_id', $item->item_id)
            ->where('status_id', 1)
            ->with(['requisitionForm', 'releasedBy'])
            ->first();

        if (!$activeTransaction) {
            return ['error' => 'No active transaction found for this item. It may not be checked out.'];
        }

        return [
            'success' => true,
            'item' => $item,
            'transaction' => $activeTransaction
        ];
    }

    /**
     * Format item data for response
     */
    public function formatItemResponse(EquipmentItem $item, array $stockData, $activeTransaction = null, $currentBookings = null, $scannedBarcode = null): array
    {
        if (!$currentBookings) {
            $currentBookings = $this->getCurrentBookings($item->equipment_id);
        }

        return [
            'status' => 'success',
            'item' => [
                'item_id' => $item->item_id,
                'item_name' => $item->item_name,
                'barcode_number' => $item->barcode_number,
                'condition_id' => $item->condition_id,
                'condition_name' => $item->condition->condition_name,
                'image_url' => $item->image_url,
                'cloudinary_public_id' => $item->cloudinary_public_id,
                'item_notes' => $item->item_notes,
                'equipment_details' => [
                    'equipment_id' => $item->equipment->equipment_id,
                    'name' => $item->equipment->equipment_name,
                    'description' => $item->equipment->description,
                    'brand' => $item->equipment->brand,
                    'storage_location' => $item->equipment->storage_location,
                    'base_fee' => $item->equipment->base_fee,
                    'rate_type' => $item->equipment->rate_type,
                    'department_id' => $item->equipment->department->department_name ?? 'N/A',
                    'category' => $item->equipment->category->category_name ?? 'N/A',
                    'availability_status' => $item->equipment->status->status_name,
                    'availability_status_id' => $item->equipment->status_id,
                ],
                'current_transaction' => $activeTransaction ? [
                    'id' => $activeTransaction->id,
                    'released_at' => $activeTransaction->released_at,
                    'request_id' => $activeTransaction->request_id,
                    'destination_name' => $activeTransaction->destination_name
                ] : null
            ],
            'current_bookings' => $currentBookings->map(function($booking) {
                return [
                    'request_id' => $booking->requisitionForm->request_id,
                    'title' => $booking->requisitionForm->calendar_title,
                    'start_date' => $booking->requisitionForm->start_date,
                    'end_date' => $booking->requisitionForm->end_date,
                    'start_time' => $booking->requisitionForm->start_time,
                    'end_time' => $booking->requisitionForm->end_time,
                    'quantity' => $booking->quantity,
                    'requester' => $booking->requisitionForm->first_name . ' ' . $booking->requisitionForm->last_name
                ];
            }),
            'available_stock' => $stockData['available_stock'],
            'total_stock' => $stockData['total_items'],
            'available_count' => $stockData['available_count'],
            'booked_items' => $stockData['booked_items'],
            'scan_debug' => [
                'scanned_barcode' => $scannedBarcode ?? $item->barcode_number,
                'matched_barcode' => $item->barcode_number,
                'match_type' => ($scannedBarcode ?? $item->barcode_number) === $item->barcode_number ? 'exact' : 'partial'
            ]
        ];
    }

    /**
     * Format transaction response for borrow
     */
    public function formatBorrowResponse($transaction, $item): array
    {
        return [
            'status' => 'success',
            'message' => 'Item borrowed successfully.',
            'transaction' => [
                'id' => $transaction->id,
                'released_at' => $transaction->released_at,
                'status' => $transaction->status_id
            ],
            'item' => [
                'item_id' => $item->item_id,
                'item_name' => $item->item_name,
                'condition_id' => $item->fresh()->condition_id,
                'status_id' => $item->fresh()->status_id,
                'item_notes' => $item->item_notes
            ]
        ];
    }

    /**
     * Format transaction response for return modal
     */
    public function formatReturnModalResponse($item, $transaction): array
    {
        return [
            'status' => 'success',
            'message' => '',
            'transaction' => [
                'id' => $transaction->id,
                'released_at' => $transaction->released_at,
                'request_id' => $transaction->request_id,
                'booking_title' => $transaction->requisitionForm->calendar_title ?? 'N/A',
                'released_by_name' => $transaction->releasedBy->name ?? 'Unknown',
                'destination_name' => $transaction->destination_name,
                'release_notes' => $transaction->release_notes
            ],
            'item' => [
                'item_id' => $item->item_id,
                'item_name' => $item->item_name,
                'barcode' => $item->barcode_number,
                'condition_id' => $item->condition_id,
                'condition_name' => $item->condition->condition_name,
                'status_id' => $item->status_id
            ]
        ];
    }

    /**
     * Format error response data
     */
    public function formatError(string $message, int $statusCode = 400, $additionalData = null): array
    {
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if ($additionalData) {
            $response = array_merge($response, $additionalData);
        }

        return $response;
    }
}