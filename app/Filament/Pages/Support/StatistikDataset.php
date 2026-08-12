<?php

namespace App\Filament\Pages\Support;

use Illuminate\Support\Collection;

interface StatistikDataset
{
    /** Label dataset untuk dropdown */
    public static function label(): string;

    /** Data jumlah per bulan: ['label bulan' => count] */
    public function perBulan(?string $from, ?string $to, ?string $mode = null): Collection;

    /** Data jumlah per hari: ['Y-m-d' => count] */
    public function perHari(?string $from, ?string $to, ?string $mode = null): Collection;

    /** Data jumlah per prodi: [['prodi' => string, 'total' => int, 'persen' => float]] */
    public function perProdi(?string $from, ?string $to, ?string $mode = null): Collection;
}
