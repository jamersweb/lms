<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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

        // Optimize unread notifications count query to prevent timeouts
        $unreadCount = 0;
        if ($user) {
            try {
                $unreadCount = $user->unreadNotifications()->count();
            } catch (\Exception $e) {
                // Silently fail if database query fails to prevent page timeout
                \Log::warning('Failed to get unread notifications count: ' . $e->getMessage());
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'unread_notifications_count' => $unreadCount,
        ];
    }
}
