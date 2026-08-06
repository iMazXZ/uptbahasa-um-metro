<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BasicListeningAttempt extends Model
{
    protected $fillable = ['user_id','session_id','quiz_id','connect_code_id','started_at','expires_at','submitted_at','score'];
    protected $casts = ['started_at'=>'datetime','expires_at'=>'datetime','submitted_at'=>'datetime'];

    public function user() { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
    public function quiz(): BelongsTo { return $this->belongsTo(BasicListeningQuiz::class,'quiz_id'); }
    public function session(): BelongsTo { return $this->belongsTo(BasicListeningSession::class,'session_id'); }
    public function answers(): HasMany { return $this->hasMany(BasicListeningAnswer::class,'attempt_id'); }
    public function connectCode(): BelongsTo 
    { 
        return $this->belongsTo(BasicListeningConnectCode::class, 'connect_code_id'); 
    }
    protected static function booted()
    {
        static::deleting(function ($attempt) {
            $attempt->answers()->delete();
        });
    }
}
