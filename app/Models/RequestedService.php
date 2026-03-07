<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestedService extends Model
{
    protected $table = 'requested_services';
    protected $primaryKey = 'requested_service_id';
    public $timestamps = true;

    protected $fillable = [
        'request_id',
        'service_id',
    ];

    // Relationship: belongs to a requisition form
    /**
     * Get the requisition form that this requested service belongs to
     */
    public function requisitionForm(): BelongsTo
    {
        return $this->belongsTo(RequisitionForm::class, 'request_id', 'request_id');
    }
    
    /**
     * Get the extra service that was requested
     * This is the relationship you're missing!
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(ExtraService::class, 'service_id', 'service_id');
    }
}
