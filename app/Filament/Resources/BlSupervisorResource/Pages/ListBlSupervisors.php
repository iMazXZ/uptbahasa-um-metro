<?php

namespace App\Filament\Resources\BlSupervisorResource\Pages;

use App\Filament\Resources\BlSupervisorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlSupervisors extends ListRecords
{
    protected static string $resource = BlSupervisorResource::class;

    protected function getHeaderActions(): array
    {
        return [ Actions\CreateAction::make()->label('Tambah Supervisor') ];
    }
}
