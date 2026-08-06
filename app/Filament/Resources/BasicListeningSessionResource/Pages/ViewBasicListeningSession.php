<?php

namespace App\Filament\Resources\BasicListeningSessionResource\Pages;

use App\Filament\Resources\BasicListeningSessionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBasicListeningSession extends ViewRecord
{
    protected static string $resource = BasicListeningSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Contoh: tombol shortcut ke buat Quiz untuk session ini, dll.
        ];
    }
}
