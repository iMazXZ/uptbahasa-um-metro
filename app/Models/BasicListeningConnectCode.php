<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasicListeningConnectCode extends Model
{
    // Paling aman: terima semua kolom
    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'bool',
        'rules'     => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BasicListeningSession::class, 'session_id');
    }

    public function withinWindow(): bool
    {
        $now = now();
        return $this->is_active && $now->between($this->starts_at, $this->ends_at);
    }

    public function quiz()
    {
        return $this->belongsTo(BasicListeningQuiz::class);
    }

    public function connectCode()
    {
        return $this->belongsTo(BasicListeningConnectCode::class, 'connect_code_id');
    }

    public function attempts()
    {
        return $this->hasMany(BasicListeningAttempt::class, 'connect_code_id');
    }

    // app/Models/BasicListeningConnectCode.php (tambahkan di dalam class)
    public function prody()
    {
        return $this->belongsTo(\App\Models\Prody::class, 'prody_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

}
