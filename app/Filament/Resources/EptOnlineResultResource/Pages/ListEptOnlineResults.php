<?php

namespace App\Filament\Resources\EptOnlineResultResource\Pages;

use App\Filament\Resources\EptOnlineResultResource;
use Filament\Resources\Pages\ListRecords;

class ListEptOnlineResults extends ListRecords
{
    protected static string $resource = EptOnlineResultResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
