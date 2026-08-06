<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\BlCompute;
use App\Support\BlGrading;

class BasicListeningGrade extends Model
{
    protected $fillable = [
        'user_id',
        'user_year',
        'attendance',
        'final_test',
        'final_numeric_cached',
        'final_letter_cached',
        'verification_code',
        'verification_url',
    ];

    /**
     * Relasi ke model User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hitung ulang cache final_numeric_cached dan final_letter_cached
     * menggunakan rumus resmi: (Attendance + Daily + Final Test) / 3
     */
    public function recomputeCache(): void
    {
        // Ambil nilai attendance & final test (nullable)
        $attendance = is_numeric($this->attendance) ? (float) $this->attendance : null;
        $finalTest  = is_numeric($this->final_test)  ? (float) $this->final_test  : null;

        // Ambil daily dari helper
        $daily = BlCompute::dailyAvgForUser($this->user_id, $this->user_year);
        $daily = is_numeric($daily) ? (float) $daily : null;

        // Jika semua null → kosongkan cache
        if ($attendance === null && $daily === null && $finalTest === null) {
            $this->final_numeric_cached = null;
            $this->final_letter_cached  = null;
            return;
        }

        // Gunakan rumus rata-rata (A + D + F) / 3
        $values = array_values(array_filter([
            $attendance,
            $daily,
            $finalTest,
        ], fn ($v) => $v !== null));

        $finalNumeric = round(array_sum($values) / count($values), 2);
        $finalLetter  = BlGrading::letter($finalNumeric);

        $this->final_numeric_cached = $finalNumeric;
        $this->final_letter_cached  = $finalLetter;
    }

    /**
     * Hook: setiap kali disimpan, otomatis perbarui cache.
     */
    protected static function booted(): void
    {
        static::saving(function (self $grade) {
            $grade->recomputeCache();
        });
    }
}
