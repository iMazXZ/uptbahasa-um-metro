<?php

namespace App\Filament\Resources\BlSurveyResource\Pages;

use App\Filament\Resources\BlSurveyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlSurveys extends ListRecords
{
    protected static string $resource = BlSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Buat Kuesioner'),
        ];
    }
}
