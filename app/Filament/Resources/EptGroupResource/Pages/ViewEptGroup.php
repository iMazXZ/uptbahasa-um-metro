<?php

namespace App\Filament\Resources\EptGroupResource\Pages;

use App\Filament\Resources\EptGroupResource;
use App\Models\EptGroup;
use App\Support\EptSchedulePostSyncService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewEptGroup extends ViewRecord
{
    protected static string $resource = EptGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            // Tetapkan Jadwal
            Actions\Action::make('tetapkan_jadwal')
                ->label('Tetapkan Jadwal')
                ->icon('heroicon-o-calendar')
                ->color('success')
                ->visible(fn () => !$this->record->jadwal)
                ->form([
                    \Filament\Forms\Components\DateTimePicker::make('jadwal')
                        ->label('Waktu Tes')
                        ->required()
                        ->native(false)
                        ->seconds(false)
                        ->default(now()->setTime(8, 30))
                        ->minDate(now()->startOfDay()),
                ])
                ->action(function (array $data) {
                    $this->record->update(['jadwal' => $data['jadwal']]);
                    app(EptSchedulePostSyncService::class)->sync($this->record->fresh(), auth()->id());
                    Notification::make()
                        ->success()
                        ->title('Jadwal berhasil ditetapkan')
                        ->send();
                }),
            Actions\Action::make('sync_schedule_post')
                ->label('Sinkronkan ke Posting Informasi')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->visible(fn () => $this->record->jadwal !== null)
                ->requiresConfirmation()
                ->modalHeading('Sinkronkan Jadwal ke Posting Informasi')
                ->modalDescription(fn () =>
                    'Gunakan ini untuk membuat atau memperbarui posting jadwal publik dari grup "' . $this->record->name . '".'
                )
                ->action(function () {
                    EptGroupResource::syncSchedulePostForView($this->record);
                }),
            
            // Kirim Notif Bulk
            Actions\Action::make('kirim_notif_wa')
                ->label('Kirim Notifikasi Semua')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('info')
                ->visible(fn () => $this->record->jadwal !== null)
                ->form([
                    \Filament\Forms\Components\Toggle::make('force_resend')
                        ->label('Kirim ulang yang sebelumnya sudah sukses')
                        ->helperText('Default aman: hanya kirim yang belum diproses, gagal, atau perlu kirim ulang.')
                        ->default(false),
                ])
                ->modalHeading('Kirim Notifikasi')
                ->modalDescription(fn () => 
                    'Grup "' . $this->record->name . '" memiliki ' . $this->record->allRegistrations()->count() . ' peserta. Status saat ini: ' . EptGroupResource::notificationSummary($this->record)
                )
                ->action(function (array $data) {
                    $this->sendBulkWA($this->record, (bool) ($data['force_resend'] ?? false));
                }),
            Actions\Action::make('kirim_notif_gagal')
                ->label('Kirim yang Gagal')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->jadwal !== null && EptGroupResource::notificationSummary($this->record) !== 'Belum ada peserta' && EptGroupResource::failedNotificationCountForView($this->record) > 0)
                ->requiresConfirmation()
                ->modalHeading('Kirim Ulang yang Gagal')
                ->modalDescription(fn () =>
                    'Kirim ulang hanya untuk peserta dengan status notifikasi gagal di grup "' . $this->record->name . '" (' . EptGroupResource::failedNotificationCountForView($this->record) . ' peserta)?'
                )
                ->action(function () {
                    $this->sendFailedOnly($this->record);
                }),

            Actions\Action::make('export_bukti_pembayaran')
                ->label('Export Bukti Pembayaran')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']))
                ->action(function () {
                    return redirect()->to(route('admin.ept-group-export-bukti.preview', [
                        'group' => $this->record->id,
                    ]));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informasi Grup')
                    ->schema([
                        Components\TextEntry::make('name')
                            ->label('Nama Grup'),
                        Components\TextEntry::make('jadwal')
                            ->label('Jadwal Tes')
                            ->dateTime('l, d M Y H:i')
                            ->placeholder('Belum ditetapkan')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'warning'),
                        Components\TextEntry::make('lokasi')
                            ->label('Lokasi'),
                        Components\TextEntry::make('notification_summary')
                            ->label('Status Notifikasi')
                            ->state(fn (EptGroup $record) => EptGroupResource::notificationSummary($record))
                            ->badge()
                            ->color('gray'),
                    ])
                    ->columns(4),
            ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Resources\EptGroupResource\Widgets\PesertaWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function sendBulkWA(EptGroup $record, bool $forceResend = false): void
    {
        $results = EptGroupResource::dispatchScheduleNotifications(
            $record,
            EptGroupResource::groupRegistrationsForNotifications($record),
            $forceResend,
        );

        Notification::make()
            ->success()
            ->title('Notifikasi diproses')
            ->body(EptGroupResource::dispatchSummary($results))
            ->send();
    }

    protected function sendFailedOnly(EptGroup $record): void
    {
        $results = EptGroupResource::dispatchScheduleNotifications(
            $record,
            EptGroupResource::groupRegistrationsForNotifications($record),
            false,
            true,
        );

        Notification::make()
            ->success()
            ->title('Notifikasi gagal diproses ulang')
            ->body(EptGroupResource::dispatchSummary($results))
            ->send();
    }
}
