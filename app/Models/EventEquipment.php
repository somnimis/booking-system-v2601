<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventEquipment extends Pivot
{
    use HasFactory;

    protected $table = 'event_equipment';
    protected $primaryKey = 'event_equipment_id';

    protected $fillable = [
        'event_id',
        'equipment_id',
        'quantity',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer'
    ];

    /**
     * Get the calendar event associated with this equipment assignment.
     */
    public function calendarEvent()
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id', 'event_id');
    }

    /**
     * Get the equipment associated with this event assignment.
     */
    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }
}