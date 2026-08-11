<?php

namespace App\Filament\Pages\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserDataset implements StatistikDataset
{
    public static function label(): string
    {
        return 'User Terdaftar';
    }

    public function perBulan(?string $from, ?string $to, ?string $mode = null): Collection
    {
        return $this->baseQuery($from, $to)
            ->selectRaw('DATE_FORMAT(users.created_at, "%Y-%m") as periode, COUNT(*) as total')
            ->groupBy('periode')
            ->orderBy('periode')
            ->pluck('total', 'periode');
    }

    public function perProdi(?string $from, ?string $to, ?string $mode = null): Collection
    {
        $rows = $this->baseQuery($from, $to)
            ->leftJoin('prodies', 'prodies.id', '=', 'users.prody_id')
            ->select('prodies.name as prodi', DB::raw('COUNT(*) as total'))
            ->groupBy('prodies.name')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $rows->sum('total');

        return $rows->map(function ($row) use ($grandTotal) {
            return [
                'prodi' => $row->prodi ?: 'Belum diisi',
                'total' => (int) $row->total,
                'persen' => $grandTotal > 0 ? round(((int) $row->total / $grandTotal) * 100, 1) : 0,
            ];
        });
    }

    protected function baseQuery(?string $from, ?string $to)
    {
        $query = User::query();

        if ($from) {
            $query->where('users.created_at', '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $query->where('users.created_at', '<=', $to . ' 23:59:59');
        }

        return $query;
    }
}
