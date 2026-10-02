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
     * Per-item equipment tracking.
     *
     * Field semantics (four-layer availability model):
     *   - status_id           → availability_statuses (Available/Unavailable/Under Maintenance/Reserved/Hidden).
     *                           Manual operator override only. NOT mutated by release/return flows.
     *   - condition_id        → conditions (New/Good/Fair/Needs Maintenance/Damaged/In Use).
     *                           Physical item health.
     *   - released_at/returned_at → physical custody layer. Source of truth for "is this in-flight?"
     *   - expected_return_at  → denormalized requisition.end_date + end_time at release.
     *                           Source of truth for overdue checks (time-level, not date-level).
     *
     * Transaction lifecycle is derived from returned_at only:
     *   - in-flight  → returned_at IS NULL
     *   - completed  → returned_at IS NOT NULL
     *
     * request_id / requested_equipment_id / item_id overlap is intentional:
     *   - request_id              → fast "all equipment for this requisition" lookups
     *   - requested_equipment_id  → per-group aggregations (e.g. "all 3 projectors returned?")
     *   - item_id                 → per-physical-unit tracking (barcode, per-item condition)
     *
     * purpose_snapshot captures purpose_name at release time so historical records don't
     * drift if the linked RequisitionPurpose is renamed.
     *
     * The attributes that are mass assignable.
     */

    protected $fillable = [
        'request_id',
        'requested_equipment_id',
        'item_id',
        'released_at',
        'returned_at',
        'expected_return_at',
        'released_by',
        'returned_by',
        'facility_id',
        'destination_name',
        'purpose_snapshot',
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
        'expected_return_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    // === RELATIONSHIPS ===
// In EquipmentTransaction.php - verify these exist

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
     * Scope a query to overdue transactions.
     *
     * Overdue = in-flight (returned_at IS NULL) AND expected_return_at has passed.
     * Uses the denormalized expected_return_at column (time-level), not requisition end_date.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNull('returned_at')
            ->whereNotNull('expected_return_at')
            ->where('expected_return_at', '<', now());
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
     * Check if this transaction is overdue.
     *
     * Overdue = not yet returned AND expected_return_at has passed.
     */
    public function isOverdue(): bool
    {
        return $this->returned_at === null
            && $this->expected_return_at !== null
            && now()->gt($this->expected_return_at);
    }

}