<?php

// This controller manages the assignment of admins to facilities

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminFacility;
use App\Models\Admin;
use App\Models\FacilityImage;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminFacilityController extends Controller
{
    /**
     * Store a new admin-facility assignment
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'admin_id' => 'required|integer|exists:admins,admin_id',
            'facility_id' => 'required|integer|exists:facilities,facility_id',
        ], [
            'admin_id.required' => 'Admin ID is required',
            'admin_id.exists' => 'The selected admin does not exist',
            'facility_id.required' => 'Facility ID is required',
            'facility_id.exists' => 'The selected facility does not exist',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if the assignment already exists
            $existingAssignment = AdminFacility::where('admin_id', $request->admin_id)
                ->where('facility_id', $request->facility_id)
                ->first();

            if ($existingAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'This admin is already assigned to this facility'
                ], 409);
            }

            // Verify that admin exists
            $admin = Admin::find($request->admin_id);
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found'
                ], 404);
            }

            // Verify that facility exists
            $facility = Facility::find($request->facility_id);
            if (!$facility) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facility not found'
                ], 404);
            }

            // Create the admin-facility assignment
            $adminFacility = AdminFacility::create([
                'admin_id' => $request->admin_id,
                'facility_id' => $request->facility_id
            ]);

            // Optionally, you can log this action
            // \App\Models\SystemLog::create([
            //     'action' => 'admin_facility_assignment_created',
            //     'description' => "Admin {$admin->first_name} {$admin->last_name} assigned to facility {$facility->facility_name}",
            //     'admin_id' => auth()->id(),
            //     'facility_id' => $facility->facility_id,
            //     'related_admin_id' => $admin->admin_id
            // ]);

            DB::commit();

            // Load relationships for response
            $adminFacility->load(['admin', 'facility']);

            return response()->json([
                'success' => true,
                'message' => 'Admin successfully assigned to facility',
                'data' => [
                    'admin_facility' => $adminFacility,
                    'admin' => [
                        'admin_id' => $admin->admin_id,
                        'first_name' => $admin->first_name,
                        'last_name' => $admin->last_name,
                        'email' => $admin->email
                    ],
                    'facility' => [
                        'facility_id' => $facility->facility_id,
                        'facility_name' => $facility->facility_name,
                        'facility_code' => $facility->facility_code,
                        'floor_level' => $facility->floor_level
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign admin to facility',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store multiple admin-facility assignments (batch assignment)
     */
public function storeMultiple(Request $request): JsonResponse
{
    \Log::info('Batch assignment request:', $request->all());
    
    $validator = Validator::make($request->all(), [
        'admin_id' => 'required|integer|exists:admins,admin_id',
        'facility_ids' => 'required|array',
        'facility_ids.*' => 'integer|exists:facilities,facility_id'
    ]);

    if ($validator->fails()) {
        \Log::error('Validation failed:', $validator->errors()->toArray());
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        DB::beginTransaction();
        
        \Log::info('Processing batch assignment for admin:', ['admin_id' => $request->admin_id]);

        $admin = Admin::find($request->admin_id);
        if (!$admin) {
            \Log::error('Admin not found:', ['admin_id' => $request->admin_id]);
            return response()->json([
                'success' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        $createdAssignments = [];
        $existingAssignments = [];

        foreach ($request->facility_ids as $facilityId) {
            // Check if assignment already exists
            $exists = AdminFacility::where('admin_id', $request->admin_id)
                ->where('facility_id', $facilityId)
                ->exists();

            if (!$exists) {
                $facility = Facility::find($facilityId);
                if ($facility) {
                    \Log::info('Creating assignment:', [
                        'admin_id' => $request->admin_id,
                        'facility_id' => $facilityId
                    ]);
                    
                    $adminFacility = AdminFacility::create([
                        'admin_id' => $request->admin_id,
                        'facility_id' => $facilityId
                    ]);
                    $createdAssignments[] = $adminFacility;
                } else {
                    \Log::warning('Facility not found:', ['facility_id' => $facilityId]);
                }
            } else {
                \Log::info('Assignment already exists:', [
                    'admin_id' => $request->admin_id,
                    'facility_id' => $facilityId
                ]);
                $existingAssignments[] = $facilityId;
            }
        }

        DB::commit();

        $response = [
            'success' => true,
            'message' => 'Batch assignment completed',
            'data' => [
                'created_count' => count($createdAssignments),
                'existing_count' => count($existingAssignments),
                'created_assignments' => $createdAssignments,
                'existing_facility_ids' => $existingAssignments
            ]
        ];

        if (empty($createdAssignments) && !empty($existingAssignments)) {
            $response['message'] = 'All assignments already exist';
        }

        \Log::info('Batch assignment completed:', $response);
        return response()->json($response, 201);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Batch assignment error:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request' => $request->all()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to process batch assignment',
            'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}

    /**
     * Get all facilities assigned to an admin
     */
public function getAdminFacilities($adminId): JsonResponse
{
    $admin = Admin::find($adminId);
    if (!$admin) {
        return response()->json([
            'success' => false,
            'message' => 'Admin not found'
        ], 404);
    }

    // Get admin facilities with facility details
    $adminFacilities = AdminFacility::where('admin_id', $adminId)
        ->with(['facility' => function ($query) {
            $query->select('facility_id', 'facility_name', 'facility_code', 'floor_level', 'status_id')
                ->with(['status', 'department']);
        }])
        ->get();

    // Manually load primary image for each facility
    $facilitiesWithImages = $adminFacilities->map(function($adminFacility) {
        $facility = $adminFacility->facility;
        
        if ($facility) {
            // Get primary image for this facility
            $primaryImage = FacilityImage::where('facility_id', $facility->facility_id)
                ->orderBy('image_type', 'desc') // 'Primary' first
                ->orderBy('sort_order', 'asc')
                ->first();
            
            // Add images array to facility object
            $facility->images = $primaryImage ? collect([$primaryImage]) : collect();
        }
        
        return $adminFacility;
    });

    return response()->json([
        'success' => true,
        'data' => [
            'admin' => $admin,
            'facilities' => $facilitiesWithImages
        ]
    ]);
}

    /**
     * Get all admins assigned to a facility
     */
    public function getFacilityAdmins($facilityId): JsonResponse
    {
        $facility = Facility::find($facilityId);
        if (!$facility) {
            return response()->json([
                'success' => false,
                'message' => 'Facility not found'
            ], 404);
        }

        $admins = AdminFacility::where('facility_id', $facilityId)
            ->with(['admin' => function ($query) {
                $query->select('admin_id', 'first_name', 'last_name', 'email', 'role_id')
                    ->with('role');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'facility' => $facility,
                'admins' => $admins
            ]
        ]);
    }
    /**
 * Delete an admin-facility assignment
 */
public function destroy($id): JsonResponse
{
    try {
        $adminFacility = AdminFacility::find($id);
        
        if (!$adminFacility) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found'
            ], 404);
        }

        $adminFacility->delete();

        return response()->json([
            'success' => true,
            'message' => 'Facility assignment removed successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to remove assignment',
            'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}
}