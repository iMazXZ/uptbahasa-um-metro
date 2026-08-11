<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Support\EptRegistrasiDataset;
use App\Filament\Pages\Support\PenerjemahanDataset;
use App\Filament\Pages\Support\StatistikDataset;
use App\Filament\Pages\Support\SuratRekomendasiDataset;
use App\Filament\Pages\Support\UserDataset;
use App\Models\EptRegistration;
use App\Exports\StatistikPerProdiExport;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
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


    // Property filter Livewire murni (wire:model.live — dijamin memicu updated())
    public string $dataset = 'ept';
    public string $mode = '';
    public ?string $from = null;
    public ?string $to = null;

    public array $datasets = [];
    public array $monthLabels = [];
    public array $monthCounts = [];
    public array $prodiRows = [];
    public ?int $grandTotal = null;

    public function datasetOptions(): array
    {
        return [
            'ept' => EptRegistrasiDataset::label(),
            'surat' => SuratRekomendasiDataset::label(),
            'penerjemahan' => PenerjemahanDataset::label(),
            'users' => UserDataset::label(),
        ];
    }

    public function modeOptions(): array
    {
        return [
            '' => 'Semua Mode',
            EptRegistration::MODE_ONLINE => 'EPT Online',
            EptRegistration::MODE_OFFLINE => 'EPT Offline',
        ];
    }

    public function mount(): void
    {
        $this->dataset = 'ept';
        $this->mode = '';
        $this->from = now()->subYear()->startOfMonth()->format('Y-m-d');
        $this->to = now()->format('Y-m-d');

        $this->refreshData();
    }

    public function updated($property, $value): void
    {
        // wire:model.live pada filter — pastikan refresh saat berubah
        if (in_array($property, ['dataset', 'mode', 'from', 'to'], true)) {
            $this->refreshData();
        }
    }

    public function refreshData(): void
    {
        $datasetKey = $this->dataset ?: 'ept';
        $from = $this->from;
        $to = $this->to;
        $mode = $this->mode;

        $dataset = $this->resolveDataset($datasetKey);
        if (! $dataset) {
            $this->datasets = [];
            $this->monthLabels = [];
            $this->monthCounts = [];
            $this->prodiRows = [];
            $this->grandTotal = null;
            return;
        }

        $from = $from ? \Carbon\Carbon::parse($from)->format('Y-m-d') : null;
        $to = $to ? \Carbon\Carbon::parse($to)->format('Y-m-d') : null;

        $perBulan = $dataset->perBulan($from, $to, $mode ?: null);
        $perProdi = $dataset->perProdi($from, $to, $mode ?: null);

        $this->datasets = $perBulan->map(fn ($total, $periode) => [
            'periode' => \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('M Y'),
            'total' => (int) $total,
        ])->values()->all();

        $this->monthLabels = array_column($this->datasets, 'periode');
        $this->monthCounts = array_column($this->datasets, 'total');
        $this->prodiRows = $perProdi->values()->all();
        $this->grandTotal = (int) $perProdi->sum('total');
    }

    public function export(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $datasetKey = $this->dataset ?: 'ept';
        $from = $this->from ? \Carbon\Carbon::parse($this->from)->format('Y-m-d') : null;
        $to = $this->to ? \Carbon\Carbon::parse($this->to)->format('Y-m-d') : null;
        $mode = $this->mode;

        $dataset = $this->resolveDataset($datasetKey);
        if (! $dataset) {
            abort(422, 'Dataset tidak valid.');
        }

        $label = $dataset::label();
        $periodLabel = sprintf(
            'Periode: %s s.d. %s',
            $from ? \Carbon\Carbon::parse($from)->translatedFormat('d M Y') : 'Awal',
            $to ? \Carbon\Carbon::parse($to)->translatedFormat('d M Y') : 'Sekarang'
        );

        if ($mode) {
            $periodLabel .= ' | ' . \App\Models\EptRegistration::modeLabel($mode);
        }

        $rows = $dataset->perProdi($from, $to, $mode ?: null);

        $filename = 'statistik_' . strtolower(str_replace(' ', '-', $label)) . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new StatistikPerProdiExport(collect($rows->values()->all()), "STATISTIK {$label} PER PRODI", $periodLabel),
            $filename
        );
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
