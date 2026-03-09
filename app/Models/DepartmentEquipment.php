<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentEquipment extends Model
{
    // Mass assignable fields
    protected $fillable = [
        'department_id',
        'equipment_id',
    ];

    /**
     * The department that owns this pivot.
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    /**
     * The equipment that belongs to this pivot.
     */
    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }
}