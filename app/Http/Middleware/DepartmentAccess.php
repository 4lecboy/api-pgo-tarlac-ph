<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DepartmentAccess
{
    public function handle(Request $request, Closure $next, ...$departments)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated (DepartmentAccess)'], 401);
        }

        // Super Admin has all access
        if ($user->isSuperAdmin()) {
             return $next($request);
        }

        // Normalize department names
        $userDept = strtolower($user->department ?? '');
        $allowed = array_map('strtolower', $departments);

        if (!in_array($userDept, $allowed)) {
            return response()->json([
                'message' => 'Forbidden: Access denied for your department',
                'user_department' => $user->department,
                'required_departments' => $departments
            ], 403);
        }

        return $next($request);
    }
}
