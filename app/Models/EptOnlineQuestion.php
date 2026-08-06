<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EptOnlineQuestion extends Model
{
    protected $fillable = [
        'form_id',
        'section_id',
        'passage_id',
        'part_label',
        'group_code',
        'number_in_section',
        'sort_order',
        'instruction',
        'prompt',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
        'meta',
    ];

    protected $casts = [
        'number_in_section' => 'integer',
        'sort_order' => 'integer',
        'correct_option' => 'encrypted',
        'meta' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(EptOnlineForm::class, 'form_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(EptOnlineSection::class, 'section_id');
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(EptOnlinePassage::class, 'passage_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EptOnlineAnswer::class, 'question_id');
    }
}
