<?php

namespace App\Filament\Resources\EptOnlineQuestionResource\Pages;

use App\Filament\Resources\EptOnlineQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEptOnlineQuestions extends ListRecords
{
    protected static string $resource = EptOnlineQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
