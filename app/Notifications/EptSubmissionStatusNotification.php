<?php

namespace App\Notifications;

use App\Services\WhatsAppService;
use App\Support\EptSubmissionNotificationTracker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EptSubmissionStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $status,
        public ?string $verificationUrl = null,
        public ?string $pdfUrl = null,
        public ?string $adminNote = null,
        public ?int $submissionId = null,
        public ?string $contentSignature = null,
    ) {
        $this->contentSignature ??= static::signatureFor(
            $this->submissionId,
            $this->status,
            $this->verificationUrl,
            $this->pdfUrl,
            $this->adminNote,
        );
    }

    public static function signatureFor(
        ?int $submissionId,
        string $status,
        ?string $verificationUrl,
        ?string $pdfUrl,
        ?string $adminNote,
    ): string {
        return hash('sha256', implode('|', [
            (string) ($submissionId ?? 0),
            $status,
            trim((string) $verificationUrl),
            trim((string) $pdfUrl),
            trim((string) $adminNote),
        ]));
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if (
            in_array($this->status, ['approved', 'rejected'], true)
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
        $actionUrl = $this->verificationUrl ?? route('dashboard.ept');

        if ($this->status === 'rejected') {
            $reason = trim((string) $this->adminNote);

            $message = "*Surat Rekomendasi EPT Ditolak*\n\n";
            $message .= "Yth. *{$notifiable->name}*,\n\n";
            $message .= "Pengajuan Anda ditolak.\n\n";
            $message .= "Alasan:\n" . ($reason !== '' ? $reason : '-') . "\n\n";
            $message .= "Perbaiki dan ajukan ulang di:\n{$actionUrl}";
        } elseif ($this->status === 'approved') {
            $downloadUrl = $this->pdfUrl ?? $this->verificationUrl ?? route('dashboard.ept');
            $adminNote = trim((string) $this->adminNote);

            $message = "*Surat Rekomendasi EPT Disetujui*\n\n";
            $message .= "Yth. *{$notifiable->name}*,\n\n";
            $message .= "Surat rekomendasi EPT Anda sudah dibuat.\n\n";

            if ($adminNote !== '') {
                $message .= "Catatan:\n{$adminNote}\n\n";
            }

            $message .= "Unduh, cetak, lalu bawa ke Kantor Lembaga Bahasa untuk cap basah:\n{$downloadUrl}";
        } else {
            $details = match ($this->status) {
                'pending' => "Pengajuan Anda saat ini MENUNGGU PROSES PENINJAUAN oleh admin.",
                default => '',
            };

            $message = "*Status Surat Rekomendasi EPT*\n\n";
            $message .= "Yth. *{$notifiable->name}*,\n\n";
            $message .= $details;

            if (filled($actionUrl)) {
                $message .= "\n\nLihat detail di:\n{$actionUrl}";
            }
        }

        $tracking = null;

        if (filled($this->submissionId) && filled($this->contentSignature)) {
            $tracking = EptSubmissionNotificationTracker::tracking(
                (int) $this->submissionId,
                (string) $this->contentSignature,
            );
        }

        return app(WhatsAppService::class)->queueMessage($notifiable->whatsapp, $message, $tracking);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = match ($this->status) {
            'approved' => 'Berhasil Dibuat',
            'rejected' => 'Ditolak',
            'pending'  => 'Menunggu Tinjauan',
            default    => ucfirst($this->status),
        };

        $mail = (new MailMessage)
            ->subject("Surat Rekomendasi EPT — {$statusLabel}")
            ->greeting("Yth. {$notifiable->name},")
            ->line('Melalui email ini kami sampaikan status pengajuan **Surat Rekomendasi EPT** Anda.');

        if ($this->status === 'approved') {
            $mail->line('Pengajuan Anda telah **Disetujui** dan **Berhasil Dibuat**.');
            $mail->line('Silakan **unduh dokumen Surat Rekomendasi**, kemudian cetak. '
                .'Bawa cetakan tersebut ke **Kantor Lembaga Bahasa, Kampus 3 UM Metro, Gedung FIKOM Lantai 2** '
                .'untuk mendapatkan **Cap Basah** dan **Legalisir** *(bila diperlukan)*.');
        }

        if ($this->status === 'rejected') {
            $mail->line('Pengajuan Anda **ditolak**. Silakan meninjau kembali persyaratan dan memperbaiki dokumen sesuai catatan admin.');
        }

        if ($this->status === 'pending') {
            $mail->line('Pengajuan Anda saat ini **menunggu proses peninjauan** oleh admin.');
        }

        if (filled($this->pdfUrl) && $this->status === 'approved') {
            $mail->action('Unduh Surat Rekomendasi (PDF)', $this->pdfUrl);
        }

        if (filled($this->verificationUrl)) {
            $mail->action('Buka Halaman Verifikasi', $this->verificationUrl);
        }

        $mail->salutation("Hormat kami, Admin Lembaga Bahasa UM Metro");

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $title = match ($this->status) {
            'approved' => 'Surat Rekomendasi Disetujui',
            'rejected' => 'Surat Rekomendasi Ditolak',
            'pending' => 'Surat Rekomendasi Menunggu Tinjauan',
            default => 'Status Surat Rekomendasi',
        };

        $body = match ($this->status) {
            'approved' => 'Pengajuan surat rekomendasi Anda telah disetujui dan siap diproses lebih lanjut.',
            'rejected' => 'Pengajuan surat rekomendasi Anda ditolak. Silakan cek catatan admin.',
            'pending' => 'Pengajuan surat rekomendasi Anda sedang menunggu peninjauan admin.',
            default => $this->details ?? 'Status pengajuan surat rekomendasi diperbarui.',
        };

        if ($this->status === 'rejected' && filled($this->adminNote)) {
            $body .= ' Alasan: ' . $this->adminNote;
        }

        return [
            'type' => 'ept_submission_status',
            'submission_id' => $this->submissionId,
            'status' => $this->status,
            'title' => $title,
            'body' => $body,
            'url' => $this->verificationUrl ?? route('dashboard.ept'),
            'color' => match ($this->status) {
                'approved' => 'emerald',
                'rejected' => 'rose',
                'pending' => 'amber',
                default => 'slate',
            },
            'icon' => match ($this->status) {
                'approved' => 'fa-solid fa-file-circle-check',
                'rejected' => 'fa-solid fa-file-circle-xmark',
                'pending' => 'fa-solid fa-file-circle-question',
                default => 'fa-solid fa-file-lines',
            },
        ];
    }
}
