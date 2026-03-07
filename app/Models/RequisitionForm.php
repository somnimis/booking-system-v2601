<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequisitionForm extends Model
{
    protected $table = "requisition_forms";
    protected $primaryKey = 'request_id';
    use HasFactory;
    protected $fillable = [
        'user_type',
        'first_name',
        'last_name',
        'email',
        'school_id',
        'organization_name',
        'contact_number',
        'num_participants',
        'num_chairs',
        'num_tables',
        'num_microphones',
        'purpose_id',
        'additional_requests',
        'status_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'all_day',
        'is_late',
        'late_penalty_fee',
        'returned_at',
        'is_finalized',
        'finalized_at',
        'finalized_by',
        'is_closed',
        'closed_at',
        'closed_by',
        'endorser',
        'date_endorsed',
        'tentative_fee',
        'approved_fee',
        'calendar_title',
        'calendar_description',
        'access_code',
        'formal_letter_url',
        'formal_letter_public_id',
        'facility_layout_url',
        'facility_layout_public_id',
        'proof_of_payment_url',
        'proof_of_payment_public_id'
    ];
    protected $casts = [
        'start_date' => 'string',
        'end_date' => 'string',
        'start_time' => 'string',
        'end_time' => 'string',
        'all_day' => 'boolean',
        'returned_at' => 'datetime',
        'finalized_at' => 'datetime',
        'closed_at' => 'datetime',
        'date_endorsed' => 'datetime',
        'is_late' => 'boolean',
        'is_finalized' => 'boolean',
        'is_closed' => 'boolean',
        'tentative_fee' => 'decimal:2',
        'approved_fee' => 'decimal:2',
    ];

    protected static function booted()
{
    static::updated(function ($requisition) {
        // Check if status changed
        if ($requisition->isDirty('status_id')) {
            $oldStatus = FormStatus::find($requisition->getOriginal('status_id'));
            $newStatus = FormStatus::find($requisition->status_id);
            
            // Create notification for Pending Approval -> Awaiting Payment
            if ($oldStatus && $newStatus && 
                $oldStatus->status_name === 'Pending Approval' && 
                $newStatus->status_name === 'Awaiting Payment') {
                
                // Get all admins who should receive this notification
                // You can modify this to notify specific admin roles
                $admins = Admin::all(); // Or filter by role
                
                foreach ($admins as $admin) {
                    Notification::create([
                        'admin_id' => $admin->admin_id,
                        'type' => 'status_update',
                        'message' => "Requisition #{$requisition->request_id} from {$requisition->first_name} {$requisition->last_name} is now awaiting payment.",
                        'request_id' => $requisition->request_id,
                        'is_read' => false
                    ]);
                }
            }
            
            // Optional: Create for other important status changes
            if ($oldStatus && $newStatus && 
                $oldStatus->status_name === 'Awaiting Payment' && 
                $newStatus->status_name === 'Scheduled') {
                
                $admins = Admin::all();
                
                foreach ($admins as $admin) {
                    Notification::create([
                        'admin_id' => $admin->admin_id,
                        'type' => 'payment_confirmed',
                        'message' => "Payment for requisition #{$requisition->request_id} has been confirmed.",
                        'request_id' => $requisition->request_id,
                        'is_read' => false
                    ]);
                }
            }
        }
    });
}
    // Relationships
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'request_id');
    }
    public function status()
    {
        return $this->belongsTo(FormStatus::class, 'status_id', 'status_id');
    }
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'request_id', 'request_id');
    }
    public function purpose()
    {
        return $this->belongsTo(RequisitionPurpose::class, 'purpose_id', 'purpose_id');
    }
    public function formStatus()
    {
        return $this->belongsTo(FormStatus::class, 'status_id');
    }
    public function requestedFacilities()
    {
        return $this->hasMany(RequestedFacility::class, 'request_id');
    }
    public function requestedEquipment()
    {
        return $this->hasMany(RequestedEquipment::class, 'request_id');
    }
    public function requisitionApprovals()
    {
        return $this->hasMany(RequisitionApproval::class, 'request_id');
    }
    public function requisitionComments()
    {
        return $this->hasMany(RequisitionComment::class, 'request_id', 'request_id');
    }
    public function requisitionFees()
    {
        return $this->hasMany(RequisitionFee::class, 'request_id', 'request_id');
    }
    public function finalizedBy()
    {
        return $this->belongsTo(Admin::class, 'finalized_by', 'admin_id');
    }
    public function closedBy()
    {
        return $this->belongsTo(Admin::class, 'closed_by', 'admin_id');
    }

    public function extraServices()
    {
        return $this->belongsToMany(
            ExtraService::class,
            'requested_services',  // ← CORRECT pivot table
            'request_id',          // ← Foreign key on pivot table to requisition_forms
            'service_id'           // ← Foreign key on pivot table to extra_services
        )->withTimestamps();       // ← Add this if your pivot table has timestamps
    }

    public function requestedServices()
    {
        return $this->hasMany(
            RequestedService::class,
            'request_id',
            'request_id'
        );
    }

}
