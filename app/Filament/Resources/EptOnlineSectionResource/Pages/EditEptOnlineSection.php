<?php

namespace App\Filament\Resources\EptOnlineSectionResource\Pages;

use App\Filament\Resources\EptOnlineSectionResource;
use Filament\Resources\Pages\EditRecord;

class EditEptOnlineSection extends EditRecord
{
    protected static string $resource = EptOnlineSectionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return EptOnlineSectionResource::prepareFormDataForFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return EptOnlineSectionResource::normalizeFormData($data);
    }
}
