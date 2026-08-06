<?php

namespace App\Filament\Resources\EptOnlineAccessTokenResource\Pages;

use App\Filament\Resources\EptOnlineAccessTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEptOnlineAccessTokens extends ListRecords
{
    protected static string $resource = EptOnlineAccessTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
