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
        $this->record->proctors()->sync($this->data['proctors'] ?? []);

        app(EptSchedulePostSyncService::class)->sync($this->record, auth()->id());
    }
}
