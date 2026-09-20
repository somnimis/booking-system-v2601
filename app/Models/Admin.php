<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Admin extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'admins';
    protected $primaryKey = 'admin_id';

    /**
     * Global admin role IDs (mirrors admin_roles.role_id).
     *
     * NOTE: SYSTEM_ADMIN is intentionally excluded from APPROVER_ROLE_IDS.
     * It is an observational / override-only role and must never be part of
     * the automatic approval chain, even if a fat-fingered assignment tries
     * to place them into a department or a role with an approver title.
     */
    public const ROLE_SYSTEM_ADMIN = 1;
    public const APPROVER_ROLE_IDS = [2, 3, 5]; // Final Approver, Approving Officer, Issuing Officer

    protected $fillable = [
        'photo_url',
        'photo_public_id',
        'wallpaper_url',
        'wallpaper_public_id',
        'first_name',
        'last_name',
        'middle_name',
        'title',
        'signature_url',
        'signature_public_id',
        'role_id',
        'school_id',
        'email',
        'contact_number',
        'hashed_password'
    ];

    protected $hidden = [
        'hashed_password',
        'role_id'
    ];
    protected $with = ['role'];  // Always load the role relationship

    // ----- Role Assignment ----- //

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'admin_id', 'admin_id');
    }

    public function unreadNotifications()
    {
        return $this->notifications()->where('is_read', false);
    }


    public function role()
    {
        return $this->belongsTo(LookupTables\AdminRole::class, 'role_id', 'role_id');
    }

    // Accessors for local file paths
    public function getPhotoUrlAttribute($value)
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
            return asset('storage/' . $value);
        }
        return $value ?: asset('storage/defaults/admin-photo.png');
    }

    public function getWallpaperUrlAttribute($value)
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
            return asset('storage/' . $value);
        }
        return $value ?: asset('storage/defaults/wallpaper.png');
    }


    // ----- Department Assignments ----- //

    public function primaryDepartment()
    {
        return $this->departments()->wherePivot('is_primary', true);
    }

    /**
     * Equipment that this admin has waived
     */
    public function waivedEquipment()
    {
        return $this->hasMany(RequestedEquipment::class, 'waived_by', 'admin_id');
    }

    /**
     * Facilities that this admin has waived
     */
    public function waivedFacilities()
    {
        return $this->hasMany(RequestedFacility::class, 'waived_by', 'admin_id');
    }


    // ----- Requisition Management ----- //

    public function finalizedByAdmin()
    {
        return $this->hasMany(RequisitionForm::class, 'finalized_by', 'admin_id');
    }

    public function closedByAdmin()
    {
        return $this->hasMany(RequisitionForm::class, 'closed_by', 'admin_id');
    }

    public function adminApprovals()
    {
        return $this->hasMany(RequisitionApproval::class, 'approved_by', 'admin_id');
    }

    public function adminRejections()
    {
        return $this->hasMany(RequisitionApproval::class, 'rejected_by', 'admin_id');
    }

    // ----- Equipment Management ----- //
    public function createEquipment()
    {
        return $this->hasMany(Equipment::class, 'created_by', 'admin_id');
    }
    public function updateEquipment()
    {
        return $this->hasMany(Equipment::class, 'updated_by', 'admin_id');
    }
    public function deleteEquipment()
    {
        return $this->hasMany(Equipment::class, 'deleted_by', 'admin_id');
    }

    // ----- Equipment Items Management ----- //
    public function createEquipmentItems()
    {
        return $this->hasMany(EquipmentItem::class, 'created_by', 'admin_id');
    }
    public function updateEquipmentItems()
    {
        return $this->hasMany(Equipment::class, 'updated_by', 'admin_id');
    }
    public function deleteEquipmentItems()
    {
        return $this->hasMany(Equipment::class, 'deleted_by', 'admin_id');
    }

    // ----- Facility Management ----- //
    public function createFacility()
    {
        return $this->hasMany(Facility::class, 'created_by', 'admin_id');
    }
    public function updateFacility()
    {
        return $this->hasMany(Facility::class, 'updated_by', 'admin_id');
    }
    public function deleteFacility()
    {
        return $this->hasMany(Facility::class, 'deleted_by', 'admin_id');
    }

    public function systemLogs()
    {
        return $this->hasMany(SystemLog::class, 'admin_id', 'admin_id');
    }

    // relationship with RequisitionComment
    public function comments()
    {
        return $this->hasMany(RequisitionComment::class, 'admin_id', 'admin_id');
    }

    public function departments()
    {
        return $this->belongsToMany(
            Department::class,
            'admin_departments',
            'admin_id',
            'department_id'
        )->withPivot('role_id', 'is_primary')
            ->withTimestamps();
    }

    public function getDepartmentRole($departmentId)
    {
        $dept = $this->departments()->where('department_id', $departmentId)->first();
        if ($dept && $dept->pivot->role_id) {
            return DepartmentRole::find($dept->pivot->role_id)->role_name ?? null;
        }
        return null;
    }

    public function isDepartmentHead($departmentId)
    {
        $headRole = DepartmentRole::where('role_name', 'Department Head')->first();
        if (!$headRole)
            return false;

        $dept = $this->departments()->where('department_id', $departmentId)->first();
        return $dept && $dept->pivot->role_id === $headRole->role_id;
    }

    public function facilities()
    {
        return $this->belongsToMany(
            Facility::class,
            'admin_facilities',
            'admin_id',
            'facility_id',
            'admin_id',
            'facility_id'
        )
            ->select('facilities.facility_id', 'facilities.facility_name', 'facilities.department_id');
        // No withPivot() means pivot data won't be included
    }

}