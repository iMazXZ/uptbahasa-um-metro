<?php

namespace App\Filament\Resources\BlSurveyResource\Pages;

use App\Filament\Resources\BlSurveyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlSurvey extends EditRecord
{
    protected static string $resource = BlSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan disimpan';
    }
}
