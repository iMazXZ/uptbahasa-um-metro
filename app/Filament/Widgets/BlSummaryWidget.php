<?php

namespace App\Filament\Widgets;

use App\Models\BasicListeningAttempt;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class BlSummaryWidget extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.bl-summary-widget';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'tutor']);
    }

    protected function getViewData(): array
    {
        $now       = now();
        $startWeek = Carbon::now()->startOfWeek();
        $lastWeekStart = $startWeek->copy()->subWeek();
        $recentCutoff = Carbon::now()->subDays(7);

        // Get period start date for filtering
        $periodStartDate = \App\Models\SiteSetting::getBlPeriodStartDate();

        $attemptsThisWeekQuery = BasicListeningAttempt::query()
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $startWeek);
        if ($periodStartDate) {
            $attemptsThisWeekQuery->where('created_at', '>=', $periodStartDate);
        }
        $attemptsThisWeek = $attemptsThisWeekQuery->count();

        $attemptsLastWeekQuery = BasicListeningAttempt::query()
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$lastWeekStart, $startWeek]);
        if ($periodStartDate) {
            $attemptsLastWeekQuery->where('created_at', '>=', $periodStartDate);
        }
        $attemptsLastWeek = $attemptsLastWeekQuery->count();

        $avgScoreWeekQuery = BasicListeningAttempt::query()
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $startWeek)
            ->whereNotNull('score');
        if ($periodStartDate) {
            $avgScoreWeekQuery->where('created_at', '>=', $periodStartDate);
        }
        $avgScoreWeek = $avgScoreWeekQuery->avg('score');

        $prodyStatsQuery = BasicListeningAttempt::query()
            ->selectRaw(
                'users.prody_id, prodies.name as prody_name, COUNT(*) as attempt_count, MAX(basic_listening_attempts.submitted_at) as latest_submitted_at,
                SUM(CASE WHEN basic_listening_attempts.submitted_at >= ? THEN 1 ELSE 0 END) as attempt_this_week,
                SUM(CASE WHEN basic_listening_attempts.submitted_at >= ? AND basic_listening_attempts.submitted_at < ? THEN 1 ELSE 0 END) as attempt_last_week',
                [$startWeek, $lastWeekStart, $startWeek]
            )
            ->join('users', 'users.id', '=', 'basic_listening_attempts.user_id')
            ->join('prodies', 'prodies.id', '=', 'users.prody_id')
            ->whereNotNull('basic_listening_attempts.submitted_at')
            ->where('basic_listening_attempts.submitted_at', '>=', $recentCutoff)
            ->whereNotNull('users.prody_id');
        
        if ($periodStartDate) {
            $prodyStatsQuery->where('basic_listening_attempts.created_at', '>=', $periodStartDate);
        }

        $prodyStats = $prodyStatsQuery
            ->groupBy('users.prody_id', 'prodies.name')
            ->orderByDesc('latest_submitted_at')
            ->get()
            ->map(function ($row) {
                return (object) [
                    'prody_name'       => $row->prody_name,
                    'attempt_count'    => (int) $row->attempt_count,
                    'attempt_this_week'=> (int) ($row->attempt_this_week ?? 0),
                    'attempt_last_week'=> (int) ($row->attempt_last_week ?? 0),
                    'latest_submitted' => $row->latest_submitted_at ? Carbon::parse($row->latest_submitted_at) : null,
                ];
            });

        return [
            'attemptsThisWeek' => $attemptsThisWeek,
            'attemptsLastWeek' => $attemptsLastWeek,
            'avgScoreWeek'     => $avgScoreWeek ? round($avgScoreWeek, 1) : null,
            'prodyStats'       => $prodyStats,
            'startWeek'        => $startWeek,
            'now'              => $now,
        ];
    }
}
