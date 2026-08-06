<?php

namespace App\Filament\Resources\EptOnlineFormResource\Pages;

use App\Exports\EptOnlineTemplateExport;
use App\Filament\Resources\EptOnlineFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListEptOnlineForms extends ListRecords
{
    protected static string $resource = EptOnlineFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Template Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(new EptOnlineTemplateExport(), 'ept-online-template.xlsx')),
            Actions\CreateAction::make(),
        ];
    }
}
