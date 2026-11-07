<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class RequireAdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->query('token') 
            ?? $request->cookie('admin_token') 
            ?? $request->session()->get('admin_token') 
            ?? $request->bearerToken();

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable_type === 'App\Models\Admin') {
                auth('sanctum')->setUser($accessToken->tokenable);

                if ($request->query('token')) {
                    cookie()->queue('admin_token', $token, 60 * 24 * 30);
                    $request->session()->put('admin_token', $token);
                }

                return $next($request);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return redirect('/admin/login');
    }
}
