<?php

namespace App\Filament\Resources\ProdyResource\Pages;

use App\Filament\Resources\ProdyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProdies extends ListRecords
{
    protected static string $resource = ProdyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                -> label('Tambah Prodi'),
        ];
    }
}
