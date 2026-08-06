<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'office_id',
        'clock_in',
        'clock_out',
        'clock_in_lat',
        'clock_in_long',
        'clock_in_photo',
        'clock_out_lat',
        'clock_out_long',
        'clock_out_photo',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'clock_in_lat' => 'decimal:8',
            'clock_in_long' => 'decimal:8',
            'clock_out_lat' => 'decimal:8',
            'clock_out_long' => 'decimal:8',
        ];
    }

    /**
     * Relasi ke user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke office.
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Cek apakah sudah clock out.
     */
    public function hasClockOut(): bool
    {
        return $this->clock_out !== null;
    }

    /**
     * Hitung durasi kerja dalam detik.
     */
    public function getWorkDurationAttribute(): int
    {
        if ($this->clock_out === null) {
            return now()->diffInSeconds($this->clock_in);
        }
        return $this->clock_out->diffInSeconds($this->clock_in);
    }

    /**
     * Format durasi kerja dalam format readable.
     */
    public function getWorkDurationFormattedAttribute(): string
    {
        $seconds = $this->work_duration;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return sprintf('%d jam %d menit', $hours, $minutes);
    }
}
