<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Penerjemahan;
use App\Models\EptSubmission; // ⬅️ pakai model yang benar
use App\Models\EptRegistration;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        // 1) Total user role "pendaftar"
        $totalPendaftar = User::whereHas('roles', fn ($q) => $q->where('name', 'pendaftar'))->count();

        // 2) Pengajuan Surat Rekomendasi (EptSubmission) status pending
        $rekomTotal   = EptSubmission::count();
        $rekomPending = EptSubmission::where('status', 'pending')->count();
        $rekomPct     = $rekomTotal > 0 ? round(($rekomPending / $rekomTotal) * 100, 1) : null;

        // 3) Penerjemahan status "Menunggu"
        $penTotal   = Penerjemahan::count();
        $penWaiting = Penerjemahan::where('status', 'Menunggu')->count();
        $penPct     = $penTotal > 0 ? round(($penWaiting / $penTotal) * 100, 1) : null;

        // 4) Pendaftaran EPT status "pending"
        $eptTotal   = EptRegistration::count();
        $eptPending = EptRegistration::pending()->count();
        $eptPct     = $eptTotal > 0 ? round(($eptPending / $eptTotal) * 100, 1) : null;

        return [
            Stat::make('Total User Pendaftar', number_format($totalPendaftar))
                ->description('User dengan role pendaftar')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::Before)
                ->icon('heroicon-o-user-group')
                ->chart([2, 4, 6, 8, 12, 14])
                ->color('success'),

            Stat::make('Surat Rekomendasi Menunggu', number_format($rekomPending))
                ->description($rekomPct !== null ? "{$rekomPct}% dari total pengajuan" : '—')
                ->descriptionIcon('heroicon-m-envelope-open', IconPosition::Before)
                ->icon('heroicon-o-envelope-open')
                ->chart([1, 1, 2, 3, 5, 8])
                ->color('warning'),

            Stat::make('Penerjemahan Menunggu', number_format($penWaiting))
                ->description($penPct !== null ? "{$penPct}% dari total permohonan" : '—')
                ->descriptionIcon('heroicon-m-document-arrow-down', IconPosition::Before)
                ->icon('heroicon-o-document-arrow-down')
                ->chart([1, 2, 3, 4, 5, 6])
                ->color('danger'),

            Stat::make('Pendaftaran EPT Menunggu', number_format($eptPending))
                ->description($eptPct !== null ? "{$eptPct}% dari total pendaftaran" : '—')
                ->descriptionIcon('heroicon-m-academic-cap', IconPosition::Before)
                ->icon('heroicon-o-academic-cap')
                ->chart([1, 2, 3, 3, 4, 5])
                ->color('danger'),
        ];
    }
}
