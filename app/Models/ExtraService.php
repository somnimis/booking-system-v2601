<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraService extends Model
{
    // Table name
    protected $table = 'extra_services';
    protected $primaryKey = 'service_id';

    // Disable timestamps
    public $timestamps = false;

    // Mass assignable fields
    protected $fillable = [
        'service_name'
    ];

    // Relationships

    public function requisitionForms()
    {
        return $this->belongsToMany(
            RequisitionForm::class,
            'requested_services',  // ← CORRECT pivot table
            'service_id',          // ← Foreign key on pivot table to extra_services  
            'request_id'           // ← Foreign key on pivot table to requisition_forms
        )->withTimestamps();       // ← Add this if your pivot table has timestamps
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

/**
 * The admins that belong to the service.
 */
public function admins()
{
    return $this->belongsToMany(Admin::class, 'admin_services', 'service_id', 'admin_id');
}

}
