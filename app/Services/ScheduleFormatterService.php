<?php

namespace App\Services;

use Carbon\Carbon;

class ScheduleFormatterService
{
    /**
     * Format schedule for different contexts
     */
    public function format($form, string $context = 'default'): array
    {
        return match ($context) {
            'fullcalendar' => $this->forFullCalendar($form),
            'display' => $this->forDisplay($form),
            'api' => $this->forApi($form),
            'email' => $this->forEmail($form),
            default => $this->getBaseSchedule($form),
        };
    }

    public function forDisplay($entity): array
    {
        $base = $this->getBaseSchedule($entity);

        return [
            'display' => $base['formatted_start_datetime'] . ' - ' . $base['formatted_end_datetime'],
            'start_date' => $entity->start_date,
            'end_date' => $entity->end_date,
            'all_day' => $entity->all_day,
            'is_multi_day' => $base['is_multi_day'],
        ];
    }

    /**
     * Get base schedule data (raw + formatted)
     */
    public function getBaseSchedule($entity): array
    {
        $startDate = $this->parseDate($entity->start_date);
        $endDate = $this->parseDate($entity->end_date);

        return [
            // Raw data
            'start_date' => $entity->start_date,
            'end_date' => $entity->end_date,
            'start_time' => $entity->start_time,
            'end_time' => $entity->end_time,
            'all_day' => $entity->all_day,

            // Formatted dates
            'formatted_start_date' => $startDate->format('F j, Y'),
            'formatted_end_date' => $endDate->format('F j, Y'),
            'formatted_start_time' => $entity->all_day ? 'All Day' : $this->formatTime($entity->start_time),
            'formatted_end_time' => $entity->all_day ? 'All Day' : $this->formatTime($entity->end_time),

            // Computed properties
            'is_multi_day' => $entity->start_date !== $entity->end_date,
            'duration_hours' => $this->calculateDurationHours($entity),
            'duration_text' => $this->getDurationText($entity),
        ];
    }

    /**
     * Format for FullCalendar (ISO strings with T)
     */
    public function forFullCalendar($entity): array
    {
        $startTime = $entity->all_day ? '' : $this->trimSeconds($entity->start_time);
        $endTime = $entity->all_day ? '' : $this->trimSeconds($entity->end_time);

        return [
            'start' => $entity->all_day
                ? $entity->start_date
                : $entity->start_date . 'T' . $startTime,
            'end' => $entity->all_day
                ? $entity->end_date . 'T23:59:59'
                : $entity->end_date . 'T' . $endTime,
            'allDay' => $entity->all_day,
        ];
    }

    /**
     * Format for API responses (structured schedule object)
     */
    public function forApi($entity): array
    {
        $base = $this->getBaseSchedule($entity);

        return [
            'display' => $this->getDisplayString($entity),
            'start_date' => $base['start_date'],
            'end_date' => $base['end_date'],
            'start_time' => $base['start_time'],
            'end_time' => $base['end_time'],
            'all_day' => $base['all_day'],
            'is_multi_day' => $base['is_multi_day'],
            'formatted' => [
                'start' => $base['formatted_start_date'] . ($entity->all_day ? '' : ' ' . $base['formatted_start_time']),
                'end' => $base['formatted_end_date'] . ($entity->all_day ? '' : ' ' . $base['formatted_end_time']),
            ]
        ];
    }

    /**
     * Get human-readable display string
     */
    public function getDisplayString($entity): string
    {
        $base = $this->getBaseSchedule($entity);

        if ($entity->all_day) {
            return $base['is_multi_day']
                ? $base['formatted_start_date'] . ' (All Day) — ' . $base['formatted_end_date'] . ' (All Day)'
                : $base['formatted_start_date'] . ' (All Day)';
        }

        return $base['is_multi_day']
            ? $base['formatted_start_date'] . ' ' . $base['formatted_start_time'] . ' — ' . $base['formatted_end_date'] . ' ' . $base['formatted_end_time']
            : $base['formatted_start_date'] . ' ' . $base['formatted_start_time'] . ' — ' . $base['formatted_end_time'];
    }

    /**
     * Format for emails (more formal)
     */
    public function forEmail($entity): array
    {
        $base = $this->getBaseSchedule($entity);

        if ($entity->all_day) {
            return [
                'start' => $base['formatted_start_date'] . ' (All Day)',
                'end' => $base['is_multi_day'] ? $base['formatted_end_date'] . ' (All Day)' : null,
            ];
        }

        return [
            'start' => $base['formatted_start_date'] . ' at ' . $base['formatted_start_time'],
            'end' => $base['is_multi_day']
                ? $base['formatted_end_date'] . ' at ' . $base['formatted_end_time']
                : $base['formatted_end_time'],
        ];
    }

    /**
     * Calculate duration in hours
     */
    public function calculateDurationHours($entity): float
    {
        if ($entity->all_day) {
            $days = Carbon::parse($entity->start_date)->diffInDays(Carbon::parse($entity->end_date)) + 1;
            return $days * 8; // Assuming 8 hours per day
        }

        $start = Carbon::parse($entity->start_date . ' ' . $entity->start_time);
        $end = Carbon::parse($entity->end_date . ' ' . $entity->end_time);
        return max(1, $start->diffInHours($end));
    }

    /**
     * Get human-readable duration text
     */
    public function getDurationText($entity): string
    {
        $hours = $this->calculateDurationHours($entity);

        if ($entity->all_day) {
            $days = $hours / 8;
            return $days == 1 ? '1 day (All Day)' : $days . ' days (All Day)';
        }

        return $hours == 1 ? '1 hour' : $hours . ' hours';
    }

    /**
     * Calculate and format duration in a human-readable format
     * Returns: "2hrs", "1h 30m", "45mins", "1 day", "3 days", etc.
     */
    public function getFormattedDuration($entity): string
    {
        if ($entity->all_day) {
            // For all-day events, calculate in days
            $start = Carbon::parse($entity->start_date);
            $end = Carbon::parse($entity->end_date);

            // Add 1 to include both start and end dates
            $days = $start->diffInDays($end) + 1;

            return $days == 1 ? '1 day' : $days . ' days';
        }

        // For timed events, calculate hours and minutes
        $start = Carbon::parse($entity->start_date . ' ' . $entity->start_time);
        $end = Carbon::parse($entity->end_date . ' ' . $entity->end_time);

        // Handle multi-day timed events
        if ($end < $start) {
            // If end is before start, add a day (overnight event)
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        // Format the duration text
        if ($hours > 0 && $minutes > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } elseif ($hours > 0) {
            return $hours . 'hrs';
        } else {
            return $minutes . 'mins';
        }
    }

    /**
     * Get duration in hours (decimal) for calculations
     */
    public function getDurationHours($entity): float
    {
        if ($entity->all_day) {
            $start = Carbon::parse($entity->start_date);
            $end = Carbon::parse($entity->end_date);
            $days = $start->diffInDays($end) + 1;
            return $days * 24; // 24 hours per day for all-day events
        }

        $start = Carbon::parse($entity->start_date . ' ' . $entity->start_time);
        $end = Carbon::parse($entity->end_date . ' ' . $entity->end_time);

        if ($end < $start) {
            $end->addDay();
        }

        return round($start->diffInMinutes($end) / 60, 1);
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes($entity): int
    {
        if ($entity->all_day) {
            $start = Carbon::parse($entity->start_date);
            $end = Carbon::parse($entity->end_date);
            $days = $start->diffInDays($end) + 1;
            return $days * 24 * 60;
        }

        $start = Carbon::parse($entity->start_date . ' ' . $entity->start_time);
        $end = Carbon::parse($entity->end_date . ' ' . $entity->end_time);

        if ($end < $start) {
            $end->addDay();
        }

        return $start->diffInMinutes($end);
    }

    // ------------------------------------------------------------------------
    // Helper methods
    // ------------------------------------------------------------------------

    private function parseDate($date): Carbon
    {
        return $date instanceof Carbon ? $date : Carbon::parse($date);
    }

    private function formatTime($time): string
    {
        return Carbon::parse($time)->format('g:i A');
    }

    private function trimSeconds($time): string
    {
        return substr($time, 0, 5);
    }
}