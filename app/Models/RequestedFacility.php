<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestedFacility extends Model
{
    use HasFactory;
    protected $primaryKey = 'requested_facility_id';

    protected $fillable = [
        'request_id',
        'facility_id',
        'venue_details',
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

    public $timestamps = false;

    public function requisitionForm()
    {
        return $this->belongsTo(RequisitionForm::class, 'request_id', 'request_id');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }

    public function isWaived()
    {
        return $this->hasMany(RequisitionFee::class, 'waived_facility', 'requested_facility_id');
    }
    public function waivedBy()
    {
        return $this->belongsTo(Admin::class, 'waived_by', 'admin_id');
    }
}