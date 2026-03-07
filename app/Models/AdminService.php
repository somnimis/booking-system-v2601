<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminService extends Model
{
    use HasFactory;

    protected $table = 'admin_services';

    protected $primaryKey = 'admin_service_id';

    protected $fillable = [
        'admin_id',
        'service_id',
    ];

    /**
     * Relationship: belongs to Admin
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    /**
     * Relationship: belongs to ExtraService
     */
    public function extraService()
    {
        return $this->belongsTo(ExtraService::class, 'service_id', 'service_id');
    }
}
