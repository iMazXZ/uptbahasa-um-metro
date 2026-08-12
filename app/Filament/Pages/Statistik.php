<?php

namespace App\Filament\Pages;

use App\Exports\StatistikPerProdiExport;
use App\Filament\Pages\Support\EptRegistrasiDataset;
use App\Filament\Pages\Support\PenerjemahanDataset;
use App\Filament\Pages\Support\StatistikDataset;
use App\Filament\Pages\Support\SuratRekomendasiDataset;
use App\Filament\Pages\Support\UserDataset;
use App\Models\EptRegistration;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class Statistik extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Statistik';
    protected static ?string $title = 'Statistik';
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.statistik';

    public string $dataset = 'ept';
    public string $mode = '';
    public string $from = '';
    public string $to = '';

    public array $monthLabels = [];
    public array $monthCounts = [];
    public array $prodiRows = [];
    public int $grandTotal = 0;
    public string $chartMode = 'monthly'; // 'monthly' | 'daily'

    public array $datasetOptions = [];
    public array $modeOptions = [];

    public function mount(): void
    {
        $this->datasetOptions = [
            'ept' => EptRegistrasiDataset::label(),
            'surat' => SuratRekomendasiDataset::label(),
            'penerjemahan' => PenerjemahanDataset::label(),
            'users' => UserDataset::label(),
        ];

        $this->modeOptions = [
            '' => 'Semua Mode',
            EptRegistration::MODE_ONLINE => 'EPT Online',
            EptRegistration::MODE_OFFLINE => 'EPT Offline',
        ];

        $this->from = now()->subYear()->startOfMonth()->format('Y-m-d');
        $this->to = now()->format('Y-m-d');

        $this->applyFilters();
    }

    /**
     * Dipanggil oleh wire:model.live setiap filter berubah.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['dataset', 'mode', 'from', 'to'], true)) {
            $this->applyFilters();
        }
    }

    /**
     * Hitung ulang semua data berdasarkan filter (grafik, tabel, ringkasan).
     */
    public function applyFilters(): void
    {
        $from = $this->normalizeDate($this->from);
        $to = $this->normalizeDate($this->to);
        $mode = $this->mode ?: null;

        $dataset = $this->resolveDataset($this->dataset);
        if (! $dataset) {
            $this->resetResults();
            return;
        }

        $perProdi = $dataset->perProdi($from, $to, $mode);

        // Auto-switch: rentang <= 31 hari tampil per hari, lebih dari itu per bulan
        $rangeDays = null;
        if ($from && $to) {
            $rangeDays = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
        }

        if ($rangeDays !== null && $rangeDays <= 31) {
            $this->chartMode = 'daily';
            $perGrafik = $dataset->perHari($from, $to, $mode);

            $this->monthLabels = $perGrafik->keys()
                ->map(fn (string $periode) => Carbon::createFromFormat('Y-m-d', $periode)->translatedFormat('d M Y'))
                ->values()
                ->all();
        } else {
            $this->chartMode = 'monthly';
            $perGrafik = $dataset->perBulan($from, $to, $mode);

            $this->monthLabels = $perGrafik->keys()
                ->map(fn (string $periode) => Carbon::createFromFormat('Y-m', $periode)->translatedFormat('M Y'))
                ->values()
                ->all();
        }

        $this->monthCounts = $perGrafik->values()->map(fn ($v) => (int) $v)->all();
        $this->prodiRows = $perProdi->values()->all();
        $this->grandTotal = (int) $perProdi->sum('total');
    }

    public function export()
    {
        $from = $this->normalizeDate($this->from);
        $to = $this->normalizeDate($this->to);
        $mode = $this->mode ?: null;

        $dataset = $this->resolveDataset($this->dataset);
        if (! $dataset) {
            Notification::make()->title('Dataset tidak valid')->danger()->send();
            return null;
        }

        $label = $dataset::label();
        $periodLabel = sprintf(
            'Periode: %s s.d. %s',
            $from ? Carbon::parse($from)->translatedFormat('d M Y') : 'Awal',
            $to ? Carbon::parse($to)->translatedFormat('d M Y') : 'Sekarang'
        );
        if ($mode) {
            $periodLabel .= ' | ' . EptRegistration::modeLabel($mode);
        }

        $rows = $dataset->perProdi($from, $to, $mode);
        $filename = 'statistik_' . strtolower(str_replace(' ', '-', $label)) . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new StatistikPerProdiExport(
                collect($rows->values()->all()),
                "STATISTIK {$label} PER PRODI",
                $periodLabel
            ),
            $filename
        );
    }

    protected function normalizeDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resetResults(): void
    {
        $this->monthLabels = [];
        $this->monthCounts = [];
        $this->prodiRows = [];
        $this->grandTotal = 0;
    }

    protected function resolveDataset(string $key): ?StatistikDataset
    {
        return match ($key) {
            'ept' => new EptRegistrasiDataset(),
            'surat' => new SuratRekomendasiDataset(),
            'penerjemahan' => new PenerjemahanDataset(),
            'users' => new UserDataset(),
            default => null,
        };
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }
}
