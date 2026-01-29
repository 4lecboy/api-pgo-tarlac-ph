<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth('api')->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = auth('api')->user();

        // Super Admin has all access
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has one of the required roles
        // We will accept roles as parameters, e.g. 'admin', 'user', 'viewer'
        // Logic: if current user's role is NOT in the allowed list, deny.
        
        // Normalize roles to lowercase for comparison
        $allowedRoles = array_map('strtolower', $roles);
        $userRoleValue = $user->role instanceof \App\Enums\UserRole ? $user->role->value : $user->role;
        $userRole = strtolower($userRoleValue);

        if (!in_array($userRole, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized. You do not have the required role.'], 403);
        }

        return $next($request);
    }
}
