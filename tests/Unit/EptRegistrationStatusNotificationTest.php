<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\EptRegistrationStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EptRegistrationStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function approved_status_is_sent_via_mail_and_dashboard(): void
    {
        $user = User::factory()->create([
            'whatsapp' => '628111111111',
            'whatsapp_verified_at' => now(),
        ]);

        $notification = new EptRegistrationStatusNotification(
            status: 'approved',
            dashboardUrl: 'https://example.test/ept',
        );

        $this->assertSame(['mail', 'database'], $notification->via($user));
        $this->assertSame(['database' => 'sync'], $notification->viaConnections());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rejected_status_uses_mail_dashboard_and_whatsapp_when_verified(): void
    {
        $user = User::factory()->create([
            'whatsapp' => '628111111111',
            'whatsapp_verified_at' => now(),
        ]);

        $notification = new EptRegistrationStatusNotification(
            status: 'rejected',
            dashboardUrl: 'https://example.test/ept',
            rejectionReason: 'Bukti tidak valid',
        );

        $this->assertSame(['mail', 'database', 'whatsapp'], $notification->via($user));
        $this->assertSame(['database' => 'sync'], $notification->viaConnections());
    }
}
