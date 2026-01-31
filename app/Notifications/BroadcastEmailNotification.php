<?php

namespace App\Notifications;

use App\Models\Broadcast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BroadcastEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Broadcast $broadcast
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url("/inbox/{$this->broadcast->id}");

        return (new MailMessage)
            ->subject($this->broadcast->title)
            ->line($this->broadcast->body)
            ->action('View in App', $url)
            ->line('Thank you for being part of our community!');
    }
}
