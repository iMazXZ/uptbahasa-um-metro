<?php

namespace App\Filament\Resources\BasicListeningConnectCodeResource\Pages;

use App\Filament\Resources\BasicListeningConnectCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBasicListeningConnectCodes extends ListRecords
{
    protected static string $resource = BasicListeningConnectCodeResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\BasicListeningConnectCodeResource\Widgets\ConnectCodeSummaryStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dashboard')
                ->label('Kembali ke Dasbor')
                ->url(route('filament.admin.pages.2'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            Actions\CreateAction::make(),
        ];
    }
}
