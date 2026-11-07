<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventVenue extends Pivot
{
    use HasFactory;

    protected $table = 'event_venues';
    protected $primaryKey = 'event_venue_id';

    protected $fillable = [
        'event_id',
        'facility_id'
    ];

    /**
     * Get the calendar event associated with this venue assignment.
     */
    public function calendarEvent()
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id', 'event_id');
    }

    /**
     * Get the facility/venue associated with this event assignment.
     */
    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }
}