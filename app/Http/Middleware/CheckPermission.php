<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * Check if user has required permission.
     * Usage: middleware('permission:admin.users.index') or middleware('permission:admin.users.*')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        if ($this->permissionService->hasPermission($user, $permission)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}
