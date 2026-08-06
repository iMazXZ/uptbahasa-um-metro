<?php

namespace App\Filament\Resources\EptGroupResource\Widgets;

use App\Models\EptGroup;
use App\Models\EptRegistration;
use App\Models\EptScheduleNotification;
use App\Notifications\EptScheduleAssignedNotification;
use App\Support\EptScheduleNotificationTracker;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class PesertaWidget extends BaseWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Daftar Peserta';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $groupId = $this->record->id;
                
                return EptRegistration::query()
                    ->where(function ($q) use ($groupId) {
                        $q->where('grup_1_id', $groupId)
                          ->orWhere('grup_2_id', $groupId)
                          ->orWhere('grup_3_id', $groupId)
                          ->orWhere('grup_4_id', $groupId);
                    })
                    ->with([
                        'user.prody',
                        'scheduleNotifications' => fn ($query) => $query->where('ept_group_id', $groupId),
                    ]);
            })
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.srn')
                    ->label('NIM')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.prody.name')
                    ->label('Prodi')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tes_ke')
                    ->label('Tes Ke')
                    ->badge()
                    ->getStateUsing(function (EptRegistration $record) {
                        $groupId = $this->record->id;
                        return (string) ($record->testNumberForGroupId((int) $groupId) ?? '-');
                    }),
                Tables\Columns\TextColumn::make('notification_status')
                    ->label('Notif Jadwal')
                    ->badge()
                    ->state(fn (EptRegistration $record): string => $this->overallNotificationStatus($record))
                    ->formatStateUsing(fn (string $state): string => EptScheduleNotification::statusLabel($state))
                    ->color(fn (string $state): string => EptScheduleNotification::statusColor($state)),
                Tables\Columns\TextColumn::make('mail_status')
                    ->label('Email')
                    ->badge()
                    ->state(fn (EptRegistration $record): string => $this->channelStatus($record, 'mail'))
                    ->formatStateUsing(fn (string $state): string => EptScheduleNotification::statusLabel($state))
                    ->color(fn (string $state): string => EptScheduleNotification::statusColor($state)),
                Tables\Columns\TextColumn::make('wa_status')
                    ->label('WA')
                    ->badge()
                    ->state(fn (EptRegistration $record): string => $this->channelStatus($record, 'whatsapp'))
                    ->formatStateUsing(fn (string $state): string => EptScheduleNotification::statusLabel($state))
                    ->color(fn (string $state): string => EptScheduleNotification::statusColor($state)),
                Tables\Columns\TextColumn::make('last_requested_at')
                    ->label('Terakhir Diproses')
                    ->since()
                    ->placeholder('Belum pernah')
                    ->state(fn (EptRegistration $record) => $this->scheduleNotificationRecord($record)?->last_requested_at),
                Tables\Columns\TextColumn::make('error_summary')
                    ->label('Error Singkat')
                    ->wrap()
                    ->placeholder('-')
                    ->state(fn (EptRegistration $record): ?string => $this->errorSummary($record, 120))
                    ->tooltip(fn (EptRegistration $record): ?string => $this->errorSummary($record))
                    ->color('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('kirim_wa')
                    ->label(fn (EptRegistration $record): string => $this->overallNotificationStatus($record) === EptScheduleNotification::STATUS_SENT ? 'Kirim Ulang' : 'Kirim Notifikasi')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->size('sm')
                    ->visible(fn (EptRegistration $record) => $this->record->jadwal && $record->user !== null)
                    ->form([
                        Forms\Components\Toggle::make('force_resend')
                            ->label('Kirim ulang meski sebelumnya sudah sukses')
                            ->helperText('Default aman: hanya kirim jika belum diproses, gagal, atau jadwal grup berubah.')
                            ->default(false),
                    ])
                    ->modalHeading('Kirim Notifikasi')
                    ->modalDescription(fn (EptRegistration $record) => 
                        "Status saat ini: " . EptScheduleNotification::statusLabel($this->overallNotificationStatus($record)) . ". Kirim notifikasi jadwal ke {$record->user->name}?"
                    )
                    ->action(function (EptRegistration $record, array $data) {
                        $this->sendIndividualWA($record, (bool) ($data['force_resend'] ?? false));
                    }),
            ])
            ->emptyStateHeading('Belum ada peserta')
            ->emptyStateDescription('Peserta akan muncul setelah pendaftaran disetujui dan dimasukkan ke grup ini.')
            ->paginated([10, 25, 50]);
    }

    protected function sendIndividualWA(EptRegistration $registration, bool $forceResend = false): void
    {
        $user = $registration->user;
        $group = $this->record;

        $tesNum = $registration->testNumberForGroupId((int) $group->id);
        $expectsWhatsApp = $this->expectsWhatsApp($registration);
        $existing = $this->scheduleNotificationRecord($registration);
        $channels = EptScheduleNotificationTracker::channelsToDispatch(
            $existing,
            $group,
            $expectsWhatsApp,
            $forceResend,
        );

        try {
            if ($tesNum === null) {
                Notification::make()
                    ->danger()
                    ->title('Gagal memproses notifikasi')
                    ->body('Peserta tidak terdaftar pada grup ini.')
                    ->send();
                return;
            }

            if ($channels === []) {
                Notification::make()
                    ->warning()
                    ->title('Notifikasi tidak dikirim ulang')
                    ->body('Status saat ini: ' . EptScheduleNotification::statusLabel($this->overallNotificationStatus($registration)) . '. Aktifkan kirim ulang jika memang diperlukan.')
                    ->send();
                return;
            }

            $tracking = EptScheduleNotificationTracker::prime(
                $registration,
                $group,
                $tesNum,
                $expectsWhatsApp,
                $channels,
            );

            $user->notify(new EptScheduleAssignedNotification(
                registrationId: (int) $registration->getKey(),
                groupId: (int) $group->getKey(),
                testNumber: $tesNum,
                groupName: (string) $group->name,
                scheduledAt: $group->jadwal,
                location: (string) $group->lokasi,
                contentSignature: (string) $tracking->content_signature,
                dashboardUrl: route('dashboard.ept-registration.index'),
                requestedChannels: $channels,
            ));

            $bodyParts = [];

            if (in_array('database', $channels, true)) {
                $bodyParts[] = 'Notifikasi dashboard diperbarui';
            }

            if (in_array('mail', $channels, true)) {
                $bodyParts[] = 'Email diproses';
            }

            if (in_array('whatsapp', $channels, true)) {
                $bodyParts[] = 'WA masuk antrean';
            } elseif (! $expectsWhatsApp) {
                $bodyParts[] = 'WA dilewati karena nomor belum terverifikasi';
            }

            $body = implode(' dan ', $bodyParts) . " untuk {$user->name}";

            Notification::make()
                ->success()
                ->title('Notifikasi diproses')
                ->body($body)
                ->send();
        } catch (\Throwable $e) {
            if (isset($tracking)) {
                EptScheduleNotificationTracker::markDispatchFailure($tracking, $expectsWhatsApp, $e->getMessage());
            }

            Notification::make()
                ->danger()
                ->title('Gagal memproses notifikasi')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function scheduleNotificationRecord(EptRegistration $registration): ?EptScheduleNotification
    {
        return $registration->scheduleNotificationForGroupId((int) $this->record->getKey());
    }

    protected function overallNotificationStatus(EptRegistration $registration): string
    {
        return $this->scheduleNotificationRecord($registration)?->overallStatus(
            $this->expectsWhatsApp($registration),
            $this->record,
        ) ?? EptScheduleNotification::STATUS_NOT_SENT;
    }

    protected function channelStatus(EptRegistration $registration, string $channel): string
    {
        return $this->scheduleNotificationRecord($registration)?->channelStatus(
            $channel,
            $this->expectsWhatsApp($registration),
        ) ?? ($channel === 'whatsapp' && ! $this->expectsWhatsApp($registration)
            ? EptScheduleNotification::STATUS_SKIPPED
            : EptScheduleNotification::STATUS_NOT_SENT);
    }

    protected function expectsWhatsApp(EptRegistration $registration): bool
    {
        return filled($registration->user?->whatsapp) && filled($registration->user?->whatsapp_verified_at);
    }

    protected function errorSummary(EptRegistration $registration, ?int $limit = null): ?string
    {
        $notification = $this->scheduleNotificationRecord($registration);

        if (! $notification) {
            return null;
        }

        $parts = [];

        if ($notification->mail_status === EptScheduleNotification::STATUS_FAILED && filled($notification->mail_error)) {
            $parts[] = 'Email: ' . $notification->mail_error;
        }

        if (
            $this->expectsWhatsApp($registration)
            && $notification->whatsapp_status === EptScheduleNotification::STATUS_FAILED
            && filled($notification->whatsapp_error)
        ) {
            $parts[] = 'WA: ' . $notification->whatsapp_error;
        }

        if ($parts === []) {
            return null;
        }

        $text = implode(' | ', $parts);

        return $limit !== null
            ? mb_strimwidth($text, 0, $limit, '...')
            : $text;
    }
}
