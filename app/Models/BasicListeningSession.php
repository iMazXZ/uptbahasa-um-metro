<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BasicListeningSession extends Model
{
    protected $fillable = [
        'number','title','summary','audio_url',
        'opens_at','closes_at','duration_minutes','is_active','show_on_landing',
    ];
    protected $casts = [
        'opens_at'=>'datetime','closes_at'=>'datetime','is_active'=>'bool','show_on_landing'=>'bool',
    ];

    public function defaultQuiz(): HasOne { return $this->hasOne(BasicListeningQuiz::class,'session_id'); }
    public function connectCodes(): HasMany { return $this->hasMany(BasicListeningConnectCode::class,'session_id'); }

    public function isOpen(): bool {
        $now = now();
        return $this->is_active
            && (!$this->opens_at || $now->gte($this->opens_at))
            && (!$this->closes_at || $now->lte($this->closes_at));
    }

    public function quizzes()
    {
        return $this->hasMany(BasicListeningQuiz::class, 'session_id');
    }

    public function attempts()
    {
        return $this->hasMany(\App\Models\BasicListeningAttempt::class, 'session_id');
    }
}
