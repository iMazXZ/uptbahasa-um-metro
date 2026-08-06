<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EptSubmissionNotification extends Model
{
    public const STATUS_NOT_SENT = 'not_sent';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'ept_submission_id',
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

    public function submission(): BelongsTo
    {
        return $this->belongsTo(EptSubmission::class, 'ept_submission_id');
    }

    public function matchesSignature(?string $signature): bool
    {
        return filled($signature)
            && filled($this->content_signature)
            && hash_equals((string) $this->content_signature, (string) $signature);
    }

    public function isFullySent(bool $expectsWhatsApp): bool
    {
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

    public function overallStatus(bool $expectsWhatsApp): string
    {
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

        if ($this->isFullySent($expectsWhatsApp)) {
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
            default => 'gray',
        };
    }
}
