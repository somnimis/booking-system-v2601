<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentRole extends Model
{
    protected $table = 'department_roles';
    protected $primaryKey = 'role_id';
    public $timestamps = true;

    protected $fillable = [
        'role_name',
        'description'
    ];

    // Constants for easy reference
    const HEAD = 1;
    const APPROVER = 2;
    const STAFF = 3;

    public function admins()
    {
        return $this->belongsToMany(Admin::class, 'admin_departments', 'role_id', 'admin_id');
    }
}