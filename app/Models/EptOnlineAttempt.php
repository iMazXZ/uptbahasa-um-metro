<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class EptOnlineAttempt extends Model
{
    public const TAB_SWITCH_LIMIT = 3;
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'public_id',
        'form_id',
        'access_token_id',
        'user_id',
        'ept_registration_id',
        'ept_group_id',
        'current_section_type',
        'status',
        'started_at',
        'current_section_started_at',
        'submitted_at',
        'expires_at',
        'ip_address',
        'user_agent',
        'integrity_flags',
        'meta',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'current_section_started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
        'integrity_flags' => 'array',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $attempt): void {
            if (blank($attempt->public_id)) {
                $attempt->public_id = (string) Str::ulid();
            }
        });
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EptOnlineForm::class, 'form_id');
    }

    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(EptOnlineAccessToken::class, 'access_token_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EptRegistration::class, 'ept_registration_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(EptGroup::class, 'ept_group_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EptOnlineAnswer::class, 'attempt_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(EptOnlineResult::class, 'attempt_id');
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
