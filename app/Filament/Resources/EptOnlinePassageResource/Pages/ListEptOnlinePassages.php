<?php

namespace App\Filament\Resources\EptOnlinePassageResource\Pages;

use App\Filament\Resources\EptOnlinePassageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEptOnlinePassages extends ListRecords
{
    protected static string $resource = EptOnlinePassageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
