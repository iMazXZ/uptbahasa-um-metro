<?php

namespace App\Filament\Resources\BasicListeningQuizResource\Pages;

use App\Filament\Resources\BasicListeningQuizResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBasicListeningQuizzes extends ListRecords
{
    protected static string $resource = BasicListeningQuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
