<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExtraService;
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
    
    public function getDropdown()
    {
        try {
            $services = ExtraService::select('service_id', 'service_name')
                ->orderBy('service_name')
                ->get();
            return response()->json(['success' => true, 'data' => $services]);
        } catch (\Exception $e) {
            \Log::error('Error fetching services dropdown: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch services'], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_name' => 'required|string|max:80',
            'managed_by' => 'nullable|exists:departments,department_id',
            'account_number' => 'nullable|integer',
            'service_fee' => 'nullable|numeric|min:0',
        ]);

        $extraService = ExtraService::create($validated);

        return response()->json([
            'message' => "Extra service '{$extraService->service_name}' was created successfully.",
            'data' => $extraService
        ], 201);
    }

    public function update(Request $request, $service_id)
    {
        $validated = $request->validate([
            'service_name' => 'required|string|max:80',
            'managed_by' => 'nullable|exists:departments,department_id',
            'account_number' => 'nullable|integer',
            'service_fee' => 'nullable|numeric|min:0',
        ]);

        $extraService = ExtraService::findOrFail($service_id);
        $extraService->update($validated);

        return response()->json([
            'message' => "Extra service '{$extraService->service_name}' was updated successfully.",
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

}