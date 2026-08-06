<?php

namespace App\Filament\Resources\BasicListeningAttemptResource\Widgets;

use App\Models\BasicListeningAttempt;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AttemptSummaryStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $q = $this->scopedAttemptsQuery();

        $total     = (clone $q)->count();
        $submitted = (clone $q)->whereNotNull('submitted_at')->count();
        $avgScore  = (clone $q)->whereNotNull('score')->avg('score');

        // hitung persen untuk label
        $pct = $total > 0 ? round(($submitted / $total) * 100, 1) : null;
        $labelSubmitted = 'Sudah Submit' . ($pct !== null ? " ({$pct}%)" : '');

        return [
            Stat::make('Total Attempt', number_format($total))
                ->icon('heroicon-o-clipboard-document-check'),

            Stat::make($labelSubmitted, number_format($submitted))
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Rata-rata Skor', $avgScore !== null ? round($avgScore, 2) : '—')
                ->icon('heroicon-o-chart-bar'),
        ];
    }


    /**
     * Menyamakan scoping dengan Resource:
     * - Admin: semua data
     * - Tutor: hanya prodi yang dia ampu
     * - Filter by period date
     */
    protected function scopedAttemptsQuery(): Builder
    {
        $q = BasicListeningAttempt::query()
            ->with(['user.prody', 'session', 'quiz', 'connectCode']);

        // Filter by BL period start date
        $startDate = \App\Models\SiteSetting::getBlPeriodStartDate();
        if ($startDate) {
            $q->where('created_at', '>=', $startDate);
        }

        $user = auth()->user();

        if ($user && $user->hasRole('Admin')) {
            return $q;
        }

        if ($user && $user->hasRole('tutor')) {
            $ids = $user->assignedProdyIds();
            if (empty($ids)) {
                // kosongkan hasil
                return $q->whereRaw('1=0');
            }
            return $q->whereHas('user', fn (Builder $sub) => $sub->whereIn('prody_id', $ids));
        }

        return $q->whereRaw('1=0');
    }
}
