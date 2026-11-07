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

    /**
     * Calendar events this service is assigned to.
     * Inverse of CalendarEvent::services().
     */
    public function calendarEvents()
    {
        return $this->belongsToMany(
            CalendarEvent::class,
            'event_services',
            'service_id',
            'event_id'
        )->withPivot(['event_service_id', 'notes'])
            ->withTimestamps();
    }

    public function managingDepartment()
    {
        return $this->belongsTo(Department::class, 'managed_by', 'department_id');
    }
}