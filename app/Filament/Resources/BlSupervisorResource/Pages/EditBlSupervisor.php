<?php

namespace App\Filament\Resources\BlSupervisorResource\Pages;

use App\Filament\Resources\BlSupervisorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlSupervisor extends EditRecord
{
    protected static string $resource = BlSupervisorResource::class;

    protected function getHeaderActions(): array
    {
        return [ Actions\DeleteAction::make() ];
    }
}
