<?php

namespace App\Filament\Resources\BasicListeningScheduleResource\Pages;

use App\Filament\Resources\BasicListeningScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBasicListeningSchedules extends ListRecords
{
    protected static string $resource = BasicListeningScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
