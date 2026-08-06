<?php

namespace App\Notifications;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Jobs\SendWhatsAppNotification;
use App\Support\WhatsAppOutboundThrottle;

class PenerjemahanStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $status,
        public ?string $verificationUrl = null,
        public ?string $rejectionReason = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if (
            ($this->status === 'Selesai' || str_contains($this->status, 'Ditolak'))
            && ! empty($notifiable->whatsapp)
            && $notifiable->whatsapp_verified_at
        ) {
            $channels[] = 'whatsapp';
        }

        return $channels;
    }

    public function viaConnections(): array
    {
        return [
            'database' => 'sync',
        ];
    }

    /**
     * Kirim notifikasi via WhatsApp
     */
    public function toWhatsApp(object $notifiable): bool
    {
        $reason = trim((string) $this->rejectionReason);

        $details = match (true) {
            $this->status === 'Diproses' => 'Dokumen Anda sedang dalam proses diterjemahkan oleh Tim Penerjemah.',
            $this->status === 'Selesai' => 'Dokumen sudah siap didownload di Menu Penerjemahan Dokumen Abstrak.',
            str_contains($this->status, 'Ditolak') => $reason !== ''
                ? "Alasan:\n{$reason}\n\nSilakan upload kembali dokumen yang sesuai."
                : 'Silakan upload kembali dokumen yang sesuai.',
            default => '',
        };
        
        $actionUrl = ($this->status === 'Selesai' && $this->verificationUrl) 
            ? $this->verificationUrl 
            : route('dashboard.translation');

        SendWhatsAppNotification::dispatch(
            phone: $notifiable->whatsapp,
            type: 'penerjemahan_status',
            status: $this->status,
            userName: $notifiable->name,
            details: $details,
            actionUrl: $actionUrl
        )->delay(WhatsAppOutboundThrottle::nextDelaySeconds());

        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Permohonan Penerjemahan [ {$this->status} ]")
            ->greeting("Halo, {$notifiable->name}")
            ->line("Status Penerjemahan Dokumen Abstrak Anda")
            ->line("**{$this->status}**")
            ->when(str_contains($this->status, 'Ditolak') && filled($this->rejectionReason), function ($message) {
                return $message->line('Alasan penolakan: ' . $this->rejectionReason);
            })
            ->when(in_array($this->status, ['Ditolak - Pembayaran Tidak Valid', 'Ditolak - Dokumen Tidak Valid']), function ($message) {
                return $message->line('Silakan buka halaman Penerjemahan Dokumen Abstrak');
            })
            ->when(in_array($this->status, ['Diproses']), function ($message) {
                return $message->line('Dokumen Anda sedang dalam **Proses Diterjemahkan** oleh Tim Penerjemah');
            })
            ->when(in_array($this->status, ['Selesai']), function ($message) {
                return $message->line('Dokumen Sudah Siap **Didownload** di Menu Penerjemahan Dokumen Abstrak');
            })
            ->when(in_array($this->status, ['Ditolak - Pembayaran Tidak Valid', 'Ditolak - Dokumen Tidak Valid']), function ($message) {
                return $message->salutation('Silakan **Upload Kembali Dokumen yang Sesuai.**');
            }, function ($message) {
                return $message->salutation('Regards, Admin.');
            });
    }

    public function toArray(object $notifiable): array
    {
        $title = match (true) {
            $this->status === 'Diproses' => 'Penerjemahan Sedang Diproses',
            $this->status === 'Selesai' => 'Penerjemahan Selesai',
            str_contains($this->status, 'Ditolak') => 'Penerjemahan Ditolak',
            default => 'Status Penerjemahan',
        };

        $body = match (true) {
            $this->status === 'Diproses' => 'Dokumen Anda sedang diproses oleh tim penerjemah.',
            $this->status === 'Selesai' => 'Dokumen terjemahan Anda sudah siap diunduh.',
            str_contains($this->status, 'Pembayaran') => 'Permohonan ditolak karena pembayaran tidak valid.',
            str_contains($this->status, 'Dokumen') => 'Permohonan ditolak karena dokumen tidak valid.',
            default => 'Status penerjemahan diperbarui.',
        };

        if (str_contains($this->status, 'Ditolak') && filled($this->rejectionReason)) {
            $body .= ' Alasan: ' . $this->rejectionReason;
        }

        return [
            'type' => 'penerjemahan_status',
            'status' => $this->status,
            'title' => $title,
            'body' => $body,
            'url' => ($this->status === 'Selesai' && $this->verificationUrl)
                ? $this->verificationUrl
                : route('dashboard.translation'),
            'color' => match (true) {
                $this->status === 'Selesai' => 'emerald',
                str_contains($this->status, 'Ditolak') => 'rose',
                $this->status === 'Diproses' => 'amber',
                default => 'slate',
            },
            'icon' => match (true) {
                $this->status === 'Selesai' => 'fa-solid fa-language',
                str_contains($this->status, 'Ditolak') => 'fa-solid fa-ban',
                $this->status === 'Diproses' => 'fa-solid fa-hourglass-half',
                default => 'fa-solid fa-file-signature',
            },
        ];
    }
}
