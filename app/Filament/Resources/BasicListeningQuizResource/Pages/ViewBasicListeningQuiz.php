<?php

namespace App\Filament\Resources\BasicListeningQuizResource\Pages;

use App\Filament\Resources\BasicListeningQuizResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBasicListeningQuiz extends ViewRecord
{
    protected static string $resource = BasicListeningQuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tambah action di header jika perlu, mis. tombol export
        ];
    }
}
