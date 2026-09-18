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
        'fee_snapshot', 
        'is_waived',
        'waived_by',
        'waived_at',
    ];

    protected $casts = [
        'is_waived'    => 'boolean',
        'fee_snapshot' => 'decimal:2',
        'waived_at'    => 'datetime',
    ];

    public function requisitionForm(): BelongsTo
    {
        return $this->belongsTo(RequisitionForm::class, 'request_id', 'request_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ExtraService::class, 'service_id', 'service_id');
    }
}