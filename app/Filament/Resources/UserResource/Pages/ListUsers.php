<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\UserResource\Widgets\UsersStats;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                -> label('Tambah User'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UsersStats::class, // tampil di atas tabel
        ];
    }
}
