<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\EptScheduleAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EptScheduleAssignedNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_mail_dashboard_and_whatsapp_when_user_has_verified_number(): void
    {
        $user = User::factory()->create([
            'whatsapp' => '628222222222',
            'whatsapp_verified_at' => now(),
        ]);

        $notification = new EptScheduleAssignedNotification(
            registrationId: 10,
            groupId: 20,
            testNumber: 2,
            groupName: '07 E 25/26',
            scheduledAt: Carbon::parse('2026-04-10 09:00:00'),
            location: 'Lab Bahasa',
            contentSignature: hash('sha256', 'example'),
            dashboardUrl: 'https://example.test/ept',
        );

        $this->assertSame(['mail', 'database', 'whatsapp'], $notification->via($user));
        $this->assertSame(['database' => 'sync'], $notification->viaConnections());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_falls_back_to_mail_and_dashboard_when_whatsapp_is_not_verified(): void
    {
        $user = User::factory()->create([
            'whatsapp' => '628222222222',
            'whatsapp_verified_at' => null,
        ]);

        $notification = new EptScheduleAssignedNotification(
            registrationId: 10,
            groupId: 20,
            testNumber: 1,
            groupName: '07 E 25/26',
            scheduledAt: Carbon::parse('2026-04-10 09:00:00'),
            location: 'Lab Bahasa',
            contentSignature: hash('sha256', 'example'),
            dashboardUrl: 'https://example.test/ept',
        );

        $this->assertSame(['mail', 'database'], $notification->via($user));
        $this->assertSame(['database' => 'sync'], $notification->viaConnections());
    }
}
