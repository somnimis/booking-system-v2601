<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalendarEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [];
        $year = 2026;
        $month = 2; // February
        
        // Get the number of days in February 2026
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        // Event types to cycle through
        $eventTypes = ['hall_booking', 'school_event', 'holiday'];
        
        // Sample event names for each type
        $eventNames = [
            'hall_booking' => [
                'University Foundation Day Preparation',
                'Department Meeting',
                'Student Orientation',
                'Alumni Homecoming',
                'Conference: Digital Innovation',
                'Seminar on Leadership',
                'Workshop: Research Writing',
                'Cultural Night Rehearsal',
                'Board of Directors Meeting',
                'Parent-Teacher Conference',
                'Guest Lecture Series',
                'Job Fair 2026',
                'Science Fair Setup',
                'Musical Concert',
                'Theater Play Performance'
            ],
            'school_event' => [
                'General Assembly',
                'Sports Festival',
                'Academic Contest',
                'Recognition Day Practice',
                'College Week Activity',
                'Student Council Meeting',
                'Organization Fair',
                'Clean-up Drive',
                'Blood Donation Drive',
                'Career Guidance Seminar',
                'Scholarship Orientation',
                'Research Colloquium',
                'Intramurals 2026',
                'Cultural Showcase',
                'Open House'
            ],
            'holiday' => [
                'Independence Day',
                'National Heroes Day',
                'Labor Day',
                'Special Non-working Holiday',
                'University Foundation Day',
                'Religious Holiday',
                'Local Holiday',
                'Day of Valor',
                'Bonifacio Day',
                'Rizal Day',
                'Christmas Break',
                'New Year\'s Eve',
                'All Saints\' Day',
                'Maundy Thursday',
                'Good Friday'
            ]
        ];
        
        // Sample descriptions
        $descriptions = [
            'Annual celebration of university foundation',
            'Regular meeting for department heads',
            'Orientation for new students',
            'Gathering of alumni from different batches',
            'Conference featuring industry experts',
            'Seminar focused on leadership skills',
            'Hands-on workshop for research methods',
            'Rehearsal for upcoming cultural night',
            'Strategic planning meeting',
            'Meeting with parents and teachers',
            'Lecture from visiting professor',
            'Career fair with company booths',
            'Setup for annual science fair',
            'Evening musical performance',
            'Theater production by students'
        ];
        
        $eventId = 1;
        
        // Loop through each day of February
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = sprintf('%d-%02d-%02d', $year, $month, $day);
            
            // Create 3 events for each day
            for ($eventNum = 1; $eventNum <= 3; $eventNum++) {
                // Alternate event types
                $eventType = $eventTypes[($eventNum - 1) % 3];
                
                // Randomly select event name based on type
                $nameIndex = array_rand($eventNames[$eventType]);
                $eventName = $eventNames[$eventType][$nameIndex];
                
                // Add day-specific variation to event names for more variety
                if ($eventType != 'holiday') {
                    $eventName .= ' (Day ' . $day . ')';
                }
                
                // Generate random times (all between 8 AM and 8 PM)
                $startHour = rand(8, 18);
                $startMinute = rand(0, 3) * 15; // 0, 15, 30, or 45 minutes
                $duration = rand(1, 4); // 1-4 hours duration
                
                $endHour = $startHour + $duration;
                if ($endHour > 20) $endHour = 20; // Cap at 8 PM
                
                $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);
                $endTime = sprintf('%02d:%02d:00', $endHour, $startMinute);
                
                // Randomly decide if all_day (10% chance)
                $allDay = (rand(1, 100) <= 10);
                
                // If all_day, set times to null or default values
                if ($allDay) {
                    $startTime = '00:00:00';
                    $endTime = '23:59:59';
                }
                
                $events[] = [
                    'event_id' => $eventId++,
                    'event_name' => $eventName . ' ' . $currentDate,
                    'event_type' => $eventType,
                    'description' => $descriptions[array_rand($descriptions)] . ' for ' . $currentDate,
                    'start_date' => $currentDate,
                    'start_time' => $startTime,
                    'end_date' => $currentDate, // Same day events for simplicity
                    'end_time' => $endTime,
                    'all_day' => $allDay,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        // Insert all events
        DB::table('calendar_events')->insert($events);
        
        // Add some multi-day events that span across February
        $multiDayEvents = [
            [
                'event_name' => 'University Week Celebration',
                'event_type' => 'school_event',
                'description' => 'Week-long celebration of university founding',
                'start_date' => '2026-02-09',
                'end_date' => '2026-02-15',
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'all_day' => true,
            ],
            [
                'event_name' => 'National Arts Month Festival',
                'event_type' => 'school_event',
                'description' => 'Celebration of National Arts Month with various activities',
                'start_date' => '2026-02-16',
                'end_date' => '2026-02-28',
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'all_day' => true,
            ],
            [
                'event_name' => 'Chinese New Year Break',
                'event_type' => 'holiday',
                'description' => 'University holiday for Chinese New Year celebration',
                'start_date' => '2026-02-17',
                'end_date' => '2026-02-17',
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'all_day' => true,
            ],
            [
                'event_name' => 'People Power Anniversary',
                'event_type' => 'holiday',
                'description' => 'Special non-working holiday',
                'start_date' => '2026-02-25',
                'end_date' => '2026-02-25',
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'all_day' => true,
            ],
            [
                'event_name' => 'International Conference on Education',
                'event_type' => 'hall_booking',
                'description' => '3-day international conference with multiple sessions',
                'start_date' => '2026-02-19',
                'end_date' => '2026-02-21',
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'all_day' => false,
            ],
        ];
        
        foreach ($multiDayEvents as $event) {
            $events[] = array_merge($event, [
                'event_id' => $eventId++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Insert multi-day events
        DB::table('calendar_events')->insert(array_slice($events, -count($multiDayEvents)));
        
        $this->command->info('Created ' . count($events) . ' calendar events for February 2026');
    }
}