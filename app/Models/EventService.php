<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventService extends Pivot
{
    use HasFactory;

    protected $table = 'event_services';
    protected $primaryKey = 'event_service_id';

    protected $fillable = [
        'event_id',
        'service_id',
        'notes',
    ];

    /**
     * Get the calendar event associated with this service assignment.
     */
    public function calendarEvent()
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id', 'event_id');
    }

    /**
     * Get the service associated with this event assignment.
     */
    public function service()
    {
        return $this->belongsTo(ExtraService::class, 'service_id', 'service_id');
    }
}