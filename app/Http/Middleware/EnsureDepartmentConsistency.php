<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDepartmentConsistency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user) {
             return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Super Admin can do anything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if request has 'department' field
        if ($request->has('department')) {
            $requestedDept = $request->input('department');
            // Compare department values case-insensitively
            if (strtolower($requestedDept) !== strtolower($user->department)) {
                return response()->json([
                    'message' => 'You cannot assign records or users to another department.',
                ], 403);
            }
        }

        return $next($request);
    }
}
