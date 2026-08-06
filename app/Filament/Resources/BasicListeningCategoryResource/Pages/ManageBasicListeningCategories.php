<?php

namespace App\Filament\Resources\BasicListeningCategoryResource\Pages;

use App\Filament\Resources\BasicListeningCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBasicListeningCategories extends ManageRecords
{
    protected static string $resource = BasicListeningCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
