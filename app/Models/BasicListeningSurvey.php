<?php

namespace App\Models;

use App\Models\BasicListeningCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BasicListeningSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'require_for_certificate',
        'target',         // 'final' | 'session'
        'session_id',     // opsional jika target='session'
        'starts_at',
        'ends_at',
        'is_active',
        'category',
        'sort_order',
    ];

    protected $casts = [
        'require_for_certificate' => 'boolean',
        'is_active'               => 'boolean',
        'starts_at'               => 'datetime',
        'ends_at'                 => 'datetime',
        'category'                => 'string',
        'sort_order'              => 'integer',
    ];

    /** Pertanyaan-pertanyaan pada survey ini */
    public function questions(): HasMany
    {
        return $this->hasMany(BasicListeningSurveyQuestion::class, 'survey_id')
                    ->orderBy('order');
    }

    /** Jawaban/respons per user */
    public function responses(): HasMany
    {
        return $this->hasMany(BasicListeningSurveyResponse::class, 'survey_id');
    }

    /** Jika kamu punya model BasicListeningSession, aktifkan relasi ini */
    public function session(): BelongsTo
    {
        return $this->belongsTo(BasicListeningSession::class, 'session_id');
    }

    /** Relasi ke master kategori (berdasarkan slug) */
    public function categoryDefinition(): BelongsTo
    {
        return $this->belongsTo(BasicListeningCategory::class, 'category', 'slug');
    }

    public function scopeRequiredFinal($q) {
        return $q->where('require_for_certificate', true)
                ->where('target', 'final')
                ->where('is_active', true);
    }

    /** Cek apakah survey sedang terbuka untuk diisi */
    public function isOpen(): bool
    {
        $now = now();
        if (! $this->is_active) return false;
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at && $now->gt($this->ends_at)) return false;
        return true;
    }

    /** Label kategori yang user-friendly */
    public function getCategoryLabelAttribute(): string
    {
        if ($this->relationLoaded('categoryDefinition') && $this->categoryDefinition) {
            return $this->categoryDefinition->name;
        }

        return ucfirst((string) $this->category);
    }
}
