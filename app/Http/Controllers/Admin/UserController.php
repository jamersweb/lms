<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Habit;
use App\Models\HabitLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function __construct(
        protected PermissionService $permissionService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        // Filter by role (admin = admins only, user = non-admins)
        if ($request->role === 'admin') {
            $adminRoleId = Role::where('slug', 'admin')->value('id');
            $query->where(function ($q) use ($adminRoleId) {
                $q->where('is_admin', true);
                if ($adminRoleId) {
                    $q->orWhere('role_id', $adminRoleId);
                }
            });
        } elseif ($request->role === 'user') {
            $adminRoleId = Role::where('slug', 'admin')->value('id');
            $query->where('is_admin', false);
            if ($adminRoleId) {
                $query->where(function ($q) use ($adminRoleId) {
                    $q->whereNull('role_id')->orWhere('role_id', '!=', $adminRoleId);
                });
            }
        }

        $users = $query->withCount(['enrollments', 'habits'])
            ->latest()
            ->paginate(15)
            ->through(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'role' => $user->role ?? 'student',
                'status' => $user->status ?? 'active',
                'enrollments_count' => $user->enrollments_count,
                'habits_count' => $user->habits_count,
                'created_at' => $user->created_at->format('M d, Y'),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->search,
                'role' => $request->role,
            ],
        ]);
    }

    /**
     * Display user profile with detailed stats.
     */
    public function show(User $user)
    {
        // Get enrollments with progress
        $enrollments = $user->enrollments()
            ->with(['course.modules.lessons'])
            ->get()
            ->map(function($enrollment) use ($user) {
                $course = $enrollment->course;
                $totalLessons = $course->modules->flatMap->lessons->count();
                $completedLessons = $user->lessonProgress()
                    ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
                    ->whereNotNull('completed_at')
                    ->count();

                return [
                    'id' => $enrollment->id,
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'enrolled_at' => $enrollment->created_at->format('M d, Y'),
                    'total_lessons' => $totalLessons,
                    'completed_lessons' => $completedLessons,
                    'progress' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0,
                ];
            });

        // Habits available to this user: legacy (user_id) + lesson-based (user completed lesson)
        $completedLessonIds = LessonProgress::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id');

        $habitsQuery = Habit::where('is_active', true)
            ->where(function($q) use ($user, $completedLessonIds) {
                $q->where('user_id', $user->id);
                if ($completedLessonIds->isNotEmpty()) {
                    $q->orWhere(fn($sq) => $sq->whereIn('lesson_id', $completedLessonIds)->whereNull('user_id'));
                }
            })
            ->with(['lesson']);

        $habits = $habitsQuery->get()->map(function($habit) use ($user) {
            $streaks = $this->habitStreaksForUser($habit, $user);
            $logsCount = $habit->logs()->where('user_id', $user->id)->count();

            return [
                'id' => $habit->id,
                'title' => $habit->title,
                'frequency_type' => $habit->frequency_type,
                'current_streak' => $streaks['current'],
                'best_streak' => $streaks['longest'],
                'logs_count' => $logsCount,
                'lesson_title' => $habit->lesson?->title,
                'created_at' => $habit->created_at->format('M d, Y'),
            ];
        });

        // Calculate total stats
        $totalCoursesEnrolled = $enrollments->count();
        $totalLessonsCompleted = LessonProgress::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->count();
        $totalHabitsCreated = $habits->count();
        $totalHabitLogs = HabitLog::where('user_id', $user->id)
            ->whereIn('habit_id', $habits->pluck('id'))
            ->count();

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'gender' => $user->gender,
                'has_bayah' => $user->has_bayah,
                'level' => $user->level,
                'whatsapp_number' => $user->whatsapp_number,
                'whatsapp_opt_in' => $user->whatsapp_opt_in,
                'last_active_at' => $user->last_active_at?->format('M d, Y H:i'),
                'created_at' => $user->created_at->format('M d, Y'),
                'email_verified_at' => $user->email_verified_at?->format('M d, Y'),
            ],
            'stats' => [
                'totalCoursesEnrolled' => $totalCoursesEnrolled,
                'totalLessonsCompleted' => $totalLessonsCompleted,
                'totalHabitsCreated' => $totalHabitsCreated,
                'totalHabitLogs' => $totalHabitLogs,
            ],
            'enrollments' => $enrollments,
            'habits' => $habits,
        ]);
    }

    /**
     * Show form for creating a new user.
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get(['id', 'name', 'slug']);
        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles,
            'statuses' => config('roles.statuses', ['active', 'inactive', 'suspended']),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_admin' => 'boolean',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
            'role_id' => $validated['role_id'],
            'status' => $validated['status'] ?? 'active',
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show form for editing user.
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get(['id', 'name', 'slug']);
        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'role' => $user->role ?? 'student',
                'role_id' => $user->role_id,
                'status' => $user->status ?? 'active',
            ],
            'roles' => $roles,
            'statuses' => config('roles.statuses', ['active', 'inactive', 'suspended']),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'is_admin' => 'boolean',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,inactive,suspended',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_admin = $validated['is_admin'] ?? false;
        $user->role_id = $validated['role_id'];
        $user->status = $validated['status'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $this->permissionService->clearUserCache($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle admin status.
     */
    public function toggleAdmin(User $user)
    {
        // Prevent removing own admin status
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own admin status.');
        }

        $user->is_admin = !$user->is_admin;
        $user->save();

        $status = $user->is_admin ? 'granted' : 'revoked';
        return back()->with('success', "Admin privileges {$status} for {$user->name}.");
    }

    /**
     * Calculate current and longest streak for a user's habit logs.
     */
    private function habitStreaksForUser(Habit $habit, User $user): array
    {
        $logs = $habit->logs()
            ->where('user_id', $user->id)
            ->where('status', 'done')
            ->orderBy('log_date', 'desc')
            ->get();

        if ($logs->isEmpty()) {
            return ['current' => 0, 'longest' => 0];
        }

        $currentStreak = 0;
        $currentDate = Carbon::today()->startOfDay();

        foreach ($logs->groupBy(fn($log) => $log->log_date->format('Y-m-d')) as $date => $dayLogs) {
            if ($currentDate->format('Y-m-d') === $date || $currentDate->copy()->subDay()->format('Y-m-d') === $date) {
                $currentStreak++;
                $currentDate = Carbon::parse($date)->startOfDay();
            } else {
                break;
            }
        }

        $longestStreak = 1;
        $tempStreak = 1;
        $prevDate = Carbon::parse($logs->first()->log_date)->startOfDay();

        foreach ($logs->skip(1) as $log) {
            $logDate = Carbon::parse($log->log_date)->startOfDay();
            if ($prevDate->diffInDays($logDate) === 1) {
                $tempStreak++;
            } else {
                $longestStreak = max($longestStreak, $tempStreak);
                $tempStreak = 1;
            }
            $prevDate = $logDate;
        }
        $longestStreak = max($longestStreak, $tempStreak);

        return ['current' => $currentStreak, 'longest' => $longestStreak];
    }
}
