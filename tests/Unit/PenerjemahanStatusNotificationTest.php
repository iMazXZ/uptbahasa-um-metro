<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\PenerjemahanStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenerjemahanStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_mail_dashboard_and_whatsapp_for_finished_when_verified(): void
    {
        $user = User::factory()->create([
            'whatsapp' => '628222222222',
            'whatsapp_verified_at' => now(),
        ]);

        $notification = new PenerjemahanStatusNotification(status: 'Selesai');

        $channels = $notification->via($user);

        $this->assertEquals(['mail', 'database', 'whatsapp'], $channels);
        $this->assertSame(['database' => 'sync'], $notification->viaConnections());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_mail_and_dashboard_only_for_diproses(): void
    {
        $user = User::factory()->create([
            'whatsapp' => '628222222222',
            'whatsapp_verified_at' => now(),
        ]);

        $notification = new PenerjemahanStatusNotification(status: 'Diproses');

        $channels = $notification->via($user);

        $this->assertEquals(['mail', 'database'], $channels);
        $this->assertSame(['database' => 'sync'], $notification->viaConnections());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_falls_back_to_mail_and_dashboard_when_whatsapp_missing(): void
    {
        $user = User::factory()->create([
            'whatsapp' => null,
            'whatsapp_verified_at' => null,
        ]);

        $notification = new PenerjemahanStatusNotification(status: 'Diproses');

        $channels = $notification->via($user);

        $this->assertEquals(['mail', 'database'], $channels);
        $this->assertSame(['database' => 'sync'], $notification->viaConnections());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_rejection_reason_in_dashboard_notification(): void
    {
        $user = User::factory()->create();

        $notification = new PenerjemahanStatusNotification(
            status: 'Ditolak - Dokumen Tidak Valid',
            rejectionReason: 'Abstrak belum sesuai format.',
        );

        $data = $notification->toArray($user);

        $this->assertSame('Penerjemahan Ditolak', $data['title']);
        $this->assertStringContainsString('Alasan: Abstrak belum sesuai format.', $data['body']);
    }
}
