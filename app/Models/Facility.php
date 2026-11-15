<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Facility extends Model
{
    use HasFactory;
    protected $table = 'facilities';
    protected $primaryKey = 'facility_id';
    protected $fillable = [
        'parent_facility_id',
        'facility_role',
        'facility_name',
        'description',
        'floor_level',
        'facility_code',
        'total_levels',
        'category_id',
        'subcategory_id',
        'location_note',
        'capacity',
        'location_type',
        'base_fee',
        'rate_type',
        'status_id',
        'created_by'
    ];

    // SCOPES //

    public function scopeContainers($query)
    {
        return $query->where('facility_role', 'Container');
    }

    public function scopeVenues($query)
    {
        return $query->where('facility_role', 'Venue');
    }

    public function scopeRooms($query)
    {
        return $query->where('facility_role', 'Room');
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->whereHas('departments', function ($q) use ($departmentId) {
            $q->where('departments.department_id', $departmentId);
        });
    }
    public function scopeBySubcategory($query, $subcategoryId)
    {
        return $query->where('subcategory_id', $subcategoryId);
    }


    // Relationships
    public function events()
    {
        return $this->belongsToMany(CalendarEvent::class, 'event_venues', 'facility_id', 'event_id')
            ->withTimestamps()
            ->using(EventVenue::class);
    }

    public function category()
    {
        return $this->belongsTo(LookupTables\FacilityCategory::class, 'category_id', 'category_id');
    }
    public function subcategory()
    {
        return $this->belongsTo(LookupTables\FacilitySubcategory::class, 'subcategory_id', 'subcategory_id');
    }
    public function status()
    {
        return $this->belongsTo(LookupTables\AvailabilityStatus::class, 'status_id', 'status_id');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_facilities', 'facility_id', 'department_id')
            ->withTimestamps();
    }
    public function images()
    {
        return $this->hasMany(FacilityImage::class, 'facility_id', 'facility_id');
    }
    public function createdByAdmin()
    {
        return $this->belongsTo(Admin::class, 'created_by', 'admin_id');
    }
    public function updatedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'updated_by', 'admin_id');
    }
    public function deletedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'deleted_by', 'admin_id');
    }

    // Relationships for building and room details
    public function parentFacility()
    {
        return $this->belongsTo(Facility::class, 'parent_facility_id', 'facility_id');
    }
    public function childFacilities()
    {
        return $this->hasMany(Facility::class, 'parent_facility_id', 'facility_id');
    }
    public function systemLogs()
    {
        return $this->hasMany(\App\Models\SystemLog::class, 'facility_id');
    }

}
