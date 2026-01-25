<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        
        $courses = Course::with('modules.lessons')
            ->get()
            ->map(function($course) use ($user) {
                $lessonsCount = $course->modules->flatMap->lessons->count();
                $totalDuration = $course->modules->flatMap->lessons->sum('duration_seconds');
                
                // Calculate progress if enrolled
                $progress = 0;
                if ($user->isEnrolledIn($course->id)) {
                    $completedLessons = $user->lessonProgress()
                        ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
                        ->whereNotNull('completed_at')
                        ->count();
                    
                    $progress = $lessonsCount > 0 ? round(($completedLessons / $lessonsCount) * 100) : 0;
                }
                
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'instructor' => $course->instructor,
                    'description' => $course->description,
                    'thumbnail' => $course->thumbnail ?? 'https://ui-avatars.com/api/?name=' . urlencode(substr($course->title, 0, 2)) . '&background=059669&color=fff&size=400',
                    'lessons_count' => $lessonsCount,
                    'duration' => gmdate('H\h i\m', $totalDuration),
                    'level' => $course->level,
                    'progress' => $progress,
                ];
            })->values()->toArray();
        
        return \Inertia\Inertia::render('Courses/Index', [
            'courses' => $courses
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        $user = auth()->user();
        
        $course->load('modules.lessons');
        
        // Check enrollment status
        $isEnrolled = $user->isEnrolledIn($course->id);
        
        // Calculate progress if enrolled
        $progress = 0;
        if ($isEnrolled) {
            $totalLessons = $course->modules->flatMap->lessons->count();
            $completedLessons = $user->lessonProgress()
                ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
                ->whereNotNull('completed_at')
                ->count();
            
            $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
        }
        
        // Format modules with lesson completion status
        $modules = $course->modules->map(function($module) use ($user) {
            return [
                'id' => $module->id,
                'title' => $module->title,
                'lessons' => $module->lessons->map(function($lesson) use ($user) {
                    $isCompleted = $user->lessonProgress()
                        ->where('lesson_id', $lesson->id)
                        ->whereNotNull('completed_at')
                        ->exists();
                    
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'duration' => gmdate('i\m', $lesson->duration_seconds),
                        'is_completed' => $isCompleted,
                        'type' => 'video'
                    ];
                })
            ];
        });
        
        return \Inertia\Inertia::render('Courses/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'instructor' => $course->instructor,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail ?? 'https://ui-avatars.com/api/?name=' . urlencode(substr($course->title, 0, 2)) . '&background=059669&color=fff&size=400',
                'lessons_count' => $course->modules->flatMap->lessons->count(),
                'duration' => gmdate('H\h i\m', $course->modules->flatMap->lessons->sum('duration_seconds')),
                'level' => $course->level,
                'students_count' => $course->enrollments()->count(),
                'is_enrolled' => $isEnrolled,
                'progress' => $progress,
                'modules' => $modules
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        //
    }
    
    /**
     * Enroll the authenticated user in a course.
     */
    public function enroll(Course $course)
    {
        $user = auth()->user();
        
        // Check if already enrolled
        if ($user->isEnrolledIn($course->id)) {
            return back()->with('info', 'You are already enrolled in this course.');
        }
        
        // Create enrollment
        $user->enrollments()->create([
            'course_id' => $course->id,
            'enrolled_at' => now()
        ]);
        
        return redirect()->route('courses.show', $course)
            ->with('success', 'Successfully enrolled in ' . $course->title);
    }
}
