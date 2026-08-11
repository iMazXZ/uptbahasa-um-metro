<?php

namespace App\Filament\Widgets;

use App\Models\EptRegistration;
use Filament\Widgets\ChartWidget;

class EptRegistrationMonthlyChart extends ChartWidget
{
    protected static ?string $heading = 'Pendaftaran EPT';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        [$mode, $rangeRaw, $granularity] = array_pad(explode(':', (string) $this->filter), 3, null);
        $mode = $mode ?: 'all';
        $range = max(1, min(24, (int) ($rangeRaw ?: 6)));
        $granularity = $granularity ?: 'monthly';

        $query = EptRegistration::query();

        if (in_array($mode, [EptRegistration::MODE_ONLINE, EptRegistration::MODE_OFFLINE], true)) {
            $query->where('mode', $mode);
        }

        if ($granularity === 'daily') {
            return $this->buildDailyData($query, $range, $mode);
        }

        return $this->buildMonthlyData($query, $range, $mode);
    }

    protected function buildMonthlyData($query, int $range, string $mode): array
    {
        $months = collect(range($range - 1, 0))->map(fn (int $i) => now()->startOfMonth()->subMonths($i));
        $labels = $months->map(fn ($date) => $date->translatedFormat('M Y'));

        $counts = (clone $query)
            ->where('created_at', '>=', $months->first()->startOfMonth())
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as bulan, COUNT(*) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $data = $months->map(fn ($date) => (int) ($counts[$date->format('Y-m')] ?? 0));

        return [
            'datasets' => [[
                'label' => $this->datasetLabel($mode, 'Pendaftar'),
                'data' => $data->values()->all(),
                'backgroundColor' => 'rgba(30, 64, 175, 0.15)',
                'borderColor' => 'rgba(30, 64, 175, 0.8)',
                'borderWidth' => 2,
                'pointBackgroundColor' => 'rgba(30, 64, 175, 1)',
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => $labels->values()->all(),
        ];
    }

    protected function buildDailyData($query, int $range, string $mode): array
    {
        $days = collect(range($range * 30 - 1, 0))->map(fn (int $i) => now()->startOfDay()->subDays($i));
        $labels = $days->map(fn ($date) => $date->translatedFormat('d M'));

        $counts = (clone $query)
            ->where('created_at', '>=', $days->first()->startOfDay())
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d") as hari, COUNT(*) as total')
            ->groupBy('hari')
            ->pluck('total', 'hari');

        $data = $days->map(fn ($date) => (int) ($counts[$date->format('Y-m-d')] ?? 0));

        return [
            'datasets' => [[
                'label' => $this->datasetLabel($mode, 'Pendaftar/hari'),
                'data' => $data->values()->all(),
                'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                'borderColor' => 'rgba(16, 185, 129, 0.8)',
                'borderWidth' => 2,
                'pointBackgroundColor' => 'rgba(16, 185, 129, 1)',
                'pointRadius' => 1,
                'fill' => true,
                'tension' => 0.2,
            ]],
            'labels' => $labels->values()->all(),
        ];
    }

    protected function datasetLabel(string $mode, string $base): string
    {
        return match ($mode) {
            EptRegistration::MODE_ONLINE => "{$base} Online",
            EptRegistration::MODE_OFFLINE => "{$base} Offline",
            default => $base,
        };
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'all:6:monthly' => '6 Bulan (per bulan)',
            'all:12:monthly' => '12 Bulan (per bulan)',
            'all:3:daily' => '3 Bulan (per hari)',
            'all:1:daily' => 'Bulan ini (per hari)',
            'online:12:monthly' => 'Online - 12 Bulan',
            'offline:12:monthly' => 'Offline - 12 Bulan',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
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
