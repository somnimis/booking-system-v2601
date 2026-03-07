<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExtraService;
use App\Models\AdminService;
use App\Models\Admin;
use Illuminate\Http\Request;

class ExtraServicesController extends Controller
{
    public function index()
    {
        return response()->json(
            ExtraService::all(),
            200
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_name' => 'required|string|max:80',
        ]);

        $extraService = ExtraService::create($validated);
        $serviceName = $extraService->service_name;

        return response()->json([
            'message' => "Extra service '{$serviceName}' was created successfully.",
            'data' => $extraService
        ], 201);
    }

    public function update(Request $request, $service_id)
    {
        $validated = $request->validate([
            'service_name' => 'required|string|max:80',
        ]);

        $extraService = ExtraService::findOrFail($service_id);
        $extraService->update($validated);
        $serviceName = $extraService->service_name;

        return response()->json([
            'message' => "Extra service '{$serviceName}' was updated successfully.",
            'data' => $extraService
        ], 200);
    }

    public function destroy($service_id)
    {
        $extraService = ExtraService::findOrFail($service_id);
        $serviceName = $extraService->service_name;

        $extraService->delete();

        return response()->json([
            'message' => "Extra service '{$serviceName}' was deleted successfully."
        ], 200);
    }

    /* ----- Assigning admins to extra services ----- */

public function assignService(Request $request)
{
    $admin = $request->user(); // Logged-in admin

    $validated = $request->validate([
        'service_ids' => 'required|array',
        'service_ids.*' => 'exists:extra_services,service_id',
    ]);

    $assignedServices = [];
    $skippedServices = [];

    foreach ($validated['service_ids'] as $serviceId) {
        $exists = AdminService::where('admin_id', $admin->admin_id)
            ->where('service_id', $serviceId)
            ->exists();

        if ($exists) {
            $skippedServices[] = $serviceId;
            continue;
        }

        $adminService = AdminService::create([
            'admin_id' => $admin->admin_id,
            'service_id' => $serviceId
        ]);

        $assignedServices[] = [
            'service_id' => $serviceId,
            'service_name' => ExtraService::find($serviceId)->service_name
        ];
    }

    $messageParts = [];
    if (!empty($assignedServices)) {
        $assignedNames = implode(', ', array_column($assignedServices, 'service_name'));
        $messageParts[] = "Assigned: {$assignedNames}";
    }
    if (!empty($skippedServices)) {
        $skippedNames = implode(', ', ExtraService::whereIn('service_id', $skippedServices)->pluck('service_name')->toArray());
        $messageParts[] = "Skipped (already assigned): {$skippedNames}";
    }

    return response()->json([
        'message' => implode(' | ', $messageParts),
        'data' => $assignedServices
    ], 201);
}


public function getAdminServices($adminId = null)
{
    if ($adminId) {
        // Return services for specific admin
        $services = AdminService::where('admin_id', $adminId)->get();
    } else {
        // Return all admin-service relationships (for filtering client-side)
        $services = AdminService::all();
    }
    
    return response()->json($services, 200);
}

    public function unassignService($adminServiceId)
    {
        $adminService = AdminService::findOrFail($adminServiceId);
        $serviceName = $adminService->service->service_name ?? 'Service';

        $adminService->delete();

        return response()->json([
            'message' => "Service '{$serviceName}' unassigned successfully"
        ], 200);
    }

}
