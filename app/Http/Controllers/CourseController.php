<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\EligibilityService;
use App\Services\JourneyService;
use App\Services\ProgressionService;
use App\Support\LockMessage;
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
        $eligibilityService = app(EligibilityService::class);
        $showLockedCourses = config('lms.show_locked_courses', true);

        $courses = Course::with('contentRule', 'modules.lessons.contentRule')
            ->get()
            ->map(function($course) use ($user, $eligibilityService, $showLockedCourses) {
                // Check course-level eligibility
                $courseResult = $eligibilityService->canAccessCourse($user, $course);

                // If hiding locked courses and course is locked, return null
                if (!$showLockedCourses && !$courseResult->allowed) {
                    return null;
                }

                $allLessons = $course->modules->flatMap->lessons;

                // Filter lessons by eligibility
                $visibleLessons = $allLessons->filter(function (Lesson $lesson) use ($user, $eligibilityService) {
                    $lessonResult = $eligibilityService->canAccessLesson($user, $lesson);
                    return $lessonResult->allowed;
                });

                // If hiding locked courses and no accessible lessons, hide course
                if (!$showLockedCourses && $visibleLessons->isEmpty() && !$allLessons->isEmpty()) {
                    return null;
                }

                $lessonsCount = $visibleLessons->count();
                $totalDuration = $visibleLessons->sum('duration_seconds');

                // Calculate progress if enrolled
                $progress = 0;
                if ($user->isEnrolledIn($course->id)) {
                    $completedLessons = $user->lessonProgress()
                        ->whereIn('lesson_id', $visibleLessons->pluck('id'))
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
                    'is_locked' => !$courseResult->allowed,
                    'lock_reasons' => $courseResult->reasons,
                    'lock_message' => LockMessage::fromEligibility($courseResult),
                    'required_level' => $courseResult->requiredLevel,
                    'required_gender' => $courseResult->requiredGender,
                    'requires_bayah' => $courseResult->requiresBayah,
                ];
            })
            ->filter()
            ->values()
            ->toArray();

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
        $eligibilityService = app(EligibilityService::class);
        $progressionService = app(ProgressionService::class);

        $course->load('contentRule', 'modules.contentRule', 'modules.lessons.contentRule', 'modules.lessons.task');

        // Check course-level eligibility
        $courseResult = $eligibilityService->canAccessCourse($user, $course);

        // Check enrollment status
        $isEnrolled = $user->isEnrolledIn($course->id);

        // Calculate progress if enrolled
        $progress = 0;
        $allLessons = $course->modules->flatMap->lessons;
        $visibleLessons = $allLessons->filter(function (Lesson $lesson) use ($user, $eligibilityService) {
            $lessonResult = $eligibilityService->canAccessLesson($user, $lesson);
            return $lessonResult->allowed;
        });

        if ($isEnrolled) {
            $totalLessons = $visibleLessons->count();
            $completedLessons = $user->lessonProgress()
                ->whereIn('lesson_id', $visibleLessons->pluck('id'))
                ->whereNotNull('completed_at')
                ->count();

            $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
        }

        // Get next lesson for "Continue" button
        $nextLesson = null;
        if ($user && $user->isEnrolledIn($course->id)) {
            foreach ($course->modules as $module) {
                $nextInModule = $progressionService->getNextLessonInModule($user, $module);
                if ($nextInModule) {
                    $nextLesson = [
                        'id' => $nextInModule->id,
                        'title' => $nextInModule->title,
                        'url' => route('lessons.show', ['course' => $course->id, 'lesson' => $nextInModule->id]),
                    ];
                    break; // Use first module's next lesson
                }
            }
        }

        // Get release schedule service for drip release info
        $releaseScheduleService = app(\App\Services\ReleaseScheduleService::class);

        // Format modules with lesson completion status and lock metadata
        $modules = $course->modules->map(function($module) use ($user, $course, $eligibilityService, $progressionService, $releaseScheduleService) {
            $moduleResult = $eligibilityService->canAccessModule($user, $module);

            // Get first incomplete lesson in this module for "is_next" flag
            $firstIncomplete = $user && $user->isEnrolledIn($course->id)
                ? $progressionService->getFirstIncompleteLessonInModule($user, $module)
                : null;

            return [
                'id' => $module->id,
                'title' => $module->title,
                'is_locked' => !$moduleResult->allowed,
                'lock_reasons' => $moduleResult->reasons,
                'lock_message' => LockMessage::fromEligibility($moduleResult),
                'lessons' => $module->lessons->map(function($lesson) use ($user, $eligibilityService, $progressionService, $firstIncomplete, $releaseScheduleService) {
                    // Use ProgressionService for combined eligibility + sequential check
                    $lessonResult = $progressionService->canAccessLesson($user, $lesson);
                    $isCompleted = $user && $user->lessonProgress()
                        ->where('lesson_id', $lesson->id)
                        ->whereNotNull('completed_at')
                        ->exists();

                    $isNext = $firstIncomplete && $firstIncomplete->id === $lesson->id;

                    // Get task info for previous lesson (if this lesson is locked due to task)
                    $taskInfo = null;
                    if (in_array('task_incomplete', $lessonResult->reasons) && $user) {
                        $previousLesson = $progressionService->getPreviousLesson($lesson);
                        if ($previousLesson && $previousLesson->task) {
                            $taskProgress = \App\Models\TaskProgress::where('task_id', $previousLesson->task->id)
                                ->where('user_id', $user->id)
                                ->first();

                            $taskInfo = [
                                'required_days' => $previousLesson->task->required_days,
                                'days_done' => $taskProgress ? $taskProgress->days_done : 0,
                            ];
                        }
                    }

                    // Get release schedule info
                    $releaseInfo = null;
                    if ($user) {
                        $remaining = $releaseScheduleService->getRemaining($user, $lesson);
                        $releaseInfo = [
                            'is_released' => $remaining['is_released'],
                            'release_at' => $remaining['release_at'],
                            'human' => $remaining['human'],
                        ];
                    }

                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'duration' => gmdate('i\m', $lesson->duration_seconds ?? 0),
                        'is_completed' => $isCompleted,
                        'is_locked' => !$lessonResult->allowed,
                        'lock_reasons' => $lessonResult->reasons,
                        'lock_message' => LockMessage::fromEligibility($lessonResult),
                        'lock_reason_codes' => $lessonResult->reasons,
                        'required_level' => $lessonResult->requiredLevel,
                        'required_gender' => $lessonResult->requiredGender,
                        'requires_bayah' => $lessonResult->requiresBayah,
                        'is_next' => $isNext,
                        'type' => 'video',
                        'task_required_days' => $taskInfo['required_days'] ?? null,
                        'task_days_done' => $taskInfo['days_done'] ?? null,
                        'is_released' => $releaseInfo['is_released'] ?? true,
                        'release_at' => $releaseInfo['release_at'] ?? null,
                        'release_human' => $releaseInfo['human'] ?? null,
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
                'lessons_count' => $visibleLessons->count(),
                'duration' => gmdate('H\h i\m', $visibleLessons->sum('duration_seconds')),
                'level' => $course->level,
                'students_count' => $course->enrollments()->count(),
                'is_enrolled' => $isEnrolled,
                'progress' => $progress,
                'is_locked' => !$courseResult->allowed,
                'lock_reasons' => $courseResult->reasons,
                'lock_message' => LockMessage::fromEligibility($courseResult),
                'required_level' => $courseResult->requiredLevel,
                'required_gender' => $courseResult->requiredGender,
                'requires_bayah' => $courseResult->requiresBayah,
                'modules' => $modules,
                'next_lesson' => $nextLesson,
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

        // Create enrollment with started_at for drip scheduling
        $user->enrollments()->create([
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'started_at' => now(), // Set started_at for relative drip calculation
        ]);

        // Initialize journey progress for this course
        JourneyService::ensureProgressRecords($user, $course);

        return redirect()->route('courses.show', $course)
            ->with('success', 'Successfully enrolled in ' . $course->title);
    }
}
