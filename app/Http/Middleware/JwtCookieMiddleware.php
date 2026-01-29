<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JwtCookieMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Force Accept JSON to prevent redirects
        $request->headers->set('Accept', 'application/json');

        if ($request->hasCookie('token')) {
            $token = $request->cookie('token');
            // Check if Authorization header is already present
            if (!$request->hasHeader('Authorization')) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        } else {
            // Log for debugging (optional, can be removed later)
            // \Log::info('JwtCookieMiddleware: No token cookie found.');
        }

        return $next($request);
    }
}
