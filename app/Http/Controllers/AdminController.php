<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Admin;
use App\Models\LookupTables\AdminRole;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    // Add this new method
    public function adminRoles(Request $request)
    {
        try {
            // Cache the roles for 1 hour (3600 seconds)
            // The cache will be automatically cleared when roles are updated
            $roles = Cache::remember('admin_roles', 3600, function () {
                return AdminRole::select('role_id', 'role_title')
                    ->orderBy('role_title') // Added ordering for consistency
                    ->get();
            });

            // Log the fetched roles (only once per cache interval)
            \Log::debug('Fetched admin roles from ' . (Cache::has('admin_roles') ? 'cache' : 'database'), [
                'count' => $roles->count(),
                'roles' => $roles->toArray()
            ]);

            return response()->json([
                'success' => true,
                'data' => $roles,
                'from_cache' => Cache::has('admin_roles')
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching admin roles: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admin roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getExtraServices(Request $request)
    {
        try {
            // Cache the results for 1 hour to reduce database queries
            // Clear cache automatically when services are updated
            $services = Cache::remember('extra_services', 3600, function () {
                return ExtraService::select('service_id', 'service_name')
                    ->orderBy('service_name')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => $services,
                'count' => $services->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch extra services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get all admin information for Admin Listing page with Department and Service Relationships.
public function getAllAdmins(Request $request)
{
    try {
        $currentAdminId = auth()->id();

        $admins = Cache::remember('all_admins_except_' . $currentAdminId, 3600, function () use ($currentAdminId) {
            return Admin::with([
                'role:role_id,role_title',
                'departments:department_id,department_name,department_code',
                'services:service_id,service_name'
            ])
                ->where('admin_id', '!=', $currentAdminId)
                ->get()
                ->map(function ($admin) {
                    $fullName = $admin->first_name;
                    if (!empty($admin->middle_name)) {
                        $fullName .= ' ' . $admin->middle_name;
                    }
                    $fullName .= ' ' . $admin->last_name;

                    return [
                        'admin_id' => $admin->admin_id,
                        'first_name' => $admin->first_name,
                        'last_name' => $admin->last_name,
                        'middle_name' => $admin->middle_name,
                        'full_name' => $fullName,
                        'title' => $admin->title,
                        'email' => $admin->email,
                        'school_id' => $admin->school_id,
                        'contact_number' => $admin->contact_number,
                        'role' => $admin->role,
                        'role_id' => $admin->role_id,
                        'departments' => $admin->departments,
                        'services' => $admin->services
                    ];
                });
        });

        return response()->json([
            'success' => true,
            'data' => $admins
        ]);

    } catch (\Exception $e) {
        \Log::error('Error fetching all admins: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch admin listings'
        ], 500);
    }
}

public function getAdminForEdit(Admin $admin)
{
    try {
        $admin->load([
            'departments:department_id,department_name,department_code',
            'services:service_id,service_name'
        ]);
        
        // Build a custom response that explicitly includes all needed data
        $response = [
            'admin_id' => $admin->admin_id,
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'middle_name' => $admin->middle_name,
            'title' => $admin->title,
            'email' => $admin->email,
            'contact_number' => $admin->contact_number,
            'school_id' => $admin->school_id,
            'role_id' => $admin->role_id,
            'role' => $admin->role,
            'signature_url' => $admin->signature_url,
            'signature_public_id' => $admin->signature_public_id,
            'departments' => $admin->departments,
            'services' => $admin->services,
            // Explicitly include just the IDs for easy frontend use
            'department_ids' => $admin->departments->pluck('department_id'),
            'service_ids' => $admin->services->pluck('service_id')
        ];
        
        return response()->json($response);
        
    } catch (\Exception $e) {
        \Log::error('Error fetching admin for edit: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to fetch admin details',
            'error' => $e->getMessage()
        ], 500);
    }
}

// Get information of a single admin
public function getAdminInfo(Admin $admin)
{
    $admin->load([
        'departments:department_id,department_name,department_code',
        'services:service_id,service_name'
    ]);
    
    // Convert to array but we need to ensure service_ids are visible
    $adminArray = $admin->toArray();
    
    // Manually add the service_ids array
    $adminArray['service_ids'] = $admin->services->pluck('service_id');
    
    return response()->json($adminArray);
}

    // Add a new method to create admin records
public function store(Request $request)
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:50',
        'last_name' => 'required|string|max:50',
        'middle_name' => 'nullable|string|max:50',
        'title' => 'nullable|string|max:100',
        'email' => 'required|email|unique:admins,email|max:150',
        'contact_number' => 'nullable|string|max:20',
        'role_id' => 'required|exists:admin_roles,role_id',
        'school_id' => 'nullable|string|max:20',
        'password' => 'required|string|min:8|max:50',
        'department_ids' => 'nullable|array',
        'department_ids.*' => 'exists:departments,department_id',
        'service_ids' => 'nullable|array',
        'service_ids.*' => 'exists:extra_services,service_id',
        'photo_url' => 'nullable|string',
        'photo_public_id' => 'nullable|string',
        'wallpaper_url' => 'nullable|string',
        'wallpaper_public_id' => 'nullable|string',
        'signature_url' => 'nullable|string',
        'signature_public_id' => 'nullable|string',
    ]);

    // Set default photo if not provided
    $validated['photo_url'] = $validated['photo_url'] ?? 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1751033911/ksdmh4mmpxdtjogdgjmm.png';
    $validated['photo_public_id'] = $validated['photo_public_id'] ?? 'ksdmh4mmpxdtjogdgjmm';

    // Hash the password
    $validated['hashed_password'] = bcrypt($validated['password']);
    unset($validated['password']);

    // Extract relationship arrays before creating admin
    $departmentIds = $validated['department_ids'] ?? [];
    $serviceIds = $validated['service_ids'] ?? [];
    unset($validated['department_ids'], $validated['service_ids']);

    \DB::beginTransaction();
    try {
        // Create new admin
        $admin = Admin::create($validated);

        // Handle department assignments - NO AUTO-ASSIGNMENT FOR ROLE 1
        if (!empty($departmentIds)) {
            $admin->departments()->sync($departmentIds);
            \Log::info("Assigned departments to new admin: {$admin->admin_id}", ['depts' => $departmentIds]);
        }

        // Handle service assignments
        if (!empty($serviceIds)) {
            $admin->services()->sync($serviceIds);
            \Log::info("Assigned services to new admin: {$admin->admin_id}", ['services' => $serviceIds]);
        }

        \DB::commit();

        // CLEAR ALL RELATED CACHES
        $this->clearAdminCaches($admin->admin_id);

        return response()->json([
            'message' => 'Admin created successfully',
            'admin' => $admin->load(['departments', 'services'])
        ], 201);

    } catch (\Exception $e) {
        \DB::rollBack();
        \Log::error('Error creating admin: ' . $e->getMessage());

        return response()->json([
            'message' => 'Failed to create admin',
            'error' => $e->getMessage()
        ], 500);
    }
}
    // Delete an admin
public function deleteAdmin(Admin $admin)
{
    try {
        // Get the admin ID before deletion
        $adminId = $admin->admin_id;
        
        // Detach all relationships
        $admin->departments()->detach();
        $admin->services()->detach();
        $admin->facilities()->detach();
        $admin->delete();

        // CLEAR ALL RELATED CACHES
        $this->clearAdminCaches($adminId);

        return response()->json([
            'message' => 'Admin deleted successfully'
        ]);
    } catch (\Exception $e) {
        \Log::error('Error deleting admin: ' . $e->getMessage());

        return response()->json([
            'message' => 'Failed to delete admin',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function update(Request $request, Admin $admin)
{
    // TEMPORARY DEBUG - REMOVE AFTER TESTING
    \Log::info('Update request data:', $request->all());
    
    $validated = $request->validate([
        'first_name' => 'required|string|max:50',
        'last_name' => 'required|string|max:50',
        'middle_name' => 'nullable|string|max:50',
        'title' => 'nullable|string|max:100',
        'email' => 'required|email|max:150|unique:admins,email,' . $admin->admin_id . ',admin_id',
        'contact_number' => 'nullable|string|max:20',
        'role_id' => 'required|exists:admin_roles,role_id',
        'school_id' => 'nullable|string|max:20',
        'password' => 'nullable|string|min:8|max:50',
        'department_ids' => 'nullable|array',
        'department_ids.*' => 'exists:departments,department_id',
        'service_ids' => 'nullable|array',
        'service_ids.*' => 'exists:extra_services,service_id',
        'signature_url' => 'nullable|string',
        'signature_public_id' => 'nullable|string',
    ]);

    // TEMPORARY DEBUG - REMOVE AFTER TESTING
    \Log::info('Validated data:', $validated);
    \Log::info('Service IDs from validated:', ['service_ids' => $validated['service_ids'] ?? 'not set']);

    // Update password only if provided
    if (!empty($validated['password'])) {
        $validated['hashed_password'] = bcrypt($validated['password']);
    }
    unset($validated['password']);

    // Extract relationship arrays
    $departmentIds = $validated['department_ids'] ?? [];
    $serviceIds = $validated['service_ids'] ?? [];
    unset($validated['department_ids'], $validated['service_ids']);

    \DB::beginTransaction();
    try {
        // Update admin basic info
        $admin->update($validated);

        // Handle department assignments - NO AUTO-ASSIGNMENT FOR ROLE 1
        // Just sync whatever was sent from the frontend
        $admin->departments()->sync($departmentIds);
        \Log::info('Synced departments for admin', ['depts' => $departmentIds]);

        // Handle service assignments
        if (empty($serviceIds)) {
            $admin->services()->detach();
            \Log::info('Detached all services for admin');
        } else {
            $admin->services()->sync($serviceIds);
            \Log::info('Synced services for admin', ['services' => $serviceIds]);
        }

        \DB::commit();

        // CLEAR ALL RELATED CACHES
        $this->clearAdminCaches($admin->admin_id);

        // Load relationships and return
        $admin->load(['departments', 'services']);

        return response()->json([
            'message' => 'Admin updated successfully',
            'admin' => $admin
        ]);

    } catch (\Exception $e) {
        \DB::rollBack();
        \Log::error('Error updating admin: ' . $e->getMessage(), [
            'admin_id' => $admin->admin_id,
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'message' => 'Failed to update admin',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Clear all admin-related caches
 */
private function clearAdminCaches($adminId = null)
{
    try {
        // Clear the roles cache
        Cache::forget('admin_roles');
        
        // Clear the services cache
        Cache::forget('extra_services');
        
        // Clear the departments cache (if you have one)
        Cache::forget('departments');
        
        // Clear the all admins cache for the current user
        $currentAdminId = auth()->id();
        if ($currentAdminId) {
            Cache::forget('all_admins_except_' . $currentAdminId);
        }
        
        // If we have a specific admin ID, also clear their individual cache
        if ($adminId) {
            Cache::forget('admin_' . $adminId);
        }
        
        \Log::info('Admin caches cleared successfully');
    } catch (\Exception $e) {
        \Log::error('Error clearing admin caches: ' . $e->getMessage());
    }
}
    // Assign department to admin
    public function assignDepartment(Request $request, Admin $admin)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,department_id',
            'is_primary' => 'sometimes|boolean'
        ]);

        $departmentId = $validated['department_id'];
        $isPrimary = $validated['is_primary'] ?? false;

        // Explicitly load department (forces use of Department model)
        $department = Department::findOrFail($departmentId);

        // Check if admin already has a different primary department
        if ($isPrimary) {
            $alreadyPrimary = $admin->departments()
                ->wherePivot('is_primary', true)
                ->where('department_id', '!=', $departmentId)
                ->exists();

            if ($alreadyPrimary) {
                return response()->json([
                    'message' => 'This admin already has a primary department.'
                ], 409);
            }
        }

        // Assign department to admin
        $admin->departments()->syncWithoutDetaching([
            $department->department_id => ['is_primary' => $isPrimary]
        ]);

        return response()->json(['message' => 'Department assigned successfully']);
    }

    public function updatePhoto(Request $request)
    {
        try {
            $request->validate([
                'photo' => 'required_without_all:wallpaper,signature|image|max:2048',
                'wallpaper' => 'required_without_all:photo,signature|image|max:5120',
                'signature' => 'required_without_all:photo,wallpaper|image|max:2048', // NEW
                'type' => 'required|in:photo,wallpaper,signature' // UPDATED
            ]);

            $admin = $request->user();
            $type = $request->type;
            $file = $request->file($type); // Either 'photo', 'wallpaper', or 'signature' file

            if (!$file) {
                throw new \Exception('No file provided');
            }

            // Define config based on type
            $config = [
                'photo' => [
                    'folder' => 'admin-photos',
                    'transformation' => ['width' => 400, 'height' => 400, 'crop' => 'fill', 'gravity' => 'face'],
                    'url_field' => 'photo_url',
                    'public_id_field' => 'photo_public_id'
                ],
                'wallpaper' => [
                    'folder' => 'admin-wallpapers',
                    'transformation' => ['width' => 1920, 'height' => 400, 'crop' => 'fill'],
                    'url_field' => 'wallpaper_url',
                    'public_id_field' => 'wallpaper_public_id'
                ],
                'signature' => [ // NEW
                    'folder' => 'admin-signatures',
                    'transformation' => ['width' => 300, 'height' => 100, 'crop' => 'fit'],
                    'url_field' => 'signature_url',
                    'public_id_field' => 'signature_public_id'
                ]
            ][$type];

            // Delete old image if exists and not default
            $oldPublicId = $admin->{$config['public_id_field']};
            $defaultIds = ['ksdmh4mmpxdtjogdgjmm', 'verzp7lqedwsfn3hz8xf'];

            if ($oldPublicId && !in_array($oldPublicId, $defaultIds)) {
                try {
                    Cloudinary::destroy($oldPublicId);
                } catch (\Exception $e) {
                    \Log::warning("Failed to delete old image: {$oldPublicId}");
                }
            }

            // Upload new image
            $result = Cloudinary::upload($file->getRealPath(), [
                'folder' => $config['folder'],
                'transformation' => $config['transformation']
            ]);

            // Update admin record with new image info
            $updateData = [
                $config['url_field'] => $result->getSecurePath(),
                $config['public_id_field'] => $result->getPublicId()
            ];

            $admin->update($updateData);

            return response()->json([
                'message' => ucfirst($type) . ' updated successfully',
                $type . '_url' => $result->getSecurePath(),
                $type . '_public_id' => $result->getPublicId()
            ]);

        } catch (\Exception $e) {
            \Log::error('Photo upload error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update ' . ($request->type ?? 'image'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePhotoRecords(Request $request)
    {
        try {
            $validated = $request->validate([
                'photo_url' => 'nullable|string',
                'photo_public_id' => 'nullable|string',
                'wallpaper_url' => 'nullable|string',
                'wallpaper_public_id' => 'nullable|string',
                'signature_url' => 'nullable|string', // NEW
                'signature_public_id' => 'nullable|string', // NEW
                'type' => 'required|in:photo,wallpaper,signature' // UPDATED
            ]);

            $admin = $request->user();
            $type = $validated['type'];

            $updateData = [];
            if ($type === 'photo') {
                $updateData['photo_url'] = $validated['photo_url'];
                $updateData['photo_public_id'] = $validated['photo_public_id'];
            } else if ($type === 'wallpaper') {
                $updateData['wallpaper_url'] = $validated['wallpaper_url'];
                $updateData['wallpaper_public_id'] = $validated['wallpaper_public_id'];
            } else { // signature
                $updateData['signature_url'] = $validated['signature_url'];
                $updateData['signature_public_id'] = $validated['signature_public_id'];
            }

            $admin->update($updateData);

            \Log::info("Admin {$admin->admin_id} updated {$type} records", $updateData);

            return response()->json([
                'message' => ucfirst($type) . ' updated successfully',
                'admin' => $admin->fresh()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating photo records: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteCloudinaryImage(Request $request)
    {
        try {
            $validated = $request->validate([
                'public_id' => 'required|string',
                'type' => 'required|in:photo,wallpaper,signature' // UPDATED
            ]);

            $publicId = $validated['public_id'];
            $type = $validated['type'];

            // Skip deletion for default images
            $defaultIds = ['ksdmh4mmpxdtjogdgjmm', 'verzp7lqedwsfn3hz8xf'];
            if (in_array($publicId, $defaultIds)) {
                return response()->json([
                    'message' => 'Default image preserved',
                    'deleted' => false
                ]);
            }

            \Log::info("Attempting to delete {$type} from Cloudinary", [
                'admin_id' => $request->user()->admin_id,
                'public_id' => $publicId
            ]);

            // Use Cloudinary API directly
            $cloudinary = new \Cloudinary\Cloudinary(env('CLOUDINARY_URL'));
            $api = $cloudinary->adminApi();

            // For simple image deletion, use the upload API destroy method
            $result = $cloudinary->uploadApi()->destroy($publicId, [
                'invalidate' => true
            ]);

            \Log::info("Cloudinary deletion result for {$publicId}:", ['result' => $result]);

            if ($result->getArrayCopy()['result'] === 'ok') {
                return response()->json([
                    'message' => 'Image deleted successfully from Cloudinary',
                    'deleted' => true,
                    'result' => $result->getArrayCopy()
                ]);
            } else {
                throw new \Exception('Cloudinary deletion failed');
            }

        } catch (\Exception $e) {
            \Log::error('Error deleting Cloudinary image: ' . $e->getMessage(), [
                'public_id' => $validated['public_id'] ?? 'unknown',
                'type' => $validated['type'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to delete image from Cloudinary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}