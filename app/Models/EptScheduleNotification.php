<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EptScheduleNotification extends Model
{
    public const STATUS_NOT_SENT = 'not_sent';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_OUTDATED = 'outdated';

    protected $fillable = [
        'ept_registration_id',
        'ept_group_id',
        'test_number',
        'content_signature',
        'last_requested_at',
        'dashboard_status',
        'dashboard_sent_at',
        'dashboard_failed_at',
        'dashboard_error',
        'mail_status',
        'mail_queued_at',
        'mail_sent_at',
        'mail_failed_at',
        'mail_error',
        'whatsapp_status',
        'whatsapp_queued_at',
        'whatsapp_sent_at',
        'whatsapp_failed_at',
        'whatsapp_error',
    ];

    protected $casts = [
        'last_requested_at' => 'datetime',
        'dashboard_sent_at' => 'datetime',
        'dashboard_failed_at' => 'datetime',
        'mail_queued_at' => 'datetime',
        'mail_sent_at' => 'datetime',
        'mail_failed_at' => 'datetime',
        'whatsapp_queued_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
        'whatsapp_failed_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EptRegistration::class, 'ept_registration_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(EptGroup::class, 'ept_group_id');
    }

    public static function signatureForGroup(EptGroup $group): string
    {
        return hash('sha256', implode('|', [
            (string) $group->getKey(),
            trim((string) $group->name),
            $group->jadwal?->format('Y-m-d H:i:s') ?? '',
            trim((string) $group->lokasi),
        ]));
    }

    public function matchesCurrentGroupState(EptGroup $group): bool
    {
        return filled($this->content_signature)
            && hash_equals($this->content_signature, static::signatureForGroup($group));
    }

    public function isFullySent(bool $expectsWhatsApp, ?EptGroup $group = null): bool
    {
        if ($group && ! $this->matchesCurrentGroupState($group)) {
            return false;
        }

        if ($this->dashboard_status !== static::STATUS_SENT) {
            return false;
        }

        if ($this->mail_status !== static::STATUS_SENT) {
            return false;
        }

        if ($expectsWhatsApp) {
            return $this->whatsapp_status === static::STATUS_SENT;
        }

        return true;
    }

    public function overallStatus(bool $expectsWhatsApp, ?EptGroup $group = null): string
    {
        if ($group && ! $this->matchesCurrentGroupState($group)) {
            return static::STATUS_OUTDATED;
        }

        if (
            $this->dashboard_status === static::STATUS_FAILED
            || $this->mail_status === static::STATUS_FAILED
            || ($expectsWhatsApp && $this->whatsapp_status === static::STATUS_FAILED)
        ) {
            return static::STATUS_FAILED;
        }

        if (
            $this->dashboard_status === static::STATUS_QUEUED
            || $this->mail_status === static::STATUS_QUEUED
            || ($expectsWhatsApp && $this->whatsapp_status === static::STATUS_QUEUED)
        ) {
            return static::STATUS_QUEUED;
        }

        if ($this->isFullySent($expectsWhatsApp, $group)) {
            return static::STATUS_SENT;
        }

        return static::STATUS_NOT_SENT;
    }

    public function channelStatus(string $channel, bool $expectsWhatsApp = true): string
    {
        return match ($channel) {
            'dashboard' => $this->dashboard_status ?: static::STATUS_NOT_SENT,
            'mail' => $this->mail_status ?: static::STATUS_NOT_SENT,
            'whatsapp' => $expectsWhatsApp
                ? ($this->whatsapp_status ?: static::STATUS_NOT_SENT)
                : static::STATUS_SKIPPED,
            default => static::STATUS_NOT_SENT,
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            static::STATUS_QUEUED => 'Antrean',
            static::STATUS_SENT => 'Terkirim',
            static::STATUS_FAILED => 'Gagal',
            static::STATUS_SKIPPED => 'Dilewati',
            static::STATUS_OUTDATED => 'Perlu Kirim Ulang',
            default => 'Belum',
        };
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            static::STATUS_QUEUED => 'warning',
            static::STATUS_SENT => 'success',
            static::STATUS_FAILED => 'danger',
            static::STATUS_SKIPPED => 'gray',
            static::STATUS_OUTDATED => 'info',
            default => 'gray',
        };
    }
}
