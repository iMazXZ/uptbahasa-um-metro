<?php

namespace App\Filament\Resources\EptGroupResource\Pages;

use App\Filament\Resources\EptGroupResource;
use App\Support\EptSchedulePostSyncService;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditEptGroup extends EditRecord
{
    protected static string $resource = EptGroupResource::class;

    protected function afterSave(): void
    {
        app(EptSchedulePostSyncService::class)->sync($this->record, auth()->id());
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
