<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AdminApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Check for token in Authorization header (API calls)
        if ($token = $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable_type === 'App\Models\Admin') {
                auth('sanctum')->setUser($accessToken->tokenable);
                return $next($request);
            }
        }
        
        // Check for token in query parameter (web page loads)
        if ($token = $request->query('token')) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable_type === 'App\Models\Admin') {
                auth('sanctum')->setUser($accessToken->tokenable);
                // Store token in cookie for future requests
                cookie()->queue('admin_token', $token, 60 * 24 * 30);
                return $next($request);
            }
        }
        
        // Check for token in cookie
        if ($token = $request->cookie('admin_token')) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable_type === 'App\Models\Admin') {
                auth('sanctum')->setUser($accessToken->tokenable);
                return $next($request);
            }
        }
        
        // Check for token in session
        if ($token = $request->session()->get('admin_token')) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable_type === 'App\Models\Admin') {
                auth('sanctum')->setUser($accessToken->tokenable);
                return $next($request);
            }
        }
        
        // No valid token found - return JSON response for API calls, redirect for web
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login again.'
            ], 401);
        }
        
        // For web requests, redirect to login
        return redirect('/admin/login');
    }
}