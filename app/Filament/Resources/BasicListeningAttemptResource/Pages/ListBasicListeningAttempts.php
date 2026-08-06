<?php

namespace App\Filament\Resources\BasicListeningAttemptResource\Pages;

use App\Filament\Resources\BasicListeningAttemptResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions as Actions;

class ListBasicListeningAttempts extends ListRecords
{
    protected static string $resource = BasicListeningAttemptResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\BasicListeningAttemptResource\Widgets\AttemptSummaryStats::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return ['sm' => 2, 'xl' => 3];
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
