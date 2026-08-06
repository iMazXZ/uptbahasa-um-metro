<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EptGroupResource\Pages;
use App\Models\EptGroup;
use App\Models\EptRegistration;
use App\Models\EptScheduleNotification;
use App\Notifications\EptScheduleAssignedNotification;
use App\Support\EptScheduleNotificationTracker;
use App\Support\EptSchedulePostSyncService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EptGroupResource extends BaseResource
{
    protected static ?string $model = EptGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'EPT';
    protected static ?string $navigationLabel = 'Grup EPT';
    protected static ?string $modelLabel = 'Grup EPT';
    protected static ?string $pluralModelLabel = 'Grup EPT';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Grup')
                    ->required()
                    ->placeholder('Contoh: Grup 001 Pasca'),
                Forms\Components\TextInput::make('quota')
                    ->label('Kuota Peserta')
                    ->numeric()
                    ->minValue(1)
                    ->default(20)
                    ->required()
                    ->helperText('Batas maksimal peserta yang bisa dimasukkan ke grup ini.'),
                Forms\Components\DateTimePicker::make('jadwal')
                    ->label('Jadwal Tes')
                    ->native(false)
                    ->seconds(false)
                    ->nullable(),
                Forms\Components\TextInput::make('lokasi')
                    ->label('Lokasi')
                    ->default('Ruang Stanford')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => static::applyIndexAggregates($query))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Grup')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quota')
                    ->label('Kuota')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('jadwal')
                    ->label('Jadwal Tes')
                    ->dateTime('l, d M Y, H:i')
                    ->placeholder('Belum ditetapkan')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('lokasi')
                    ->label('Lokasi')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('peserta_count')
                    ->label('Jumlah Peserta')
                    ->alignCenter()
                    ->getStateUsing(fn (EptGroup $record) =>
                        (int) ($record->participants_total ?? $record->allRegistrations()->count())
                    )
                    ->toggleable(),
                Tables\Columns\TextColumn::make('notification_overview')
                    ->label('Status Notif')
                    ->wrap()
                    ->getStateUsing(fn (EptGroup $record) => static::notificationSummary($record))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('remaining_quota')
                    ->label('Sisa')
                    ->alignCenter()
                    ->getStateUsing(function (EptGroup $record): int {
                        $remaining = (int) $record->quota - (int) ($record->participants_total ?? $record->allRegistrations()->count());

                        return max(0, $remaining);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_jadwal')
                    ->label('Status Jadwal')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah ada jadwal')
                    ->falseLabel('Belum ada jadwal')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('jadwal'),
                        false: fn ($q) => $q->whereNull('jadwal'),
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    // ACTION: TETAPKAN JADWAL
                    Tables\Actions\Action::make('tetapkan_jadwal')
                        ->label('Tetapkan Jadwal')
                        ->icon('heroicon-o-calendar')
                        ->color('success')
                        ->visible(fn (EptGroup $record) => !$record->jadwal)
                        ->form([
                            Forms\Components\DateTimePicker::make('jadwal')
                                ->label('Waktu Tes')
                                ->required()
                                ->native(false)
                                ->seconds(false)
                                ->default(now()->setTime(8, 30))
                                ->minDate(now()->startOfDay()),
                        ])
                        ->action(function (EptGroup $record, array $data) {
                            $record->update(['jadwal' => $data['jadwal']]);
                            app(EptSchedulePostSyncService::class)->sync($record->fresh(), auth()->id());
                            Notification::make()
                                ->success()
                                ->title('Jadwal berhasil ditetapkan')
                                ->body('Pendaftar sekarang bisa melihat jadwal di dashboard.')
                                ->send();
                        }),
                    Tables\Actions\Action::make('sync_schedule_post')
                        ->label('Sinkronkan ke Posting Informasi')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->visible(fn (EptGroup $record) => $record->jadwal !== null)
                        ->requiresConfirmation()
                        ->modalHeading('Sinkronkan Jadwal ke Posting Informasi')
                        ->modalDescription(fn (EptGroup $record) =>
                            'Gunakan ini untuk membuat atau memperbarui posting jadwal publik dari grup "' . $record->name . '". Cocok untuk grup lama yang sudah punya jadwal sebelum fitur sinkron otomatis ditambahkan.'
                        )
                        ->action(function (EptGroup $record) {
                            static::syncSchedulePostAndNotify($record);
                        }),

                    // ACTION: KIRIM NOTIF
                    Tables\Actions\Action::make('kirim_notif_wa')
                        ->label('Kirim Notifikasi')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('info')
                        ->visible(fn (EptGroup $record) => $record->jadwal !== null)
                        ->form([
                            Forms\Components\Toggle::make('force_resend')
                                ->label('Kirim ulang yang sebelumnya sudah sukses')
                                ->helperText('Default aman: hanya kirim yang belum diproses, gagal, atau perlu kirim ulang karena jadwal berubah.')
                                ->default(false),
                        ])
                        ->modalHeading('Kirim Notifikasi')
                        ->modalDescription(fn (EptGroup $record) => 
                            'Grup "' . $record->name . '" memiliki ' . $record->allRegistrations()->count() . ' peserta. Status saat ini: ' . static::notificationSummary($record)
                        )
                        ->action(function (EptGroup $record, array $data) {
                            $results = static::dispatchScheduleNotifications(
                                $record,
                                static::groupRegistrationsForNotifications($record),
                                (bool) ($data['force_resend'] ?? false),
                            );

                            Notification::make()
                                ->success()
                                ->title('Notifikasi diproses')
                                ->body(static::dispatchSummary($results))
                                ->send();
                        }),
                    Tables\Actions\Action::make('kirim_notif_gagal')
                        ->label('Kirim yang Gagal')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (EptGroup $record) => $record->jadwal !== null && static::failedNotificationCount($record) > 0)
                        ->requiresConfirmation()
                        ->modalHeading('Kirim Ulang yang Gagal')
                        ->modalDescription(fn (EptGroup $record) =>
                            'Kirim ulang hanya untuk peserta dengan status notifikasi gagal di grup "' . $record->name . '" (' . static::failedNotificationCount($record) . ' peserta)?'
                        )
                        ->action(function (EptGroup $record) {
                            $results = static::dispatchScheduleNotifications(
                                $record,
                                static::groupRegistrationsForNotifications($record),
                                false,
                                true,
                            );

                            Notification::make()
                                ->success()
                                ->title('Notifikasi gagal diproses ulang')
                                ->body(static::dispatchSummary($results))
                                ->send();
                        }),

                    Tables\Actions\Action::make('export_bukti_pembayaran')
                        ->label('Export Bukti')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('warning')
                        ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']))
                        ->action(function (EptGroup $record) {
                            return redirect()->to(route('admin.ept-group-export-bukti.preview', [
                                'group' => $record->id,
                            ]));
                        }),
                    
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Peserta'),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-cog-6-tooth'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_sync_schedule_posts')
                    ->label('Sinkronkan ke Posting Informasi')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $created = 0;
                        $updated = 0;
                        $skipped = 0;

                        foreach ($records as $record) {
                            if (! $record->jadwal) {
                                $skipped++;
                                continue;
                            }

                            $result = static::syncSchedulePost($record);

                            if ($result['created']) {
                                $created++;
                                continue;
                            }

                            if ($result['post']) {
                                $updated++;
                                continue;
                            }

                            $skipped++;
                        }

                        Notification::make()
                            ->success()
                            ->title('Sinkronisasi selesai')
                            ->body("Dibuat: {$created}, Diperbarui: {$updated}, Dilewati: {$skipped}")
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                // BULK: KIRIM NOTIF KE BANYAK GRUP
                Tables\Actions\BulkAction::make('bulk_kirim_notif')
                    ->label('Kirim Notifikasi')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->form([
                        Forms\Components\Toggle::make('force_resend')
                            ->label('Kirim ulang yang sebelumnya sudah sukses')
                            ->helperText('Default aman: hanya kirim yang belum diproses, gagal, atau perlu kirim ulang.')
                            ->default(false),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $totalDashboardUpdated = 0;
                        $totalEmailQueued = 0;
                        $totalWaQueued = 0;
                        $totalFailed = 0;
                        $totalSkipped = 0;

                        foreach ($records as $record) {
                            if (! $record->jadwal) {
                                continue;
                            }

                            $results = static::dispatchScheduleNotifications(
                                $record,
                                static::groupRegistrationsForNotifications($record),
                                (bool) ($data['force_resend'] ?? false),
                            );

                            $totalDashboardUpdated += $results['dashboard_updated'];
                            $totalEmailQueued += $results['email_queued'];
                            $totalWaQueued += $results['wa_queued'];
                            $totalSkipped += $results['skipped'];
                            $totalFailed += $results['failed'];
                        }

                        Notification::make()
                            ->success()
                            ->title('Notifikasi diproses')
                            ->body("Dashboard: {$totalDashboardUpdated}, Email: {$totalEmailQueued}, WA: {$totalWaQueued}, Dilewati: {$totalSkipped}, Gagal: {$totalFailed}")
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('bulk_kirim_gagal')
                    ->label('Kirim yang Gagal')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $totalDashboardUpdated = 0;
                        $totalEmailQueued = 0;
                        $totalWaQueued = 0;
                        $totalFailed = 0;
                        $totalSkipped = 0;

                        foreach ($records as $record) {
                            if (! $record->jadwal) {
                                continue;
                            }

                            $results = static::dispatchScheduleNotifications(
                                $record,
                                static::groupRegistrationsForNotifications($record),
                                false,
                                true,
                            );

                            $totalDashboardUpdated += $results['dashboard_updated'];
                            $totalEmailQueued += $results['email_queued'];
                            $totalWaQueued += $results['wa_queued'];
                            $totalSkipped += $results['skipped'];
                            $totalFailed += $results['failed'];
                        }

                        Notification::make()
                            ->success()
                            ->title('Notifikasi gagal diproses ulang')
                            ->body("Dashboard: {$totalDashboardUpdated}, Email: {$totalEmailQueued}, WA: {$totalWaQueued}, Dilewati: {$totalSkipped}, Gagal: {$totalFailed}")
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEptGroups::route('/'),
            'create' => Pages\CreateEptGroup::route('/create'),
            'view' => Pages\ViewEptGroup::route('/{record}'),
            'edit' => Pages\EditEptGroup::route('/{record}/edit'),
        ];
    }

    public static function notificationSummary(EptGroup $group): string
    {
        $counts = static::notificationCounts($group);
        $parts = [];

        foreach ([
            EptScheduleNotification::STATUS_SENT,
            EptScheduleNotification::STATUS_QUEUED,
            EptScheduleNotification::STATUS_FAILED,
            EptScheduleNotification::STATUS_OUTDATED,
            EptScheduleNotification::STATUS_NOT_SENT,
        ] as $status) {
            $count = $counts[$status] ?? 0;

            if ($count < 1) {
                continue;
            }

            $parts[] = EptScheduleNotification::statusLabel($status) . ' ' . $count;
        }

        if ($parts === []) {
            return 'Belum ada peserta';
        }

        return implode(' • ', $parts);
    }

    protected static function applyIndexAggregates(Builder $query): Builder
    {
        $groupsTable = $query->getModel()->getTable();
        $registrationsTable = (new EptRegistration())->getTable();
        $notificationsTable = (new EptScheduleNotification())->getTable();
        $usersTable = 'users';
        $signatureSql = static::currentGroupSignatureSql($groupsTable);
        $expectsWhatsAppSql = "({$usersTable}.whatsapp IS NOT NULL AND {$usersTable}.whatsapp <> '' AND {$usersTable}.whatsapp_verified_at IS NOT NULL)";

        return $query
            ->select("{$groupsTable}.*")
            ->selectSub(
                EptRegistration::query()
                    ->selectRaw('COUNT(*)')
                    ->where(function (Builder $registrationQuery) use ($groupsTable): void {
                        $registrationQuery
                            ->whereColumn('grup_1_id', "{$groupsTable}.id")
                            ->orWhereColumn('grup_2_id', "{$groupsTable}.id")
                            ->orWhereColumn('grup_3_id', "{$groupsTable}.id")
                            ->orWhereColumn('grup_4_id', "{$groupsTable}.id");
                    }),
                'participants_total'
            )
            ->selectRaw(
                "(SELECT COUNT(*) FROM {$notificationsTable} AS sn
                    INNER JOIN {$registrationsTable} AS er ON er.id = sn.ept_registration_id
                    INNER JOIN {$usersTable} ON {$usersTable}.id = er.user_id
                    WHERE sn.ept_group_id = {$groupsTable}.id
                      AND sn.content_signature = {$signatureSql}
                      AND (
                        sn.dashboard_status = ?
                        OR sn.mail_status = ?
                        OR ({$expectsWhatsAppSql} AND sn.whatsapp_status = ?)
                      )
                ) AS notification_failed_total",
                [
                    EptScheduleNotification::STATUS_FAILED,
                    EptScheduleNotification::STATUS_FAILED,
                    EptScheduleNotification::STATUS_FAILED,
                ]
            )
            ->selectRaw(
                "(SELECT COUNT(*) FROM {$notificationsTable} AS sn
                    INNER JOIN {$registrationsTable} AS er ON er.id = sn.ept_registration_id
                    INNER JOIN {$usersTable} ON {$usersTable}.id = er.user_id
                    WHERE sn.ept_group_id = {$groupsTable}.id
                      AND sn.content_signature = {$signatureSql}
                      AND NOT (
                        sn.dashboard_status = ?
                        OR sn.mail_status = ?
                        OR ({$expectsWhatsAppSql} AND sn.whatsapp_status = ?)
                      )
                      AND (
                        sn.dashboard_status = ?
                        OR sn.mail_status = ?
                        OR ({$expectsWhatsAppSql} AND sn.whatsapp_status = ?)
                      )
                ) AS notification_queued_total",
                [
                    EptScheduleNotification::STATUS_FAILED,
                    EptScheduleNotification::STATUS_FAILED,
                    EptScheduleNotification::STATUS_FAILED,
                    EptScheduleNotification::STATUS_QUEUED,
                    EptScheduleNotification::STATUS_QUEUED,
                    EptScheduleNotification::STATUS_QUEUED,
                ]
            )
            ->selectRaw(
                "(SELECT COUNT(*) FROM {$notificationsTable} AS sn
                    INNER JOIN {$registrationsTable} AS er ON er.id = sn.ept_registration_id
                    INNER JOIN {$usersTable} ON {$usersTable}.id = er.user_id
                    WHERE sn.ept_group_id = {$groupsTable}.id
                      AND sn.content_signature = {$signatureSql}
                      AND sn.dashboard_status = ?
                      AND sn.mail_status = ?
                      AND (
                        ({$expectsWhatsAppSql} AND sn.whatsapp_status = ?)
                        OR NOT {$expectsWhatsAppSql}
                      )
                ) AS notification_sent_total",
                [
                    EptScheduleNotification::STATUS_SENT,
                    EptScheduleNotification::STATUS_SENT,
                    EptScheduleNotification::STATUS_SENT,
                ]
            )
            ->selectRaw(
                "(SELECT COUNT(*) FROM {$notificationsTable} AS sn
                    WHERE sn.ept_group_id = {$groupsTable}.id
                      AND (sn.content_signature IS NULL OR sn.content_signature <> {$signatureSql})
                ) AS notification_outdated_total"
            );
    }

    /**
     * @return array{dashboard_updated:int,email_queued:int,wa_queued:int,skipped:int,failed:int}
     */
    public static function dispatchScheduleNotifications(
        EptGroup $group,
        Collection $registrations,
        bool $forceResend = false,
        bool $failedOnly = false,
    ): array {
        $results = [
            'dashboard_updated' => 0,
            'email_queued' => 0,
            'wa_queued' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($registrations as $registration) {
            $user = $registration->user;

            if (! $user) {
                $results['failed']++;
                continue;
            }

            $testNumber = $registration->testNumberForGroupId((int) $group->getKey());

            if ($testNumber === null) {
                $results['failed']++;
                continue;
            }

            $expectsWhatsApp = static::expectsWhatsApp($registration);
            $existing = $registration->scheduleNotificationForGroupId((int) $group->getKey());

            if (
                $failedOnly
                && $existing?->overallStatus($expectsWhatsApp, $group) !== EptScheduleNotification::STATUS_FAILED
            ) {
                $results['skipped']++;
                continue;
            }

            $channels = EptScheduleNotificationTracker::channelsToDispatch(
                $existing,
                $group,
                $expectsWhatsApp,
                $forceResend,
            );

            if ($channels === []) {
                $results['skipped']++;
                continue;
            }

            $tracking = EptScheduleNotificationTracker::prime(
                $registration,
                $group,
                $testNumber,
                $expectsWhatsApp,
                $channels,
            );

            try {
                $user->notify(new EptScheduleAssignedNotification(
                    registrationId: (int) $registration->getKey(),
                    groupId: (int) $group->getKey(),
                    testNumber: $testNumber,
                    groupName: (string) $group->name,
                    scheduledAt: $group->jadwal,
                    location: (string) $group->lokasi,
                    contentSignature: (string) $tracking->content_signature,
                    dashboardUrl: route('dashboard.ept-registration.index'),
                    requestedChannels: $channels,
                ));

                if (in_array('database', $channels, true)) {
                    $results['dashboard_updated']++;
                }

                if (in_array('mail', $channels, true)) {
                    $results['email_queued']++;
                }

                if ($expectsWhatsApp && in_array('whatsapp', $channels, true)) {
                    $results['wa_queued']++;
                }
            } catch (\Throwable $e) {
                EptScheduleNotificationTracker::markDispatchFailure($tracking, $expectsWhatsApp, $e->getMessage());
                $results['failed']++;
            }
        }

        return $results;
    }

    public static function dispatchSummary(array $results): string
    {
        return "Dashboard: {$results['dashboard_updated']}, Email: {$results['email_queued']}, WA: {$results['wa_queued']}, Dilewati: {$results['skipped']}, Gagal: {$results['failed']}";
    }

    public static function groupRegistrationsForNotifications(EptGroup $group): Collection
    {
        $groupId = (int) $group->getKey();

        return $group->allRegistrations()
            ->with([
                'user',
                'scheduleNotifications' => fn ($query) => $query->where('ept_group_id', $groupId),
            ])
            ->get();
    }

    protected static function notificationCounts(EptGroup $group): array
    {
        if (isset($group->participants_total)) {
            $sent = (int) ($group->notification_sent_total ?? 0);
            $queued = (int) ($group->notification_queued_total ?? 0);
            $failed = (int) ($group->notification_failed_total ?? 0);
            $outdated = (int) ($group->notification_outdated_total ?? 0);
            $participants = (int) ($group->participants_total ?? 0);

            return [
                EptScheduleNotification::STATUS_NOT_SENT => max(0, $participants - $sent - $queued - $failed - $outdated),
                EptScheduleNotification::STATUS_QUEUED => $queued,
                EptScheduleNotification::STATUS_SENT => $sent,
                EptScheduleNotification::STATUS_FAILED => $failed,
                EptScheduleNotification::STATUS_OUTDATED => $outdated,
            ];
        }

        $counts = [
            EptScheduleNotification::STATUS_NOT_SENT => 0,
            EptScheduleNotification::STATUS_QUEUED => 0,
            EptScheduleNotification::STATUS_SENT => 0,
            EptScheduleNotification::STATUS_FAILED => 0,
            EptScheduleNotification::STATUS_OUTDATED => 0,
        ];

        foreach (static::groupRegistrationsForNotifications($group) as $registration) {
            $record = $registration->scheduleNotificationForGroupId((int) $group->getKey());
            $status = $record?->overallStatus(static::expectsWhatsApp($registration), $group)
                ?? EptScheduleNotification::STATUS_NOT_SENT;

            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    public static function failedNotificationCountForView(EptGroup $group): int
    {
        return static::failedNotificationCount($group);
    }

    protected static function failedNotificationCount(EptGroup $group): int
    {
        return static::notificationCounts($group)[EptScheduleNotification::STATUS_FAILED] ?? 0;
    }

    protected static function expectsWhatsApp(EptRegistration $registration): bool
    {
        return filled($registration->user?->whatsapp) && filled($registration->user?->whatsapp_verified_at);
    }

    protected static function currentGroupSignatureSql(string $groupsTable): string
    {
        return "SHA2(CONCAT_WS('|', CAST({$groupsTable}.id AS CHAR), TRIM({$groupsTable}.name), COALESCE(DATE_FORMAT({$groupsTable}.jadwal, '%Y-%m-%d %H:%i:%s'), ''), TRIM(COALESCE({$groupsTable}.lokasi, ''))), 256)";
    }

    public static function syncSchedulePostForView(EptGroup $group): void
    {
        static::syncSchedulePostAndNotify($group);
    }

    protected static function syncSchedulePostAndNotify(EptGroup $group): void
    {
        $result = static::syncSchedulePost($group);

        if (! $result['post']) {
            Notification::make()
                ->warning()
                ->title('Posting jadwal tidak disinkronkan')
                ->body('Pastikan grup sudah memiliki jadwal dan author tersedia.')
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title($result['created'] ? 'Posting jadwal berhasil dibuat' : 'Posting jadwal berhasil diperbarui')
            ->body('Jadwal grup sekarang tersedia di Posting Informasi dan halaman publik.')
            ->send();
    }

    /**
     * @return array{post:\App\Models\Post|null,created:bool}
     */
    protected static function syncSchedulePost(EptGroup $group): array
    {
        $existing = $group->schedulePost()->first();
        $post = app(EptSchedulePostSyncService::class)->sync($group->fresh(), auth()->id());

        return [
            'post' => $post,
            'created' => $existing === null && $post !== null,
        ];
    }
}
