<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Cache unread count 60s per user to avoid slow query on every request
        $unreadCount = 0;
        $permissions = [];
        $canAccessAdmin = false;

        if ($user) {
            try {
                $unreadCount = (int) Cache::remember('inertia_unread_' . $user->id, 60, function () use ($user) {
                    return $user->unreadNotifications()->count();
                });
            } catch (\Exception $e) {
                \Log::warning('Failed to get unread notifications count: ' . $e->getMessage());
            }

            $permissions = app(PermissionService::class)->getUserPermissionSlugs($user);
            $canAccessAdmin = $user->is_admin || collect($permissions)->contains(fn ($p) => str_starts_with($p, 'admin.'));
        }

        $contentLocale = $user?->content_locale
            ?? $request->session()->get('content_locale')
            ?? app()->getLocale();

        // Ensure auth.user has non-null role/status (prevents Vue "toString of null" in production)
        $authUser = $user ? array_merge($user->toArray(), [
            'role' => $user->role ?? 'student',
            'status' => $user->status ?? 'active',
        ]) : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $authUser,
                'permissions' => $permissions,
                'can_access_admin' => $canAccessAdmin,
            ],
            'locale' => app()->getLocale(),
            'content_locale' => $contentLocale,
            'unread_notifications_count' => $unreadCount,
            'csrf_token' => $request->session()->token(),
        ];
    }
}
