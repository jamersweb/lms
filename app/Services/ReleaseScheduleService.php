<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;

/**
 * Service for managing lesson release schedules (daily drip).
 *
 * Supports two scheduling strategies:
 * 1. Absolute release: lesson.release_at (same for all students)
 * 2. Relative drip: lesson.release_day_offset (per-student based on enrollment started_at)
 *
 * Precedence: release_at > release_day_offset
 */
class ReleaseScheduleService
{
    /**
     * Get the release datetime for a lesson for a specific user.
     *
     * Returns null if no release schedule is configured.
     *
     * @return Carbon|null
     */
    public function getLessonReleaseAt(User $user, Lesson $lesson): ?Carbon
    {
        // Strategy A: Absolute release (overrides offset)
        if ($lesson->release_at) {
            return Carbon::parse($lesson->release_at);
        }

        // Strategy B: Relative drip (per-student based on enrollment)
        if ($lesson->release_day_offset !== null) {
            // Get the course containing this lesson
            if (!$lesson->relationLoaded('module')) {
                $lesson->load('module');
            }

            $module = $lesson->module;
            if (!$module) {
                return null; // Can't determine release without module
            }

            if (!$module->relationLoaded('course')) {
                $module->load('course');
            }

            $course = $module->course;
            if (!$course) {
                return null; // Can't determine release without course
            }

            // Find user's enrollment for this course
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (!$enrollment || !$enrollment->started_at) {
                // No enrollment or started_at not set - can't calculate relative release
                return null;
            }

            // Calculate release date: started_at + offset days (same time of day)
            $startedAt = Carbon::parse($enrollment->started_at);
            return $startedAt->copy()->addDays($lesson->release_day_offset);
        }

        // No release schedule configured
        return null;
    }

    /**
     * Check if a lesson is released for a user.
     *
     * Returns true if:
     * - No release schedule is configured, OR
     * - Current time is >= release_at
     */
    public function isReleased(User $user, Lesson $lesson): bool
    {
        $releaseAt = $this->getLessonReleaseAt($user, $lesson);

        if ($releaseAt === null) {
            return true; // No drip restriction
        }

        return now()->gte($releaseAt);
    }

    /**
     * Get remaining time information for UI display.
     *
     * @return array{release_at: string|null, is_released: bool, human: string}
     */
    public function getRemaining(User $user, Lesson $lesson): array
    {
        $releaseAt = $this->getLessonReleaseAt($user, $lesson);

        if ($releaseAt === null) {
            return [
                'release_at' => null,
                'is_released' => true,
                'human' => 'Available now',
            ];
        }

        $isReleased = now()->gte($releaseAt);

        if ($isReleased) {
            return [
                'release_at' => $releaseAt->toIso8601String(),
                'is_released' => true,
                'human' => 'Available now',
            ];
        }

        // Calculate human-readable remaining time
        $diff = now()->diff($releaseAt);
        $human = $this->formatRemainingTime($diff, $releaseAt);

        return [
            'release_at' => $releaseAt->toIso8601String(),
            'is_released' => false,
            'human' => $human,
        ];
    }

    /**
     * Format remaining time in a human-readable way.
     */
    private function formatRemainingTime(\DateInterval $diff, Carbon $releaseAt): string
    {
        // If less than 24 hours, show hours/minutes
        if ($diff->days === 0) {
            if ($diff->h > 0) {
                return sprintf('Available in %d hour%s', $diff->h, $diff->h > 1 ? 's' : '');
            }
            if ($diff->i > 0) {
                return sprintf('Available in %d minute%s', $diff->i, $diff->i > 1 ? 's' : '');
            }
            return 'Available soon';
        }

        // If same day tomorrow, show "tomorrow at {time}"
        if ($diff->days === 1 && $releaseAt->isTomorrow()) {
            return sprintf('Available tomorrow at %s', $releaseAt->format('g:i A'));
        }

        // Otherwise show date and time
        if ($diff->days <= 7) {
            return sprintf('Available %s at %s', $releaseAt->format('l'), $releaseAt->format('g:i A'));
        }

        return sprintf('Available on %s', $releaseAt->format('M j, Y g:i A'));
    }
}
