<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\RequisitionForm;
use App\Models\DepartmentRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequiredApprovalsController extends Controller
{
    /**
     * Get approval status for a requisition based on department matching
     */
    public function getApprovalStatus($requestId)
    {
        $requisition = RequisitionForm::with([
            'requisitionApprovals' => function ($query) {
                $query->with(['admin.role'])
                    ->orderBy('stage')
                    ->orderBy('approval_id');
            }
        ])->findOrFail($requestId);

        $approvals = $requisition->requisitionApprovals;

        // Optional: filter out any approvals for admins that no longer exist (safety)
        $approvals = $approvals->filter(fn($a) => $a->admin !== null);

        $maxApprovals = $approvals->count();
        $currentApprovalCount = $approvals->where('status', 'Approved')->count();

        // Build the per-admin list — no derivation needed, just map the existing rows
        $adminStatus = $approvals->map(function ($approval) {
            $admin = $approval->admin;

            return [
                'approval_id' => $approval->approval_id,
                'admin_id' => $admin->admin_id,
                'name' => trim("{$admin->first_name} {$admin->last_name}"),
                'first_name' => $admin->first_name,
                'last_name' => $admin->last_name,
                'title' => $admin->title,
                'email' => $admin->email,
                'photo_url' => $admin->photo_url,
                'role_id' => $admin->role_id,
                'role_name' => $admin->role?->role_title,
                'stage' => $approval->stage,
                'status' => $approval->status,                 // Pending | Approved | Rejected
                'has_approved' => $approval->status === 'Approved',
                'acted_at' => $approval->acted_at,
                'remarks' => $approval->remarks,
            ];
        })->values();

        return response()->json([
            'max_approvals' => $maxApprovals,
            'current_approvals' => $currentApprovalCount,
            'approval_status' => "{$currentApprovalCount}/{$maxApprovals}",
            'is_fully_approved' => $maxApprovals > 0 && $currentApprovalCount >= $maxApprovals,
            'required_admins' => $adminStatus,
        ]);
    }


    /**
     * Check if current admin can approve this requisition based on department-head matching
     */
    public function canAdminApprove(Request $request, $requestId)
    {
        $admin = Auth::user();

        $requisition = RequisitionForm::with([
            'requestedFacilities.facility',
            'requestedEquipment.equipment',
            'requestedServices.service',
            'purpose',
        ])->findOrFail($requestId);

        // Departments where THIS admin is a Department Head
        $headDeptIds = $admin->departments
            ->filter(fn($d) => $d->pivot->role_id === DepartmentRole::HEAD)
            ->pluck('department_id');

        // Collect the departments involved in this requisition
        $resourceDeptIds = collect()
            ->merge(
                $requisition->requestedFacilities
                    ->map(fn($rf) => $rf->facility?->managed_by)
                    ->filter()
            )
            ->merge(
                $requisition->requestedEquipment
                    ->map(fn($re) => $re->equipment?->managed_by)
                    ->filter()
            )
            ->merge(
                $requisition->requestedServices
                    ->map(fn($rs) => $rs->service?->managed_by)
                    ->filter()
            );

        if ($requisition->purpose?->routes_to) {
            $resourceDeptIds->push($requisition->purpose->routes_to);
        }

        $resourceDeptIds = $resourceDeptIds->unique();

        // Which of those departments does this admin head?
        $matchingDeptIds = $headDeptIds->intersect($resourceDeptIds);

        // Per-resource breakdown (for debugging / UI hints)
        $hasMatchingFacilityDepartment = $requisition->requestedFacilities
            ->pluck('facility.managed_by')
            ->filter()
            ->intersect($headDeptIds)
            ->isNotEmpty();

        $hasMatchingEquipmentDepartment = $requisition->requestedEquipment
            ->map(fn($re) => $re->equipment?->managed_by)
            ->filter()
            ->intersect($headDeptIds)
            ->isNotEmpty();

        $hasMatchingServiceDepartment = $requisition->requestedServices
            ->map(fn($rs) => $rs->service?->managed_by)
            ->filter()
            ->intersect($headDeptIds)
            ->isNotEmpty();

        $hasMatchingPurposeDepartment = $requisition->purpose?->routes_to
            && $headDeptIds->contains($requisition->purpose->routes_to);

        $canApproveResource = $matchingDeptIds->isNotEmpty();

        // Has this admin already acted on this request?
        $hasApproved = $requisition->requisitionApprovals()
            ->where('admin_id', $admin->admin_id)
            ->where('status', '!=', 'Pending')
            ->exists();

        return response()->json([
            'can_approve' => $canApproveResource && !$hasApproved,
            'has_matching_facility_department' => $hasMatchingFacilityDepartment,
            'has_matching_equipment_department' => $hasMatchingEquipmentDepartment,
            'has_matching_service_department' => $hasMatchingServiceDepartment,
            'has_matching_purpose_department' => $hasMatchingPurposeDepartment,
            'has_approved' => $hasApproved,
            'admin_id' => $admin->admin_id,
            'admin_head_departments' => $headDeptIds->values(),
            'resource_departments' => $resourceDeptIds->values(),
            'matching_departments' => $matchingDeptIds->values(),
        ]);
    }

    /**
     * Get approval progress for dashboard (simplified version)
     */
    public function getApprovalProgress($requestId)
    {
        $requisition = RequisitionForm::with([
            'requestedFacilities.facility.departments',
            'requestedEquipment.equipment.departments',
            'requestedServices.service.admins'
        ])->findOrFail($requestId);

        // Get all unique admin IDs based on department matching and service assignments
        $adminIds = collect();

        // Get department IDs from all requested facilities and equipment
        $resourceDepartmentIds = collect();

        // From facilities
        foreach ($requisition->requestedFacilities as $requestedFacility) {
            if ($requestedFacility->facility && $requestedFacility->facility->departments) {
                $resourceDepartmentIds = $resourceDepartmentIds->merge(
                    $requestedFacility->facility->departments->pluck('department_id')
                );
            }
        }

        // From equipment
        foreach ($requisition->requestedEquipment as $requestedEquipment) {
            if ($requestedEquipment->equipment && $requestedEquipment->equipment->departments) {
                $resourceDepartmentIds = $resourceDepartmentIds->merge(
                    $requestedEquipment->equipment->departments->pluck('department_id')
                );
            }
        }

        $resourceDepartmentIds = $resourceDepartmentIds->unique();

        // Get admins who manage these departments
        if ($resourceDepartmentIds->isNotEmpty()) {
            $adminIds = $adminIds->merge(
                Admin::whereHas('departments', function ($query) use ($resourceDepartmentIds) {
                    $query->whereIn('department_id', $resourceDepartmentIds);
                })->pluck('admin_id')
            );
        }

        // Get admin IDs from services
        foreach ($requisition->requestedServices as $requestedService) {
            if ($requestedService->service && $requestedService->service->admins) {
                $adminIds = $adminIds->merge(
                    $requestedService->service->admins->pluck('admin_id')
                );
            }
        }

        $adminIds = $adminIds->unique();

        // Get current approvals
        $approvedIds = $requisition->requisitionApprovals()
            ->whereNotNull('approved_by')
            ->pluck('approved_by')
            ->unique();

        return response()->json([
            'required' => $adminIds->count(),
            'approved' => $approvedIds->count(),
            'pending' => $adminIds->count() - $approvedIds->count(),
            'progress_percentage' => $adminIds->count() > 0
                ? round(($approvedIds->count() / $adminIds->count()) * 100, 2)
                : 0,
            'status_text' => "{$approvedIds->count()}/{$adminIds->count()} admins have approved",
            'breakdown' => [
                'facilities_count' => $requisition->requestedFacilities->count(),
                'equipment_count' => $requisition->requestedEquipment->count(),
                'services_count' => $requisition->requestedServices->count()
            ]
        ]);
    }

    /**
     * Get all admins that need to approve this request (for email notifications)
     */
    public function getAdminsToNotify($requestId)
    {
        $requisition = RequisitionForm::with([
            'requestedFacilities.facility',
            'requestedEquipment.equipment',
            'requestedServices.service',
            'purpose',
        ])->findOrFail($requestId);

        // ─────────────────────────────────────────────
        // 1. Normalize every resource into a flat list
        //    Each row: { type, id, name, department_id }
        // ─────────────────────────────────────────────
        $resources = collect();

        foreach ($requisition->requestedFacilities as $rf) {
            if (!$rf->facility?->managed_by)
                continue;
            $resources->push([
                'type' => 'facility',
                'id' => $rf->facility->facility_id,
                'name' => $rf->facility->facility_name ?? 'Unknown Facility',
                'department_id' => $rf->facility->managed_by,
            ]);
        }

        foreach ($requisition->requestedEquipment as $re) {
            if (!$re->equipment?->managed_by)
                continue;
            $resources->push([
                'type' => 'equipment',
                'id' => $re->equipment->equipment_id,
                'name' => $re->equipment->equipment_name ?? 'Unknown Equipment',
                'department_id' => $re->equipment->managed_by,
            ]);
        }

        foreach ($requisition->requestedServices as $rs) {
            if (!$rs->service?->managed_by)
                continue;
            $resources->push([
                'type' => 'service',
                'id' => $rs->service_id,
                'name' => $rs->service->service_name ?? 'Unknown Service',
                'department_id' => $rs->service->managed_by,
            ]);
        }

        if ($requisition->purpose?->routes_to) {
            $resources->push([
                'type' => 'purpose',
                'id' => $requisition->purpose->purpose_id,
                'name' => $requisition->purpose->purpose_name ?? 'Booking Purpose',
                'department_id' => $requisition->purpose->routes_to,
            ]);
        }

        // ─────────────────────────────────────────────
        // 2. Find Department Heads for the involved departments
        // ─────────────────────────────────────────────
        $departmentIds = $resources->pluck('department_id')->unique()->values();

        $heads = $departmentIds->isEmpty()
            ? collect()
            : Admin::whereIn('admins.admin_id', function ($sub) use ($departmentIds) {
                $sub->select('admin_departments.admin_id')
                    ->from('admin_departments')
                    ->whereIn('admin_departments.department_id', $departmentIds)
                    ->where('admin_departments.role_id', DepartmentRole::HEAD);
            })
                ->with('departments')
                ->get();

        // ─────────────────────────────────────────────
        // 3. Build admin → resources map
        // ─────────────────────────────────────────────
        $map = [];

        foreach ($heads as $admin) {
            // Departments where this admin is a HEAD (filtered by role on the pivot)
            $headDeptIds = $admin->departments
                ->filter(fn($d) => (int) $d->pivot->role_id === (int) DepartmentRole::HEAD)
                ->pluck('department_id');

            foreach ($resources as $r) {
                if (!$headDeptIds->contains($r['department_id']))
                    continue;

                $map[$admin->admin_id] ??= [
                    'admin_id' => $admin->admin_id,
                    'email' => $admin->email,
                    'name' => trim("{$admin->first_name} {$admin->last_name}"),
                    'resources' => [],
                ];

                $map[$admin->admin_id]['resources'][$r['type'] . ':' . $r['id']] = [
                    'type' => $r['type'],
                    'id' => $r['id'],
                    'name' => $r['name'],
                ];
            }
        }

        $adminsToNotify = collect($map)->map(function ($entry) {
            $entry['resources'] = array_values($entry['resources']);
            return $entry;
        })->values();

        return response()->json([
            'admins' => $adminsToNotify,
            'total_admins' => $adminsToNotify->count(),
            'facilities_count' => $requisition->requestedFacilities->count(),
            'equipment_count' => $requisition->requestedEquipment->count(),
            'services_count' => $requisition->requestedServices->count(),
            'purposes_count' => $requisition->purpose ? 1 : 0,
            'request_details' => [
                'request_id' => $requisition->request_id,
                'title' => $requisition->event_title,
                'requester' => $requisition->first_name . ' ' . $requisition->last_name,
            ],
        ]);
    }

    /**
     * Get breakdown of which resources need approvals based on department matching
     */
    public function getResourceApprovalBreakdown($requestId)
    {
        $requisition = RequisitionForm::with([
            'requestedFacilities.facility.departments',
            'requestedEquipment.equipment.departments',
            'requestedServices.service.admins',
            'requisitionApprovals'
        ])->findOrFail($requestId);

        // Get current approvals
        $approvedAdminIds = $requisition->requisitionApprovals()
            ->whereNotNull('approved_by')
            ->pluck('approved_by')
            ->unique();

        // Process facilities
        $facilityBreakdown = $requisition->requestedFacilities->map(function ($requestedFacility) use ($approvedAdminIds) {
            $facility = $requestedFacility->facility;

            // Get departments for this facility
            $departmentIds = $facility && $facility->departments ? $facility->departments->pluck('department_id') : collect();

            // Get admins who manage these departments
            $admins = collect();
            if ($departmentIds->isNotEmpty()) {
                $admins = \App\Models\Admin::whereHas('departments', function ($query) use ($departmentIds) {
                    $query->whereIn('department_id', $departmentIds);
                })->get();
            }

            $approvalDetails = $admins->map(function ($admin) use ($approvedAdminIds) {
                return [
                    'admin_id' => $admin->admin_id,
                    'name' => $admin->first_name . ' ' . $admin->last_name,
                    'has_approved' => $approvedAdminIds->contains($admin->admin_id)
                ];
            });

            return [
                'type' => 'facility',
                'id' => $requestedFacility->facility_id,
                'name' => $facility->facility_name ?? 'Unknown Facility',
                'departments' => $facility && $facility->departments ? $facility->departments->pluck('department_name') : [],
                'total_admins' => $admins->count(),
                'approved_admins' => $admins->whereIn('admin_id', $approvedAdminIds)->count(),
                'admin_details' => $approvalDetails
            ];
        });

        // Process equipment
        $equipmentBreakdown = $requisition->requestedEquipment->map(function ($requestedEquipment) use ($approvedAdminIds) {
            $equipment = $requestedEquipment->equipment;

            // Get departments for this equipment
            $departmentIds = $equipment && $equipment->departments ? $equipment->departments->pluck('department_id') : collect();

            // Get admins who manage these departments
            $admins = collect();
            if ($departmentIds->isNotEmpty()) {
                $admins = \App\Models\Admin::whereHas('departments', function ($query) use ($departmentIds) {
                    $query->whereIn('department_id', $departmentIds);
                })->get();
            }

            $approvalDetails = $admins->map(function ($admin) use ($approvedAdminIds) {
                return [
                    'admin_id' => $admin->admin_id,
                    'name' => $admin->first_name . ' ' . $admin->last_name,
                    'has_approved' => $approvedAdminIds->contains($admin->admin_id)
                ];
            });

            return [
                'type' => 'equipment',
                'id' => $requestedEquipment->equipment_id,
                'name' => $equipment->equipment_name ?? 'Unknown Equipment',
                'departments' => $equipment && $equipment->departments ? $equipment->departments->pluck('department_name') : [],
                'total_admins' => $admins->count(),
                'approved_admins' => $admins->whereIn('admin_id', $approvedAdminIds)->count(),
                'admin_details' => $approvalDetails
            ];
        });

        // Process services (direct assignment)
        $serviceBreakdown = $requisition->requestedServices->map(function ($requestedService) use ($approvedAdminIds) {
            $service = $requestedService->service;
            $admins = $service ? $service->admins : collect();

            $approvalDetails = $admins->map(function ($admin) use ($approvedAdminIds) {
                return [
                    'admin_id' => $admin->admin_id,
                    'name' => $admin->first_name . ' ' . $admin->last_name,
                    'has_approved' => $approvedAdminIds->contains($admin->admin_id)
                ];
            });

            return [
                'type' => 'service',
                'id' => $requestedService->service_id,
                'name' => $service->service_name ?? 'Unknown Service',
                'total_admins' => $admins->count(),
                'approved_admins' => $admins->whereIn('admin_id', $approvedAdminIds)->count(),
                'admin_details' => $approvalDetails
            ];
        });

        // Calculate fully approved resources
        $fullyApprovedFacilities = $facilityBreakdown->filter(function ($item) {
            return $item['total_admins'] > 0 && $item['approved_admins'] >= $item['total_admins'];
        })->count();

        $fullyApprovedEquipment = $equipmentBreakdown->filter(function ($item) {
            return $item['total_admins'] > 0 && $item['approved_admins'] >= $item['total_admins'];
        })->count();

        $fullyApprovedServices = $serviceBreakdown->filter(function ($item) {
            return $item['total_admins'] > 0 && $item['approved_admins'] >= $item['total_admins'];
        })->count();

        return response()->json([
            'facilities' => $facilityBreakdown,
            'equipment' => $equipmentBreakdown,
            'services' => $serviceBreakdown,
            'summary' => [
                'total_resources' => $facilityBreakdown->count() + $equipmentBreakdown->count() + $serviceBreakdown->count(),
                'total_facilities' => $facilityBreakdown->count(),
                'total_equipment' => $equipmentBreakdown->count(),
                'total_services' => $serviceBreakdown->count(),
                'fully_approved_facilities' => $fullyApprovedFacilities,
                'fully_approved_equipment' => $fullyApprovedEquipment,
                'fully_approved_services' => $fullyApprovedServices,
                'total_fully_approved' => $fullyApprovedFacilities + $fullyApprovedEquipment + $fullyApprovedServices
            ]
        ]);
    }
}