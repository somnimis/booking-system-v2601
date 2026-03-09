<?php

namespace App\Http\Controllers;

use App\Models\RequisitionForm;
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
            'requestedFacilities.facility.departments',
            'requestedEquipment.equipment.departments',
            'requestedServices.service.admins', // Keep service admins as they're direct
            'requisitionApprovals'
        ])->findOrFail($requestId);

        // Get all unique departments from requested facilities
        $facilityDepartmentIds = collect();
        foreach ($requisition->requestedFacilities as $requestedFacility) {
            if ($requestedFacility->facility && $requestedFacility->facility->departments) {
                $facilityDepartmentIds = $facilityDepartmentIds->merge(
                    $requestedFacility->facility->departments->pluck('department_id')
                );
            }
        }
        
        // Get all unique departments from requested equipment
        $equipmentDepartmentIds = collect();
        foreach ($requisition->requestedEquipment as $requestedEquipment) {
            if ($requestedEquipment->equipment && $requestedEquipment->equipment->departments) {
                $equipmentDepartmentIds = $equipmentDepartmentIds->merge(
                    $requestedEquipment->equipment->departments->pluck('department_id')
                );
            }
        }
        
        // Combine all department IDs from facilities and equipment
        $allResourceDepartmentIds = $facilityDepartmentIds->merge($equipmentDepartmentIds)->unique();
        
        // Get all unique service IDs from requested services
        $serviceIds = $requisition->requestedServices->pluck('service_id')->unique();
        
        // Get all admins who manage departments that match the resource departments
        $requiredAdmins = collect();
        
        // Find admins who manage departments that match facility/equipment departments
        if ($allResourceDepartmentIds->isNotEmpty()) {
            $adminsWithMatchingDepartments = \App\Models\Admin::whereHas('departments', function($query) use ($allResourceDepartmentIds) {
                $query->whereIn('department_id', $allResourceDepartmentIds);
            })->with(['departments'])->get();
            
            foreach ($adminsWithMatchingDepartments as $admin) {
                // Get the specific departments this admin manages that match our resource departments
                $matchingDepartments = $admin->departments->whereIn('department_id', $allResourceDepartmentIds);
                
                foreach ($matchingDepartments as $dept) {
                    // Find which resources belong to this department
                    $facilitiesInDept = $requisition->requestedFacilities->filter(function($rf) use ($dept) {
                        return $rf->facility && $rf->facility->departments->contains('department_id', $dept->department_id);
                    })->map(function($rf) {
                        return [
                            'facility_id' => $rf->facility_id,
                            'facility_name' => $rf->facility->facility_name ?? 'Unknown'
                        ];
                    })->unique('facility_id')->values();
                    
                    $equipmentInDept = $requisition->requestedEquipment->filter(function($re) use ($dept) {
                        return $re->equipment && $re->equipment->departments->contains('department_id', $dept->department_id);
                    })->map(function($re) {
                        return [
                            'equipment_id' => $re->equipment_id,
                            'equipment_name' => $re->equipment->equipment_name ?? 'Unknown'
                        ];
                    })->unique('equipment_id')->values();
                    
                    if ($facilitiesInDept->isNotEmpty() || $equipmentInDept->isNotEmpty()) {
                        $requiredAdmins->push([
                            'admin_id' => $admin->admin_id,
                            'first_name' => $admin->first_name,
                            'last_name' => $admin->last_name,
                            'title' => $admin->title,
                            'email' => $admin->email,
                            'department_id' => $dept->department_id,
                            'department_name' => $dept->department_name ?? 'Unknown',
                            'managing_facilities' => $facilitiesInDept,
                            'managing_equipment' => $equipmentInDept,
                            'managing_service_id' => null,
                            'managing_service_name' => null
                        ]);
                    }
                }
            }
        }
        
        // Collect admins from requested services (direct assignment, not department based)
        foreach ($requisition->requestedServices as $requestedService) {
            if ($requestedService->service && $requestedService->service->admins) {
                foreach ($requestedService->service->admins as $admin) {
                    $requiredAdmins->push([
                        'admin_id' => $admin->admin_id,
                        'first_name' => $admin->first_name,
                        'last_name' => $admin->last_name,
                        'title' => $admin->title,
                        'email' => $admin->email,
                        'department_id' => null,
                        'department_name' => null,
                        'managing_facilities' => collect(),
                        'managing_equipment' => collect(),
                        'managing_service_id' => $requestedService->service_id,
                        'managing_service_name' => $requestedService->service->service_name ?? 'Unknown'
                    ]);
                }
            }
        }
        
        // Get unique admins based on admin_id
        $uniqueAdmins = $requiredAdmins->unique('admin_id');
        
        // Get current approvals
        $currentApprovals = $requisition->requisitionApprovals()
            ->whereNotNull('approved_by')
            ->get()
            ->pluck('approved_by')
            ->unique();
        
        // Calculate counts
        $maxApprovals = $uniqueAdmins->count();
        $currentApprovalCount = $currentApprovals->count();
        
        // Prepare detailed admin approval status
        $adminStatus = $uniqueAdmins->map(function ($adminData) use ($requiredAdmins, $currentApprovals) {
            $adminId = $adminData['admin_id'];
            
            // Aggregate all responsibilities for this admin
            $allAdminEntries = $requiredAdmins->where('admin_id', $adminId);
            
            $managingFacilities = $allAdminEntries->pluck('managing_facilities')->flatten(1)->unique('facility_id')->values();
            $managingEquipment = $allAdminEntries->pluck('managing_equipment')->flatten(1)->unique('equipment_id')->values();
            $managingServices = $allAdminEntries->whereNotNull('managing_service_id')
                ->map(function ($item) {
                    return [
                        'service_id' => $item['managing_service_id'],
                        'service_name' => $item['managing_service_name']
                    ];
                })->unique('service_id')->values();
            
            $departments = $allAdminEntries->whereNotNull('department_id')
                ->map(function ($item) {
                    return [
                        'department_id' => $item['department_id'],
                        'department_name' => $item['department_name']
                    ];
                })->unique('department_id')->values();
            
            return [
                'admin_id' => $adminId,
                'name' => $adminData['first_name'] . ' ' . $adminData['last_name'],
                'title' => $adminData['title'],
                'email' => $adminData['email'],
                'has_approved' => $currentApprovals->contains($adminId),
                'departments' => $departments,
                'managing_facilities' => $managingFacilities,
                'managing_equipment' => $managingEquipment,
                'managing_services' => $managingServices,
                'total_responsibilities' => $managingFacilities->count() + $managingEquipment->count() + $managingServices->count()
            ];
        })->values();

        return response()->json([
            'max_approvals' => $maxApprovals,
            'current_approvals' => $currentApprovalCount,
            'approval_status' => $currentApprovalCount . '/' . $maxApprovals,
            'is_fully_approved' => $maxApprovals > 0 && $currentApprovalCount >= $maxApprovals,
            'required_admins' => $adminStatus,
            'facilities_count' => $requisition->requestedFacilities->count(),
            'equipment_count' => $requisition->requestedEquipment->count(),
            'services_count' => $serviceIds->count(),
            'debug' => [
                'resource_department_ids' => $allResourceDepartmentIds->values(),
                'service_ids' => $serviceIds->values(),
                'total_admin_assignments' => $requiredAdmins->count(),
                'unique_admins_count' => $maxApprovals,
            ]
        ]);
    }

    /**
     * Check if current admin can approve this requisition based on department matching
     */
    public function canAdminApprove(Request $request, $requestId)
    {
        $admin = Auth::user();
        
        $requisition = RequisitionForm::with([
            'requestedFacilities.facility.departments',
            'requestedEquipment.equipment.departments',
            'requestedServices.service.admins'
        ])->findOrFail($requestId);
        
        // Check if admin has any departments
        $adminDepartmentIds = $admin->departments->pluck('department_id');
        
        // Check for matching departments with requested facilities
        $hasMatchingFacilityDepartment = false;
        foreach ($requisition->requestedFacilities as $requestedFacility) {
            if ($requestedFacility->facility && $requestedFacility->facility->departments) {
                $facilityDepartmentIds = $requestedFacility->facility->departments->pluck('department_id');
                if ($facilityDepartmentIds->intersect($adminDepartmentIds)->isNotEmpty()) {
                    $hasMatchingFacilityDepartment = true;
                    break;
                }
            }
        }
        
        // Check for matching departments with requested equipment
        $hasMatchingEquipmentDepartment = false;
        if (!$hasMatchingFacilityDepartment) {
            foreach ($requisition->requestedEquipment as $requestedEquipment) {
                if ($requestedEquipment->equipment && $requestedEquipment->equipment->departments) {
                    $equipmentDepartmentIds = $requestedEquipment->equipment->departments->pluck('department_id');
                    if ($equipmentDepartmentIds->intersect($adminDepartmentIds)->isNotEmpty()) {
                        $hasMatchingEquipmentDepartment = true;
                        break;
                    }
                }
            }
        }
        
        // Check if admin is assigned to any requested services (direct assignment)
        $serviceIds = $requisition->requestedServices->pluck('service_id');
        $isAdminForServices = false;
        if ($serviceIds->isNotEmpty()) {
            $isAdminForServices = \App\Models\AdminService::where('admin_id', $admin->admin_id)
                ->whereIn('service_id', $serviceIds)
                ->exists();
        }
        
        $isAdminForAnyResource = $hasMatchingFacilityDepartment || $hasMatchingEquipmentDepartment || $isAdminForServices;
        
        // Check if admin has already approved
        $hasApproved = $requisition->requisitionApprovals()
            ->where('approved_by', $admin->admin_id)
            ->exists();
        
        return response()->json([
            'can_approve' => $isAdminForAnyResource && !$hasApproved,
            'has_matching_facility_department' => $hasMatchingFacilityDepartment,
            'has_matching_equipment_department' => $hasMatchingEquipmentDepartment,
            'is_admin_for_services' => $isAdminForServices,
            'has_approved' => $hasApproved,
            'admin_id' => $admin->admin_id,
            'admin_departments' => $adminDepartmentIds
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
                \App\Models\Admin::whereHas('departments', function($query) use ($resourceDepartmentIds) {
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
            'requestedFacilities.facility.departments',
            'requestedEquipment.equipment.departments',
            'requestedServices.service.admins'
        ])->findOrFail($requestId);
        
        $adminsToNotify = collect();
        
        // Get department IDs from all requested facilities and equipment
        $resourceDepartmentIds = collect();
        $resourceMapping = [];
        
        // Map facilities to departments
        foreach ($requisition->requestedFacilities as $requestedFacility) {
            if ($requestedFacility->facility && $requestedFacility->facility->departments) {
                foreach ($requestedFacility->facility->departments as $dept) {
                    $resourceDepartmentIds->push($dept->department_id);
                    $resourceMapping[] = [
                        'department_id' => $dept->department_id,
                        'resource_type' => 'facility',
                        'resource_id' => $requestedFacility->facility_id,
                        'resource_name' => $requestedFacility->facility->facility_name ?? 'Unknown Facility'
                    ];
                }
            }
        }
        
        // Map equipment to departments
        foreach ($requisition->requestedEquipment as $requestedEquipment) {
            if ($requestedEquipment->equipment && $requestedEquipment->equipment->departments) {
                foreach ($requestedEquipment->equipment->departments as $dept) {
                    $resourceDepartmentIds->push($dept->department_id);
                    $resourceMapping[] = [
                        'department_id' => $dept->department_id,
                        'resource_type' => 'equipment',
                        'resource_id' => $requestedEquipment->equipment_id,
                        'resource_name' => $requestedEquipment->equipment->equipment_name ?? 'Unknown Equipment'
                    ];
                }
            }
        }
        
        $resourceDepartmentIds = $resourceDepartmentIds->unique();
        
        // Get admins who manage these departments
        if ($resourceDepartmentIds->isNotEmpty()) {
            $admins = \App\Models\Admin::whereHas('departments', function($query) use ($resourceDepartmentIds) {
                $query->whereIn('department_id', $resourceDepartmentIds);
            })->with('departments')->get();
            
            foreach ($admins as $admin) {
                // Get departments this admin manages that are in our resource departments
                $adminDeptIds = $admin->departments->pluck('department_id');
                $matchingDeptIds = $adminDeptIds->intersect($resourceDepartmentIds);
                
                // Get all resources from matching departments
                $resources = collect();
                foreach ($matchingDeptIds as $deptId) {
                    $deptResources = collect($resourceMapping)
                        ->where('department_id', $deptId)
                        ->map(function($item) {
                            return [
                                'type' => $item['resource_type'],
                                'id' => $item['resource_id'],
                                'name' => $item['resource_name']
                            ];
                        });
                    $resources = $resources->merge($deptResources);
                }
                
                $adminsToNotify->push([
                    'admin_id' => $admin->admin_id,
                    'email' => $admin->email,
                    'name' => $admin->first_name . ' ' . $admin->last_name,
                    'resources' => $resources->unique()->values()
                ]);
            }
        }
        
        // Add service admins (direct assignment)
        foreach ($requisition->requestedServices as $requestedService) {
            if ($requestedService->service && $requestedService->service->admins) {
                foreach ($requestedService->service->admins as $admin) {
                    $existingEntry = $adminsToNotify->firstWhere('admin_id', $admin->admin_id);
                    
                    $resource = [
                        'type' => 'service',
                        'id' => $requestedService->service_id,
                        'name' => $requestedService->service->service_name ?? 'Unknown Service'
                    ];
                    
                    if ($existingEntry) {
                        // Update existing entry
                        $existingResources = $existingEntry['resources']->toArray();
                        if (!collect($existingResources)->contains('id', $resource['id'])) {
                            $existingResources[] = $resource;
                            $existingEntry['resources'] = collect($existingResources);
                            $adminsToNotify = $adminsToNotify->replace([$existingEntry]);
                        }
                    } else {
                        $adminsToNotify->push([
                            'admin_id' => $admin->admin_id,
                            'email' => $admin->email,
                            'name' => $admin->first_name . ' ' . $admin->last_name,
                            'resources' => collect([$resource])
                        ]);
                    }
                }
            }
        }
        
        // Remove duplicates and ensure unique admin_id
        $adminsToNotify = $adminsToNotify->unique('admin_id')->values();
        
        return response()->json([
            'admins' => $adminsToNotify,
            'total_admins' => $adminsToNotify->count(),
            'facilities_count' => $requisition->requestedFacilities->count(),
            'equipment_count' => $requisition->requestedEquipment->count(),
            'services_count' => $requisition->requestedServices->count(),
            'request_details' => [
                'request_id' => $requisition->request_id,
                'title' => $requisition->calendar_title,
                'requester' => $requisition->first_name . ' ' . $requisition->last_name
            ]
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
                $admins = \App\Models\Admin::whereHas('departments', function($query) use ($departmentIds) {
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
                $admins = \App\Models\Admin::whereHas('departments', function($query) use ($departmentIds) {
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
        $fullyApprovedFacilities = $facilityBreakdown->filter(function($item) {
            return $item['total_admins'] > 0 && $item['approved_admins'] >= $item['total_admins'];
        })->count();
        
        $fullyApprovedEquipment = $equipmentBreakdown->filter(function($item) {
            return $item['total_admins'] > 0 && $item['approved_admins'] >= $item['total_admins'];
        })->count();
        
        $fullyApprovedServices = $serviceBreakdown->filter(function($item) {
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