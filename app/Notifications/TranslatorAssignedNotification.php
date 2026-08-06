<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TranslatorAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $pemohonName,
        public int $wordCount,
        public string $dashboardUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📋 Tugas Penerjemahan Baru - Lembaga Bahasa UM Metro')
            ->greeting("Halo, {$notifiable->name}")
            ->line('Anda mendapat tugas penerjemahan baru.')
            ->line("**Pemohon:** {$this->pemohonName}")
            ->line("**Jumlah Kata:** {$this->wordCount} kata")
            ->line('Mohon untuk menyelesaikan dalam waktu **3 hari kerja**.')
            ->action('Kerjakan Sekarang', $this->dashboardUrl)
            ->salutation('Terima kasih atas kerjasamanya.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'pemohon_name' => $this->pemohonName,
            'word_count' => $this->wordCount,
        ];
    }
}
