<?php

namespace App\Filament\Resources\PenerjemahanResource\Pages;

use Filament\Notifications\Notification;
use App\Filament\Resources\PenerjemahanResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditPenerjemahan extends EditRecord
{
    protected static string $resource = PenerjemahanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        if (auth()->user()->hasRole(['Admin', 'Staf Administrasi'])) {
            return [
                Actions\DeleteAction::make(),
            ];
        }

        return [];
    }

    public function getSubheading(): ?string
    {
        if ($this->record) {
            if ($this->record->status === 'Ditolak - Dokumen Tidak Valid') {
                return 'Silakan Perbaiki dan Upload Ulang Dokumen Anda, kemudian Klik Tombol Perbarui Permohonan.';
            }
            if ($this->record->status === 'Ditolak - Pembayaran Tidak Valid') {
                return 'Silakan Perbaiki Bukti Pembayaran Anda dan Upload Ulang, kemudian Klik Tombol Perbarui Permohonan.';
            }
        }
        return null;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Permohonan diperbarui')
            ->body('Data permohonan penerjemahan berhasil diperbarui.');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Perbarui Permohonan')
                ->submit('save')
                ->icon('heroicon-o-check-circle'),
            Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url($this->getResource()::getUrl('index'))
                ->icon('heroicon-o-x-circle'),
        ];
    }
}
