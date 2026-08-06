<?php

namespace App\Filament\Resources\ManualCertificateResource\Pages;

use App\Filament\Resources\ManualCertificateResource;
use App\Models\CertificateCategory;
use Filament\Resources\Pages\CreateRecord;

class CreateManualCertificate extends CreateRecord
{
    protected static string $resource = ManualCertificateResource::class;
}
