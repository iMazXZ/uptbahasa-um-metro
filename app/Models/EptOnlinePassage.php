<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EptOnlinePassage extends Model
{
    protected $fillable = [
        'form_id',
        'section_id',
        'passage_code',
        'title',
        'content',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'sort_order' => 'integer',
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

    public function questions(): HasMany
    {
        return $this->hasMany(EptOnlineQuestion::class, 'passage_id')->orderBy('sort_order');
    }
}
