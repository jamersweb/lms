<?php

namespace App\Notifications;

use App\Models\Question;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewQuestionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Question $question)
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // In-app only for teachers/admins (they check inbox regularly)
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $questionUrl = route('admin.questions.show', $this->question);

        return (new MailMessage)
            ->subject("New Question: {$this->question->title}")
            ->greeting("Assalamu Alaikum {$notifiable->name},")
            ->line("A student has submitted a new question.")
            ->line("**Student:** {$this->question->user->name}")
            ->line("**Question:** {$this->question->title}")
            ->action('View Question', $questionUrl)
            ->line('Please respond when you have a moment.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_question',
            'question_id' => $this->question->id,
            'title' => $this->question->title,
            'student_name' => $this->question->user->name,
            'url' => route('admin.questions.show', $this->question),
        ];
    }
}
