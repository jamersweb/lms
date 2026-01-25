<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the student dashboard with real data.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get enrolled courses with progress
        $enrollments = $user->enrollments()
            ->with('course.modules.lessons')
            ->get();
        
        // Calculate stats
        $stats = [
            'courses_enrolled' => $enrollments->count(),
            'lessons_watched' => $user->lessonProgress()->whereNotNull('completed_at')->count(),
            'current_streak' => $this->getCurrentStreak($user),
        ];
        
        // Get recent activity (last 5 completed lessons)
        $recentActivity = $user->lessonProgress()
            ->with('lesson.module.course')
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit(5)
            ->get()
            ->map(function($progress) {
                return [
                    'id' => $progress->id,
                    'type' => 'lesson',
                    'title' => $progress->lesson->title,
                    'course' => $progress->lesson->module->course->title,
                    'completed_at' => $progress->completed_at->diffForHumans()
                ];
            });
        
        // Get continue learning (most recent incomplete course)
        $continueLearning = null;
        foreach ($enrollments as $enrollment) {
            $course = $enrollment->course;
            $totalLessons = $course->modules->flatMap->lessons->count();
            
            if ($totalLessons === 0) continue;
            
            $completedLessons = $user->lessonProgress()
                ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
                ->whereNotNull('completed_at')
                ->count();
            
            $progress = round(($completedLessons / $totalLessons) * 100);
            
            if ($progress < 100) {
                // Find next incomplete lesson
                $nextLesson = $course->modules->flatMap->lessons
                    ->first(function($lesson) use ($user) {
                        return !$user->lessonProgress()
                            ->where('lesson_id', $lesson->id)
                            ->whereNotNull('completed_at')
                            ->exists();
                    });
                
                $continueLearning = [
                    'id' => $course->id,
                    'title' => $course->title,
                    'current_lesson' => $nextLesson ? $nextLesson->title : 'Start first lesson',
                    'progress' => $progress,
                    'image' => $course->thumbnail ?? 'https://ui-avatars.com/api/?name=' . urlencode(substr($course->title, 0, 2)) . '&background=059669&color=fff&size=400',
                ];
                break;
            }
        }
        
        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'continue_learning' => $continueLearning
        ]);
    }
    
    /**
     * Calculate current habit streak for user.
     */
    private function getCurrentStreak($user)
    {
        $habitLogs = $user->habitLogs()
            ->whereDate('log_date', '>=', now()->subDays(30))
            ->orderBy('log_date', 'desc')
            ->get();
        
        if ($habitLogs->isEmpty()) {
            return 0;
        }
        
        $streak = 0;
        $currentDate = now()->startOfDay();
        
        foreach ($habitLogs->groupBy(fn($log) => $log->log_date) as $date => $logs) {
            if ($currentDate->format('Y-m-d') === $date || $currentDate->subDay()->format('Y-m-d') === $date) {
                $streak++;
                $currentDate = \Carbon\Carbon::parse($date)->startOfDay();
            } else {
                break;
            }
        }
        
        return $streak;
    }
}
