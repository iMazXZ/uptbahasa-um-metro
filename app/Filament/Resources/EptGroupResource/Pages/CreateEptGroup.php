<?php

namespace App\Filament\Resources\EptGroupResource\Pages;

use App\Filament\Resources\EptGroupResource;
use App\Support\EptSchedulePostSyncService;
use Filament\Resources\Pages\CreateRecord;

class CreateEptGroup extends CreateRecord
{
    protected static string $resource = EptGroupResource::class;

    protected function afterCreate(): void
    {
        app(EptSchedulePostSyncService::class)->sync($this->record, auth()->id());
    }
}
