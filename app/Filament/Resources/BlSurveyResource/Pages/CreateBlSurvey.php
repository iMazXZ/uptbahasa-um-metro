<?php

namespace App\Filament\Resources\BlSurveyResource\Pages;

use App\Filament\Resources\BlSurveyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlSurvey extends CreateRecord
{
    protected static string $resource = BlSurveyResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Kuesioner berhasil dibuat';
    }
}
