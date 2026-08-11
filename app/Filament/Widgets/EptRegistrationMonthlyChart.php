<?php

namespace App\Filament\Widgets;

use App\Models\EptRegistration;
use Filament\Widgets\ChartWidget;

class EptRegistrationMonthlyChart extends ChartWidget
{
    protected static ?string $heading = 'Pendaftaran EPT per Bulan';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(function (int $i) {
            return now()->startOfMonth()->subMonths($i);
        });

        $labels = $months->map(fn ($date) => $date->translatedFormat('M Y'));

        $query = EptRegistration::query()
            ->where('created_at', '>=', $months->first()->startOfMonth());

        $mode = $this->filter;
        if (in_array($mode, [EptRegistration::MODE_ONLINE, EptRegistration::MODE_OFFLINE], true)) {
            $query->where('mode', $mode);
        }

        $counts = (clone $query)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as bulan, COUNT(*) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $data = $months->map(function ($date) use ($counts) {
            return (int) ($counts[$date->format('Y-m')] ?? 0);
        });

        return [
            'datasets' => [
                [
                    'label' => $mode === EptRegistration::MODE_ONLINE ? 'Pendaftar Online' : ($mode === EptRegistration::MODE_OFFLINE ? 'Pendaftar Offline' : 'Pendaftar'),
                    'data' => $data->values()->all(),
                    'backgroundColor' => 'rgba(30, 64, 175, 0.15)',
                    'borderColor' => 'rgba(30, 64, 175, 0.8)',
                    'borderWidth' => 2,
                    'pointBackgroundColor' => 'rgba(30, 64, 175, 1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels->values()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'all' => 'Semua',
            'online' => 'EPT Online',
            'offline' => 'EPT Offline',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }
}
