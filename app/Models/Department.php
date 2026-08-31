<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;

class Department extends Model
{
    protected $table = "departments";
    protected $primaryKey = "department_id";
    public $timestamps = false;

    protected $fillable = [
        'department_name',
        'department_code'
    ];

    public function getHead()
    {
        $headRole = DepartmentRole::where('role_name', 'Department Head')->first();
        return $headRole ? $this->admins()->wherePivot('role_id', $headRole->role_id)->first() : null;
    }

    public function admins()
    {
        return $this->belongsToMany(Admin::class, 'admin_departments', 'department_id', 'admin_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
    public function equipment()
    {
        return $this->hasMany(Equipment::class, 'department_id', 'department_id');
    }
    public function facilities()
    {
        return $this->hasMany(Facility::class, 'department_id', 'department_id');
    }

}
