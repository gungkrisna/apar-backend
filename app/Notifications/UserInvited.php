<?php

namespace App\Notifications;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvited extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Invitation $invitation, public User $sender)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $appName = env('APP_NAME');
        $frontendUrl = env('FRONTEND_URL');

        return (new MailMessage)
            ->subject('Personal Invitation')
            ->greeting('Hello!')
            ->line("You have been invited by {$this->sender->name} to join the {$appName} Inventory Management System!")
            ->action('Click here to register your account', $frontendUrl . '/register?invite=' . $this->invitation->invite_token)
            ->line('Note: this link will expires after 24 hours.');
        
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
