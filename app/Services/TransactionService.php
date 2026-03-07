<?php

namespace App\Services;

use App\Models\EquipmentTransaction;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Release an equipment item (scan out)
     */
    public function releaseItem(
        EquipmentTransaction $transaction,
        ?int $facilityId = null,
        ?string $destinationName = null,
        ?string $notes = null
    ): EquipmentTransaction {
        // Business rule: Can't release if already released
        if ($transaction->released_at) {
            throw new \Exception('Item already released');
        }

        // Business rule: Check for existing active transactions
        $this->ensureNoActiveTransaction($transaction->item_id, $transaction->id);

        // Update transaction
        $transaction->update([
            'released_at' => now(),
            'facility_id' => $facilityId,
            'destination_name' => $destinationName,
            'release_notes' => $notes,
            'status_id' => 1 // active
        ]);

        // Update equipment item status
        $transaction->equipmentItem->update(['status' => 'in_use']);

        // Simple log instead of event
        Log::info('Equipment released', [
            'transaction_id' => $transaction->id,
            'item_id' => $transaction->item_id,
            'released_by' => $transaction->released_by
        ]);

        return $transaction->fresh();
    }

    /**
     * Return an equipment item (scan in)
     */
    public function returnItem(
        EquipmentTransaction $transaction,
        int $conditionId,
        ?string $notes = null
    ): EquipmentTransaction {
        // Business rule: Can't return if already returned
        if ($transaction->returned_at) {
            throw new \Exception('Item already returned');
        }

        // Business rule: Can't return if not released
        if (!$transaction->released_at) {
            throw new \Exception('Cannot return an item that was never released');
        }

        // Update transaction
        $transaction->update([
            'returned_at' => now(),
            'condition_id' => $conditionId,
            'return_notes' => $notes,
            'status_id' => 3 // completed
        ]);

        // Update equipment item status
        $transaction->equipmentItem->update(['status' => 'available']);

        // Simple log
        Log::info('Equipment returned', [
            'transaction_id' => $transaction->id,
            'item_id' => $transaction->item_id,
            'returned_by' => $transaction->returned_by,
            'condition_id' => $conditionId
        ]);

        // Check if return is late and log it
        if ($this->isOverdue($transaction)) {
            Log::warning('Equipment returned late', [
                'transaction_id' => $transaction->id,
                'item_id' => $transaction->item_id,
                'expected_return' => $transaction->requisitionForm->end_date ?? 'unknown'
            ]);
        }

        return $transaction->fresh();
    }

    /**
     * Ensure no active transaction exists for this item
     */
    protected function ensureNoActiveTransaction(int $equipmentItemId, ?int $excludeId = null): void
    {
        $query = EquipmentTransaction::where('item_id', $equipmentItemId)
            ->where('status_id', 1);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new \Exception('Equipment item already has an active transaction');
        }
    }

    /**
     * Check if transaction is overdue
     */
    protected function isOverdue(EquipmentTransaction $transaction): bool
    {
        if (!$transaction->requisitionForm || !$transaction->requisitionForm->end_date) {
            return false;
        }

        return now()->gt($transaction->requisitionForm->end_date) && !$transaction->returned_at;
    }
}