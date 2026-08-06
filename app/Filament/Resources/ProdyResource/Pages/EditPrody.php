<?php

namespace App\Filament\Resources\ProdyResource\Pages;

use App\Filament\Resources\ProdyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrody extends EditRecord
{
    protected static string $resource = ProdyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
