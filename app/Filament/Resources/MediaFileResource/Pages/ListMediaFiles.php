<?php

namespace App\Filament\Resources\MediaFileResource\Pages;

use App\Filament\Resources\MediaFileResource;
use App\Models\MediaFile;
use Filament\Resources\Pages\ListRecords;

class ListMediaFiles extends ListRecords
{
    protected static string $resource = MediaFileResource::class;

    public function getHeading(): string
    {
        $totalFiles = MediaFile::count();
        $totalSize = MediaFile::sum('size');

        $size = $totalSize ? MediaFileResource::humanBytes((int) $totalSize) : '0 B';

        return "Media ({$totalFiles} file · {$size})";
    }

    protected function getHeaderActions(): array
    {
        // Header actions diatur langsung di Table header.
        return [];
    }
}
