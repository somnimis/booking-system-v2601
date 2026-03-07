<?php

namespace App\Services;

use App\Services\FeeCalculatorService;
use App\Services\ScheduleFormatterService;

class CalendarEventService
{
    protected $feeCalculator;
    protected $scheduleFormatter;

    public function __construct(FeeCalculatorService $feeCalculator, ScheduleFormatterService $scheduleFormatter) 
    {
        $this->feeCalculator = $feeCalculator;
        $this->scheduleFormatter = $scheduleFormatter;
    }

    /**
     * Transform a regular calendar event for FullCalendar (Table: calendar_events)
     */
    public function transformRegularEvent($event)
    {
        $fcSchedule = $this->scheduleFormatter->forFullCalendar($event);
        $displaySchedule = $this->scheduleFormatter->forApi($event);
        
        return [
            'id' => $event->id,
            'title' => $event->event_name,
            'start' => $fcSchedule['start'],
            'end' => $fcSchedule['end'],
            'allDay' => $fcSchedule['allDay'],
            'description' => $event->description,
            'extendedProps' => [
                'description' => $event->description,
                'all_day' => $event->all_day,
                'schedule' => $displaySchedule, // Now using the formatted schedule from ScheduleFormatter
            ]
        ];
    }

    /**
     * Transform requisition forms into calendar event format for FullCalendar
     */
    public function transformCalendarEvent($requisition, $isAdmin = false)
    {
        // Get formatted schedules from the ScheduleFormatter
        $fcSchedule = $this->scheduleFormatter->forFullCalendar($requisition);
        $displaySchedule = $this->scheduleFormatter->forApi($requisition);
        $baseSchedule = $this->scheduleFormatter->getBaseSchedule($requisition);

        // Event title
        $title = $requisition->calendar_title ?: "Booking #{$requisition->request_id}";

        // Status color
        $statusColor = $requisition->formStatus->color_code;

        // Get facility names with category and subcategory information
        $facilities = $requisition->requestedFacilities->map(function ($requestedFacility) {
            $facility = $requestedFacility->facility;

            $categoryId = null;
            $categoryName = null;
            $subcategoryId = null;
            $subcategoryName = null;

            if ($facility) {
                if (isset($facility->category)) {
                    $categoryId = $facility->category->category_id ?? null;
                    $categoryName = $facility->category->category_name ?? null;
                }

                if (isset($facility->subcategory)) {
                    $subcategoryId = $facility->subcategory->subcategory_id ?? null;
                    $subcategoryName = $facility->subcategory->subcategory_name ?? null;
                }

                $subcategoryId = $facility->facility_id;
            }

            return [
                'facility_id' => $requestedFacility->facility_id,
                'name' => $facility->facility_name ?? 'Unknown Facility',
                'fee' => $facility->external_fee ?? 0,
                'rate_type' => $facility->rate_type ?? 'Per Event',
                'is_waived' => $requestedFacility->is_waived ?? false,
                'category_id' => $categoryId,
                'category_name' => $categoryName,
                'subcategory_id' => $subcategoryId,
                'subcategory_name' => $subcategoryName,
            ];
        })->toArray();

        // Get equipment with quantities
        $equipment = $requisition->requestedEquipment->map(function ($requestedEquipment) {
            return [
                'equipment_id' => $requestedEquipment->equipment_id,
                'name' => $requestedEquipment->equipment->equipment_name ?? 'Unknown Equipment',
                'quantity' => $requestedEquipment->quantity,
                'fee' => $requestedEquipment->equipment->external_fee ?? 0,
                'rate_type' => $requestedEquipment->equipment->rate_type ?? 'Per Event',
                'is_waived' => $requestedEquipment->is_waived ?? false
            ];
        })->toArray();

        // Calculate fees if admin
        $fees = null;
        if ($isAdmin) {
            $feeSummary = $this->feeCalculator->getFeeSummary($requisition);

            $fees = [
                'tentative_fee' => $feeSummary['base_fee'] + ($requisition->is_late ? $requisition->late_penalty_fee : 0),
                'approved_fee' => $feeSummary['approved_fee'],
                'late_penalty_fee' => $requisition->late_penalty_fee ?? 0,
                'is_late' => $requisition->is_late ?? false,
                'breakdown' => [
                    'base_fees' => $feeSummary['base_fee'],
                    'additional_fees' => $feeSummary['additional_fees'],
                    'discounts' => $feeSummary['discounts'],
                    'late_penalty' => $feeSummary['late_penalty'],
                    'facilities' => $feeSummary['breakdown']['facilities'],
                    'equipment' => $feeSummary['breakdown']['equipment'],
                ]
            ];
        }

        // Extract all category and subcategory IDs for easy filtering
        $categoryIds = [];
        $subcategoryIds = [];
        foreach ($facilities as $facility) {
            if ($facility['category_id']) {
                $categoryIds[] = $facility['category_id'];
            }
            if ($facility['subcategory_id']) {
                $subcategoryIds[] = $facility['subcategory_id'];
            }
        }

        $categoryIds = array_unique($categoryIds);
        $subcategoryIds = array_unique($subcategoryIds);

        // Build event data using the formatted schedules from ScheduleFormatter
        $eventData = [
            'id' => $requisition->request_id,
            'request_id' => $requisition->request_id,
            'title' => $title,
            'start' => $fcSchedule['start'],
            'end' => $fcSchedule['end'],
            'allDay' => $fcSchedule['allDay'],
            'color' => $statusColor,
            'backgroundColor' => $statusColor,
            'borderColor' => $this->darkenColor($statusColor),
            'textColor' => '#ffffff',
            'extendedProps' => [
                'status' => $requisition->formStatus->status_name,
                'status_id' => $requisition->formStatus->status_id,
                'requester' => $requisition->first_name . ' ' . $requisition->last_name,
                'purpose' => $requisition->purpose->purpose_name ?? 'N/A',
                'num_participants' => $requisition->num_participants,
                'facilities' => $facilities,
                'equipment' => $equipment,
                'calendar_title' => $title,
                'calendar_description' => $requisition->calendar_description,
                'all_day' => $requisition->all_day,
                'category_ids' => $categoryIds,
                'subcategory_ids' => $subcategoryIds,
                'schedule' => $displaySchedule, // Using the formatted API schedule
                'duration' => [
                    'hours' => $baseSchedule['duration_hours'],
                    'text' => $baseSchedule['duration_text'],
                ],
                'is_admin_view' => $isAdmin
            ]
        ];

        // Add admin-only data
        if ($isAdmin) {
            $eventData['extendedProps']['user_details'] = [
                'user_type' => $requisition->user_type,
                'first_name' => $requisition->first_name,
                'last_name' => $requisition->last_name,
                'email' => $requisition->email,
                'school_id' => $requisition->school_id,
                'organization_name' => $requisition->organization_name,
                'contact_number' => $requisition->contact_number
            ];

            $eventData['extendedProps']['fees'] = $fees;
            $eventData['extendedProps']['additional_requests'] = $requisition->additional_requests;
            $eventData['extendedProps']['access_code'] = $requisition->access_code;
        }

        return $eventData;
    }

    /**
     * Helper function to darken a color for borders
     * Accepts both hex (#RRGGBB) and rgb(r,g,b) formats
     */
    private function darkenColor($color)
    {
        // Handle RGB format (e.g., "rgb(127, 0, 196)")
        if (preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/i', $color, $matches)) {
            $r = (int) $matches[1];
            $g = (int) $matches[2];
            $b = (int) $matches[3];
        }
        // Handle hex format
        else {
            // Remove # if present
            $hex = str_replace('#', '', $color);

            // Ensure it's a 6-character hex
            if (strlen($hex) == 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }

            // Convert to RGB
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        // Darken by 20%
        $r = max(0, min(255, (int) ($r * 0.8)));
        $g = max(0, min(255, (int) ($g * 0.8)));
        $b = max(0, min(255, (int) ($b * 0.8)));

        // Convert back to hex
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
}