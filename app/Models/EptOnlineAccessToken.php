<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EptOnlineAccessToken extends Model
{
    protected $fillable = [
        'form_id',
        'user_id',
        'ept_registration_id',
        'ept_group_id',
        'token_hash',
        'token_hint',
        'starts_at',
        'ends_at',
        'max_attempts',
        'used_attempts',
        'is_active',
        'last_used_at',
        'revoked_at',
        'meta',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_attempts' => 'integer',
        'used_attempts' => 'integer',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'meta' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(EptOnlineForm::class, 'form_id');
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

    public function attempts(): HasMany
    {
        return $this->hasMany(EptOnlineAttempt::class, 'access_token_id');
    }

    public function withinWindow(): bool
    {
        $now = now();

        return $this->is_active
            && ! $this->revoked_at
            && (! $this->starts_at || $now->greaterThanOrEqualTo($this->starts_at))
            && (! $this->ends_at || $now->lessThanOrEqualTo($this->ends_at));
    }
}
