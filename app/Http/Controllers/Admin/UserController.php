<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Habit;
use App\Models\HabitLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
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

        // Filter by role
        if ($request->role === 'admin') {
            $query->where('is_admin', true);
        } elseif ($request->role === 'user') {
            $query->where('is_admin', false);
        }

        $users = $query->withCount(['enrollments', 'habits'])
            ->latest()
            ->paginate(15)
            ->through(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
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

        // Get habits with streaks
        $habits = $user->habits()
            ->withCount('logs')
            ->get()
            ->map(fn($habit) => [
                'id' => $habit->id,
                'name' => $habit->name,
                'frequency' => $habit->frequency,
                'current_streak' => $habit->current_streak,
                'best_streak' => $habit->best_streak,
                'logs_count' => $habit->logs_count,
                'created_at' => $habit->created_at->format('M d, Y'),
            ]);

        // Calculate total stats
        $totalCoursesEnrolled = $enrollments->count();
        $totalLessonsCompleted = LessonProgress::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->count();
        $totalHabitsCreated = $habits->count();
        $totalHabitLogs = HabitLog::whereIn('habit_id', $user->habits->pluck('id'))->count();

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
        return Inertia::render('Admin/Users/Create');
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
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
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
        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
            ],
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
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_admin = $validated['is_admin'] ?? false;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

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
}
