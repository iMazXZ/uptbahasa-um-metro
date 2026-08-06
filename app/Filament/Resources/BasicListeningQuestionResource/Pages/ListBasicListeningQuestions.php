<?php

namespace App\Filament\Resources\BasicListeningQuestionResource\Pages;

use App\Filament\Resources\BasicListeningQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBasicListeningQuestions extends ListRecords
{
    protected static string $resource = BasicListeningQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
