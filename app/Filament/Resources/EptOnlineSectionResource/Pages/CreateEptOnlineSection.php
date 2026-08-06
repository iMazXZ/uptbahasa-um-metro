<?php

namespace App\Filament\Resources\EptOnlineSectionResource\Pages;

use App\Filament\Resources\EptOnlineSectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEptOnlineSection extends CreateRecord
{
    protected static string $resource = EptOnlineSectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return EptOnlineSectionResource::normalizeFormData($data);
    }
}
