<?php

namespace App\Support;

use App\Models\EptSubmission;
use App\Models\EptSubmissionNotification;
use App\Notifications\EptSubmissionStatusNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\SendQueuedNotifications;
use Throwable;

class EptSubmissionNotificationTracker
{
    public static function prime(
        EptSubmission $submission,
        bool $expectsWhatsApp,
        array $channels,
        string $contentSignature,
    ): EptSubmissionNotification {
        $now = now();

        $record = EptSubmissionNotification::query()->firstOrNew([
            'ept_submission_id' => $submission->getKey(),
        ]);

        $record->fill([
            'content_signature' => $contentSignature,
            'last_requested_at' => $now,
        ]);

        if (in_array('database', $channels, true)) {
            $record->fill([
                'dashboard_status' => EptSubmissionNotification::STATUS_QUEUED,
                'dashboard_sent_at' => null,
                'dashboard_failed_at' => null,
                'dashboard_error' => null,
            ]);
        }

        if (in_array('mail', $channels, true)) {
            $record->fill([
                'mail_status' => EptSubmissionNotification::STATUS_QUEUED,
                'mail_queued_at' => $now,
                'mail_sent_at' => null,
                'mail_failed_at' => null,
                'mail_error' => null,
            ]);
        }

        if ($expectsWhatsApp) {
            if (in_array('whatsapp', $channels, true)) {
                $record->fill([
                    'whatsapp_status' => EptSubmissionNotification::STATUS_QUEUED,
                    'whatsapp_queued_at' => $now,
                    'whatsapp_sent_at' => null,
                    'whatsapp_failed_at' => null,
                    'whatsapp_error' => null,
                ]);
            }
        } else {
            $record->fill([
                'whatsapp_status' => EptSubmissionNotification::STATUS_SKIPPED,
                'whatsapp_queued_at' => null,
                'whatsapp_sent_at' => null,
                'whatsapp_failed_at' => null,
                'whatsapp_error' => null,
            ]);
        }

        $record->save();

        return $record;
    }

    public static function tracking(int $submissionId, string $contentSignature): array
    {
        return [
            'kind' => 'ept_submission',
            'submission_id' => $submissionId,
            'content_signature' => $contentSignature,
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
                'dashboard_status' => EptSubmissionNotification::STATUS_SENT,
                'dashboard_sent_at' => $now,
                'dashboard_failed_at' => null,
                'dashboard_error' => null,
            ])->save();

            return;
        }

        if ($event->channel === 'mail') {
            $record->forceFill([
                'mail_status' => EptSubmissionNotification::STATUS_SENT,
                'mail_sent_at' => $now,
                'mail_failed_at' => null,
                'mail_error' => null,
            ])->save();

            return;
        }

        if ($event->channel === 'whatsapp' && $event->response === false) {
            $record->forceFill([
                'whatsapp_status' => EptSubmissionNotification::STATUS_FAILED,
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
                'dashboard_status' => EptSubmissionNotification::STATUS_FAILED,
                'dashboard_failed_at' => $now,
                'dashboard_error' => $message,
            ])->save();

            return;
        }

        if ($channel === 'mail') {
            $record->forceFill([
                'mail_status' => EptSubmissionNotification::STATUS_FAILED,
                'mail_failed_at' => $now,
                'mail_error' => $message,
            ])->save();

            return;
        }

        if ($channel === 'whatsapp') {
            $record->forceFill([
                'whatsapp_status' => EptSubmissionNotification::STATUS_FAILED,
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
            'whatsapp_status' => EptSubmissionNotification::STATUS_SENT,
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
            'whatsapp_status' => EptSubmissionNotification::STATUS_FAILED,
            'whatsapp_failed_at' => now(),
            'whatsapp_error' => filled($error) ? mb_strimwidth($error, 0, 1000, '...') : 'Gagal mengirim WhatsApp.',
        ])->save();
    }

    public static function markDispatchFailure(
        EptSubmissionNotification $record,
        bool $expectsWhatsApp,
        string $error,
    ): void {
        $message = mb_strimwidth($error, 0, 1000, '...');
        $now = now();
        $updates = [];

        if ($record->dashboard_status === EptSubmissionNotification::STATUS_QUEUED) {
            $updates['dashboard_status'] = EptSubmissionNotification::STATUS_FAILED;
            $updates['dashboard_failed_at'] = $now;
            $updates['dashboard_error'] = $message;
        }

        if ($record->mail_status === EptSubmissionNotification::STATUS_QUEUED) {
            $updates['mail_status'] = EptSubmissionNotification::STATUS_FAILED;
            $updates['mail_failed_at'] = $now;
            $updates['mail_error'] = $message;
        }

        if ($expectsWhatsApp && $record->whatsapp_status === EptSubmissionNotification::STATUS_QUEUED) {
            $updates['whatsapp_status'] = EptSubmissionNotification::STATUS_FAILED;
            $updates['whatsapp_failed_at'] = $now;
            $updates['whatsapp_error'] = $message;
        }

        if ($updates !== []) {
            $record->forceFill($updates)->save();
        }
    }

    protected static function extractTrackingFromNotification(object $notification): ?array
    {
        if (! $notification instanceof EptSubmissionStatusNotification) {
            return null;
        }

        if (! filled($notification->submissionId) || ! filled($notification->contentSignature)) {
            return null;
        }

        return [
            'submission_id' => (int) $notification->submissionId,
            'content_signature' => (string) $notification->contentSignature,
        ];
    }

    protected static function findRecord(?array $tracking): ?EptSubmissionNotification
    {
        if (blank($tracking['submission_id'] ?? null) || blank($tracking['content_signature'] ?? null)) {
            return null;
        }

        return EptSubmissionNotification::query()
            ->where('ept_submission_id', (int) $tracking['submission_id'])
            ->where('content_signature', (string) $tracking['content_signature'])
            ->first();
    }
}
