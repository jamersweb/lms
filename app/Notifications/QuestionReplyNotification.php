<?php

namespace App\Notifications;

use App\Models\Question;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuestionReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Question $question,
        public User $replier
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

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
        $questionUrl = route('questions.show', $this->question);

        return (new MailMessage)
            ->subject("New Reply to Your Question: {$this->question->title}")
            ->greeting("Assalamu Alaikum {$notifiable->name},")
            ->line("You have received a new reply to your question.")
            ->line("**Question:** {$this->question->title}")
            ->line("**Replied by:** {$this->replier->name}")
            ->action('View Reply', $questionUrl)
            ->line('May Allah bless your learning journey.');
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        $questionUrl = route('questions.show', $this->question);
        return "Assalamu Alaikum! You have a new reply to your question: {$this->question->title}. View it here: {$questionUrl}";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'question_reply',
            'question_id' => $this->question->id,
            'title' => $this->question->title,
            'replier_name' => $this->replier->name,
            'url' => route('questions.show', $this->question),
        ];
    }
}
