<?php

namespace App\Filament\Resources\EptSubmissionResource\Pages;

use App\Filament\Resources\EptSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEptSubmissions extends ListRecords
{
    protected static string $resource = EptSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
