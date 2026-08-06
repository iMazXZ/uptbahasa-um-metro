<?php

namespace App\Notifications;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EptRegistrationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $status,
        public string $dashboardUrl,
        public ?string $rejectionReason = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if (
            $this->status === 'rejected'
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

    public function toWhatsApp(object $notifiable): bool
    {
        if ($this->status !== 'rejected') {
            return true;
        }

        $message = "*Pendaftaran EPT Ditolak*\n\n";
        $message .= "Yth. *{$notifiable->name}*,\n\n";
        $message .= "Mohon maaf, pendaftaran Tes EPT Anda *tidak dapat diproses*.\n\n";
        $message .= "*Alasan:*\n" . ($this->rejectionReason ?: 'Tidak ada keterangan.') . "\n\n";
        $message .= "Silakan upload ulang bukti pembayaran yang valid melalui link berikut:\n{$this->dashboardUrl}\n\n";
        $message .= "_Terima kasih atas pengertiannya._";

        return app(WhatsAppService::class)->queueMessage($notifiable->whatsapp, $message);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->greeting("Yth. {$notifiable->name},");

        if ($this->status === 'approved') {
            return $mail
                ->subject('Pendaftaran EPT Diterima')
                ->line('Pembayaran Tes EPT Anda sudah kami verifikasi dan valid.')
                ->line('Mohon menunggu penetapan jadwal tes. Ketika kuota peserta sudah terpenuhi, jadwal tes akan segera dikirimkan.')
                ->line('Jika notifikasi WhatsApp tidak berfungsi, pantau info jadwal melalui Instagram: https://www.instagram.com/labahasa_um_metro/')
                ->action('Buka Status Pendaftaran EPT', $this->dashboardUrl)
                ->salutation('Hormat kami, Admin Lembaga Bahasa UM Metro');
        }

        return $mail
            ->subject('Pendaftaran EPT Ditolak')
            ->line('Mohon maaf, pendaftaran Tes EPT Anda tidak dapat diproses.')
            ->line('Alasan penolakan: ' . ($this->rejectionReason ?: 'Tidak ada keterangan.'))
            ->line('Silakan upload ulang bukti pembayaran yang valid melalui halaman pendaftaran EPT.')
            ->action('Buka Pendaftaran EPT', $this->dashboardUrl)
            ->salutation('Hormat kami, Admin Lembaga Bahasa UM Metro');
    }

    public function toArray(object $notifiable): array
    {
        if ($this->status === 'approved') {
            return [
                'type' => 'ept_registration_status',
                'status' => 'approved',
                'title' => 'Pendaftaran EPT Diterima',
                'body' => 'Pembayaran Anda sudah diverifikasi. Silakan tunggu penetapan jadwal tes.',
                'url' => $this->dashboardUrl,
                'color' => 'emerald',
                'icon' => 'fa-solid fa-circle-check',
            ];
        }

        return [
            'type' => 'ept_registration_status',
            'status' => 'rejected',
            'title' => 'Pendaftaran EPT Ditolak',
            'body' => 'Alasan: ' . ($this->rejectionReason ?: 'Tidak ada keterangan.'),
            'url' => $this->dashboardUrl,
            'color' => 'rose',
            'icon' => 'fa-solid fa-circle-xmark',
        ];
    }
}
