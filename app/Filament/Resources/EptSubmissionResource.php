<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EptSubmissionResource\Pages;
use App\Models\EptSubmission;
use App\Models\EptSubmissionNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use App\Notifications\EptSubmissionStatusNotification;
use App\Support\EptSubmissionNotificationTracker;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Support\Verification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Js;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class EptSubmissionResource extends BaseResource
{
    protected static ?string $model = EptSubmission::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Layanan Lembaga Bahasa';

    public static ?string $slug = 'suratrekomendasi';
    protected static ?string $navigationLabel = 'Pengajuan Surat Rekomendasi';
    protected static ?string $modelLabel = 'Pengajuan Surat Rekomendasi';
    protected static ?string $pluralModelLabel = 'Pengajuan Surat Rekomendasi';
    protected static ?int $navigationSort = 3;

    protected static function suggestedSuratNomor(?EptSubmission $ignoreRecord = null): string
    {
        $year = now()->year;
        $pattern = '/^(\d{3})\/II\.3\.AU\/F\/KET\/LB_UMM\/' . preg_quote((string) $year, '/') . '$/';

        $maxSequence = EptSubmission::query()
            ->whereNotNull('surat_nomor')
            ->when(
                $ignoreRecord?->exists,
                fn ($query) => $query->whereKeyNot($ignoreRecord->getKey()),
            )
            ->pluck('surat_nomor')
            ->reduce(static function (int $carry, ?string $nomor) use ($pattern): int {
                if (! is_string($nomor) || ! preg_match($pattern, $nomor, $matches)) {
                    return $carry;
                }

                return max($carry, (int) $matches[1]);
            }, 0);

        $nextSequence = str_pad((string) ($maxSequence + 1), 3, '0', STR_PAD_LEFT);

        return "{$nextSequence}/II.3.AU/F/KET/LB_UMM/{$year}";
    }

    public static function form(Form $form): Form
    {
        // Admin memverifikasi via tabel/view, jadi form kosong
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['notificationTracking', 'user.prody']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nama disalin'),
                Tables\Columns\TextColumn::make('user.srn')
                    ->label('NPM')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('NPM disalin'),
                Tables\Columns\TextColumn::make('user.prody.name')
                    ->label('Prodi')
                    ->searchable()
                    ->limit(15)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.whatsapp')
                    ->label('Nomor WA')
                    ->searchable()
                    ->badge()
                    ->color('success')
                    ->toggleable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('surat_nomor')
                    ->label('Nomor Surat')
                    ->searchable()
                    ->limit(15)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->formatStateUsing(fn ($state) => $state?->diffForHumans() ?? '-')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->created_at?->format('d/m/Y H:i')),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('notification_status')
                    ->label('Status Notif')
                    ->badge()
                    ->state(fn (EptSubmission $record): string => static::overallNotificationStatus($record))
                    ->formatStateUsing(fn (string $state): string => EptSubmissionNotification::statusLabel($state))
                    ->color(fn (string $state): string => EptSubmissionNotification::statusColor($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('mail_notification_status')
                    ->label('Email')
                    ->badge()
                    ->state(fn (EptSubmission $record): string => static::notificationChannelStatus($record, 'mail'))
                    ->formatStateUsing(fn (string $state): string => EptSubmissionNotification::statusLabel($state))
                    ->color(fn (string $state): string => EptSubmissionNotification::statusColor($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('wa_notification_status')
                    ->label('WA')
                    ->badge()
                    ->state(fn (EptSubmission $record): string => static::notificationChannelStatus($record, 'whatsapp'))
                    ->formatStateUsing(fn (string $state): string => EptSubmissionNotification::statusLabel($state))
                    ->color(fn (string $state): string => EptSubmissionNotification::statusColor($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('notification_last_requested_at')
                    ->label('Terakhir Diproses')
                    ->since()
                    ->placeholder('Belum pernah')
                    ->state(fn (EptSubmission $record) => $record->notificationTracking?->last_requested_at)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('catatan_admin')
                    ->label('Catatan Staf')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($record) => filled($record?->catatan_admin)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('user.prody_id')
                    ->relationship('user.prody', 'name')
                    ->label('Prodi'),
                Tables\Filters\Filter::make('created_at')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(' ')
                    ->icon('heroicon-s-eye')
                    ->extraModalFooterActions(function (Tables\Actions\ViewAction $action): array {
                        $record = $action->getRecord();

                        if (
                            ! $record instanceof EptSubmission
                            || $record->status !== 'pending'
                            || ! auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi'])
                        ) {
                            return [];
                        }

                        $recordKey = Js::from((string) $record->getKey());

                        return [
                            $action->makeModalAction('approve_from_view')
                                ->label('Approve')
                                ->icon('heroicon-s-check-circle')
                                ->color('success')
                                ->action("replaceMountedTableAction('approve', {$recordKey})"),
                            $action->makeModalAction('reject_from_view')
                                ->label('Reject')
                                ->icon('heroicon-s-x-circle')
                                ->color('danger')
                                ->action("replaceMountedTableAction('reject', {$recordKey})"),
                        ];
                    }),

                Tables\Actions\ActionGroup::make([
                    Action::make('print')
                        ->label('Unduh PDF')
                        ->icon('heroicon-s-arrow-down-tray')
                        ->color('success')
                        ->visible(fn (EptSubmission $record) => $record->status === 'approved')
                        ->url(fn (EptSubmission $record) => route('ept-submissions.pdf', [$record, 'dl' => 1]))
                        ->openUrlInNewTab(),

                    Action::make('resendWa')
                        ->label('Kirim Ulang WA')
                        ->icon('heroicon-s-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Kirim Ulang WhatsApp')
                        ->modalDescription('Kirim ulang hanya kanal WhatsApp. Email dan notifikasi dashboard tidak akan dikirim ulang.')
                        ->visible(fn (EptSubmission $record): bool => static::canResendWhatsApp($record))
                        ->action(function (EptSubmission $record) {
                            $pemohon = $record->user;
                            if (! $pemohon) {
                                Notification::make()->title('User tidak ditemukan.')->danger()->send();
                                return;
                            }

                            if (! $pemohon->whatsapp || ! $pemohon->whatsapp_verified_at) {
                                Notification::make()
                                    ->title('Nomor WA belum terverifikasi')
                                    ->body('Tidak bisa kirim ulang. Pastikan nomor sudah diverifikasi atau gunakan email.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            if (! static::queueWhatsAppResend($record)) {
                                Notification::make()
                                    ->title('WA gagal masuk antrean')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('WA diproses ulang')
                                ->body('Pesan WhatsApp masuk antrean kirim ulang.')
                                ->success()
                                ->send();
                        }),

                    Action::make('editSuratNomor')
                        ->label('Edit Nomor Surat')
                        ->icon('heroicon-s-pencil')
                        ->color('info')
                        ->visible(fn (EptSubmission $record): bool =>
                            $record->status === 'approved' && auth()->user()?->hasAnyRole(['Admin','Staf Administrasi'])
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Edit Nomor Surat')
                        ->modalDescription('Perubahan nomor surat akan dicatat dalam riwayat dan tidak dapat dibatalkan.')
                        ->form(function (EptSubmission $record) {
                            return [
                                Forms\Components\Placeholder::make('current_nomor')
                                    ->label('Nomor Surat Saat Ini')
                                    ->content($record->surat_nomor ?? '-'),
                                Forms\Components\TextInput::make('new_surat_nomor')
                                    ->label('Nomor Surat Baru')
                                    ->default($record->surat_nomor)
                                    ->required()
                                    ->maxLength(100)
                                    ->rule('regex:/^\d{3}\/II\.3\.AU\/F\/KET\/LB_UMM\/\d{4}$/')
                                    ->helperText('Format: 001/II.3.AU/F/KET/LB_UMM/2025')
                                    ->rule(fn () => Rule::unique('ept_submissions', 'surat_nomor')
                                        ->ignore($record->id)
                                        ->where(fn ($q) => $q->whereNotNull('surat_nomor')))
                                    ->validationMessages([
                                        'required' => 'Nomor surat baru wajib diisi.',
                                        'regex'    => 'Format nomor surat tidak sesuai',
                                        'unique'   => 'Nomor surat sudah digunakan oleh submission lain.',
                                    ]),
                                Forms\Components\Textarea::make('alasan_perubahan')
                                    ->label('Alasan Perubahan')
                                    ->required()
                                    ->rows(3)
                                    ->helperText('Jelaskan mengapa nomor surat perlu diubah'),
                            ];
                        })
                        ->action(function (EptSubmission $record, array $data) {
                            if ($record->status !== 'approved') {
                                Notification::make()->title('Status harus approved untuk edit nomor surat.')->danger()->send();
                                return;
                            }

                            $oldNomor = $record->surat_nomor;
                            $newNomor = $data['new_surat_nomor'];

                            if ($oldNomor === $newNomor) {
                                Notification::make()->title('Nomor surat baru harus berbeda dari yang lama.')->warning()->send();
                                return;
                            }

                            DB::transaction(function () use ($record, $newNomor, $oldNomor, $data) {
                                $locked = EptSubmission::query()->lockForUpdate()->find($record->id);
                                
                                // Add to history sebelum update
                                $locked->addSuratNomorHistory($oldNomor, $newNomor, $data['alasan_perubahan']);
                                
                                // Update surat_nomor field
                                $locked->update([
                                    'surat_nomor' => $newNomor,
                                ]);
                            });

                            Notification::make()
                                ->title('✓ Nomor surat berhasil diperbarui')
                                ->body("Dari: {$oldNomor}\nMenjadi: {$newNomor}")
                                ->success()
                                ->send();
                        }),
                ])
                ->label('Dokumen')
                ->icon('heroicon-s-document'),

                Tables\Actions\ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-s-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (EptSubmission $record): bool =>
                            $record->status === 'pending' && auth()->user()?->hasAnyRole(['Admin','Staf Administrasi'])
                        )
                        ->form(function (EptSubmission $record) {
                            $suggest = static::suggestedSuratNomor($record);

                            return [
                                Forms\Components\Placeholder::make('warning')
                                    ->label('')
                                    ->content(new \Illuminate\Support\HtmlString(
                                        '<div style="background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; color: #92400e; font-weight: 600;">
                                            ⚠️ Pastikan Nomor Surat Sesuai dengan urutan di buku
                                        </div>'
                                    )),
                                Forms\Components\TextInput::make('surat_nomor')
                                    ->label('Nomor Surat')
                                    ->default($record->surat_nomor ?: $suggest)
                                    ->required()
                                    ->maxLength(100)
                                    ->rule('regex:/^\d{3}\/II\.3\.AU\/F\/KET\/LB_UMM\/\d{4}$/')
                                    ->helperText('Format: 001/II.3.AU/F/KET/LB_UMM/2025')
                                    ->rule(fn () => Rule::unique('ept_submissions', 'surat_nomor')
                                        ->ignore($record->id)
                                        ->where(fn ($q) => $q->whereNotNull('surat_nomor')))
                                    ->validationMessages([
                                        'required' => 'Nomor surat wajib diisi.',
                                        'regex'    => 'Format nomor surat tidak sesuai',
                                        'unique'   => 'Nomor surat sudah digunakan.',
                                    ]),
                                Forms\Components\Textarea::make('catatan_admin')
                                    ->label('Catatan (Opsional)')
                                    ->rows(4),
                            ];
                        })
                        ->action(function (EptSubmission $record, array $data) {
                            if ($record->status !== 'pending') {
                                Notification::make()->title('Status sudah tidak pending.')->danger()->send();
                                return;
                            }

                            DB::transaction(function () use ($record, $data) {
                                $locked = EptSubmission::query()->lockForUpdate()->find($record->id);

                                // Generate kode verifikasi jika kosong (pakai App\Support\Verification)
                                $code = $locked->verification_code ?: Verification::generateCode();

                                $locked->update([
                                    'status'            => 'approved',
                                    'catatan_admin'     => $data['catatan_admin'] ?? $locked->catatan_admin,
                                    'approved_at'       => now(),
                                    'approved_by'       => auth()->id(),
                                    'verification_code' => $code,
                                    'verification_url'  => route('verification.show', ['code' => $code]),
                                    'surat_nomor'       => $data['surat_nomor'],
                                ]);
                            });

                            // Kirim email setelah commit
                            $fresh   = $record->fresh();
                            $pemohon = $fresh->user; // relasi pemohon

                            if ($pemohon) {
                                $verificationUrl = $fresh->verification_url;
                                // Sertakan link unduh PDF jika memang route-nya ada & aksesnya sesuai
                                $pdfUrl = route('ept-submissions.pdf', $fresh);
                                $notification = new EptSubmissionStatusNotification(
                                    'approved',
                                    $verificationUrl,
                                    $pdfUrl,
                                    $data['catatan_admin'] ?? null,
                                    $fresh->getKey(),
                                );

                                $channels = $notification->via($pemohon);
                                $tracking = EptSubmissionNotificationTracker::prime(
                                    $fresh,
                                    static::expectsWhatsApp($fresh),
                                    $channels,
                                    (string) $notification->contentSignature,
                                );

                                try {
                                    $pemohon->notify($notification);
                                } catch (\Throwable $e) {
                                    EptSubmissionNotificationTracker::markDispatchFailure(
                                        $tracking,
                                        static::expectsWhatsApp($fresh),
                                        $e->getMessage(),
                                    );

                                    throw $e;
                                }
                            }

                            Notification::make()->title('Pengajuan disetujui. Notifikasi diproses.')->success()->send();
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-s-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (EptSubmission $record): bool =>
                            $record->status === 'pending' && auth()->user()?->hasAnyRole(['Admin','Staf Administrasi'])
                        )
                        ->form([
                            Forms\Components\Textarea::make('catatan_admin')
                                ->label('Alasan Penolakan')
                                ->required()
                                ->rows(4),
                        ])
                        ->action(function (EptSubmission $record, array $data) {
                            if ($record->status !== 'pending') {
                                Notification::make()->title('Status sudah tidak pending.')->danger()->send();
                                return;
                            }

                            DB::transaction(function () use ($record, $data) {
                                $record->update([
                                    'status'        => 'rejected',
                                    'catatan_admin' => $data['catatan_admin'],
                                    'rejected_at'   => now(),
                                    'rejected_by'   => auth()->id(),
                                ]);
                            });

                            // kirim email setelah commit
                            $fresh = $record->fresh('user'); // pastikan relasi diload
                            $pemohon = $fresh?->user;

                            if (! $pemohon) {
                                Log::warning('EPT rejected: user not found for record', ['id' => $record->id]);
                                Notification::make()->title('Ditolak, tapi user tidak ditemukan.')->danger()->send();
                                return;
                            }

                            try {
                                // rejected tidak perlu link
                                $notification = new EptSubmissionStatusNotification(
                                    'rejected',
                                    null,
                                    null,
                                    $data['catatan_admin'],
                                    $fresh->getKey(),
                                );
                                $channels = $notification->via($pemohon);
                                $tracking = EptSubmissionNotificationTracker::prime(
                                    $fresh,
                                    static::expectsWhatsApp($fresh),
                                    $channels,
                                    (string) $notification->contentSignature,
                                );

                                $pemohon->notify($notification);
                                Notification::make()->title('Pengajuan ditolak. Notifikasi diproses.')->success()->send();
                            } catch (\Throwable $e) {
                                if (isset($tracking)) {
                                    EptSubmissionNotificationTracker::markDispatchFailure(
                                        $tracking,
                                        static::expectsWhatsApp($fresh),
                                        $e->getMessage(),
                                    );
                                }

                                Log::error('Gagal kirim email rejected', [
                                    'e' => $e->getMessage(),
                                    'record_id' => $record->id,
                                    'user_id' => $pemohon->id,
                                ]);
                                Notification::make()->title('Ditolak, tapi gagal mengirim email (cek logs).')->danger()->send();
                            }
                        }),
                ])
                ->label('Approval')
                ->icon('heroicon-s-check-badge'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('resend_failed_whatsapp')
                        ->label('Kirim Ulang WA Gagal')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Kirim Ulang WA Terpilih')
                        ->modalDescription('Hanya data terpilih yang status WA-nya gagal/belum/dilewati dan nomor WA-nya valid yang akan diproses.')
                        ->visible(fn (): bool => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $queued = 0;
                            $skipped = 0;
                            $failed = 0;

                            $records->loadMissing(['user', 'notificationTracking']);

                            foreach ($records as $record) {
                                if (! $record instanceof EptSubmission || ! static::canResendWhatsApp($record)) {
                                    $skipped++;
                                    continue;
                                }

                                if (static::queueWhatsAppResend($record)) {
                                    $queued++;
                                } else {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('Kirim ulang WA diproses')
                                ->body("Masuk antrean: {$queued}. Dilewati: {$skipped}. Gagal antrean: {$failed}.")
                                ->color($failed > 0 ? 'warning' : 'success')
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['Admin']))
                ]),
            ])
            ->emptyStateHeading('Belum ada pengajuan')
            ->emptyStateDescription('Pengajuan yang dikirim oleh pendaftar akan muncul di sini.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Components\Section::make('Informasi Pendaftar')
                ->schema([
                    Components\TextEntry::make('user.name')
                        ->label('Nama Pendaftar')
                        ->copyable()
                        ->copyMessage('Nama disalin'),
                    Components\TextEntry::make('user.srn')
                        ->label('NPM')
                        ->copyable()
                        ->copyMessage('NPM disalin'),
                    Components\TextEntry::make('user.prody.name')->label('Prodi'),
                    Components\TextEntry::make('user.whatsapp')
                        ->label('Nomor WA')
                        ->copyable()
                        ->copyMessage('Nomor WA disalin')
                        ->badge()
                        ->color('success'),
                    Components\TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'pending' => 'Menunggu',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            default => ucfirst($state),
                        })
                        ->color(fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'gray',
                        }),
                    Components\TextEntry::make('notification_status')
                        ->label('Status Notif')
                        ->badge()
                        ->state(fn (EptSubmission $record): string => static::overallNotificationStatus($record))
                        ->formatStateUsing(fn (string $state): string => EptSubmissionNotification::statusLabel($state))
                        ->color(fn (string $state): string => EptSubmissionNotification::statusColor($state)),
                    Components\TextEntry::make('notification_last_requested_at')
                        ->label('Terakhir Diproses')
                        ->since()
                        ->state(fn (EptSubmission $record) => $record->notificationTracking?->last_requested_at)
                        ->placeholder('Belum pernah'),
                    Components\TextEntry::make('catatan_admin')
                        ->label('Catatan dari Staf')
                        ->visible(fn ($record) => filled($record?->catatan_admin)),
                ])->columns(3),

            Components\Section::make('Status Kanal Notifikasi')
                ->schema([
                    Components\TextEntry::make('mail_notification_status')
                        ->label('Email')
                        ->badge()
                        ->state(fn (EptSubmission $record): string => static::notificationChannelStatus($record, 'mail'))
                        ->formatStateUsing(fn (string $state): string => EptSubmissionNotification::statusLabel($state))
                        ->color(fn (string $state): string => EptSubmissionNotification::statusColor($state)),
                    Components\TextEntry::make('wa_notification_status')
                        ->label('WA')
                        ->badge()
                        ->state(fn (EptSubmission $record): string => static::notificationChannelStatus($record, 'whatsapp'))
                        ->formatStateUsing(fn (string $state): string => EptSubmissionNotification::statusLabel($state))
                        ->color(fn (string $state): string => EptSubmissionNotification::statusColor($state)),
                ])
                ->columns(2),

            Components\Section::make('Data Tes 1')
                ->schema([
                    Components\TextEntry::make('nilai_tes_1')->label('Nilai'),
                    Components\TextEntry::make('tanggal_tes_1')->label('Tanggal')->date(),
                    Components\ImageEntry::make('foto_path_1')
                        ->label('Bukti Foto')
                        ->disk('public')
                        ->height(120)
                        ->square(false)
                        ->url(fn ($state) => \Storage::disk('public')->url($state)) // link ke file asli
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => filled($record?->foto_path_1)),
                ])->columns(3),

            Components\Section::make('Data Tes 2')
                ->schema([
                    Components\TextEntry::make('nilai_tes_2')->label('Nilai'),
                    Components\TextEntry::make('tanggal_tes_2')->label('Tanggal')->date(),
                    Components\ImageEntry::make('foto_path_2')
                        ->label('Bukti Foto')
                        ->disk('public')
                        ->height(120)
                        ->square(false)
                        ->url(fn ($state) => \Storage::disk('public')->url($state))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => filled($record?->foto_path_2)),
                ])
                ->columns(3)
                ->visible(fn ($record) =>
                    filled($record?->nilai_tes_2) ||
                    filled($record?->foto_path_2) ||
                    filled($record?->tanggal_tes_2)
                ),

            Components\Section::make('Data Tes 3')
                ->schema([
                    Components\TextEntry::make('nilai_tes_3')->label('Nilai'),
                    Components\TextEntry::make('tanggal_tes_3')->label('Tanggal')->date(),
                    Components\ImageEntry::make('foto_path_3')
                        ->label('Bukti Foto')
                        ->disk('public')
                        ->height(120)
                        ->square(false)
                        ->url(fn ($state) => \Storage::disk('public')->url($state))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => filled($record?->foto_path_3)),
                ])
                ->columns(3)
                ->visible(fn ($record) =>
                    filled($record?->nilai_tes_3) ||
                    filled($record?->foto_path_3) ||
                    filled($record?->tanggal_tes_3)
                ),

            Components\Section::make('Riwayat Perubahan Nomor Surat')
                ->schema([
                    Components\ViewEntry::make('surat_nomor_history')
                        ->label('')
                        ->view('filament.infolists.entries.surat-nomor-history'),
                ])
                ->visible(fn ($record) => filled($record?->surat_nomor_history) && !empty($record?->surat_nomor_history))
                ->collapsed(),

        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $u = auth()->user();
        if (! $u || ! $u->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga'])) {
            return null;
        }

        $pending = \App\Models\EptSubmission::where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $u = auth()->user();
        if (! $u || ! $u->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga'])) {
            return null;
        }

        $pending = \App\Models\EptSubmission::where('status', 'pending')->count();
        $total   = \App\Models\EptSubmission::count();
        $today   = \App\Models\EptSubmission::whereDate('created_at', now()->toDateString())->count();

        return "Menunggu: {$pending} • Total: {$total} • Masuk hari ini: {$today}";
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEptSubmissions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // admin tidak membuat data di sini
    }

    protected static function expectsWhatsApp(EptSubmission $record): bool
    {
        return in_array($record->status, ['approved', 'rejected'], true)
            && filled($record->user?->whatsapp)
            && filled($record->user?->whatsapp_verified_at);
    }

    protected static function overallNotificationStatus(EptSubmission $record): string
    {
        return $record->notificationTracking?->overallStatus(static::expectsWhatsApp($record))
            ?? EptSubmissionNotification::STATUS_NOT_SENT;
    }

    protected static function notificationChannelStatus(EptSubmission $record, string $channel): string
    {
        return $record->notificationTracking?->channelStatus($channel, static::expectsWhatsApp($record))
            ?? ($channel === 'whatsapp' && ! static::expectsWhatsApp($record)
                ? EptSubmissionNotification::STATUS_SKIPPED
                : EptSubmissionNotification::STATUS_NOT_SENT);
    }

    protected static function canResendWhatsApp(EptSubmission $record): bool
    {
        if (! auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi'])) {
            return false;
        }

        if (! in_array($record->status, ['approved', 'rejected'], true)) {
            return false;
        }

        if (! static::expectsWhatsApp($record)) {
            return false;
        }

        return in_array(static::notificationChannelStatus($record, 'whatsapp'), [
            EptSubmissionNotification::STATUS_FAILED,
            EptSubmissionNotification::STATUS_NOT_SENT,
            EptSubmissionNotification::STATUS_SKIPPED,
        ], true);
    }

    protected static function makeStatusNotification(EptSubmission $record): EptSubmissionStatusNotification
    {
        if ($record->status === 'approved') {
            return new EptSubmissionStatusNotification(
                status: 'approved',
                verificationUrl: $record->verification_url,
                pdfUrl: route('ept-submissions.pdf', $record),
                adminNote: $record->catatan_admin,
                submissionId: $record->getKey(),
            );
        }

        return new EptSubmissionStatusNotification(
            status: 'rejected',
            adminNote: $record->catatan_admin,
            submissionId: $record->getKey(),
        );
    }

    protected static function queueWhatsAppResend(EptSubmission $record): bool
    {
        if (! static::expectsWhatsApp($record)) {
            return false;
        }

        $pemohon = $record->user;

        if (! $pemohon) {
            return false;
        }

        $notification = static::makeStatusNotification($record);
        $tracking = EptSubmissionNotificationTracker::prime(
            $record,
            true,
            ['whatsapp'],
            (string) $notification->contentSignature,
        );

        try {
            $queued = $notification->toWhatsApp($pemohon);

            if (! $queued) {
                EptSubmissionNotificationTracker::markDispatchFailure(
                    $tracking,
                    true,
                    'Pesan WhatsApp tidak berhasil masuk antrean.',
                );

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            EptSubmissionNotificationTracker::markDispatchFailure(
                $tracking,
                true,
                $e->getMessage(),
            );

            Log::error('Gagal antre ulang WA surat rekomendasi', [
                'e' => $e->getMessage(),
                'record_id' => $record->getKey(),
                'user_id' => $pemohon->getKey(),
            ]);

            return false;
        }
    }
}
