<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use Illuminate\Support\Facades\Cache;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $admin = Admin::where('email', $request->email)->first();

            if (!$admin) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            if (!Hash::check($request->password, $admin->hashed_password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            // Clear old tokens before issuing a new one
            $admin->tokens()->delete();
            
            // Create new token with 30-day expiration
            $token = $admin->createToken('admin-token', ['*'], now()->addDays(30))->plainTextToken;

            // Add cache headers to prevent repeated requests
            return response()->json([
                'token' => $token,
                'admin' => $admin->makeHidden('hashed_password')
            ])->header('Cache-Control', 'private, max-age=3600');
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        // Cache profile data for 5 minutes to reduce database queries
        $userId = $request->user()->admin_id;
        
        $profileData = Cache::remember("admin_profile_{$userId}", 300, function () use ($request) {
            return $request->user()->load('role');
        });
        
        if ($request->expectsJson()) {
            return response()->json([
                'admin' => $profileData
            ])->header('Cache-Control', 'private, max-age=300');
        }
        
        $adminData = $profileData;
        return view('admin.admin-profile', ['currentAdminData' => $adminData]);
    }

    public function logout(Request $request)
    {
        // Clear user cache on logout
        $userId = $request->user()->admin_id;
        Cache::forget("admin_profile_{$userId}");
        
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}