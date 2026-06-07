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
     * Get all form initialization data EXCEPT facilities and equipment
     * These will be lazy-loaded separately
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
            
            // Only load lightweight data initially (no facilities or equipment)
            $data = [
                'purposes' => RequisitionPurpose::all(['purpose_id', 'purpose_name']),
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
     * Get facilities with pagination and filtering (LAZY LOADING)
     */
    public function getFacilities(Request $request)
    {
        try {
            $admin = $request->user();
            
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search', '');
            $rateType = $request->input('rate_type', '');
            $status = $request->input('status', '');
            
            $query = Facility::with('status')
                ->select(['facility_id', 'facility_name', 'base_fee', 'rate_type', 'capacity', 'status_id', 'location_note']);
            
            // Apply search filter
            if (!empty($search)) {
                $query->where('facility_name', 'like', "%{$search}%");
            }
            
            // Apply rate type filter
            if (!empty($rateType)) {
                $query->where('rate_type', $rateType);
            }
            
            // Apply status filter
            if (!empty($status)) {
                if ($status === 'available') {
                    $query->whereHas('status', function ($q) {
                        $q->where('status_name', 'Available');
                    });
                } elseif ($status === 'unavailable') {
                    $query->whereHas('status', function ($q) {
                        $q->where('status_name', '!=', 'Available');
                    });
                }
            }
            
            $facilities = $query->orderBy('facility_name')
                ->paginate($perPage, ['*'], 'page', $page);
            
            return response()->json([
                'success' => true,
                'data' => $facilities->items(),
                'pagination' => [
                    'current_page' => $facilities->currentPage(),
                    'last_page' => $facilities->lastPage(),
                    'per_page' => $facilities->perPage(),
                    'total' => $facilities->total(),
                    'from' => $facilities->firstItem(),
                    'to' => $facilities->lastItem()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get facilities: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load facilities'
            ], 500);
        }
    }

    /**
     * Get equipment with pagination and filtering (LAZY LOADING)
     */
    public function getEquipment(Request $request)
    {
        try {
            $admin = $request->user();
            
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search', '');
            $rateType = $request->input('rate_type', '');
            $status = $request->input('status', '');
            
            $query = Equipment::with('status')
                ->select(['equipment_id', 'equipment_name', 'base_fee', 'rate_type', 'status_id', 'description']);
            
            // Apply search filter
            if (!empty($search)) {
                $query->where('equipment_name', 'like', "%{$search}%");
            }
            
            // Apply rate type filter
            if (!empty($rateType)) {
                $query->where('rate_type', $rateType);
            }
            
            // Apply status filter
            if (!empty($status)) {
                if ($status === 'available') {
                    $query->whereHas('status', function ($q) {
                        $q->where('status_name', 'Available');
                    });
                } elseif ($status === 'unavailable') {
                    $query->whereHas('status', function ($q) {
                        $q->where('status_name', '!=', 'Available');
                    });
                }
            }
            
            $equipment = $query->orderBy('equipment_name')
                ->paginate($perPage, ['*'], 'page', $page);
            
            return response()->json([
                'success' => true,
                'data' => $equipment->items(),
                'pagination' => [
                    'current_page' => $equipment->currentPage(),
                    'last_page' => $equipment->lastPage(),
                    'per_page' => $equipment->perPage(),
                    'total' => $equipment->total(),
                    'from' => $equipment->firstItem(),
                    'to' => $equipment->lastItem()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get equipment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load equipment'
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