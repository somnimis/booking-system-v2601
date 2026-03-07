<?php

namespace App\Http\Controllers;

use App\Models\RequisitionForm;
use Illuminate\Http\Request;

class RequiredApprovalsController extends Controller
{
public function getApprovalStatus($requestId)
{
    // Get the requisition form with its requested facilities, services, and approvals
    $requisition = RequisitionForm::with([
        'requestedFacilities.facility.admins',
        'requestedServices.service.admins',
        'requisitionApprovals'
    ])->findOrFail($requestId);

    // Get all unique facilities requested in this form
    $facilityIds = $requisition->requestedFacilities->pluck('facility_id')->unique();
    
    // Get all unique services requested in this form
    $serviceIds = $requisition->requestedServices->pluck('service_id')->unique();
    
    if ($facilityIds->isEmpty() && $serviceIds->isEmpty()) {
        return response()->json([
            'max_approvals' => 0,
            'current_approvals' => 0,
            'approval_status' => '0/0',
            'is_fully_approved' => false,
            'message' => 'No facilities or services found for this request'
        ]);
    }

    // Collect all admins who manage at least one of the requested facilities or services
    $requiredAdmins = collect();
    
    // Collect admins from requested facilities
    foreach ($requisition->requestedFacilities as $requestedFacility) {
        if ($requestedFacility->facility && $requestedFacility->facility->admins) {
            foreach ($requestedFacility->facility->admins as $admin) {
                // Add admin with metadata about which facility they're managing
                $requiredAdmins->push([
                    'admin_id' => $admin->admin_id,
                    'first_name' => $admin->first_name,
                    'last_name' => $admin->last_name,
                    'title' => $admin->title,
                    'email' => $admin->email,
                    'managing_facility_id' => $requestedFacility->facility_id,
                    'managing_facility_name' => $requestedFacility->facility->facility_name ?? 'Unknown',
                    'managing_service_id' => null,
                    'managing_service_name' => null
                ]);
            }
        }
    }
    
    // Collect admins from requested services
    foreach ($requisition->requestedServices as $requestedService) {
        if ($requestedService->service && $requestedService->service->admins) {
            foreach ($requestedService->service->admins as $admin) {
                // Add admin with metadata about which service they're managing
                $requiredAdmins->push([
                    'admin_id' => $admin->admin_id,
                    'first_name' => $admin->first_name,
                    'last_name' => $admin->last_name,
                    'title' => $admin->title,
                    'email' => $admin->email,
                    'managing_facility_id' => null,
                    'managing_facility_name' => null,
                    'managing_service_id' => $requestedService->service_id,
                    'managing_service_name' => $requestedService->service->service_name ?? 'Unknown'
                ]);
            }
        }
    }
    
    // Get unique admins based on admin_id (an admin might manage multiple facilities/services)
    $uniqueAdmins = $requiredAdmins->unique('admin_id');
    
    // Get current approvals (approved_by is not null)
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
        
        // Find all facilities this admin manages that are in this request
        $managingFacilities = $requiredAdmins
            ->where('admin_id', $adminId)
            ->whereNotNull('managing_facility_id')
            ->map(function ($item) {
                return [
                    'facility_id' => $item['managing_facility_id'],
                    'facility_name' => $item['managing_facility_name']
                ];
            })
            ->unique('facility_id')
            ->values();
        
        // Find all services this admin manages that are in this request
        $managingServices = $requiredAdmins
            ->where('admin_id', $adminId)
            ->whereNotNull('managing_service_id')
            ->map(function ($item) {
                return [
                    'service_id' => $item['managing_service_id'],
                    'service_name' => $item['managing_service_name']
                ];
            })
            ->unique('service_id')
            ->values();
        
        return [
            'admin_id' => $adminId,
            'name' => $adminData['first_name'] . ' ' . $adminData['last_name'],
            'title' => $adminData['title'],
            'email' => $adminData['email'],
            'has_approved' => $currentApprovals->contains($adminId),
            'managing_facilities' => $managingFacilities,
            'managing_services' => $managingServices,
            'total_responsibilities' => $managingFacilities->count() + $managingServices->count()
        ];
    })->values();

    return response()->json([
        'max_approvals' => $maxApprovals,
        'current_approvals' => $currentApprovalCount,
        'approval_status' => $currentApprovalCount . '/' . $maxApprovals,
        'is_fully_approved' => $currentApprovalCount >= $maxApprovals,
        'required_admins' => $adminStatus,
        'facilities_count' => $facilityIds->count(),
        'services_count' => $serviceIds->count(),
        'facility_ids' => $facilityIds->values(),
        'service_ids' => $serviceIds->values(),
        'debug' => [
            'total_admin_assignments' => $requiredAdmins->count(),
            'unique_admins_count' => $maxApprovals,
            'facility_details' => $requisition->requestedFacilities->map(function($rf) {
                return [
                    'facility_id' => $rf->facility_id,
                    'facility_name' => $rf->facility->facility_name ?? 'Unknown',
                    'assigned_admins_count' => $rf->facility->admins->count() ?? 0
                ];
            }),
            'service_details' => $requisition->requestedServices->map(function($rs) {
                return [
                    'service_id' => $rs->service_id,
                    'service_name' => $rs->service->service_name ?? 'Unknown',
                    'assigned_admins_count' => $rs->service->admins->count() ?? 0
                ];
            })
        ]
    ]);
}

    /**
     * Check if current admin can approve this requisition
     */
    public function canAdminApprove(Request $request, $requestId)
    {
        $admin = auth()->user();
        
        $requisition = RequisitionForm::with([
            'requestedFacilities.facility.admins',
            'requestedServices.service.admins'
        ])->findOrFail($requestId);
        
        // Get all facility IDs and service IDs from this requisition
        $facilityIds = $requisition->requestedFacilities->pluck('facility_id');
        $serviceIds = $requisition->requestedServices->pluck('service_id');
        
        // Check if admin is assigned to any of these facilities
        $isAdminForFacilities = false;
        if (!$facilityIds->isEmpty()) {
            $isAdminForFacilities = \App\Models\AdminFacility::where('admin_id', $admin->admin_id)
                ->whereIn('facility_id', $facilityIds)
                ->exists();
        }
        
        // Check if admin is assigned to any of these services
        $isAdminForServices = false;
        if (!$serviceIds->isEmpty()) {
            $isAdminForServices = \App\Models\AdminService::where('admin_id', $admin->admin_id)
                ->whereIn('service_id', $serviceIds)
                ->exists();
        }
        
        $isAdminForAnyResource = $isAdminForFacilities || $isAdminForServices;
        
        // Check if admin has already approved
        $hasApproved = $requisition->requisitionApprovals()
            ->where('approved_by', $admin->admin_id)
            ->exists();
        
        return response()->json([
            'can_approve' => $isAdminForAnyResource && !$hasApproved,
            'is_admin_for_facilities' => $isAdminForFacilities,
            'is_admin_for_services' => $isAdminForServices,
            'has_approved' => $hasApproved,
            'admin_id' => $admin->admin_id
        ]);
    }

    /**
     * Get approval progress for dashboard (simplified version)
     */
    public function getApprovalProgress($requestId)
    {
        $requisition = RequisitionForm::with([
            'requestedFacilities.facility.admins',
            'requestedServices.service.admins'
        ])->findOrFail($requestId);
        
        // Get all unique admin IDs from all requested facilities and services
        $adminIds = collect();
        
        // Get admin IDs from facilities
        foreach ($requisition->requestedFacilities as $requestedFacility) {
            if ($requestedFacility->facility && $requestedFacility->facility->admins) {
                $adminIds = $adminIds->merge(
                    $requestedFacility->facility->admins->pluck('admin_id')
                );
            }
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
        
        // Simple count response
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
            'requestedFacilities.facility.admins',
            'requestedServices.service.admins'
        ])->findOrFail($requestId);
        
        $adminsToNotify = collect();
        
        // Collect admins from facilities
        foreach ($requisition->requestedFacilities as $requestedFacility) {
            if ($requestedFacility->facility && $requestedFacility->facility->admins) {
                foreach ($requestedFacility->facility->admins as $admin) {
                    $adminsToNotify->push([
                        'admin_id' => $admin->admin_id,
                        'email' => $admin->email,
                        'name' => $admin->first_name . ' ' . $admin->last_name,
                        'resource_type' => 'facility',
                        'resource_id' => $requestedFacility->facility_id,
                        'resource_name' => $requestedFacility->facility->facility_name ?? 'Unknown Facility'
                    ]);
                }
            }
        }
        
        // Collect admins from services
        foreach ($requisition->requestedServices as $requestedService) {
            if ($requestedService->service && $requestedService->service->admins) {
                foreach ($requestedService->service->admins as $admin) {
                    $adminsToNotify->push([
                        'admin_id' => $admin->admin_id,
                        'email' => $admin->email,
                        'name' => $admin->first_name . ' ' . $admin->last_name,
                        'resource_type' => 'service',
                        'resource_id' => $requestedService->service_id,
                        'resource_name' => $requestedService->service->service_name ?? 'Unknown Service'
                    ]);
                }
            }
        }
        
        // Remove duplicates by admin_id
        $adminsToNotify = $adminsToNotify->unique('admin_id');
        
        // Group by admin to show all resources they're responsible for
        $groupedAdmins = $adminsToNotify->groupBy('admin_id')->map(function ($items, $adminId) {
            $firstItem = $items->first();
            $resources = $items->map(function ($item) {
                return [
                    'type' => $item['resource_type'],
                    'id' => $item['resource_id'],
                    'name' => $item['resource_name']
                ];
            });
            
            return [
                'admin_id' => $adminId,
                'email' => $firstItem['email'],
                'name' => $firstItem['name'],
                'resources' => $resources
            ];
        })->values();
        
        return response()->json([
            'admins' => $groupedAdmins,
            'total_admins' => $groupedAdmins->count(),
            'facilities_count' => $requisition->requestedFacilities->count(),
            'services_count' => $requisition->requestedServices->count(),
            'request_details' => [
                'request_id' => $requisition->request_id,
                'title' => $requisition->calendar_title,
                'requester' => $requisition->first_name . ' ' . $requisition->last_name
            ]
        ]);
    }

    /**
     * Get breakdown of which resources need approvals
     */
    public function getResourceApprovalBreakdown($requestId)
    {
        $requisition = RequisitionForm::with([
            'requestedFacilities.facility.admins',
            'requestedServices.service.admins'
        ])->findOrFail($requestId);
        
        // Get current approvals
        $approvedAdminIds = $requisition->requisitionApprovals()
            ->whereNotNull('approved_by')
            ->pluck('approved_by')
            ->unique();
        
        // Process facilities
        $facilityBreakdown = $requisition->requestedFacilities->map(function ($requestedFacility) use ($approvedAdminIds) {
            $facility = $requestedFacility->facility;
            $admins = $facility ? $facility->admins : collect();
            
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
                'total_admins' => $admins->count(),
                'approved_admins' => $admins->whereIn('admin_id', $approvedAdminIds)->count(),
                'admin_details' => $approvalDetails
            ];
        });
        
        // Process services
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
        
        return response()->json([
            'facilities' => $facilityBreakdown,
            'services' => $serviceBreakdown,
            'summary' => [
                'total_resources' => $facilityBreakdown->count() + $serviceBreakdown->count(),
                'total_facilities' => $facilityBreakdown->count(),
                'total_services' => $serviceBreakdown->count(),
                'fully_approved_facilities' => $facilityBreakdown->where('total_admins', '>', 0)->where('approved_admins', '>=', function($item) {
                    return $item['total_admins'];
                })->count(),
                'fully_approved_services' => $serviceBreakdown->where('total_admins', '>', 0)->where('approved_admins', '>=', function($item) {
                    return $item['total_admins'];
                })->count()
            ]
        ]);
    }
}