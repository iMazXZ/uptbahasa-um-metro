<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EptOnlineForm extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'code',
        'title',
        'description',
        'status',
        'listening_audio_path',
        'show_score_after_submit',
        'imported_at',
        'published_at',
        'last_import_summary',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'show_score_after_submit' => 'boolean',
        'imported_at' => 'datetime',
        'published_at' => 'datetime',
        'last_import_summary' => 'array',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(EptOnlineSection::class, 'form_id')->orderBy('sort_order');
    }

    public function passages(): HasMany
    {
        return $this->hasMany(EptOnlinePassage::class, 'form_id')->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(EptOnlineQuestion::class, 'form_id')->orderBy('sort_order');
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(EptOnlineAccessToken::class, 'form_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(EptOnlineAttempt::class, 'form_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }
}
