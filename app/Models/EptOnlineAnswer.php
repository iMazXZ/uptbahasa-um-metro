<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EptOnlineAnswer extends Model
{
    protected $fillable = [
        'attempt_id',
        'question_id',
        'section_id',
        'selected_option',
        'answered_at',
        'meta',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'meta' => 'array',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(EptOnlineAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(EptOnlineQuestion::class, 'question_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(EptOnlineSection::class, 'section_id');
    }
}
