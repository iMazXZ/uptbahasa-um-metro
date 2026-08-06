<?php

namespace Tests\Unit;

use App\Models\EptGroup;
use App\Models\EptRegistration;
use App\Models\EptScheduleNotification;
use App\Models\User;
use App\Notifications\EptScheduleAssignedNotification;
use App\Support\EptScheduleNotificationTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EptScheduleNotificationTrackerTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_primes_and_marks_mail_dashboard_sent(): void
    {
        $user = User::factory()->create([
            'whatsapp' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $group = EptGroup::query()->create([
            'name' => '07 E 25/26',
            'quota' => 20,
            'jadwal' => Carbon::parse('2026-04-20 08:00:00'),
            'lokasi' => 'Lab Bahasa',
        ]);

        $registration = EptRegistration::query()->create([
            'user_id' => $user->getKey(),
            'student_status' => EptRegistration::STUDENT_STATUS_REGULAR,
            'test_quota' => EptRegistration::DEFAULT_MULTI_TEST_QUOTA,
            'bukti_pembayaran' => 'ept-registrations/payments/example.webp',
            'status' => 'approved',
            'grup_1_id' => $group->getKey(),
        ]);

        $tracking = EptScheduleNotificationTracker::prime($registration, $group, 1, true, ['database', 'mail', 'whatsapp']);

        $this->assertSame(EptScheduleNotification::STATUS_QUEUED, $tracking->mail_status);
        $this->assertSame(EptScheduleNotification::STATUS_QUEUED, $tracking->dashboard_status);
        $this->assertSame(EptScheduleNotification::STATUS_QUEUED, $tracking->whatsapp_status);

        $notification = new EptScheduleAssignedNotification(
            registrationId: (int) $registration->getKey(),
            groupId: (int) $group->getKey(),
            testNumber: 1,
            groupName: $group->name,
            scheduledAt: $group->jadwal,
            location: (string) $group->lokasi,
            contentSignature: (string) $tracking->content_signature,
            dashboardUrl: 'https://example.test/dashboard/ept',
        );

        EptScheduleNotificationTracker::handleNotificationSent(
            new NotificationSent($user, $notification, 'database')
        );
        EptScheduleNotificationTracker::handleNotificationSent(
            new NotificationSent($user, $notification, 'mail')
        );

        $tracking->refresh();

        $this->assertSame(EptScheduleNotification::STATUS_SENT, $tracking->dashboard_status);
        $this->assertNotNull($tracking->dashboard_sent_at);
        $this->assertSame(EptScheduleNotification::STATUS_SENT, $tracking->mail_status);
        $this->assertNotNull($tracking->mail_sent_at);
        $this->assertSame(EptScheduleNotification::STATUS_QUEUED, $tracking->whatsapp_status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_marks_whatsapp_as_skipped_for_unverified_numbers(): void
    {
        $user = User::factory()->create([
            'whatsapp' => null,
            'whatsapp_verified_at' => null,
        ]);

        $group = EptGroup::query()->create([
            'name' => '08 E 25/26',
            'quota' => 20,
            'jadwal' => Carbon::parse('2026-04-21 10:00:00'),
            'lokasi' => 'Ruang Stanford',
        ]);

        $registration = EptRegistration::query()->create([
            'user_id' => $user->getKey(),
            'student_status' => EptRegistration::STUDENT_STATUS_GENERAL,
            'test_quota' => EptRegistration::GENERAL_TEST_QUOTA,
            'bukti_pembayaran' => 'ept-registrations/payments/example-2.webp',
            'status' => 'approved',
            'grup_1_id' => $group->getKey(),
        ]);

        $tracking = EptScheduleNotificationTracker::prime($registration, $group, 1, false, ['database', 'mail']);

        $this->assertSame(EptScheduleNotification::STATUS_SKIPPED, $tracking->whatsapp_status);
        $this->assertFalse($tracking->isFullySent(false, $group));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_only_resends_failed_channels_for_current_group_state(): void
    {
        $user = User::factory()->create();

        $group = EptGroup::query()->create([
            'name' => '09 E 25/26',
            'quota' => 20,
            'jadwal' => Carbon::parse('2026-04-22 13:00:00'),
            'lokasi' => 'Lab Bahasa',
        ]);

        $registration = EptRegistration::query()->create([
            'user_id' => $user->getKey(),
            'student_status' => EptRegistration::STUDENT_STATUS_REGULAR,
            'test_quota' => EptRegistration::DEFAULT_MULTI_TEST_QUOTA,
            'bukti_pembayaran' => 'ept-registrations/payments/example-3.webp',
            'status' => 'approved',
            'grup_1_id' => $group->getKey(),
        ]);

        $record = EptScheduleNotification::query()->create([
            'ept_registration_id' => $registration->getKey(),
            'ept_group_id' => $group->getKey(),
            'test_number' => 1,
            'content_signature' => EptScheduleNotification::signatureForGroup($group),
            'dashboard_status' => EptScheduleNotification::STATUS_SENT,
            'mail_status' => EptScheduleNotification::STATUS_SENT,
            'whatsapp_status' => EptScheduleNotification::STATUS_FAILED,
        ]);

        $channels = EptScheduleNotificationTracker::channelsToDispatch($record, $group, true);

        $this->assertSame(['whatsapp'], $channels);
    }
}
