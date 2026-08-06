<?php

namespace App\Support;

use App\Models\User;
use App\Models\BasicListeningGrade;

class BlSource
{
    /**
     * Ambil nilai akhir (numeric & letter) sesuai angkatan:
     * - ≤ 2024: manual dari users.nilaibasiclistening
     * - ≥ 2025: otomatis dari BL (cache di basic_listening_grades)
     */
    public static function finalFor(User $user): array
    {
        $year = (int) ($user->year ?? 0);

        if ($year >= 2025) {
            $g = BasicListeningGrade::firstOrCreate([
                'user_id'   => $user->id,
                'user_year' => $user->year,
            ]);

            $num = $g->final_numeric_cached;
            $let = $g->final_letter_cached;

            return [$num, $let];
        }

        // ≤ 2024 → manual
        $num = LegacyBasicListeningScores::effectiveScoreForUser($user);
        $let = $num !== null ? BlGrading::letter($num) : null;

        // Opsional: sinkronkan juga ke cache agar sertifikat & laporan seragam
        if ($num !== null) {
            $g = BasicListeningGrade::firstOrCreate([
                'user_id'   => $user->id,
                'user_year' => $user->year,
            ]);
            if ($g->final_numeric_cached !== $num || $g->final_letter_cached !== $let) {
                $g->final_numeric_cached = $num;
                $g->final_letter_cached  = $let;
                $g->save();
            }
        }

        return [$num, $let];
    }
}
