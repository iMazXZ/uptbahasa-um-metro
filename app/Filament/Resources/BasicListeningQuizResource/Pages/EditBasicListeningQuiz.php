<?php

namespace App\Filament\Resources\BasicListeningQuizResource\Pages;

use App\Filament\Resources\BasicListeningQuizResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBasicListeningQuiz extends EditRecord
{
    protected static string $resource = BasicListeningQuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
