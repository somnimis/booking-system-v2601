<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DepartmentRole;
use Illuminate\Http\JsonResponse;

class DepartmentRoleController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $roles = DepartmentRole::select('role_id', 'role_name', 'description')
                ->orderBy('role_name')
                ->get();
            return response()->json(['success' => true, 'data' => $roles]);
        } catch (\Exception $e) {
            \Log::error('Error fetching department roles', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch department roles'], 500);
        }
    }
}