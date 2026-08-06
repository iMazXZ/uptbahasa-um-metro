<?php

namespace App\Filament\Resources\EptOnlineSectionResource\Pages;

use App\Filament\Resources\EptOnlineSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEptOnlineSections extends ListRecords
{
    protected static string $resource = EptOnlineSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
