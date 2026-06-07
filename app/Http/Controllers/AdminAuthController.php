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

            // Clear old tokens
            $admin->tokens()->delete();
            
            // Create new token
            $token = $admin->createToken('admin-token', ['*'], now()->addDays(30))->plainTextToken;

            // Cache profile
            Cache::put("admin_profile_{$admin->admin_id}", $admin->load('role'), now()->addDays(30));

            // DO NOT use auth('sanctum')->login() - it doesn't exist!
            // Just return the token - the frontend will store it

            return response()->json([
                'success' => true,
                'token' => $token,
                'admin' => $admin->makeHidden('hashed_password')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        $admin = $request->user();
        
        if (!$admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $userId = $admin->admin_id;
        
        $profileData = Cache::remember("admin_profile_{$userId}", 2592000, function () use ($admin) {
            return $admin->load('role');
        });
        
        if ($request->expectsJson()) {
            return response()->json($profileData);
        }
        
        return view('admin.admin-profile', ['currentAdminData' => $profileData]);
    }

    public function logout(Request $request)
    {
        $admin = $request->user();
        
        if ($admin) {
            $userId = $admin->admin_id;
            Cache::forget("admin_profile_{$userId}");
            $admin->currentAccessToken()->delete();
        }
        
        return response()->json(['message' => 'Logged out']);
    }
}