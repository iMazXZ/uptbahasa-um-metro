<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\EptSubmissionStatusNotification;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EptSubmissionStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_mail_dashboard_and_whatsapp_for_approved_when_verified(): void
    {
        $user = User::factory()->create([
            'whatsapp' => '628111111111',
            'whatsapp_verified_at' => now(),
        ]);

        $notification = new EptSubmissionStatusNotification(status: 'approved');

        $channels = $notification->via($user);

        $this->assertEquals(['mail', 'database', 'whatsapp'], $channels);
        $this->assertSame(['database' => 'sync'], $notification->viaConnections());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_mail_and_dashboard_only_for_pending_even_when_whatsapp_verified(): void
    {
        $user = User::factory()->create([
            'whatsapp' => '628111111111',
            'whatsapp_verified_at' => now(),
        ]);

        $notification = new EptSubmissionStatusNotification(status: 'pending');

        $channels = $notification->via($user);

        $this->assertEquals(['mail', 'database'], $channels);
        $this->assertSame(['database' => 'sync'], $notification->viaConnections());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_short_whatsapp_message_for_approved_submission(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'whatsapp' => '628111111111',
            'whatsapp_verified_at' => now(),
        ]);

        $expectedMessage = "*Surat Rekomendasi EPT Disetujui*\n\n"
            . "Yth. *Budi Santoso*,\n\n"
            . "Surat rekomendasi EPT Anda sudah dibuat.\n\n"
            . "Catatan:\nSilakan legalisir bila diperlukan.\n\n"
            . "Unduh, cetak, lalu bawa ke Kantor Lembaga Bahasa untuk cap basah:\n"
            . 'https://example.test/surat-rekomendasi.pdf';

        $this->mock(WhatsAppService::class, function ($mock) use ($expectedMessage) {
            $mock->shouldReceive('queueMessage')
                ->once()
                ->with('628111111111', $expectedMessage, Mockery::any())
                ->andReturn(true);
        });

        $notification = new EptSubmissionStatusNotification(
            status: 'approved',
            verificationUrl: 'https://example.test/verifikasi',
            pdfUrl: 'https://example.test/surat-rekomendasi.pdf',
            adminNote: 'Silakan legalisir bila diperlukan.',
        );

        $this->assertTrue($notification->toWhatsApp($user));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_short_whatsapp_message_for_rejected_submission(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'whatsapp' => '628111111111',
            'whatsapp_verified_at' => now(),
        ]);

        $expectedMessage = "*Surat Rekomendasi EPT Ditolak*\n\n"
            . "Yth. *Budi Santoso*,\n\n"
            . "Pengajuan Anda ditolak.\n\n"
            . "Alasan:\nBukti nilai tidak terbaca.\n\n"
            . "Perbaiki dan ajukan ulang di:\n"
            . route('dashboard.ept');

        $this->mock(WhatsAppService::class, function ($mock) use ($expectedMessage) {
            $mock->shouldReceive('queueMessage')
                ->once()
                ->with('628111111111', $expectedMessage, Mockery::any())
                ->andReturn(true);
        });

        $notification = new EptSubmissionStatusNotification(
            status: 'rejected',
            adminNote: 'Bukti nilai tidak terbaca.',
        );

        $this->assertTrue($notification->toWhatsApp($user));
    }
}
