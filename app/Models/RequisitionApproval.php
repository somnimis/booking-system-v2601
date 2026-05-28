<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionApproval extends Model
{
    protected $table = "requisition_approvals";
    protected $primaryKey = "approval_id";
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'admin_id',
        'acted_by',
        'acted_at',
        'stage',
        'status',
        'remarks',
        'date_updated',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
        'date_updated' => 'datetime',
    ];

    // Relationships

    public function requisition()
    {
        return $this->belongsTo(RequisitionForm::class, 'request_id', 'request_id');
    }

    // The assigned signatory
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    // The admin who actually acted (approved/rejected)
    public function actedBy()
    {
        return $this->belongsTo(Admin::class, 'acted_by', 'admin_id');
    }
}