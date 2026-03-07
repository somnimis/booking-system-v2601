<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\LookupTables\Condition;
use Illuminate\Support\Carbon;

class EquipmentTransaction extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'request_id',
        'requested_equipment_id',
        'item_id',
        'released_at',
        'returned_at',
        'released_by',
        'returned_by',
        'facility_id',
        'destination_name',
        'condition_id',
        'release_notes',
        'return_notes',
        'status_id'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'released_at' => 'datetime',
        'returned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // === RELATIONSHIPS ===

    /**
     * Get the requisition form this transaction belongs to.
     */
    public function requisitionForm()
    {
        return $this->belongsTo(RequisitionForm::class, 'request_id', 'request_id');
    }

    /**
     * Get the requested equipment entry this transaction is for.
     */
    public function requestedEquipment()
    {
        return $this->belongsTo(RequestedEquipment::class, 'requested_equipment_id', 'requested_equipment_id');
    }

    /**
     * Get the specific equipment item being transacted.
     */
    public function equipmentItem()
    {
        return $this->belongsTo(EquipmentItem::class, 'item_id', 'item_id');
    }

    /**
     * Get the admin who released the item.
     */
    public function releasedBy()
    {
        return $this->belongsTo(Admin::class, 'released_by', 'admin_id');
    }

    /**
     * Get the admin who received/returned the item.
     */
    public function returnedBy()
    {
        return $this->belongsTo(Admin::class, 'returned_by', 'admin_id');
    }

    /**
     * Get the facility where the equipment was used.
     */
    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }

    /**
     * Get the condition of the item upon return.
     */
    public function condition()
    {
        return $this->belongsTo(Condition::class, 'condition_id', 'condition_id');
    }

    /**
     * Get the current status of this transaction.
     */
    public function status()
    {
        return $this->belongsTo(FormStatus::class, 'status_id', 'status_id');
    }

    // === SCOPES ===

    /**
     * Scope a query to only active transactions.
     */
    public function scopeActive($query)
    {
        return $query->where('status_id', 1);
    }

    /**
     * Scope a query to completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status_id', 3);
    }

    /**
     * Scope a query to overdue transactions (active + returned_at is null + released_at older than expected).
     */
    public function scopeOverdue($query, $expectedReturnDays = 7)
    {
        return $query->where('status_id', 1)
            ->whereNull('returned_at')
            ->where('released_at', '<=', now()->subDays($expectedReturnDays));
    }

    /**
     * Scope a query to transactions for a specific equipment item.
     */
    public function scopeForEquipmentItem($query, $equipmentItemId)
    {
        return $query->where('item_id', $equipmentItemId);
    }

    // === HELPER METHODS ===

    /**
     * Check if this transaction is active.
     */
    public function isActive(): bool
    {
        return $this->status_id === 1;
    }

    /**
     * Check if this transaction is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status_id === 3;
    }

    /**
     * Check if the item has been released.
     */
    public function isReleased(): bool
    {
        return !is_null($this->released_at);
    }

    /**
     * Check if the item has been returned.
     */
    public function isReturned(): bool
    {
        return !is_null($this->returned_at);
    }

    /**
     * Get the duration of this transaction in days.
     */
    public function getDurationInDaysAttribute(): ?float
    {
        if (!$this->released_at) {
            return null;
        }
        
        $end = $this->returned_at ?? now();
        return $this->released_at->diffInDays($end, true);
    }

    /**
     * Check if the return is overdue (if you have an expected return date).
     */
    public function isOverdue(?Carbon $expectedReturnDate = null): bool
    {
        if (!$this->isActive() || $this->returned_at) {
            return false;
        }
        
        if ($expectedReturnDate) {
            return now()->gt($expectedReturnDate);
        }
        
        // If you have an expected return date from the requisition form
        if ($this->requisitionForm && $this->requisitionForm->end_date) {
            return now()->gt($this->requisitionForm->end_date);
        }
        
        return false;
    }

}