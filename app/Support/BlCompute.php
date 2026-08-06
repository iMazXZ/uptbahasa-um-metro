<?php

namespace App\Support;

use App\Models\BasicListeningAttempt;
use App\Models\BasicListeningManualScore;

class BlCompute
{
    /**
     * Hitung rata-rata Daily (S1..S5) dengan prioritas:
     *   1) Nilai manual override (jika ada)
     *   2) Fallback ke nilai attempt quiz (attempt terbaru yang sudah submitted)
     *
     * @param  int        $userId
     * @param  int|null   $userYear  (opsional; filter ke tahun/angkatan tertentu bila kolom disediakan)
     * @return float|null
     */
    public static function dailyAvgForUser(int $userId, ?int $userYear = null): ?float
    {
        $meetings = [1, 2, 3, 4, 5];

        // Ambil semua skor manual untuk S1..S5 dalam 1 query
        $manualByMeeting = BasicListeningManualScore::query()
            ->where('user_id', $userId)
            ->when($userYear !== null, fn ($q) => $q->where('user_year', $userYear))
            ->whereIn('meeting', $meetings)
            ->pluck('score', 'meeting');

        // Ambil attempt terbaru (berdasarkan submitted_at) untuk S1..S5 dalam 1 query
        $latestAttempts = BasicListeningAttempt::query()
            ->select(['user_id', 'session_id', 'score', 'submitted_at'])
            ->where('user_id', $userId)
            ->whereIn('session_id', $meetings)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->get()
            ->unique('session_id')              // simpan attempt terbaru per session
            ->keyBy('session_id');              // akses cepat: $latestAttempts[$m]

        $values = [];

        foreach ($meetings as $m) {
            // 1) Manual override jika ada
            $manual = $manualByMeeting[$m] ?? null;
            if (is_numeric($manual)) {
                $values[] = (float) $manual;
                continue;
            }

            // 2) Fallback ke attempt terbaru yang sudah submitted
            $attemptScore = $latestAttempts->get($m)?->score;
            if (is_numeric($attemptScore)) {
                $values[] = (float) $attemptScore;
            }
        }

        if (empty($values)) {
            return null;
        }

        return round(array_sum($values) / count($values), 2);
    }

    /**
     * (Opsional) Breakdown per meeting: manual, attempt, dan nilai yang dipakai.
     * Berguna untuk debug/preview di UI.
     *
     * @return array<int, array{manual: float|null, attempt: float|null, used: float|null}>
     */
    public static function dailyBreakdownForUser(int $userId, ?int $userYear = null): array
    {
        $meetings = [1, 2, 3, 4, 5];

        $manualByMeeting = BasicListeningManualScore::query()
            ->where('user_id', $userId)
            ->when($userYear !== null, fn ($q) => $q->where('user_year', $userYear))
            ->whereIn('meeting', $meetings)
            ->pluck('score', 'meeting');

        $latestAttempts = BasicListeningAttempt::query()
            ->select(['user_id', 'session_id', 'score', 'submitted_at'])
            ->where('user_id', $userId)
            ->whereIn('session_id', $meetings)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->get()
            ->unique('session_id')
            ->keyBy('session_id');

        $out = [];

        foreach ($meetings as $m) {
            $manual  = $manualByMeeting[$m] ?? null;
            $attempt = $latestAttempts->get($m)?->score;

            $used = null;
            if (is_numeric($manual)) {
                $used = (float) $manual;
            } elseif (is_numeric($attempt)) {
                $used = (float) $attempt;
            }

            $out[$m] = [
                'manual'  => is_numeric($manual)  ? (float) $manual  : null,
                'attempt' => is_numeric($attempt) ? (float) $attempt : null,
                'used'    => $used,
            ];
        }

        return $out;
    }
}
