<?php

namespace App\Filament\Resources\CertificateCategoryResource\Pages;

use App\Filament\Resources\CertificateCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCertificateCategory extends EditRecord
{
    protected static string $resource = CertificateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
