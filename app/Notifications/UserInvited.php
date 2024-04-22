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
            ->subject('Undangan Pribadi')
            ->greeting('Halo!')
            ->line("Anda telah diundang oleh {$this->sender->name} untuk bergabung ke Sistem Manajemen Inventaris {$appName}!")
            ->action('Klik di sini untuk mendaftar akun Anda', $frontendUrl . '/dashboard/register?invite=' . $this->invitation->invite_token)
            ->line('Catatan: tautan ini akan kedaluwarsa setelah 24 jam.');
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
