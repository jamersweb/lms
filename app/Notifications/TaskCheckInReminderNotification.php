<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCheckInReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public TaskProgress $taskProgress
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        $settings = app(\App\Services\AppSettings::class)->getNotificationSettings();

        if ($settings['channels']['email'] && ($notifiable->email_reminders_opt_in ?? true)) {
            $channels[] = 'mail';
        }

        if ($settings['channels']['whatsapp'] && $notifiable->whatsapp_opt_in && $notifiable->whatsapp_number) {
            $channels[] = 'whatsapp';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $lesson = $this->task->taskable; // Should be a Lesson
        $lessonUrl = $lesson ? route('lessons.show', ['course' => $lesson->module->course->id, 'lesson' => $lesson->id]) : route('dashboard');

        return (new MailMessage)
            ->subject("Task Reminder: {$this->task->title}")
            ->greeting("Assalamu Alaikum {$notifiable->name},")
            ->line("Don't forget to check in for today's practice task!")
            ->line("**Task:** {$this->task->title}")
            ->line("**Progress:** Day {$this->taskProgress->days_done} of {$this->task->required_days}")
            ->line($this->task->instructions ? "**Instructions:** {$this->task->instructions}" : '')
            ->action('Check In Now', $lessonUrl)
            ->line('Consistency is key to success!');
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        $lesson = $this->task->taskable;
        $lessonUrl = $lesson ? route('lessons.show', ['course' => $lesson->module->course->id, 'lesson' => $lesson->id]) : route('dashboard');

        return "Assalamu Alaikum! ⏰\n\n" .
               "Don't forget today's check-in:\n\n" .
               "✅ {$this->task->title}\n" .
               "📊 Day {$this->taskProgress->days_done} of {$this->task->required_days}\n\n" .
               "Check in: {$lessonUrl}\n\n" .
               "Consistency is key! 💪";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'days_done' => $this->taskProgress->days_done,
            'required_days' => $this->task->required_days,
        ];
    }
}
