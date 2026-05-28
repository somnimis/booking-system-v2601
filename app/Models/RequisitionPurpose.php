<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionPurpose extends Model
{
    protected $table = "requisition_purposes";
    protected $primaryKey = "purpose_id";
    public $timestamps = false;

    protected $fillable = [
        'purpose_name',
        'routes_to',
        'discount_fee',
        'discount_type',
    ];

    // Relationships

    public function requisitionForms()
    {
        return $this->hasMany(RequisitionForm::class, 'purpose_id', 'purpose_id');
    }

    public function routedAdmin()
    {
        return $this->belongsTo(Admin::class, 'routes_to', 'admin_id');
    }
}