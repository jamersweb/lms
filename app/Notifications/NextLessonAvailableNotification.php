<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NextLessonAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Course $course,
        public Lesson $lesson
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        // Check user preferences and global settings
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
        $lessonUrl = route('lessons.show', ['course' => $this->course->id, 'lesson' => $this->lesson->id]);

        return (new MailMessage)
            ->subject("New Lesson Available: {$this->lesson->title}")
            ->greeting("Assalamu Alaikum {$notifiable->name},")
            ->line("Your next lesson is now available!")
            ->line("**Course:** {$this->course->title}")
            ->line("**Lesson:** {$this->lesson->title}")
            ->action('Start Lesson', $lessonUrl)
            ->line('May Allah bless your learning journey.');
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        $lessonUrl = route('lessons.show', ['course' => $this->course->id, 'lesson' => $this->lesson->id]);

        return "Assalamu Alaikum! 🌟\n\n" .
               "Your next lesson is now available:\n\n" .
               "📚 {$this->course->title}\n" .
               "📖 {$this->lesson->title}\n\n" .
               "Start learning: {$lessonUrl}\n\n" .
               "May Allah bless your journey! 🤲";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'lesson_id' => $this->lesson->id,
            'lesson_title' => $this->lesson->title,
        ];
    }
}
