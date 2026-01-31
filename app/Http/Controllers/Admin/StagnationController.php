<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseProgressSnapshot;
use App\Models\DailyUserMetric;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class StagnationController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $inactiveDays = $request->input('inactive_days', config('notifications_lms.stagnation.inactive_days', 3));
        $courseId = $request->input('course_id');

        // Get stagnant users (last_activity_at older than threshold OR no recent activity)
        $threshold = now()->subDays($inactiveDays);

        $query = User::query()
            ->where('users.is_admin', false) // Exclude admins
            ->where(function ($q) use ($threshold) {
                $q->whereHas('latestMetric', function ($metricQuery) use ($threshold) {
                    $metricQuery->where('last_activity_at', '<', $threshold);
                })
                ->orWhereDoesntHave('latestMetric')
                ->orWhere('users.last_active_at', '<', $threshold)
                ->orWhereNull('users.last_active_at');
            });

        // Filter by course if provided
        if ($courseId) {
            $query->whereHas('enrollments', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        // Get all users and batch load snapshots
        $users = $query->with(['enrollments.course', 'latestMetric'])
            ->get();

        // Batch load all course progress snapshots
        $userIds = $users->pluck('id')->toArray();
        $snapshots = CourseProgressSnapshot::whereIn('user_id', $userIds)
            ->with('course')
            ->get()
            ->groupBy('user_id')
            ->map(function ($snapshots) {
                return $snapshots->sortByDesc('updated_at')->first();
            });

        // Sort users by last activity (only once)
        $users = $users->sortBy(function ($user) {
            return $user->latestMetric?->last_activity_at
                ?? $user->last_active_at
                ?? $user->created_at;
        })->values();

        // Transform users with pre-loaded snapshots
        $users = $users->map(function (User $user) use ($snapshots) {
            $latestSnapshot = $snapshots->get($user->id);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'last_active_at' => $user->last_active_at?->diffForHumans(),
                'last_activity_at' => $user->latestMetric?->last_activity_at?->diffForHumans(),
                'stagnation_score' => $user->latestMetric?->stagnation_score ?? 0,
                'current_course' => $latestSnapshot ? [
                    'id' => $latestSnapshot->course->id,
                    'title' => $latestSnapshot->course->title,
                    'progress' => $latestSnapshot->lessons_total > 0
                        ? round(($latestSnapshot->lessons_completed / $latestSnapshot->lessons_total) * 100)
                        : 0,
                    'lessons_completed' => $latestSnapshot->lessons_completed,
                    'lessons_total' => $latestSnapshot->lessons_total,
                    'blocked_by' => $latestSnapshot->blocked_by,
                ] : null,
            ];
        });

        // Manual pagination
        $page = $request->input('page', 1);
        $perPage = 20;
        $total = $users->count();
        $items = $users->forPage($page, $perPage)->values();
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Get courses for filter dropdown
        $courses = \App\Models\Course::select('id', 'title')->orderBy('title')->get();

        return Inertia::render('Admin/Analytics/Stagnation', [
            'users' => $paginated,
            'courses' => $courses,
            'filters' => [
                'inactive_days' => $inactiveDays,
                'course_id' => $courseId,
            ],
        ]);
    }
}
