<?php

namespace App\Filament\Resources\EptGroupResource\Pages;

use App\Filament\Resources\EptGroupResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListEptGroups extends ListRecords
{
    protected static string $resource = EptGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
