<?php

namespace App\Filament\Resources\EptOnlineFormResource\Pages;

use App\Filament\Resources\EptOnlineFormResource;
use App\Models\EptOnlineForm;
use Filament\Resources\Pages\CreateRecord;

class CreateEptOnlineForm extends CreateRecord
{
    protected static string $resource = EptOnlineFormResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        if (($data['status'] ?? EptOnlineForm::STATUS_DRAFT) === EptOnlineForm::STATUS_PUBLISHED) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
