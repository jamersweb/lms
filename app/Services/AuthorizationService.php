<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuthorizationService
{
    /**
     * Ensure user has access to a lesson (enrollment or free preview).
     * Throws 403 if access is denied.
     *
     * @param User $user
     * @param Lesson $lesson
     * @param bool $allowFreePreview
     * @return void
     * @throws HttpResponseException
     */
    public function ensureLessonAccess(User $user, Lesson $lesson, bool $allowFreePreview = true): void
    {
        $course = $lesson->module->course;

        if ($allowFreePreview && $lesson->is_free_preview) {
            return; // Free preview lessons are accessible
        }

        if (!$user->isEnrolledIn($course->id)) {
            abort(403, 'You must be enrolled in this course to access this lesson.');
        }
    }

    /**
     * Ensure user is enrolled in a course.
     * Throws 403 if not enrolled.
     *
     * @param User $user
     * @param Course|int $course
     * @return void
     * @throws HttpResponseException
     */
    public function ensureEnrolled(User $user, Course|int $course): void
    {
        $courseId = $course instanceof Course ? $course->id : $course;

        if (!$user->isEnrolledIn($courseId)) {
            abort(403, 'You must be enrolled in this course to perform this action.');
        }
    }

    /**
     * Check if user has access to a lesson (enrollment or free preview).
     * Returns true/false instead of throwing.
     *
     * @param User $user
     * @param Lesson $lesson
     * @param bool $allowFreePreview
     * @return bool
     */
    public function hasLessonAccess(User $user, Lesson $lesson, bool $allowFreePreview = true): bool
    {
        $course = $lesson->module->course;

        if ($allowFreePreview && $lesson->is_free_preview) {
            return true;
        }

        return $user->isEnrolledIn($course->id);
    }

    /**
     * Ensure user has access to lesson resources (must be enrolled and completed).
     * Throws 403 if access is denied.
     *
     * @param User $user
     * @param Lesson $lesson
     * @param bool $requireCompletion
     * @return void
     * @throws HttpResponseException
     */
    public function ensureLessonResourceAccess(User $user, Lesson $lesson, bool $requireCompletion = true): void
    {
        $course = $lesson->module->course;

        // Check enrollment
        $enrollment = $user->enrollments()
            ->whereHas('course.modules.lessons', fn($q) => $q->where('lessons.id', $lesson->id))
            ->exists();

        if (!$enrollment) {
            abort(403, 'You do not have access to this lesson.');
        }

        // Check completion if required
        if ($requireCompletion) {
            $progress = $user->lessonProgress()
                ->where('lesson_id', $lesson->id)
                ->first();

            if (!$progress || !$progress->is_completed) {
                abort(403, 'You must complete this lesson before accessing resources.');
            }
        }
    }
}
