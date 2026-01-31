<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StagnationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $days)
    {
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
        return (new MailMessage)
            ->subject('Time to return to your Sunnah learning')
            ->greeting('Assalamu alaikum '.$notifiable->name.'!')
            ->line("We haven't seen any new lesson activity from you in the last {$this->days} days.")
            ->line('Even a few minutes a day can keep your momentum alive, in shaa Allah.')
            ->action('Continue your course', url('/dashboard'))
            ->line('BarakAllahu feek for continuing your learning journey.');
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        $dashboardUrl = route('dashboard');

        return "Assalamu Alaikum! 📚\n\n" .
               "We haven't seen you in {$this->days} days.\n\n" .
               "Even a few minutes a day keeps your momentum alive, in shaa Allah.\n\n" .
               "Continue: {$dashboardUrl}\n\n" .
               "BarakAllahu feek! 🤲";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stagnation',
            'days' => $this->days,
        ];
    }
}
