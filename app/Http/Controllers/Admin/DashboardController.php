<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Models\Habit;
use App\Models\Discussion;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Key metrics
        $totalUsers = User::count();
        $totalAdmins = User::where('is_admin', true)->count();
        $totalCourses = Course::count();
        $totalLessons = Lesson::count();
        $totalEnrollments = Enrollment::count();
        $totalHabits = Habit::count();
        $totalDiscussions = Discussion::count();
        
        // Recent activity (last 30 days)
        $newUsersThisMonth = User::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $newEnrollmentsThisMonth = Enrollment::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        
        // Course stats with enrollment counts
        $popularCourses = Course::withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->take(5)
            ->get()
            ->map(fn($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'enrollments_count' => $course->enrollments_count,
            ]);
        
        // Recent users
        $recentUsers = User::latest()
            ->take(5)
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at->diffForHumans(),
            ]);
        
        // Recent enrollments
        $recentEnrollments = Enrollment::with(['user', 'course'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($enrollment) => [
                'id' => $enrollment->id,
                'user_name' => $enrollment->user->name,
                'course_title' => $enrollment->course->title,
                'enrolled_at' => $enrollment->created_at->diffForHumans(),
            ]);
        
        // Completion stats
        $completedLessons = LessonProgress::whereNotNull('completed_at')->count();
        $averageProgress = $totalLessons > 0 
            ? round(($completedLessons / ($totalLessons * max($totalEnrollments, 1))) * 100, 1)
            : 0;

        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'totalAdmins' => $totalAdmins,
                'totalCourses' => $totalCourses,
                'totalLessons' => $totalLessons,
                'totalEnrollments' => $totalEnrollments,
                'totalHabits' => $totalHabits,
                'totalDiscussions' => $totalDiscussions,
                'newUsersThisMonth' => $newUsersThisMonth,
                'newEnrollmentsThisMonth' => $newEnrollmentsThisMonth,
                'completedLessons' => $completedLessons,
                'averageProgress' => $averageProgress,
            ],
            'popularCourses' => $popularCourses,
            'recentUsers' => $recentUsers,
            'recentEnrollments' => $recentEnrollments,
        ]);
    }
}
