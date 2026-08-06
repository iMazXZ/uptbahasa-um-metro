<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BasicListeningSurveyResponse extends Model
{
    use HasFactory;

    /**
     * Bila nama tabel Anda standar (basic_listening_survey_responses), ini bisa diabaikan.
     * protected $table = 'basic_listening_survey_responses';
     */

    protected $fillable = [
        'survey_id',
        'user_id',
        'session_id',    // null jika target = 'final'
        'submitted_at',  // null = draft
        'tutor_id',
        'supervisor_id',
        'meta',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'meta'         => 'array',
    ];

    /* =========================
     * Relations
     * ========================= */

    public function survey(): BelongsTo
    {
        return $this->belongsTo(BasicListeningSurvey::class, 'survey_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(BasicListeningSession::class, 'session_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(BasicListeningSupervisor::class, 'supervisor_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(BasicListeningSurveyAnswer::class, 'response_id');
    }

    /* =========================
     * Scopes
     * ========================= */

    public function scopeSubmitted($q)
    {
        return $q->whereNotNull('submitted_at');
    }

    public function scopeDraft($q)
    {
        return $q->whereNull('submitted_at');
    }

    /**
     * Filter berdasarkan kategori survey: tutor|supervisor|institute
     */
    public function scopeForCategory($q, string $category)
    {
        return $q->whereHas('survey', fn ($s) => $s->where('category', $category));
    }

    public function scopeForTutor($q, ?int $tutorId)
    {
        if ($tutorId) {
            $q->where('tutor_id', $tutorId);
        }
        return $q;
    }

    public function scopeForSupervisor($q, ?int $supervisorId)
    {
        if ($supervisorId) {
            $q->where('supervisor_id', $supervisorId);
        }
        return $q;
    }

    /* =========================
     * Accessors / Helpers
     * ========================= */

    public function getIsSubmittedAttribute(): bool
    {
        return ! is_null($this->submitted_at);
    }

    /* =========================
     * Model Events
     * ========================= */

    protected static function booted(): void
    {
        // Hapus jawaban anak saat response dihapus (cascade manual).
        static::deleting(function (self $model) {
            // Pakai query langsung agar efisien
            $model->answers()->delete();
        });
    }
}
