<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BasicListeningSurveyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'type',         // 'likert' | 'text'
        'question',
        'options',      // json (opsional)
        'is_required',
        'order',
    ];

    protected $casts = [
        'options'     => 'array',
        'is_required' => 'boolean',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(BasicListeningSurvey::class, 'survey_id');
    }
}
