<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DepartmentAccess
{
    public function handle(Request $request, Closure $next, ...$departments)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Normalize department names
        $userDept = strtolower($user->department);
        $allowed = array_map('strtolower', $departments);

        if (!in_array($userDept, $allowed)) {
            return response()->json(['error' => 'Forbidden: Access denied for your department'], 403);
        }

        return $next($request);
    }
}
