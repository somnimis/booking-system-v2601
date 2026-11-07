<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CheckAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckAvailabilityController extends Controller
{
    protected $availabilityChecker;

    public function __construct(CheckAvailabilityService $availabilityChecker)
    {
        $this->availabilityChecker = $availabilityChecker;
    }

    /**
     * Check availability (admin). Normalizes legacy input into the shared
     * CheckAvailabilityService::checkAll() contract so both public and admin
     * endpoints return identical response shapes.
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'all_day' => 'required|boolean',
            'facilities' => 'array',
            'facilities.*.facility_id' => 'exists:facilities,facility_id',
            'equipment' => 'array',
            'equipment.*.equipment_id' => 'exists:equipment,equipment_id',
            'equipment.*.quantity' => 'integer|min:1',
            'services' => 'array',
            'services.*.service_id' => 'exists:extra_services,service_id',
            'current_request_id' => 'nullable|exists:requisition_forms,request_id',
        ]);

        $items = [];

        foreach ($validated['facilities'] ?? [] as $f) {
            $items[] = ['type' => 'facility', 'facility_id' => $f['facility_id']];
        }
        foreach ($validated['equipment'] ?? [] as $e) {
            $items[] = [
                'type' => 'equipment',
                'equipment_id' => $e['equipment_id'],
                'quantity' => $e['quantity'] ?? 1,
            ];
        }
        foreach ($validated['services'] ?? [] as $s) {
            $items[] = ['type' => 'service', 'service_id' => $s['service_id']];
        }

        $result = $this->availabilityChecker->checkAll([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'all_day' => (bool) $validated['all_day'],
            'items' => $items,
            'current_request_id' => $validated['current_request_id'] ?? null,
        ]);

        $allConflicts = array_merge($result['hard_conflicts'], $result['soft_conflicts']);
        $result['conflict_html'] = $this->availabilityChecker->formatConflictsToHtml($allConflicts);

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }
}
