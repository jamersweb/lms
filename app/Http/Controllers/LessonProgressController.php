<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\ActivityLogger;
use App\Services\EligibilityService;
use App\Services\PointsService;
use App\Services\JourneyService;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    public function __construct(
        private EligibilityService $eligibilityService
    ) {}

    /**
     * Mark a lesson as complete with Phase 2 validation.
     */
    public function complete(Request $request, Lesson $lesson)
    {
        $user = auth()->user();

        // Check if user is enrolled in the course
        $course = $lesson->module->course;
        if (!$user->isEnrolledIn($course->id)) {
            abort(403, 'You must be enrolled in this course to mark lessons as complete.');
        }

        // Check eligibility using Phase 1 EligibilityService
        $eligibility = $this->eligibilityService->canAccessLesson($user, $lesson);
        if (!$eligibility->allowed) {
            abort(403, 'You are not allowed to access this lesson.');
        }

        // Fetch or create progress record
        $progress = $user->lessonProgress()->firstOrCreate(
            ['lesson_id' => $lesson->id],
            [
                'is_completed' => false,
                'watched_seconds' => 0,
                'max_playback_rate' => 1.0,
                'seek_attempts' => 0,
                'violations' => [],
            ]
        );

        // If already completed, no-op
        if ($progress->completed_at) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => true]);
            }
            return back()->with('info', 'Lesson already marked as complete.');
        }

        // Get configuration
        $maxPlaybackRate = config('video_guard.max_playback_rate', 1.5);
        $minWatchRatio = config('video_guard.min_watch_ratio', 0.95);
        $minWatchSeconds = config('video_guard.min_watch_seconds', 30);
        $requireDuration = config('video_guard.require_duration_for_completion', true);

        // Determine duration
        $duration = (int) ($lesson->duration_seconds ?? 0);

        // Fallback to transcript duration if video duration is unknown (only if not strict)
        if ($duration <= 0 && !$requireDuration) {
            $duration = (int) round($lesson->transcriptSegments()->max('end_seconds') ?? 0);
        }

        // Validation errors
        $errors = [];
        $errorMessages = [];

        // Check duration requirement
        if ($requireDuration && $duration <= 0) {
            $errors[] = 'missing_duration';
            $errorMessages[] = 'Duration not configured. Please contact support.';
        }

        // Compute required watch time
        $requiredWatched = 0;
        if ($duration > 0) {
            $requiredWatched = max(
                (int) ceil($duration * $minWatchRatio),
                $minWatchSeconds
            );
        }

        $watchedSeconds = (int) ($progress->watched_seconds ?? 0);

        // Check watch time
        if ($duration > 0 && $watchedSeconds < $requiredWatched) {
            $errors[] = 'insufficient_watch_time';
            $percentage = $duration > 0 ? round(($watchedSeconds / $duration) * 100, 1) : 0;
            $errorMessages[] = sprintf(
                'You must watch at least %d%% of the lesson to complete it. (Watched: %d%%)',
                round($minWatchRatio * 100),
                $percentage
            );
        }

        // Check playback rate
        $maxRate = (float) ($progress->max_playback_rate ?? 1.0);
        if ($maxRate > $maxPlaybackRate) {
            $errors[] = 'playback_rate_too_high';
            $errorMessages[] = sprintf(
                'Playback speed exceeded %.1fx. Maximum allowed is %.1fx.',
                $maxRate,
                $maxPlaybackRate
            );
        }

        // Check for seek violations
        $hasSeekViolations = false;
        $violations = $progress->violations ?? [];
        foreach ($violations as $violation) {
            if (isset($violation['type']) && $violation['type'] === 'seek_forward') {
                $hasSeekViolations = true;
                break;
            }
        }

        // Also check seek_attempts count
        if (($progress->seek_attempts ?? 0) > 0 || $hasSeekViolations) {
            $errors[] = 'seek_detected';
            $errorMessages[] = 'Skipping ahead is not allowed. Please watch the lesson sequentially.';
        }

        // If any errors, deny completion
        if (!empty($errors)) {
            $message = !empty($errorMessages)
                ? implode(' ', $errorMessages)
                : 'Please watch the lesson fully (no skipping) at max ' . $maxPlaybackRate . 'x speed to complete.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'errors' => $errors,
                    'message' => $message,
                    'watched_seconds' => $watchedSeconds,
                    'required_watched' => $requiredWatched,
                    'duration' => $duration,
                    'max_playback_rate' => $maxRate,
                    'seek_attempts' => $progress->seek_attempts ?? 0,
                ], 422);
            }

            return back()
                ->withErrors(['completion' => $message])
                ->with('completion_errors', $errors);
        }

        // All validation passed - mark as completed
        $progress->is_completed = true;
        $progress->completed_at = now();
        $progress->verified_completion = true;
        $progress->verified_at = now();

        // Store completion metadata snapshot
        $progress->completion_meta = [
            'watched_seconds' => $watchedSeconds,
            'duration_seconds' => $duration,
            'max_playback_rate' => $maxRate,
            'seek_attempts' => $progress->seek_attempts ?? 0,
            'violations_count' => count($violations),
            'completed_at' => now()->toIso8601String(),
        ];

        $progress->save();

        // Log lesson completion
        $this->activityLogger->log(
            \App\Models\ActivityEvent::TYPE_LESSON_WATCH_COMPLETED,
            $user,
            [
                'subject' => $lesson,
                'course_id' => $course->id,
                'module_id' => $lesson->module_id,
                'lesson_id' => $lesson->id,
                'meta' => [
                    'watched_seconds' => $watchedSeconds,
                    'duration_seconds' => $duration,
                    'violations_summary' => [
                        'count' => count($violations),
                        'has_seek' => $hasSeekViolations,
                    ],
                ],
            ]
        );

        // Award points
        PointsService::award($user, 'lesson_completed', 10);

        // Check if course is completed
        $totalLessons = $course->modules->flatMap->lessons->count();
        $completedLessons = $user->lessonProgress()
            ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
            ->whereNotNull('completed_at')
            ->count();

        if ($totalLessons === $completedLessons) {
            PointsService::award($user, 'course_completed', 50);

            // Award course completion certificate
            $certificateService = new CertificateService();
            $certificateService->awardCertificate($user, 'course_completion', $course);

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'course_completed' => true,
                ]);
            }
            return back()->with('success', 'Congratulations! You completed the course! A certificate has been awarded.');
        }

        // Recompute journey statuses after completion
        JourneyService::computeStatusesForCourse($user, $course);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'course_completed' => false,
            ]);
        }

        return back()->with('success', 'Lesson marked as complete! +10 points');
    }
}
