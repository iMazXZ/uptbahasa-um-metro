<?php

namespace App\Filament\Resources\EptOnlineAttemptResource\Pages;

use App\Filament\Resources\EptOnlineAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListEptOnlineAttempts extends ListRecords
{
    protected static string $resource = EptOnlineAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
