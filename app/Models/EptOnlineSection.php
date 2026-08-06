<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EptOnlineSection extends Model
{
    public const TYPE_LISTENING = 'listening';
    public const TYPE_STRUCTURE = 'structure';
    public const TYPE_READING = 'reading';

    protected $fillable = [
        'form_id',
        'type',
        'title',
        'instructions',
        'duration_minutes',
        'sort_order',
        'audio_path',
        'audio_duration_seconds',
        'meta',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'audio_duration_seconds' => 'integer',
        'meta' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(EptOnlineForm::class, 'form_id');
    }

    public function passages(): HasMany
    {
        return $this->hasMany(EptOnlinePassage::class, 'section_id')->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(EptOnlineQuestion::class, 'section_id')->orderBy('sort_order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EptOnlineAnswer::class, 'section_id');
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_LISTENING => 'Listening',
            self::TYPE_STRUCTURE => 'Structure',
            self::TYPE_READING => 'Reading',
        ];
    }
}
