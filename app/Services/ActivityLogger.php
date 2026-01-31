<?php

namespace App\Services;

use App\Models\ActivityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Log an activity event.
     *
     * This method is safe and will never throw exceptions.
     * It silently fails with a log warning if something goes wrong.
     *
     * @param string $eventType Event type constant from ActivityEvent
     * @param User|null $user User who performed the action (null for system events)
     * @param array $context Context data:
     *   - subject: Model instance (Lesson/Task/Question/etc.)
     *   - course_id, module_id, lesson_id: IDs for filtering
     *   - meta: array of additional metadata
     */
    public function log(string $eventType, ?User $user = null, array $context = []): void
    {
        try {
            $subject = $context['subject'] ?? null;
            $courseId = $context['course_id'] ?? null;
            $moduleId = $context['module_id'] ?? null;
            $lessonId = $context['lesson_id'] ?? null;
            $meta = $context['meta'] ?? [];

            // Extract IDs from subject if it's a model
            if ($subject instanceof Model) {
                $subjectType = get_class($subject);
                $subjectId = $subject->id;

                // Try to extract course/module/lesson IDs from subject
                if (!$courseId && method_exists($subject, 'course_id')) {
                    $courseId = $subject->course_id;
                }
                if (!$courseId && method_exists($subject, 'course')) {
                    $courseId = $subject->course?->id;
                }
                if (!$moduleId && method_exists($subject, 'module_id')) {
                    $moduleId = $subject->module_id;
                }
                if (!$moduleId && method_exists($subject, 'module')) {
                    $moduleId = $subject->module?->id;
                }
                if (!$lessonId && method_exists($subject, 'lesson_id')) {
                    $lessonId = $subject->lesson_id;
                }
                if (!$lessonId && $subject instanceof \App\Models\Lesson) {
                    $lessonId = $subject->id;
                }
            } else {
                $subjectType = null;
                $subjectId = null;
            }

            // Override with explicit context values if provided
            $courseId = $context['course_id'] ?? $courseId;
            $moduleId = $context['module_id'] ?? $moduleId;
            $lessonId = $context['lesson_id'] ?? $lessonId;

            ActivityEvent::create([
                'user_id' => $user?->id,
                'event_type' => $eventType,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'lesson_id' => $lessonId,
                'meta' => $meta,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'occurred_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail with log warning
            Log::warning('ActivityLogger failed to log event', [
                'event_type' => $eventType,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
