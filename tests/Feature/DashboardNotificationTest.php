<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EptRegistrationStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_delete_owned_dashboard_notification(): void
    {
        $user = User::factory()->create();

        $user->notify(new EptRegistrationStatusNotification(
            status: 'approved',
            dashboardUrl: 'https://example.test/ept',
        ));

        $notificationId = $user->notifications()->value('id');

        $this->actingAs($user)
            ->post(route('dashboard.notifications.destroy', $notificationId))
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function delete_read_only_removes_read_notifications(): void
    {
        $user = User::factory()->create();

        $user->notify(new EptRegistrationStatusNotification(
            status: 'approved',
            dashboardUrl: 'https://example.test/ept',
        ));

        $user->notify(new EptRegistrationStatusNotification(
            status: 'rejected',
            dashboardUrl: 'https://example.test/ept',
            rejectionReason: 'Bukti tidak valid',
        ));

        $readNotification = $user->notifications()->latest()->first();
        $readNotification?->markAsRead();

        $unreadNotificationId = $user->notifications()
            ->whereNull('read_at')
            ->value('id');

        $this->actingAs($user)
            ->post(route('dashboard.notifications.destroy-read'))
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', [
            'id' => $readNotification?->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $unreadNotificationId,
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);
    }
}
