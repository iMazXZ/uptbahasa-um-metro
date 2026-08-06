<?php

namespace App\Filament\Pages\Widgets;

use App\Models\User;
use App\Models\BasicListeningGrade;
use App\Support\BlCompute;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class TutorMahasiswaSummaryStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $q = $this->scopedStudentsQuery();

        $total = (clone $q)->count();

        // Sudah attempt (distinct session submitted >=1)
        $withAnyAttempt = (clone $q)
            ->whereHas('basicListeningAttempts', fn ($s) => $s->whereNotNull('submitted_at'))
            ->count();

        // Sudah lengkap komponen akhir (attendance & final_test terisi)
        $completeFinals = (clone $q)
            ->whereHas('basicListeningGrade', function ($s) {
                $s->whereNotNull('attendance')->whereNotNull('final_test');
            })
            ->count();

        // Rata-rata nilai akhir (pakai cached jika ada, fallback hitung (A + Daily + F)/n)
        $avgFinal = $this->avgFinalScore($q);

        $pct = $total > 0 ? round($withAnyAttempt / $total * 100, 1) : null;
            $label = 'Pernah Attempt' . ($pct !== null ? " ({$pct}%)" : '');

        return [
            Stat::make('Mahasiswa Binaan Anda', number_format($total))
                ->icon('heroicon-o-users'),

            Stat::make($label, number_format($withAnyAttempt))
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Data Nilai Lengkap', number_format($completeFinals))
                ->icon('heroicon-o-document-check'),

            Stat::make('Rata-Rata Nilai Akhir', $avgFinal !== null ? number_format($avgFinal, 2) : '—')
                ->icon('heroicon-o-chart-bar'),
        ];
    }

    /** Samakan scoping dengan page: Admin = semua; Tutor = prodi binaan + angkatan sesuai setting */
    protected function scopedStudentsQuery(): Builder
    {
        $q = User::query()
            ->with(['basicListeningAttempts', 'basicListeningGrade'])
            ->whereNotNull('srn');

        $u = auth()->user();
        if ($u?->hasRole('Admin')) {
            return $q;
        }

        if ($u?->hasRole('tutor')) {
            $ids = $u->assignedProdyIds();
            if (empty($ids)) {
                return $q->whereRaw('1=0');
            }

            // Gunakan setting bl_active_batch (support multiple: "25,26")
            $activeBatch = \App\Models\SiteSetting::get('bl_active_batch', now()->format('y'));
            $batches = array_map('trim', explode(',', $activeBatch));

            return $q
                ->whereIn('prody_id', $ids)
                ->where(function ($query) use ($batches) {
                    foreach ($batches as $batch) {
                        $query->orWhere('srn', 'like', $batch.'%');
                    }
                });
        }

        return $q->whereRaw('1=0');
    }

    /** Hitung rata-rata final_numeric (prioritaskan cache jika ada) */
    protected function avgFinalScore(Builder $base): ?float
    {
        // Coba pakai cache langsung kalau kolom ada
        $cachedAvg = BasicListeningGrade::query()
            ->whereIn('user_id', (clone $base)->pluck('id'))
            ->whereNotNull('final_numeric_cached')
            ->avg('final_numeric_cached');

        if ($cachedAvg !== null) {
            return (float) $cachedAvg;
        }

        // Fallback: hitung manual
        $users = (clone $base)->with('basicListeningGrade')->get();

        $scores = [];
        foreach ($users as $u) {
            $g = $u->basicListeningGrade;

            $att = is_numeric($g?->attendance) ? (float) $g->attendance : null;
            $fin = is_numeric($g?->final_test)  ? (float) $g->final_test  : null;
            $dly = BlCompute::dailyAvgForUser($u->id, $u->year);
            $dly = is_numeric($dly) ? (float) $dly : null;

            $parts = array_values(array_filter([$att, $dly, $fin], fn ($v) => $v !== null));
            if ($parts) {
                $scores[] = array_sum($parts) / count($parts);
            }
        }

        return count($scores) ? array_sum($scores) / count($scores) : null;
    }
}
