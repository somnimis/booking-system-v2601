<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraService extends Model
{
    protected $table = 'extra_services';
    protected $primaryKey = 'service_id';
    public $timestamps = false;

    protected $fillable = [
        'service_name',
        'managed_by',
        'account_number',
        'service_fee'
    ];

    // Relationships

    public function requisitionForms()
    {
        return $this->belongsToMany(
            RequisitionForm::class,
            'requested_services',
            'service_id',
            'request_id'
        );
    }

    public function requestedServices()
    {
        return $this->hasMany(
            RequestedService::class,
            'service_id',
            'service_id'
        );
    }

    public function adminServices()
    {
        return $this->hasMany(AdminService::class, 'service_id', 'service_id');
    }

    public function admins()
    {
        return $this->belongsToMany(Admin::class, 'admin_services', 'service_id', 'admin_id');
    }

    // NEW: single assigned manager (from migration)
    public function manager()
    {
        return $this->belongsTo(Admin::class, 'managed_by', 'admin_id');
    }
}