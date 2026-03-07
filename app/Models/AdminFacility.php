<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminFacility extends Model
{
    protected $table = 'admin_facilities';
    protected $primaryKey = 'admin_facility_id';

    protected $fillable = [
        'facility_id',
        'admin_id'
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }
}
