<?php

namespace App\Notifications;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class EptScheduleAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $registrationId,
        public int $groupId,
        public int $testNumber,
        public string $groupName,
        public Carbon $scheduledAt,
        public string $location,
        public string $contentSignature,
        public string $dashboardUrl,
        public array $requestedChannels = ['mail', 'database', 'whatsapp'],
    ) {}

    public function via(object $notifiable): array
    {
        $channels = array_values(array_intersect(
            ['mail', 'database', 'whatsapp'],
            $this->requestedChannels,
        ));

        if (in_array('whatsapp', $channels, true) && (! filled($notifiable->whatsapp) || ! filled($notifiable->whatsapp_verified_at))) {
            $channels = array_values(array_diff($channels, ['whatsapp']));
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
        $message = "*Jadwal Tes EPT Ditetapkan*\n\n";
        $message .= "Yth. *{$notifiable->name}*,\n\n";
        $message .= "Jadwal *Tes ke-{$this->testNumber}* EPT Anda telah ditetapkan:\n\n";
        $message .= "*Grup:* {$this->groupName}\n";
        $message .= "*Waktu:* {$this->formattedSchedule()} WIB\n";
        $message .= "*Lokasi:* {$this->location}\n\n";
        $message .= "Silakan download dan cetak Kartu Peserta melalui:\n{$this->dashboardUrl}\n\n";
        $message .= "Setelah tes selesai, nilai dan kelulusan tidak dikirim via WA. Silakan cek mandiri di:\nhttps://lembagabahasa.site/nilai-ujian\n\n";
        $message .= "_Wajib print & membawa kartu peserta dan KTP/Kartu Mahasiswa setiap kali tes._";

        return app(WhatsAppService::class)->queueMessage($notifiable->whatsapp, $message, [
            'registration_id' => $this->registrationId,
            'group_id' => $this->groupId,
            'test_number' => $this->testNumber,
            'content_signature' => $this->contentSignature,
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Jadwal Tes EPT Ditetapkan')
            ->greeting("Yth. {$notifiable->name},")
            ->line("Jadwal Tes ke-{$this->testNumber} EPT Anda telah ditetapkan.")
            ->line("Grup: {$this->groupName}")
            ->line("Waktu: {$this->formattedSchedule()} WIB")
            ->line("Lokasi: {$this->location}")
            ->action('Buka Dashboard EPT', $this->dashboardUrl)
            ->line('Silakan download dan cetak Kartu Peserta dari dashboard.')
            ->line('Setelah tes selesai, nilai dan kelulusan tidak dikirim via WhatsApp. Silakan cek mandiri di https://lembagabahasa.site/nilai-ujian')
            ->line('Wajib print dan membawa kartu peserta serta KTP/Kartu Mahasiswa setiap kali tes.')
            ->salutation('Hormat kami, Admin Lembaga Bahasa UM Metro');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ept_schedule_assigned',
            'status' => 'scheduled',
            'title' => 'Jadwal Tes EPT Ditetapkan',
            'body' => "Tes ke-{$this->testNumber}, Grup {$this->groupName}, {$this->formattedSchedule()} WIB di {$this->location}.",
            'url' => $this->dashboardUrl,
            'color' => 'blue',
            'icon' => 'fa-solid fa-calendar-check',
        ];
    }

    protected function formattedSchedule(): string
    {
        return $this->scheduledAt->copy()->translatedFormat('l, d F Y H:i');
    }
}
