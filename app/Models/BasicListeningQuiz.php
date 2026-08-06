<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BasicListeningQuiz extends Model
{
    protected $fillable = ['session_id','title','is_active'];
    protected $casts = ['is_active'=>'bool'];

    public function session(): BelongsTo { return $this->belongsTo(BasicListeningSession::class,'session_id'); }
    public function questions(): HasMany { return $this->hasMany(BasicListeningQuestion::class,'quiz_id')->orderBy('order'); }
    public function scopeActive($q){ return $q->where('is_active', true); }
}
