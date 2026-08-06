<?php

namespace App\Filament\Resources\EptSubmissionResource\Pages;

use App\Filament\Resources\EptSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEptSubmission extends EditRecord
{
    protected static string $resource = EptSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
