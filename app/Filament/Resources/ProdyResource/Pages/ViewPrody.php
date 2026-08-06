<?php

namespace App\Filament\Resources\ProdyResource\Pages;

use App\Filament\Resources\ProdyResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPrody extends ViewRecord
{
    protected static string $resource = ProdyResource::class;

    // Jika ingin menambahkan tombol tambahan di header, bisa override getHeaderActions().
    // protected function getHeaderActions(): array
    // {
    //     return [];
    // }
}
