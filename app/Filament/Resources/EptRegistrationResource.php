<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EptRegistrationResource\Pages;
use App\Models\EptGroup;
use App\Models\EptRegistration;
use App\Notifications\EptRegistrationStatusNotification;
use App\Support\LegacyBasicListeningScores;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EptRegistrationResource extends BaseResource
{
    protected static ?string $model = EptRegistration::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'EPT';
    protected static ?string $navigationLabel = 'Pendaftaran EPT';
    protected static ?string $modelLabel = 'Pendaftaran EPT';
    protected static ?string $pluralModelLabel = 'Pendaftaran EPT';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi'])) {
            return null;
        }
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Pendaftar')->schema([
                Forms\Components\Placeholder::make('user_name')
                    ->label('Nama')
                    ->content(fn ($record) => $record->user->name ?? '-'),
                Forms\Components\Placeholder::make('user_srn')
                    ->label('NPM')
                    ->content(fn ($record) => $record->user->srn ?? '-'),
                Forms\Components\Placeholder::make('user_prody')
                    ->label('Program Studi')
                    ->content(fn ($record) => $record->user->prody->name ?? '-'),
                Forms\Components\Placeholder::make('student_status')
                    ->label('Status Peserta')
                    ->content(fn ($record) => $record?->student_status_label ?? '-'),
                Forms\Components\Placeholder::make('test_quota')
                    ->label('Kuota Tes')
                    ->content(fn ($record) => $record?->test_quota_label ?? '-'),
                Forms\Components\Placeholder::make('registration_status')
                    ->label('Status Pendaftaran')
                    ->content(function ($record): string {
                        return match ($record->status) {
                            'pending' => 'Menunggu',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            default => (string) $record->status,
                        };
                    }),
                Forms\Components\Placeholder::make('rejection_reason')
                    ->label('Alasan Ditolak')
                    ->content(fn ($record) => filled($record->rejection_reason) ? $record->rejection_reason : '-')
                    ->visible(fn ($record): bool => $record->status === 'rejected'),
            ])->columns(3),

            Forms\Components\Section::make('Nilai Pendukung EPT')
                ->description('Ringkasan nilai biodata yang dipakai untuk evaluasi syarat EPT.')
                ->schema([
                    Forms\Components\Placeholder::make('basic_listening_score')
                        ->label('Nilai Basic Listening')
                        ->content(fn (EptRegistration $record): string => static::basicListeningSummary($record)),

                    Forms\Components\Grid::make(6)
                        ->schema([
                            Forms\Components\Placeholder::make('interactive_class_1')
                                ->label('IC Sem 1')
                                ->content(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_1)),
                            Forms\Components\Placeholder::make('interactive_class_2')
                                ->label('IC Sem 2')
                                ->content(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_2)),
                            Forms\Components\Placeholder::make('interactive_class_3')
                                ->label('IC Sem 3')
                                ->content(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_3)),
                            Forms\Components\Placeholder::make('interactive_class_4')
                                ->label('IC Sem 4')
                                ->content(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_4)),
                            Forms\Components\Placeholder::make('interactive_class_5')
                                ->label('IC Sem 5')
                                ->content(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_5)),
                            Forms\Components\Placeholder::make('interactive_class_6')
                                ->label('IC Sem 6')
                                ->content(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_6)),
                        ])
                        ->columnSpanFull()
                        ->visible(fn (EptRegistration $record): bool => static::isPbiUser($record)),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Placeholder::make('interactive_bahasa_arab_1')
                                ->label('Bahasa Arab 1')
                                ->content(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_bahasa_arab_1)),
                            Forms\Components\Placeholder::make('interactive_bahasa_arab_2')
                                ->label('Bahasa Arab 2')
                                ->content(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_bahasa_arab_2)),
                        ])
                        ->columnSpanFull()
                        ->visible(fn (EptRegistration $record): bool => static::isIslamicProgramUser($record)),
                ])
                ->columns(3),

            Forms\Components\Section::make('Bukti Pembayaran')->schema([
                Forms\Components\Placeholder::make('bukti')
                    ->label('')
                    ->content(fn ($record) => view('filament.components.image-preview', [
                        'url' => Storage::url($record->bukti_pembayaran),
                    ])),
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('download_bukti_pembayaran')
                        ->label('Download Bukti Pembayaran')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(fn (?EptRegistration $record): bool => filled($record?->bukti_pembayaran))
                        ->action(fn (?EptRegistration $record) => $record ? static::downloadPaymentProof($record) : null),
                ]),
            ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Data Pendaftar')
                ->columns(3)
                ->schema([
                    TextEntry::make('user.name')
                        ->label('Nama')
                        ->weight('bold'),
                    TextEntry::make('user.srn')
                        ->label('NPM / SRN')
                        ->placeholder('-')
                        ->copyable()
                        ->copyMessage('NPM / SRN disalin'),
                    TextEntry::make('user.whatsapp')
                        ->label('Nomor WhatsApp')
                        ->placeholder('-')
                        ->copyable(fn (EptRegistration $record): bool => filled($record->user?->whatsapp))
                        ->copyableState(fn (EptRegistration $record): ?string => $record->user?->whatsapp)
                        ->copyMessage('Nomor WhatsApp disalin'),
                    TextEntry::make('user.email')
                        ->label('Email')
                        ->placeholder('-')
                        ->copyable(),
                    TextEntry::make('user.prody.name')
                        ->label('Program Studi')
                        ->placeholder('-'),
                    TextEntry::make('student_status_label')
                        ->label('Status Peserta')
                        ->badge()
                        ->color(fn (EptRegistration $record): string => match ($record->student_status) {
                            EptRegistration::STUDENT_STATUS_REGULAR => 'primary',
                            EptRegistration::STUDENT_STATUS_MAGISTER => 'warning',
                            EptRegistration::STUDENT_STATUS_KONVERSI => 'info',
                            EptRegistration::STUDENT_STATUS_GENERAL => 'success',
                            default => 'gray',
                        }),
                    TextEntry::make('test_quota_label')
                        ->label('Kuota Tes'),
                    TextEntry::make('status_label')
                        ->label('Status Pendaftaran')
                        ->badge()
                        ->color(fn (EptRegistration $record): string => $record->status_color),
                    TextEntry::make('approved_at')
                        ->label('Tanggal Disetujui')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('-')
                        ->visible(fn (EptRegistration $record): bool => $record->status === 'approved'),
                    TextEntry::make('rejected_at')
                        ->label('Tanggal Ditolak')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('-')
                        ->visible(fn (EptRegistration $record): bool => $record->status === 'rejected'),
                    TextEntry::make('created_at')
                        ->label('Tanggal Daftar')
                        ->dateTime('d M Y, H:i'),
                    TextEntry::make('updated_at')
                        ->label('Terakhir Diperbarui')
                        ->dateTime('d M Y, H:i'),
                    TextEntry::make('rejection_reason')
                        ->label('Alasan Ditolak')
                        ->placeholder('-')
                        ->columnSpanFull()
                        ->visible(fn (EptRegistration $record): bool => $record->status === 'rejected'),
                ]),

            InfoSection::make('Grup Tes')
                ->columns(4)
                ->schema([
                    TextEntry::make('grup1.name')
                        ->label('Grup Tes 1')
                        ->placeholder('-'),
                    TextEntry::make('grup2.name')
                        ->label('Grup Tes 2')
                        ->placeholder('-')
                        ->visible(fn (EptRegistration $record): bool => $record->requiredGroupCount() >= 2),
                    TextEntry::make('grup3.name')
                        ->label('Grup Tes 3')
                        ->placeholder('-')
                        ->visible(fn (EptRegistration $record): bool => $record->requiredGroupCount() >= 3),
                    TextEntry::make('grup4.name')
                        ->label('Grup Tes 4')
                        ->placeholder('-')
                        ->visible(fn (EptRegistration $record): bool => $record->requiredGroupCount() >= 4),
                ]),

            InfoSection::make('Nilai Pendukung EPT')
                ->columns(3)
                ->schema([
                    TextEntry::make('basic_listening_score')
                        ->label('Nilai Basic Listening')
                        ->state(fn (EptRegistration $record): string => static::basicListeningSummary($record)),
                    TextEntry::make('interactive_class_1')
                        ->label('IC Sem 1')
                        ->state(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_1))
                        ->visible(fn (EptRegistration $record): bool => static::isPbiUser($record)),
                    TextEntry::make('interactive_class_2')
                        ->label('IC Sem 2')
                        ->state(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_2))
                        ->visible(fn (EptRegistration $record): bool => static::isPbiUser($record)),
                    TextEntry::make('interactive_class_3')
                        ->label('IC Sem 3')
                        ->state(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_3))
                        ->visible(fn (EptRegistration $record): bool => static::isPbiUser($record)),
                    TextEntry::make('interactive_class_4')
                        ->label('IC Sem 4')
                        ->state(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_4))
                        ->visible(fn (EptRegistration $record): bool => static::isPbiUser($record)),
                    TextEntry::make('interactive_class_5')
                        ->label('IC Sem 5')
                        ->state(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_5))
                        ->visible(fn (EptRegistration $record): bool => static::isPbiUser($record)),
                    TextEntry::make('interactive_class_6')
                        ->label('IC Sem 6')
                        ->state(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_class_6))
                        ->visible(fn (EptRegistration $record): bool => static::isPbiUser($record)),
                    TextEntry::make('interactive_bahasa_arab_1')
                        ->label('Bahasa Arab 1')
                        ->state(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_bahasa_arab_1))
                        ->visible(fn (EptRegistration $record): bool => static::isIslamicProgramUser($record)),
                    TextEntry::make('interactive_bahasa_arab_2')
                        ->label('Bahasa Arab 2')
                        ->state(fn (EptRegistration $record): string => static::scoreText($record->user?->interactive_bahasa_arab_2))
                        ->visible(fn (EptRegistration $record): bool => static::isIslamicProgramUser($record)),
                ]),

            InfoSection::make('Bukti Pembayaran')
                ->schema([
                    ImageEntry::make('bukti_pembayaran')
                        ->label('Preview Bukti')
                        ->disk('public')
                        ->height(320)
                        ->visibility('public'),
                    TextEntry::make('bukti_pembayaran')
                        ->label('File')
                        ->state(fn (EptRegistration $record): string => $record->bukti_pembayaran)
                        ->copyable()
                        ->copyMessage('Path bukti pembayaran disalin'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nama disalin')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.srn')
                    ->label('NPM')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('NPM disalin')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('bukti_pembayaran')
                    ->label('Bukti')
                    ->formatStateUsing(fn ($state): string => filled($state) ? 'Lihat' : '-')
                    ->url(fn (EptRegistration $record): ?string => filled($record->bukti_pembayaran)
                        ? Storage::disk('public')->url($record->bukti_pembayaran)
                        : null)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.prody.name')
                    ->label('Prodi')
                    ->limit(20)
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('student_status')
                    ->label('Status Peserta')
                    ->color(fn (?string $state): string => match ($state) {
                        EptRegistration::STUDENT_STATUS_REGULAR => 'primary',
                        EptRegistration::STUDENT_STATUS_MAGISTER => 'warning',
                        EptRegistration::STUDENT_STATUS_KONVERSI => 'info',
                        EptRegistration::STUDENT_STATUS_GENERAL => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => filled($state) ? EptRegistration::studentStatusLabel($state) : 'Belum diisi')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('test_quota')
                    ->label('Kuota Tes')
                    ->getStateUsing(fn (EptRegistration $record): string => $record->test_quota_label)
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('assigned_groups')
                    ->label('Grup Tes')
                    ->getStateUsing(function (EptRegistration $record): string {
                        return collect([
                            $record->grup1?->name,
                            $record->grup2?->name,
                            $record->grup3?->name,
                            $record->grup4?->name,
                        ])->filter()->implode(' / ') ?: 'Belum ditetapkan';
                    })
                    ->limit(28)
                    ->tooltip(function (EptRegistration $record): ?string {
                        $groups = collect([
                            $record->grup1?->name,
                            $record->grup2?->name,
                            $record->grup3?->name,
                            $record->grup4?->name,
                        ])->filter()->implode(' / ');

                        return $groups !== '' ? $groups : null;
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Tanggal Status')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(fn ($record) => match ($record->status) {
                        'approved' => $record->approved_at,
                        'rejected' => $record->rejected_at,
                        default => null,
                    })
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('student_status')
                    ->label('Status Peserta')
                    ->options(EptRegistration::studentStatusOptions()),
                Tables\Filters\SelectFilter::make('assigned_group')
                    ->label('Grup Tes')
                    ->options(fn (): array => \App\Models\EptGroup::query()
                        ->select(['id', 'name'])
                        ->latest('id')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $groupId = $data['value'] ?? null;

                        if (! filled($groupId)) {
                            return $query;
                        }

                        return $query->where(function (Builder $groupQuery) use ($groupId): void {
                            $groupQuery->where('grup_1_id', $groupId)
                                ->orWhere('grup_2_id', $groupId)
                                ->orWhere('grup_3_id', $groupId)
                                ->orWhere('grup_4_id', $groupId);
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\Action::make('download_bukti')
                        ->label('Download Bukti')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(fn ($record) => !empty($record->bukti_pembayaran))
                        ->action(fn (EptRegistration $record) => static::downloadPaymentProof($record)),
                    Tables\Actions\Action::make('edit_student_status')
                        ->label('Ubah Status Peserta')
                        ->icon('heroicon-o-identification')
                        ->color('gray')
                        ->form(function (EptRegistration $record): array {
                            return [
                                Forms\Components\Select::make('student_status')
                                    ->label('Status Peserta')
                                    ->options(EptRegistration::studentStatusOptions())
                                    ->default($record->student_status)
                                    ->required()
                                    ->native(false),
                            ];
                        })
                        ->action(function (EptRegistration $record, array $data): void {
                            $newStatus = (string) ($data['student_status'] ?? '');
                            $oldStatus = (string) $record->student_status;

                            if ($newStatus === '' || $newStatus === $oldStatus) {
                                Notification::make()
                                    ->info()
                                    ->title('Tidak ada perubahan')
                                    ->body('Status peserta tetap sama.')
                                    ->send();

                                return;
                            }

                            $oldGroup2 = $record->grup_2_id;
                            $oldGroup3 = $record->grup_3_id;
                            $oldGroup4 = $record->grup_4_id;
                            $newQuota = EptRegistration::normalizeTestQuota(
                                $record->test_quota,
                                $newStatus,
                            );

                            $payload = [
                                'student_status' => $newStatus,
                                'test_quota' => $newQuota,
                            ];

                            if ($newStatus === EptRegistration::STUDENT_STATUS_GENERAL) {
                                $payload['grup_2_id'] = null;
                                $payload['grup_3_id'] = null;
                                $payload['grup_4_id'] = null;
                            } elseif ($newQuota < 4) {
                                $payload['grup_4_id'] = null;
                            }

                            $record->update($payload);
                            $record->refresh();

                            $body = 'Status peserta berhasil diperbarui.';

                            if (
                                $newStatus === EptRegistration::STUDENT_STATUS_GENERAL
                                && ($oldGroup2 !== null || $oldGroup3 !== null || $oldGroup4 !== null)
                            ) {
                                $body .= ' Grup tes 2, 3, dan 4 dikosongkan karena peserta Umum hanya 1 grup.';
                            } elseif (
                                $record->status === 'approved'
                                && $newStatus !== EptRegistration::STUDENT_STATUS_GENERAL
                                && ($record->grup_2_id === null || $record->grup_3_id === null)
                            ) {
                                $body .= ' Lengkapi penetapan Grup Tes 2 dan 3 lewat aksi "Ubah Grup".';
                            } elseif (
                                $record->status === 'approved'
                                && $record->requiredGroupCount() === EptRegistration::EXTRA_MULTI_TEST_QUOTA
                                && $record->grup_4_id === null
                            ) {
                                $body .= ' Lengkapi penetapan Grup Tes 4 lewat aksi "Ubah Grup".';
                            }

                            Notification::make()
                                ->success()
                                ->title('Status Peserta Diperbarui')
                                ->body($body)
                                ->send();
                        }),
                    Tables\Actions\Action::make('approve')
                        ->label('Setujui')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->form(function (EptRegistration $record): array {
                            return static::groupAssignmentForm($record);
                        })
                        ->action(function ($record, array $data) {
                            $testQuota = EptRegistration::normalizeTestQuota(
                                (int) ($data['test_quota'] ?? $record->requiredGroupCount()),
                                $record->student_status,
                            );
                            $groupAssignments = static::extractGroupAssignments($data, $testQuota);

                            static::ensureRequiredGroupAssignmentsFilled($groupAssignments);

                            if (! EptRegistration::hasDistinctGroupAssignments($groupAssignments)) {
                                throw ValidationException::withMessages(static::groupAssignmentMessages(
                                    $testQuota,
                                    "Grup Tes 1 sampai {$testQuota} harus berbeda."
                                ));
                            }

                            static::ensureQuotaAvailableForGroups($groupAssignments);

                            $record->update([
                                'status' => 'approved',
                                'approved_at' => now(),
                                'rejected_at' => null,
                                'test_quota' => $testQuota,
                                'grup_1_id' => $groupAssignments[0] ?? null,
                                'grup_2_id' => $testQuota >= 2 ? ($groupAssignments[1] ?? null) : null,
                                'grup_3_id' => $testQuota >= 3 ? ($groupAssignments[2] ?? null) : null,
                                'grup_4_id' => $testQuota >= 4 ? ($groupAssignments[3] ?? null) : null,
                            ]);

                            $user = $record->user;
                            if ($user) {
                                $user->notify(new EptRegistrationStatusNotification(
                                    status: 'approved',
                                    dashboardUrl: route('dashboard.ept-registration.index'),
                                ));
                            }

                            Notification::make()
                                ->success()
                                ->title('Pendaftaran Disetujui')
                                ->body('Peserta berhasil ditambahkan ke grup. Notifikasi status diproses via email.')
                                ->send();
                        }),
                    Tables\Actions\Action::make('edit_groups')
                        ->label('Ubah Grup')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'approved')
                        ->form(function (EptRegistration $record): array {
                            return static::groupAssignmentForm(
                                $record,
                                $record,
                                allowPastScheduledGroups: true,
                            );
                        })
                        ->action(function ($record, array $data) {
                            $testQuota = EptRegistration::normalizeTestQuota(
                                (int) ($data['test_quota'] ?? $record->requiredGroupCount()),
                                $record->student_status,
                            );
                            $groupAssignments = static::extractGroupAssignments($data, $testQuota);

                            static::ensureRequiredGroupAssignmentsFilled($groupAssignments);

                            if (! EptRegistration::hasDistinctGroupAssignments($groupAssignments)) {
                                throw ValidationException::withMessages(static::groupAssignmentMessages(
                                    $testQuota,
                                    "Grup Tes 1 sampai {$testQuota} harus berbeda."
                                ));
                            }

                            static::ensureQuotaAvailableForGroups(
                                $groupAssignments,
                                $record,
                                allowPastScheduledGroups: true,
                            );

                            $record->update([
                                'test_quota' => $testQuota,
                                'grup_1_id' => $groupAssignments[0] ?? null,
                                'grup_2_id' => $testQuota >= 2 ? ($groupAssignments[1] ?? null) : null,
                                'grup_3_id' => $testQuota >= 3 ? ($groupAssignments[2] ?? null) : null,
                                'grup_4_id' => $testQuota >= 4 ? ($groupAssignments[3] ?? null) : null,
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Grup Tes Diperbarui')
                                ->body('Penetapan grup tes berhasil diubah.')
                                ->send();
                        }),
                    Tables\Actions\Action::make('reject')
                        ->label('Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => 'rejected',
                                'rejected_at' => now(),
                                'approved_at' => null,
                                'rejection_reason' => $data['rejection_reason'],
                            ]);

                            $user = $record->user;
                            $waEligible = $user
                                && $user->whatsapp
                                && $user->whatsapp_verified_at;

                            if ($user) {
                                $user->notify(new EptRegistrationStatusNotification(
                                    status: 'rejected',
                                    dashboardUrl: route('dashboard.ept-registration.index'),
                                    rejectionReason: $data['rejection_reason'],
                                ));
                            }

                            if ($waEligible) {
                                Notification::make()
                                    ->warning()
                                    ->title('Pendaftaran Ditolak')
                                    ->body('Notifikasi penolakan diproses via email. WA juga masuk antrean.')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Pendaftaran Ditolak')
                                    ->body('Notifikasi penolakan diproses via email. WA dilewati karena nomor belum terverifikasi.')
                                    ->send();
                            }
                        }),
                ])
                    ->label('')
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('preview_export_bukti')
                        ->label('Preview Export Bukti')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->modalHeading('Preview export bukti pembayaran')
                        ->modalDescription('Data terpilih akan dibawa ke layout designer untuk crop, susun, dan atur jumlah foto per halaman.')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

                            if (empty($ids)) {
                                Notification::make()
                                    ->danger()
                                    ->title('Tidak ada data dipilih')
                                    ->send();

                                return null;
                            }

                            return redirect()->to(route('admin.ept-registration-export-bukti.preview', [
                                'ids' => $ids,
                            ]));
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user.prody',
                'grup1:id,name',
                'grup2:id,name',
                'grup3:id,name',
                'grup4:id,name',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEptRegistrations::route('/'),
            'view' => Pages\ViewEptRegistration::route('/{record}'),
        ];
    }

    protected static function groupAssignmentForm(
        EptRegistration $record,
        ?EptRegistration $contextRecord = null,
        bool $allowPastScheduledGroups = false,
    ): array {
        $groupOptionMeta = static::buildGroupOptionMeta($contextRecord, $allowPastScheduledGroups);
        $groupOptions = $groupOptionMeta['options'];
        $disabledGroupOptions = $groupOptionMeta['disabled'];
        $isGeneral = $record->isGeneralParticipant();

        return [
            Forms\Components\Placeholder::make('participant_rule')
                ->label('Skema Tes')
                ->content($isGeneral
                    ? 'Peserta Umum hanya dijadwalkan ke 1 grup tes.'
                    : 'Peserta dapat dijadwalkan ke 3 grup tes, atau 4 grup jika tagihan/pembayaran 200.'),
            Forms\Components\Radio::make('test_quota')
                ->label('Kuota Tes')
                ->options(EptRegistration::testQuotaOptionsForStudentStatus($record->student_status))
                ->default($record->requiredGroupCount())
                ->inline()
                ->live()
                ->required()
                ->helperText($isGeneral
                    ? 'Peserta Umum tetap 1 kali tes.'
                    : 'Biarkan 3 untuk pembayaran normal. Pilih 4 hanya jika tagihan/pembayaran 200.'),
            Forms\Components\Select::make('grup_1_id')
                ->label($isGeneral ? 'Grup Tes' : 'Grup Tes 1')
                ->options($groupOptions)
                ->default($contextRecord?->grup_1_id)
                ->disableOptionWhen(fn ($value): bool => isset($disabledGroupOptions[(int) $value]))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('grup_2_id')
                ->label('Grup Tes 2')
                ->options($groupOptions)
                ->default($contextRecord?->grup_2_id)
                ->disableOptionWhen(fn ($value): bool => isset($disabledGroupOptions[(int) $value]))
                ->searchable()
                ->preload()
                ->visible(fn (Get $get): bool => static::selectedTestQuota($get, $record) >= 2)
                ->dehydrated(fn (Get $get): bool => static::selectedTestQuota($get, $record) >= 2)
                ->required(fn (Get $get): bool => static::selectedTestQuota($get, $record) >= 2),
            Forms\Components\Select::make('grup_3_id')
                ->label('Grup Tes 3')
                ->options($groupOptions)
                ->default($contextRecord?->grup_3_id)
                ->disableOptionWhen(fn ($value): bool => isset($disabledGroupOptions[(int) $value]))
                ->searchable()
                ->preload()
                ->visible(fn (Get $get): bool => static::selectedTestQuota($get, $record) >= 3)
                ->dehydrated(fn (Get $get): bool => static::selectedTestQuota($get, $record) >= 3)
                ->required(fn (Get $get): bool => static::selectedTestQuota($get, $record) >= 3),
            Forms\Components\Select::make('grup_4_id')
                ->label('Grup Tes 4')
                ->options($groupOptions)
                ->default($contextRecord?->grup_4_id)
                ->disableOptionWhen(fn ($value): bool => isset($disabledGroupOptions[(int) $value]))
                ->searchable()
                ->preload()
                ->visible(fn (Get $get): bool => static::selectedTestQuota($get, $record) >= 4)
                ->dehydrated(fn (Get $get): bool => static::selectedTestQuota($get, $record) >= 4)
                ->required(fn (Get $get): bool => static::selectedTestQuota($get, $record) >= 4),
        ];
    }

    protected static function selectedTestQuota(Get $get, EptRegistration $record): int
    {
        return EptRegistration::normalizeTestQuota(
            filled($get('test_quota')) ? (int) $get('test_quota') : $record->requiredGroupCount(),
            $record->student_status,
        );
    }

    protected static function extractGroupAssignments(array $data, int $testQuota): array
    {
        return collect(range(1, $testQuota))
            ->map(fn (int $slot) => $data["grup_{$slot}_id"] ?? null)
            ->all();
    }

    protected static function ensureRequiredGroupAssignmentsFilled(array $groupAssignments): void
    {
        $missingSlots = collect($groupAssignments)
            ->filter(fn ($groupId) => ! filled($groupId))
            ->isNotEmpty();

        if (! $missingSlots) {
            return;
        }

        throw ValidationException::withMessages(static::groupAssignmentMessages(
            count($groupAssignments),
            'Semua grup tes sesuai kuota wajib dipilih.'
        ));
    }

    protected static function groupAssignmentMessages(int $testQuota, string $message): array
    {
        return collect(range(1, $testQuota))
            ->mapWithKeys(fn (int $slot) => ["grup_{$slot}_id" => $message])
            ->all();
    }

    /**
     * Bangun opsi grup: "Nama Grup (terisi/kuota)" + daftar grup yang sudah penuh.
     *
     * @return array{
     *     options: array<int, string>,
     *     disabled: array<int, bool>
     * }
     */
    protected static function buildGroupOptionMeta(
        ?EptRegistration $contextRecord = null,
        bool $includePastScheduledGroups = false,
    ): array
    {
        $currentGroupIds = array_values(array_filter([
            $contextRecord?->grup_1_id,
            $contextRecord?->grup_2_id,
            $contextRecord?->grup_3_id,
            $contextRecord?->grup_4_id,
        ]));

        $groups = EptGroup::query()
            ->select(['id', 'name', 'quota', 'jadwal'])
            ->when(! $includePastScheduledGroups, function (Builder $query) use ($currentGroupIds): void {
                $query->where(function (Builder $query) use ($currentGroupIds): void {
                    $query->whereNull('jadwal')
                        ->orWhere('jadwal', '>=', now());

                    if ($currentGroupIds !== []) {
                        $query->orWhereIn('id', $currentGroupIds);
                    }
                });
            })
            ->withCount([
                'registrationsAsGrup1',
                'registrationsAsGrup2',
                'registrationsAsGrup3',
                'registrationsAsGrup4',
            ])
            ->latest('id')
            ->get();

        $options = [];
        $disabled = [];

        foreach ($groups as $group) {
            $participantCount = (int) (
                ($group->registrations_as_grup1_count ?? 0)
                + ($group->registrations_as_grup2_count ?? 0)
                + ($group->registrations_as_grup3_count ?? 0)
                + ($group->registrations_as_grup4_count ?? 0)
            );

            // Saat edit record yang sudah ada, slot grup miliknya sendiri tidak boleh dianggap penuh.
            if (in_array($group->id, $currentGroupIds, true) && $participantCount > 0) {
                $participantCount--;
            }

            $quota = max(1, (int) ($group->quota ?? 0));
            $pastScheduleLabel = $group->jadwal?->isPast() ? ' - jadwal lewat' : '';
            $options[$group->id] = sprintf(
                '%s (%d/%d)%s',
                $group->name,
                $participantCount,
                $quota,
                $pastScheduleLabel,
            );

            if ($participantCount >= $quota) {
                $disabled[$group->id] = true;
            }
        }

        return [
            'options' => $options,
            'disabled' => $disabled,
        ];
    }

    /**
     * Validasi server-side agar kuota tetap aman walau ada race condition.
     */
    protected static function ensureQuotaAvailableForGroups(
        array $groupAssignments,
        ?EptRegistration $contextRecord = null,
        bool $allowPastScheduledGroups = false,
    ): void
    {
        $groupIds = array_values(array_unique(array_filter(
            array_map(
                static fn ($groupId) => filled($groupId) ? (int) $groupId : null,
                $groupAssignments,
            ),
            static fn ($groupId) => $groupId !== null,
        )));

        if ($groupIds === []) {
            return;
        }

        $groups = EptGroup::query()
            ->whereIn('id', $groupIds)
            ->get(['id', 'name', 'quota', 'jadwal']);
        $currentGroupIds = array_values(array_filter([
            $contextRecord?->grup_1_id,
            $contextRecord?->grup_2_id,
            $contextRecord?->grup_3_id,
            $contextRecord?->grup_4_id,
        ]));

        foreach ($groups as $group) {
            if (
                ! $allowPastScheduledGroups
                && $group->jadwal !== null
                && $group->jadwal->isPast()
                && ! in_array($group->id, $currentGroupIds, true)
            ) {
                $message = sprintf(
                    'Grup "%s" jadwal tesnya sudah lewat dan tidak bisa dipilih lagi.',
                    $group->name,
                );

                throw ValidationException::withMessages([
                    'grup_1_id' => $message,
                    'grup_2_id' => $message,
                    'grup_3_id' => $message,
                    'grup_4_id' => $message,
                ]);
            }

            $quota = max(1, (int) ($group->quota ?? 0));
            $participantCount = EptRegistration::query()
                ->where(function (Builder $query) use ($group): void {
                    $query->where('grup_1_id', $group->id)
                        ->orWhere('grup_2_id', $group->id)
                        ->orWhere('grup_3_id', $group->id)
                        ->orWhere('grup_4_id', $group->id);
                })
                ->when(
                    $contextRecord !== null,
                    fn (Builder $query) => $query->whereKeyNot($contextRecord->getKey())
                )
                ->count();

            if ($participantCount >= $quota) {
                $message = sprintf(
                    'Kuota grup "%s" sudah penuh (%d/%d). Pilih grup lain.',
                    $group->name,
                    $participantCount,
                    $quota,
                );

                throw ValidationException::withMessages([
                    'grup_1_id' => $message,
                    'grup_2_id' => $message,
                    'grup_3_id' => $message,
                    'grup_4_id' => $message,
                ]);
            }
        }
    }

    protected static function downloadPaymentProof(EptRegistration $record)
    {
        $proofPath = (string) ($record->bukti_pembayaran ?? '');
        $publicDisk = Storage::disk('public');

        if ($proofPath === '' || ! $publicDisk->exists($proofPath)) {
            Notification::make()
                ->danger()
                ->title('File tidak ditemukan')
                ->send();

            return null;
        }

        $extension = strtolower((string) pathinfo($proofPath, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = 'jpg';
        }

        $filename = sprintf(
            'bukti_pembayaran_%s_%s.%s',
            $record->user?->srn ?? $record->id,
            now()->format('Ymd_His'),
            $extension
        );

        return response()->download($publicDisk->path($proofPath), $filename);
    }

    protected static function basicListeningSummary(EptRegistration $record): string
    {
        $user = $record->user;
        if (! $user) {
            return 'Belum tersedia';
        }

        $prodyName = $user?->prody?->name ?? null;
        $year = (int) ($user?->year ?? 0);

        if (! LegacyBasicListeningScores::requiresLegacyScore($year, $prodyName)) {
            return 'Tidak diwajibkan';
        }

        return static::scoreText(LegacyBasicListeningScores::effectiveScoreForUser($user));
    }

    protected static function isPbiUser(EptRegistration $record): bool
    {
        $user = $record->user;

        return (int) ($user?->year ?? 0) > 0
            && (int) ($user?->year ?? 0) <= 2024
            && ($user?->prody?->name ?? null) === 'Pendidikan Bahasa Inggris';
    }

    protected static function isIslamicProgramUser(EptRegistration $record): bool
    {
        $user = $record->user;

        return (int) ($user?->year ?? 0) > 0
            && (int) ($user?->year ?? 0) <= 2024
            && in_array($user?->prody?->name ?? null, [
                'Komunikasi dan Penyiaran Islam',
                'Pendidikan Agama Islam',
                'Pendidikan Islam Anak Usia Dini',
            ], true);
    }

    protected static function scoreText(mixed $value): string
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            return 'Belum tersedia';
        }

        $score = (float) $value;

        return fmod($score, 1.0) === 0.0
            ? (string) (int) $score
            : number_format($score, 2, ',', '.');
    }
}
