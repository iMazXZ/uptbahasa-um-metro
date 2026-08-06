<?php

namespace App\Filament\Resources\EptRegistrationResource\Pages;

use App\Filament\Resources\EptRegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEptRegistrations extends ListRecords
{
    protected static string $resource = EptRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        $topicUrl = trim((string) config('services.ntfy.topic_url', 'https://ntfy.sh/lembagabahasa_umm_notif'));

        return [
            Actions\Action::make('openNtfyTopic')
                ->label('Aktifkan Notifikasi')
                ->icon('heroicon-o-bell-alert')
                ->color('gray')
                ->tooltip('Buka halaman subscribe notifikasi ntfy.')
                ->url($topicUrl)
                ->openUrlInNewTab(),
        ];
    }
}
