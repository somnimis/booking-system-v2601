<?php

namespace App\Http\Controllers\Dropdowns;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
public function getDropdown(): JsonResponse
{
    try {
        $departments = Department::select('department_id', 'department_name', 'department_code')
            ->orderBy('department_name')
            ->get();
        return response()->json($departments);
    } catch (\Exception $e) {
        \Log::error('Error fetching departments for dropdown', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch departments'
        ], 500);
    }
}
    public function index(): JsonResponse
    {
        try {
            $departments = Department::select('department_id', 'department_name', 'department_code')
                ->with([
                    'admins' => function ($query) {
                        $query->select('admins.admin_id', 'admins.first_name', 'admins.last_name', 'admins.title')
                            ->withPivot('role_id', 'is_primary');
                    }
                ])
                ->get();

            // Load roles separately to avoid N+1
            $roleIds = $departments->flatMap(function ($dept) {
                return $dept->admins->pluck('pivot.role_id');
            })->unique()->filter();

            $roles = DepartmentRole::whereIn('role_id', $roleIds)
                ->pluck('role_name', 'role_id');

            // Attach role names to each admin
            $departments->each(function ($dept) use ($roles) {
                $dept->admins->each(function ($admin) use ($roles) {
                    $admin->pivot->role_name = $roles->get($admin->pivot->role_id);
                    unset($admin->pivot->role_id);
                });
            });

            return response()->json($departments);
        } catch (\Exception $e) {
            \Log::error('Error fetching departments', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch departments'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $department = Department::find($id);
            if (!$department) {
                return response()->json(['message' => 'Department not found'], 404);
            }
            return response()->json(['data' => $department]);
        } catch (\Exception $e) {
            \Log::error('Error fetching department', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch department'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'department_name' => 'required|string|max:80',
            'department_code' => 'nullable|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $department = Department::create([
                'department_name' => $request->department_name,
                'department_code' => $request->department_code
            ]);

            return response()->json(['data' => $department], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating department', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create department'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $department = Department::find($id);
        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'department_name' => 'required|string|max:80',
            'department_code' => 'nullable|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $department->update([
                'department_name' => $request->department_name,
                'department_code' => $request->department_code
            ]);

            return response()->json(['data' => $department]);
        } catch (\Exception $e) {
            \Log::error('Error updating department', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update department'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $department = Department::find($id);
        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        try {
            $department->delete();
            return response()->json(['message' => 'Department deleted successfully']);
        } catch (\Exception $e) {
            \Log::error('Error deleting department', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete department'], 500);
        }
    }
}