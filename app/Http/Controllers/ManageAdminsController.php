<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Department;
use App\Models\ExtraService;
use App\Models\RequisitionPurpose;
use Illuminate\Http\Request;

class ManageAdminsController extends Controller
{
/**
     * Get simplified admin listing with pagination
     * GET /api/manage/admins?page=1
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        
        $admins = Admin::select(
            'admin_id',
            'first_name',
            'last_name',
            'middle_name',
            'title',
            'email',
            'contact_number',
            'school_id',
            'role_id'
        )
        ->with(['role' => function($query) {
            $query->select('role_id', 'role_title');
        }])
        ->paginate($perPage);
        
        // Transform the data
        $admins->getCollection()->transform(function($admin) {
            return [
                'admin_id' => $admin->admin_id,
                'full_name' => $this->getFullName($admin),
                'first_name' => $admin->first_name,
                'last_name' => $admin->last_name,
                'middle_name' => $admin->middle_name,
                'title' => $admin->title,
                'email' => $admin->email,
                'contact_number' => $admin->contact_number,
                'school_id' => $admin->school_id,
                'role_id' => $admin->role_id,
                'role_title' => $admin->role ? $admin->role->role_title : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $admins
        ]);
    }

/**
 * Get single admin with all relationships
 * GET /api/manage/admins/{id}
 */
public function show($id)
{
    $admin = Admin::with([
        'role',
        'departments' => function($query) {
            $query->select('departments.department_id', 'departments.department_name', 'departments.department_code')
                  ->withPivot('role_id', 'is_primary');
        },
        'services' => function($query) {
            $query->select('extra_services.service_id', 'extra_services.service_name');
        }
    ])->find($id);

    if (!$admin) {
        return response()->json([
            'success' => false,
            'message' => 'Admin not found'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'admin_id' => $admin->admin_id,
            'full_name' => $this->getFullName($admin),
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'middle_name' => $admin->middle_name,
            'title' => $admin->title,
            'email' => $admin->email,
            'contact_number' => $admin->contact_number,
            'school_id' => $admin->school_id,
            'role_id' => $admin->role_id,
            'role_title' => $admin->role ? $admin->role->role_title : null,
            'departments' => $admin->departments->map(function($dept) {
                return [
                    'department_id' => $dept->department_id,
                    'department_name' => $dept->department_name,
                    'department_code' => $dept->department_code,
                    'is_primary' => $dept->pivot->is_primary ?? false,
                    'role_id' => $dept->pivot->role_id ?? null  // Add this line
                ];
            }),
            'services' => $admin->services->map(function($service) {
                return [
                    'service_id' => $service->service_id,
                    'service_name' => $service->service_name
                ];
            })
        ]
    ]);
}

    /**
     * Get admins grouped by department
     * GET /api/manage/departments/admins
     */
    public function getAdminsByDepartment()
    {
        $departments = Department::with(['admins' => function($query) {
            $query->select(
                'admins.admin_id',
                'admins.first_name',
                'admins.last_name',
                'admins.middle_name',
                'admins.title',
                'admins.email'
            )->with(['role' => function($q) {
                $q->select('role_id', 'role_title');
            }]);
        }])->get();

        $result = $departments->map(function($department) {
            return [
                'department_id' => $department->department_id,
                'department_name' => $department->department_name,
                'department_code' => $department->department_code,
                'admins' => $department->admins->map(function($admin) use ($department) {
                    return [
                        'admin_id' => $admin->admin_id,
                        'full_name' => $this->getFullName($admin),
                        'title' => $admin->title,
                        'email' => $admin->email,
                        'role_title' => $admin->role ? $admin->role->role_title : null,
                        'is_primary' => $admin->pivot->is_primary ?? false
                    ];
                })
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Get departments with their admins (simplified)
     * GET /api/manage/departments
     */
    public function getDepartmentsWithAdmins()
    {
        $departments = Department::with(['admins' => function($query) {
            $query->select('admins.admin_id', 'admins.first_name', 'admins.last_name', 'admins.middle_name');
        }])->get();

        return response()->json([
            'success' => true,
            'data' => $departments->map(function($dept) {
                return [
                    'department_id' => $dept->department_id,
                    'department_name' => $dept->department_name,
                    'department_code' => $dept->department_code,
                    'admin_count' => $dept->admins->count(),
                    'admins' => $dept->admins->map(function($admin) {
                        return [
                            'admin_id' => $admin->admin_id,
                            'full_name' => $this->getFullName($admin)
                        ];
                    })
                ];
            })
        ]);
    }

    /**
     * Get services with their managing department
     * GET /api/manage/services
     */
    public function getServicesWithManager()
    {
        $services = ExtraService::with(['manager' => function($query) {
            $query->select('admins.admin_id', 'admins.first_name', 'admins.last_name', 'admins.middle_name');
        }])->get();

        return response()->json([
            'success' => true,
            'data' => $services->map(function($service) {
                return [
                    'service_id' => $service->service_id,
                    'service_name' => $service->service_name,
                    'managed_by' => $service->managed_by,
                    'service_fee' => $service->service_fee,
                    'account_number' => $service->account_number,
                    'manager' => $service->manager ? [
                        'admin_id' => $service->manager->admin_id,
                        'full_name' => $this->getFullName($service->manager)
                    ] : null
                ];
            })
        ]);
    }

    /**
     * Get purposes with their routed department
     * GET /api/manage/purposes
     */
    public function getPurposesWithRoutes()
    {
        $purposes = RequisitionPurpose::with(['routedAdmin' => function($query) {
            $query->select('admins.admin_id', 'admins.first_name', 'admins.last_name', 'admins.middle_name');
        }])->get();

        return response()->json([
            'success' => true,
            'data' => $purposes->map(function($purpose) {
                return [
                    'purpose_id' => $purpose->purpose_id,
                    'purpose_name' => $purpose->purpose_name,
                    'routes_to' => $purpose->routes_to,
                    'discount_fee' => $purpose->discount_fee,
                    'discount_type' => $purpose->discount_type,
                    'routed_admin' => $purpose->routedAdmin ? [
                        'admin_id' => $purpose->routedAdmin->admin_id,
                        'full_name' => $this->getFullName($purpose->routedAdmin)
                    ] : null
                ];
            })
        ]);
    }

    /**
     * Get complete dashboard data (all relationships in one call)
     * GET /api/manage/dashboard
     */
    public function getDashboardData()
    {
        // Get all admins with their departments and services
        $admins = Admin::with([
            'role',
            'departments' => function($q) {
                $q->select('departments.department_id', 'departments.department_name', 'departments.department_code');
            },
            'services' => function($q) {
                $q->select('extra_services.service_id', 'extra_services.service_name');
            }
        ])->get();

        // Get all departments with their admins
        $departments = Department::with(['admins' => function($q) {
            $q->select('admins.admin_id', 'admins.first_name', 'admins.last_name', 'admins.middle_name');
        }])->get();

        // Get services with manager
        $services = ExtraService::with(['manager' => function($q) {
            $q->select('admins.admin_id', 'admins.first_name', 'admins.last_name', 'admins.middle_name');
        }])->get();

        // Get purposes with routes
        $purposes = RequisitionPurpose::with(['routedAdmin' => function($q) {
            $q->select('admins.admin_id', 'admins.first_name', 'admins.last_name', 'admins.middle_name');
        }])->get();

        return response()->json([
            'success' => true,
            'data' => [
                'admins' => $admins->map(function($admin) {
                    return [
                        'admin_id' => $admin->admin_id,
                        'full_name' => $this->getFullName($admin),
                        'title' => $admin->title,
                        'email' => $admin->email,
                        'role_title' => $admin->role ? $admin->role->role_title : null,
                        'departments' => $admin->departments->map(function($dept) {
                            return [
                                'department_id' => $dept->department_id,
                                'department_name' => $dept->department_name
                            ];
                        }),
                        'services' => $admin->services->map(function($service) {
                            return [
                                'service_id' => $service->service_id,
                                'service_name' => $service->service_name
                            ];
                        })
                    ];
                }),
                'departments' => $departments->map(function($dept) {
                    return [
                        'department_id' => $dept->department_id,
                        'department_name' => $dept->department_name,
                        'department_code' => $dept->department_code,
                        'admin_count' => $dept->admins->count()
                    ];
                }),
                'services' => $services->map(function($service) {
                    return [
                        'service_id' => $service->service_id,
                        'service_name' => $service->service_name,
                        'service_fee' => $service->service_fee,
                        'manager' => $service->manager ? $this->getFullName($service->manager) : null
                    ];
                }),
                'purposes' => $purposes->map(function($purpose) {
                    return [
                        'purpose_id' => $purpose->purpose_id,
                        'purpose_name' => $purpose->purpose_name,
                        'discount_fee' => $purpose->discount_fee,
                        'discount_type' => $purpose->discount_type,
                        'routes_to_admin' => $purpose->routedAdmin ? $this->getFullName($purpose->routedAdmin) : null
                    ];
                })
            ]
        ]);
    }

    /**
     * Helper method to get full name
     */
    private function getFullName($admin)
    {
        $middle = $admin->middle_name ? $admin->middle_name . ' ' : '';
        return trim($admin->first_name . ' ' . $middle . $admin->last_name);
    }

    /**
 * Get all static data in ONE API call (departments, services, purposes, roles)
 * GET /api/manage/static-data
 */
public function getStaticData()
{
    // Get all departments with their admins (for departments tab)
    $departments = Department::with(['admins' => function($query) {
        $query->select(
            'admins.admin_id',
            'admins.first_name',
            'admins.last_name',
            'admins.middle_name',
            'admins.title',
            'admins.email'
        )->with(['role' => function($q) {
            $q->select('role_id', 'role_title');
        }]);
    }])->get();

    // Get services with manager
    $services = ExtraService::with(['manager' => function($query) {
        $query->select('admins.admin_id', 'admins.first_name', 'admins.last_name', 'admins.middle_name');
    }])->get();

    // Get purposes with routes
    $purposes = RequisitionPurpose::with(['routedAdmin' => function($query) {
        $query->select('admins.admin_id', 'admins.first_name', 'admins.last_name', 'admins.middle_name');
    }])->get();

    // Get roles
    $roles = \App\Models\LookupTables\AdminRole::all();

    // Get departments list for checkboxes (simplified)
    $departmentsList = Department::select('department_id', 'department_name', 'department_code')->get();

    return response()->json([
        'success' => true,
        'data' => [
            'departments' => $departments->map(function($department) {
                return [
                    'department_id' => $department->department_id,
                    'department_name' => $department->department_name,
                    'department_code' => $department->department_code,
                    'admins' => $department->admins->map(function($admin) {
                        return [
                            'admin_id' => $admin->admin_id,
                            'full_name' => $this->getFullName($admin),
                            'title' => $admin->title,
                            'email' => $admin->email,
                            'role_title' => $admin->role ? $admin->role->role_title : null,
                            'is_primary' => $admin->pivot->is_primary ?? false
                        ];
                    })
                ];
            }),
            'services' => $services->map(function($service) {
                return [
                    'service_id' => $service->service_id,
                    'service_name' => $service->service_name,
                    'managed_by' => $service->managed_by,
                    'service_fee' => $service->service_fee,
                    'account_number' => $service->account_number,
                    'manager' => $service->manager ? [
                        'admin_id' => $service->manager->admin_id,
                        'full_name' => $this->getFullName($service->manager)
                    ] : null
                ];
            }),
            'purposes' => $purposes->map(function($purpose) {
                return [
                    'purpose_id' => $purpose->purpose_id,
                    'purpose_name' => $purpose->purpose_name,
                    'routes_to' => $purpose->routes_to,
                    'discount_fee' => $purpose->discount_fee,
                    'discount_type' => $purpose->discount_type,
                    'routed_admin' => $purpose->routedAdmin ? [
                        'admin_id' => $purpose->routedAdmin->admin_id,
                        'full_name' => $this->getFullName($purpose->routedAdmin)
                    ] : null
                ];
            }),
            'roles' => $roles->map(function($role) {
                return [
                    'role_id' => $role->role_id,
                    'role_title' => $role->role_title
                ];
            }),
            'departments_list' => $departmentsList->map(function($dept) {
                return [
                    'department_id' => $dept->department_id,
                    'department_name' => $dept->department_name,
                    'department_code' => $dept->department_code
                ];
            })
        ]
    ]);
}
}