<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\PointsService;
use App\Services\JourneyService;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    /**
     * Mark a lesson as complete.
     */
    public function complete(Request $request, Lesson $lesson)
    {
        $user = auth()->user();

        // Check if user is enrolled in the course
        $course = $lesson->module->course;
        if (!$user->isEnrolledIn($course->id)) {
            abort(403, 'You must be enrolled in this course to mark lessons as complete.');
        }

        // Fetch or create progress record
        $progress = $user->lessonProgress()->firstOrCreate(
            ['lesson_id' => $lesson->id],
            ['is_completed' => false]
        );

        // If already completed, no-op
        if ($progress->completed_at) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => true]);
            }

            return back()->with('info', 'Lesson already marked as complete.');
        }

        // Verification rules
        $duration = (int) ($lesson->video_duration_seconds ?? $lesson->duration_seconds ?? 0);

        // Fallback to transcript duration if video duration is unknown
        if ($duration <= 0) {
            $duration = (int) round($lesson->transcriptSegments()->max('end_seconds') ?? 0);
        }

        // Require at least 90% of the duration watched
        $minWatched = $duration > 0 ? (int) ceil($duration * 0.9) : 0;

        $errors = [];

        if ($duration > 0 && $progress->time_watched_seconds < $minWatched) {
            $errors[] = 'insufficient_watch_time';
        }

        if ($progress->max_playback_rate_seen > 1.5) {
            $errors[] = 'playback_rate_too_high';
        }

        if ($progress->seek_detected) {
            $errors[] = 'seek_detected';
        }

        if (! empty($errors)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'errors' => $errors,
                ], 422);
            }

            return back()
                ->withErrors([
                    'completion' => "Please watch the lesson fully (no skipping) at max 1.5x speed to complete.",
                ])
                ->with('completion_errors', $errors);
        }

        // Mark as completed and verified
        $progress->is_completed = true;
        $progress->completed_at = now();
        $progress->verified_completion = true;
        $progress->verified_at = now();
        $progress->save();

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
