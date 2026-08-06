<?php

namespace App\Support;

use App\Models\EptGroup;
use App\Models\EptRegistration;
use App\Models\EptScheduleNotification;
use App\Notifications\EptScheduleAssignedNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\SendQueuedNotifications;
use Throwable;

class EptScheduleNotificationTracker
{
    public static function prime(
        EptRegistration $registration,
        EptGroup $group,
        int $testNumber,
        bool $expectsWhatsApp,
        array $channels,
    ): EptScheduleNotification {
        $now = now();
        $signature = EptScheduleNotification::signatureForGroup($group);

        $record = EptScheduleNotification::query()->firstOrNew([
            'ept_registration_id' => $registration->getKey(),
            'ept_group_id' => $group->getKey(),
            'test_number' => $testNumber,
        ]);

        $record->fill([
            'content_signature' => $signature,
            'last_requested_at' => $now,
        ]);

        if (in_array('database', $channels, true)) {
            $record->fill([
                'dashboard_status' => EptScheduleNotification::STATUS_QUEUED,
                'dashboard_sent_at' => null,
                'dashboard_failed_at' => null,
                'dashboard_error' => null,
            ]);
        }

        if (in_array('mail', $channels, true)) {
            $record->fill([
                'mail_status' => EptScheduleNotification::STATUS_QUEUED,
                'mail_queued_at' => $now,
                'mail_sent_at' => null,
                'mail_failed_at' => null,
                'mail_error' => null,
            ]);
        }

        if ($expectsWhatsApp) {
            if (in_array('whatsapp', $channels, true)) {
                $record->fill([
                    'whatsapp_status' => EptScheduleNotification::STATUS_QUEUED,
                    'whatsapp_queued_at' => $now,
                    'whatsapp_sent_at' => null,
                    'whatsapp_failed_at' => null,
                    'whatsapp_error' => null,
                ]);
            }
        } else {
            $record->fill([
                'whatsapp_status' => EptScheduleNotification::STATUS_SKIPPED,
                'whatsapp_queued_at' => null,
                'whatsapp_sent_at' => null,
                'whatsapp_failed_at' => null,
                'whatsapp_error' => null,
            ]);
        }

        $record->save();

        return $record;
    }

    public static function shouldDispatch(
        ?EptScheduleNotification $record,
        EptGroup $group,
        bool $expectsWhatsApp,
        bool $forceResend = false,
    ): bool {
        return static::channelsToDispatch($record, $group, $expectsWhatsApp, $forceResend) !== [];
    }

    public static function channelsToDispatch(
        ?EptScheduleNotification $record,
        EptGroup $group,
        bool $expectsWhatsApp,
        bool $forceResend = false,
    ): array {
        $channels = ['database', 'mail'];

        if ($expectsWhatsApp) {
            $channels[] = 'whatsapp';
        }

        if ($forceResend || $record === null || ! $record->matchesCurrentGroupState($group)) {
            return $channels;
        }

        $pendingChannels = [];

        if (in_array($record->dashboard_status, [null, EptScheduleNotification::STATUS_NOT_SENT, EptScheduleNotification::STATUS_FAILED], true)) {
            $pendingChannels[] = 'database';
        }

        if (in_array($record->mail_status, [null, EptScheduleNotification::STATUS_NOT_SENT, EptScheduleNotification::STATUS_FAILED], true)) {
            $pendingChannels[] = 'mail';
        }

        if (
            $expectsWhatsApp
            && in_array($record->whatsapp_status, [null, EptScheduleNotification::STATUS_NOT_SENT, EptScheduleNotification::STATUS_FAILED], true)
        ) {
            $pendingChannels[] = 'whatsapp';
        }

        return $pendingChannels;
    }

    public static function notificationTracking(
        EptRegistration $registration,
        EptGroup $group,
    ): array {
        return [
            'registration_id' => $registration->getKey(),
            'group_id' => $group->getKey(),
            'test_number' => $registration->testNumberForGroupId((int) $group->getKey()),
            'content_signature' => EptScheduleNotification::signatureForGroup($group),
        ];
    }

    public static function handleNotificationSent(NotificationSent $event): void
    {
        $tracking = static::extractTrackingFromNotification($event->notification);

        if ($tracking === null) {
            return;
        }

        $record = static::findRecord($tracking);

        if (! $record) {
            return;
        }

        $now = now();

        if ($event->channel === 'database') {
            $record->forceFill([
                'dashboard_status' => EptScheduleNotification::STATUS_SENT,
                'dashboard_sent_at' => $now,
                'dashboard_failed_at' => null,
                'dashboard_error' => null,
            ])->save();

            return;
        }

        if ($event->channel === 'mail') {
            $record->forceFill([
                'mail_status' => EptScheduleNotification::STATUS_SENT,
                'mail_sent_at' => $now,
                'mail_failed_at' => null,
                'mail_error' => null,
            ])->save();

            return;
        }

        if ($event->channel === 'whatsapp' && $event->response === false) {
            $record->forceFill([
                'whatsapp_status' => EptScheduleNotification::STATUS_FAILED,
                'whatsapp_failed_at' => $now,
                'whatsapp_error' => 'Pesan tidak berhasil masuk antrean WhatsApp.',
            ])->save();
        }
    }

    public static function handleQueuedNotificationFailure(
        SendQueuedNotifications $job,
        Throwable $exception,
    ): void {
        $tracking = static::extractTrackingFromNotification($job->notification);

        if ($tracking === null) {
            return;
        }

        $record = static::findRecord($tracking);

        if (! $record) {
            return;
        }

        $channel = $job->channels[0] ?? null;
        $now = now();
        $message = mb_strimwidth($exception->getMessage(), 0, 1000, '...');

        if ($channel === 'database') {
            $record->forceFill([
                'dashboard_status' => EptScheduleNotification::STATUS_FAILED,
                'dashboard_failed_at' => $now,
                'dashboard_error' => $message,
            ])->save();

            return;
        }

        if ($channel === 'mail') {
            $record->forceFill([
                'mail_status' => EptScheduleNotification::STATUS_FAILED,
                'mail_failed_at' => $now,
                'mail_error' => $message,
            ])->save();

            return;
        }

        if ($channel === 'whatsapp') {
            $record->forceFill([
                'whatsapp_status' => EptScheduleNotification::STATUS_FAILED,
                'whatsapp_failed_at' => $now,
                'whatsapp_error' => $message,
            ])->save();
        }
    }

    public static function markWhatsAppSent(?array $tracking): void
    {
        $record = static::findRecord($tracking);

        if (! $record) {
            return;
        }

        $record->forceFill([
            'whatsapp_status' => EptScheduleNotification::STATUS_SENT,
            'whatsapp_sent_at' => now(),
            'whatsapp_failed_at' => null,
            'whatsapp_error' => null,
        ])->save();
    }

    public static function markWhatsAppFailed(?array $tracking, ?string $error = null): void
    {
        $record = static::findRecord($tracking);

        if (! $record) {
            return;
        }

        $record->forceFill([
            'whatsapp_status' => EptScheduleNotification::STATUS_FAILED,
            'whatsapp_failed_at' => now(),
            'whatsapp_error' => filled($error) ? mb_strimwidth($error, 0, 1000, '...') : 'Gagal mengirim WhatsApp.',
        ])->save();
    }

    public static function markDispatchFailure(
        EptScheduleNotification $record,
        bool $expectsWhatsApp,
        string $error,
    ): void {
        $message = mb_strimwidth($error, 0, 1000, '...');
        $now = now();
        $updates = [];

        if ($record->dashboard_status === EptScheduleNotification::STATUS_QUEUED) {
            $updates['dashboard_status'] = EptScheduleNotification::STATUS_FAILED;
            $updates['dashboard_failed_at'] = $now;
            $updates['dashboard_error'] = $message;
        }

        if ($record->mail_status === EptScheduleNotification::STATUS_QUEUED) {
            $updates['mail_status'] = EptScheduleNotification::STATUS_FAILED;
            $updates['mail_failed_at'] = $now;
            $updates['mail_error'] = $message;
        }

        if ($expectsWhatsApp && $record->whatsapp_status === EptScheduleNotification::STATUS_QUEUED) {
            $updates['whatsapp_status'] = EptScheduleNotification::STATUS_FAILED;
            $updates['whatsapp_failed_at'] = $now;
            $updates['whatsapp_error'] = $message;
        }

        if ($updates !== []) {
            $record->forceFill($updates)->save();
        }
    }

    public static function jobFromPayload(array $payload): ?SendQueuedNotifications
    {
        $serialized = data_get($payload, 'data.command');

        if (! is_string($serialized) || $serialized === '') {
            return null;
        }

        try {
            $job = unserialize($serialized, ['allowed_classes' => true]);
        } catch (Throwable) {
            return null;
        }

        return $job instanceof SendQueuedNotifications ? $job : null;
    }

    protected static function extractTrackingFromNotification(object $notification): ?array
    {
        if (! $notification instanceof EptScheduleAssignedNotification) {
            return null;
        }

        if (
            ! filled($notification->registrationId)
            || ! filled($notification->groupId)
            || ! filled($notification->testNumber)
            || ! filled($notification->contentSignature)
        ) {
            return null;
        }

        return [
            'registration_id' => (int) $notification->registrationId,
            'group_id' => (int) $notification->groupId,
            'test_number' => (int) $notification->testNumber,
            'content_signature' => (string) $notification->contentSignature,
        ];
    }

    protected static function findRecord(?array $tracking): ?EptScheduleNotification
    {
        if (
            blank($tracking['registration_id'] ?? null)
            || blank($tracking['group_id'] ?? null)
            || blank($tracking['test_number'] ?? null)
            || blank($tracking['content_signature'] ?? null)
        ) {
            return null;
        }

        return EptScheduleNotification::query()
            ->where('ept_registration_id', $tracking['registration_id'])
            ->where('ept_group_id', $tracking['group_id'])
            ->where('test_number', $tracking['test_number'])
            ->where('content_signature', $tracking['content_signature'])
            ->first();
    }
}
