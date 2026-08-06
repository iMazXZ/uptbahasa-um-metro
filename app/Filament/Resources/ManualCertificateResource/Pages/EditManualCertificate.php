<?php

namespace App\Filament\Resources\ManualCertificateResource\Pages;

use App\Filament\Resources\ManualCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManualCertificate extends EditRecord
{
    protected static string $resource = ManualCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn() => route('manual-certificate.download', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
