<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentFacility extends Model
{
    // Mass assignable fields
    protected $fillable = [
        'department_id',
        'facility_id',
    ];

    /**
     * The department that owns this pivot.
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    /**
     * The facility that belongs to this pivot.
     */
    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }
}