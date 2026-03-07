<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasRole
{
    /**
     * Check if user has required role for the route.
     * Usage: middleware('role:admin,mentor')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        // Admin (is_admin or role=admin) always has access
        if ($user->is_admin || $user->role === 'admin') {
            return $next($request);
        }

        // Check if user's role is in allowed roles
        if (in_array($user->role ?? 'student', $roles)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}
