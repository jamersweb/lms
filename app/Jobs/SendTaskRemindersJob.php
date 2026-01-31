<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\TaskProgress;
use App\Notifications\TaskCheckInReminderNotification;
use App\Services\AppSettings;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTaskRemindersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $settings = app(AppSettings::class)->getNotificationSettings();

        // Check if task reminders are enabled
        if (!$settings['enabled'] || !$settings['task']['enabled']) {
            return;
        }

        // Check if current hour matches send_hour
        $currentHour = now()->hour;
        if ($currentHour != $settings['task']['send_hour']) {
            return;
        }

        $today = Carbon::today();

        // Find users with active task progress that:
        // 1. Status is in_progress (not completed)
        // 2. last_checkin_on < today (hasn't checked in today)
        // 3. User has opted into notifications
        // 4. Haven't been reminded today for this task

        $taskProgresses = TaskProgress::with(['user', 'task.taskable'])
            ->where('status', TaskProgress::STATUS_IN_PROGRESS)
            ->where(function ($query) use ($today) {
                $query->whereNull('last_checkin_on')
                    ->orWhere('last_checkin_on', '<', $today);
            })
            ->whereHas('user', function ($q) {
                $q->where(function ($query) {
                    $query->where('email_reminders_opt_in', true)
                        ->orWhere(function ($q) {
                            $q->where('whatsapp_opt_in', true)
                                ->whereNotNull('whatsapp_number');
                        });
                });
            })
            ->get();

        foreach ($taskProgresses as $taskProgress) {
            $user = $taskProgress->user;
            $task = $taskProgress->task;

            // Check if already reminded today for this task
            $alreadyReminded = NotificationLog::where('user_id', $user->id)
                ->where('type', 'task')
                ->where('sent_on', $today->toDateString())
                ->where(function ($query) use ($task) {
                    $query->whereJsonContains('meta->task_id', $task->id)
                        ->orWhereJsonContains('meta->task_id', (string) $task->id);
                })
                ->exists();

            if ($alreadyReminded) {
                continue;
            }

            // Send notification
            $user->notify(new TaskCheckInReminderNotification($task, $taskProgress));

            // Log notification (use updateOrCreate to handle race conditions)
            try {
                NotificationLog::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'type' => 'task',
                        'sent_on' => $today->toDateString(),
                    ],
                    [
                        'meta' => [
                            'task_id' => $task->id,
                            'task_progress_id' => $taskProgress->id,
                        ],
                    ]
                );
            } catch (\Exception $e) {
                // Ignore duplicate entry errors
            }
        }
    }
}
