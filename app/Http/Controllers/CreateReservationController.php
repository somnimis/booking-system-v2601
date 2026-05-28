<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\ExtraService;
use App\Models\Equipment;
use App\Models\RequisitionPurpose;
use App\Models\FormStatus;
use App\Models\RequisitionForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class CreateReservationController extends Controller
{

/**
 * Get all form initialization data in a single request
 */
public function getFormInitData(Request $request)
{
    try {
        $admin = $request->user();
        
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        // Fetch all data in parallel using eager loading where possible
        $data = [
            'purposes' => RequisitionPurpose::all(['purpose_id', 'purpose_name']),
            'facilities' => Facility::with('status')
                ->select(['facility_id', 'facility_name', 'base_fee', 'rate_type', 'capacity', 'status_id', 'location_note'])
                ->get(),
            'equipment' => Equipment::with('status')
                ->select(['equipment_id', 'equipment_name', 'base_fee', 'rate_type', 'status_id', 'description'])
                ->get(),
            'services' => ExtraService::all(['service_id', 'service_name', 'service_fee']),
            'statuses' => FormStatus::whereNotIn('status_name', ['Returned', 'Late Return', 'Completed', 'Rejected', 'Cancelled'])
                ->select(['status_id', 'status_name', 'color_code'])
                ->get(),
            'access_code' => $this->generateUniqueAccessCode()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
        
    } catch (\Exception $e) {
        Log::error('Failed to get form init data: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to load form data'
        ], 500);
    }
}

/**
 * Generate a unique access code
 */
private function generateUniqueAccessCode()
{
    do {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 10; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
    } while (RequisitionForm::where('access_code', $code)->exists());
    
    return $code;
}

}
