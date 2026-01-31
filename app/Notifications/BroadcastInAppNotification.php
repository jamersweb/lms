<?php

namespace App\Notifications;

use App\Models\Broadcast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BroadcastInAppNotification extends Notification implements ShouldQueue
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
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'broadcast_id' => $this->broadcast->id,
            'title' => $this->broadcast->title,
            'body' => $this->broadcast->body,
            'type' => 'broadcast',
        ];
    }
}
