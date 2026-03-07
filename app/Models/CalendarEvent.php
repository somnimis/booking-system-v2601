<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $primaryKey = 'event_id';
    
    protected $fillable = [
        'event_name',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'all_day'
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'start_time' => 'string',  // CRITICAL: Cast to string to prevent ISO conversion
        'end_time' => 'string',    // CRITICAL: Cast to string to prevent ISO conversion
        'all_day' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    // Accessor to ensure time is always in H:i:s format
    public function getStartTimeAttribute($value)
    {
        if ($value) {
            // If it's a Carbon instance, format it
            if ($value instanceof \Carbon\Carbon) {
                return $value->format('H:i:s');
            }
            // If it's a string, ensure it's just time
            return substr($value, 0, 8);
        }
        return $value;
    }

    public function getEndTimeAttribute($value)
    {
        if ($value) {
            if ($value instanceof \Carbon\Carbon) {
                return $value->format('H:i:s');
            }
            return substr($value, 0, 8);
        }
        return $value;
    }
}