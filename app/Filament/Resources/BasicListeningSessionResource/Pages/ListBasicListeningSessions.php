<?php

namespace App\Filament\Resources\BasicListeningSessionResource\Pages;

use App\Filament\Resources\BasicListeningSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBasicListeningSessions extends ListRecords
{
    protected static string $resource = BasicListeningSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
