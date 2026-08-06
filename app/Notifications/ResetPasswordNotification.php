<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Facades\Filament;

class ResetPasswordNotification extends Notification
{
    use Queueable;
 
    public function __construct(private readonly string $token)
    {}
 
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
 
    public function toMail(object $notifiable): MailMessage
    {
        $expireMinutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return (new MailMessage)
            ->subject('Lupa Kata Sandi - Lembaga Bahasa')
            ->greeting("Hai {$notifiable->name}")
            ->line('Permintaan untuk mengubah kata sandi akun Anda telah diterima.')
            ->action('Ubah Kata Sandi', $this->resetUrl($notifiable))
            ->line("Tautan aman ini berlaku selama {$expireMinutes} menit ke depan.")
            ->line('Jika Anda tidak meminta perubahan ini, abaikan pesan ini.');
    }
 
    protected function resetUrl(mixed $notifiable): string
    {
        return Filament::getResetPasswordUrl($this->token, $notifiable);
    }
}
