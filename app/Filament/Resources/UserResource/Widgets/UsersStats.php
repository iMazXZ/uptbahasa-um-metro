<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class UsersStats extends BaseWidget
{
    /** Matikan auto refresh */
    protected static ?string $pollingInterval = null;

    protected function getCards(): array
    {
        $total      = User::count();

        // Hitung role via relasi spatie/permission
        $tutor      = User::whereHas('roles', fn ($q) => $q->where('name', 'tutor'))->count();
        $pendaftar  = User::whereHas('roles', fn ($q) => $q->where('name', 'pendaftar'))->count();

        return [
            Card::make('Total User', number_format($total))
                ->icon('heroicon-o-users')
                ->color('primary'),

            Card::make('Pendaftar', number_format($pendaftar))
                ->icon('heroicon-o-user')
                ->color('warning'),

            Card::make('Jumlah Asisten Lembaga', number_format($tutor))
                ->icon('heroicon-o-academic-cap')
                ->color('success'),
        ];
    }
}
