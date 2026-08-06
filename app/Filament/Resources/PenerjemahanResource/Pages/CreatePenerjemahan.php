<?php

namespace App\Filament\Resources\PenerjemahanResource\Pages;

use App\Filament\Resources\PenerjemahanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePenerjemahan extends CreateRecord
{
    protected static string $resource = PenerjemahanResource::class;

    protected static ?string $title = 'Permohonan Penerjemahan Dokumen Abstrak';

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Permohonan berhasil diajukan')
            ->body('Permohonan penerjemahan Anda telah berhasil disimpan.');
    }
}
