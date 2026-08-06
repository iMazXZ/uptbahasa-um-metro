<?php

namespace App\Filament\Resources\BasicListeningScheduleResource\Pages;

use App\Filament\Resources\BasicListeningScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBasicListeningSchedule extends CreateRecord
{
    protected static string $resource = BasicListeningScheduleResource::class;

    protected function afterCreate(): void
    {
        $ids = $this->form->getState()['tutors'] ?? [];
        $this->record->tutors()->sync($ids);
    }
}
