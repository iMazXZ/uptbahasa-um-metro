<?php

namespace App\Filament\Widgets;

use App\Models\EptSubmission;
use App\Models\Penerjemahan;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

class AdminQueuesWidget extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.admin-queues-widget';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']);
    }

    protected function getViewData(): array
    {
        $terjemahanPending   = Penerjemahan::where('status', 'Menunggu')->count();
        $terjemahanApproved  = Penerjemahan::where('status', 'Disetujui')->count();
        $terjemahanInProcess = Penerjemahan::where('status', 'Diproses')->count();

        $terjemahanLatest = Penerjemahan::with('users')
            ->whereIn('status', ['Menunggu', 'Disetujui', 'Diproses'])
            ->latest()
            ->take(5)
            ->get();

        $suratPending = EptSubmission::where('status', 'pending')->count();

        $suratLatest = EptSubmission::with('user')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        return [
            'terjemahan' => [
                'pending_count'  => $terjemahanPending,
                'approved_count' => $terjemahanApproved,
                'process_count'  => $terjemahanInProcess,
                'latest'         => $terjemahanLatest,
            ],
            'surat' => [
                'pending_count' => $suratPending,
                'latest'        => $suratLatest,
            ],
        ];
    }
}
