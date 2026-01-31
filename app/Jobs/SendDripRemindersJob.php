<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\NextLessonAvailableNotification;
use App\Services\AppSettings;
use App\Services\ProgressionService;
use App\Services\ReleaseScheduleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SendDripRemindersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $settings = app(AppSettings::class)->getNotificationSettings();

        // Check if drip reminders are enabled
        if (!$settings['enabled'] || !$settings['drip']['enabled']) {
            return;
        }

        // Check if current hour matches send_hour (within same hour)
        $currentHour = now()->hour;
        if ($currentHour != $settings['drip']['send_hour']) {
            return; // Not the right time to send
        }

        $progressionService = app(ProgressionService::class);
        $releaseScheduleService = app(ReleaseScheduleService::class);

        // Find users who:
        // 1. Have opted into at least one channel
        // 2. Are enrolled in at least one course
        // 3. Have a next lesson that is released and accessible
        // 4. Haven't been reminded today for that lesson

        $users = User::where(function ($query) {
            $query->where('email_reminders_opt_in', true)
                ->orWhere(function ($q) {
                    $q->where('whatsapp_opt_in', true)
                        ->whereNotNull('whatsapp_number');
                });
        })
        ->whereHas('enrollments')
        ->get();

        $today = now()->toDateString(); // Format: Y-m-d

        foreach ($users as $user) {
            // Find next lesson across all enrolled courses
            $enrollments = $user->enrollments()->with('course.modules.lessons')->get();

            foreach ($enrollments as $enrollment) {
                $course = $enrollment->course;

                // Find next lesson in this course
                foreach ($course->modules as $module) {
                    $nextLesson = $progressionService->getNextLessonInModule($user, $module);

                    if (!$nextLesson) {
                        continue; // No next lesson in this module
                    }

                    // Check if lesson is released (drip gate passed)
                    if (!$releaseScheduleService->isReleased($user, $nextLesson)) {
                        continue; // Not released yet
                    }

                    // Check if user can access it (all gates passed except completion)
                    $result = $progressionService->canAccessLesson($user, $nextLesson);

                    // If not allowed, check if it's only because not completed (not other gates)
                    if (!$result->allowed) {
                        // Check if it's just because not started/completed (no other blocking reasons)
                        $hasBlockingReasons = !empty(array_diff($result->reasons, ['previous_lesson_incomplete', 'reflection_required', 'task_incomplete', 'not_released_yet']));
                        if ($hasBlockingReasons) {
                            continue; // Blocked by other gates, skip
                        }

                        // Check if previous lesson is completed (sequential gate passed)
                        $previousLesson = $progressionService->getPreviousLesson($nextLesson);
                        if ($previousLesson) {
                            $previousProgress = \App\Models\LessonProgress::where('user_id', $user->id)
                                ->where('lesson_id', $previousLesson->id)
                                ->whereNotNull('completed_at')
                                ->exists();

                            if (!$previousProgress) {
                                continue; // Previous lesson not completed
                            }
                        }
                    }

                    // Check if already reminded today (unique constraint: one reminder per user per type per day)
                    $alreadyReminded = NotificationLog::where('user_id', $user->id)
                        ->where('type', 'drip')
                        ->whereDate('sent_on', $today)
                        ->exists();

                    if ($alreadyReminded) {
                        continue; // Already reminded today
                    }

                    // Check if lesson is already completed
                    $lessonProgress = \App\Models\LessonProgress::where('user_id', $user->id)
                        ->where('lesson_id', $nextLesson->id)
                        ->whereNotNull('completed_at')
                        ->exists();

                    if ($lessonProgress) {
                        continue; // Already completed
                    }

                    // Send notification
                    $user->notify(new NextLessonAvailableNotification($course, $nextLesson));

                    // Log notification (use updateOrCreate to handle race conditions)
                    try {
                        NotificationLog::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'type' => 'drip',
                                'sent_on' => $today,
                            ],
                            [
                                'meta' => [
                                    'lesson_id' => $nextLesson->id,
                                    'course_id' => $course->id,
                                ],
                            ]
                        );
                    } catch (\Exception $e) {
                        // Ignore duplicate entry errors (race condition)
                        // Log entry already exists, which is fine
                    }
                }
            }
        }
    }
}
