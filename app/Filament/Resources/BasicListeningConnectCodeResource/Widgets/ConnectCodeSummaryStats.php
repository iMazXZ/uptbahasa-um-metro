<?php

namespace App\Filament\Resources\BasicListeningConnectCodeResource\Widgets;

use App\Models\BasicListeningConnectCode;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ConnectCodeSummaryStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $now = Carbon::now();

        // === Base scoping (samakan dg Resource) ===
        $q = $this->scopedCodesQuery();

        $total = (clone $q)->count();

        $activeNow = (clone $q)
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->count();

        $usesToday = $this->scopedUsagesTodayCount();

        return [
            Stat::make('Total Kode', number_format($total))
                ->icon('heroicon-o-key'),

            Stat::make('Aktif Sekarang', number_format($activeNow))
                ->icon('heroicon-o-bolt'),

            Stat::make('Pemakaian Hari Ini', number_format($usesToday))
                ->icon('heroicon-o-chart-bar'),
        ];
    }

    /** Scoping query utk codes, mengikuti Resource */
    protected function scopedCodesQuery(): Builder
    {
        $q = BasicListeningConnectCode::query()->with(['prody', 'creator']);
        
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
                return $q->where('created_by', $user->id);
            }

            return $q->where(function (Builder $sub) use ($ids, $user) {
                $sub->whereIn('prody_id', $ids)
                    ->orWhere('created_by', $user->id);
            });
        }

        return $q->whereRaw('1=0');
    }

    /** Hitung usages hari ini dengan join ke codes agar ikut scoping. */
    protected function scopedUsagesTodayCount(): int
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $base = DB::table('basic_listening_code_usages as u')
            ->join('basic_listening_connect_codes as c', 'c.id', '=', 'u.connect_code_id')
            ->whereDate('u.created_at', $today);

        // Filter by BL period start date
        $startDate = \App\Models\SiteSetting::getBlPeriodStartDate();
        if ($startDate) {
            $base->where('c.created_at', '>=', $startDate);
        }

        if ($user && $user->hasRole('Admin')) {
            return (int) $base->count();
        }

        if ($user && $user->hasRole('tutor')) {
            $ids = $user->assignedProdyIds();

            // Jika tutor tak punya prodi binaan, hitung hanya yang dia buat
            if (empty($ids)) {
                return (int) $base->where('c.created_by', $user->id)->count();
            }

            return (int) $base
                ->where(function ($q) use ($ids, $user) {
                    $q->whereIn('c.prody_id', $ids)
                      ->orWhere('c.created_by', $user->id);
                })
                ->count();
        }

        return 0;
    }
}
