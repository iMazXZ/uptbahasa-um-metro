<?php

namespace App\Filament\Resources\BasicListeningSessionResource\Pages;

use App\Filament\Resources\BasicListeningSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBasicListeningSession extends EditRecord
{
    protected static string $resource = BasicListeningSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
