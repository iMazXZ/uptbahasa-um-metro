<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasicListeningAnswer extends Model
{
    protected $fillable = ['attempt_id','question_id','blank_index','answer','is_correct'];
    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function attempt(): BelongsTo { return $this->belongsTo(BasicListeningAttempt::class,'attempt_id'); }

    public function question(): BelongsTo { 
        return $this->belongsTo(BasicListeningQuestion::class,'question_id'); 
    }
}
