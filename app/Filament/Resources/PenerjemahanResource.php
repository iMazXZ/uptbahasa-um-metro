<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenerjemahanResource\Pages;
use App\Models\Penerjemahan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Support\ImageTransformer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Exports\PenerjemahanTriwulanExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;

class PenerjemahanResource extends BaseResource
{
    protected static ?string $model = Penerjemahan::class;

    protected static ?string $navigationIcon  = 'heroicon-s-language';
    protected static ?string $navigationLabel = 'Penerjemahan Dokumen Abstrak';
    protected static ?string $navigationGroup = 'Layanan Lembaga Bahasa';

    public static ?string $slug  = 'penerjemahan';
    public static ?string $label = 'Penerjemahan Dokumen Abstrak';

    public static function getTitle(): string
    {
        return 'Penerjemahan Dokumen Abstrak';
    }

    /* -----------------------------------------------------------
    |  FORM
    |----------------------------------------------------------- */
    public static function form(Form $form): Form
    {
        // Helper lokal untuk kompres → WebP
        $compress = function (TemporaryUploadedFile $file, string $subdir = 'general', int $quality = 82, int $maxWidth = 2000) {
            $tmp = $file->store('tmp');
            $out = ImageTransformer::toWebp(
                inputPath: storage_path('app/' . $tmp),
                targetDisk: 'public',
                targetDir: "penerjemahan/images/{$subdir}",
                quality: $quality,
                maxWidth: $maxWidth
            );
            Storage::delete($tmp);
            return $out['path'];
        };

        return $form->schema([

            // Identitas pemohon (readonly placeholders)
            Forms\Components\Placeholder::make('pemohon_nama')
                ->label('Keterangan Pemohon')
                ->content(function ($record) {
                    $u = $record?->users ?: auth()->user();
                    $name = $u?->name ?? '-';
                    $prodi = $u?->prody?->name ?? '-';
                    return "{$name} — {$prodi}";
                }),

            Forms\Components\Placeholder::make('pemohon_srn')
                ->label('NPM')
                ->content(function ($record) {
                    $u = $record?->users ?: auth()->user();
                    return $u?->srn ?? '-';
                }),

            // Relasi pemohon & status default
            Forms\Components\Hidden::make('user_id')->default(fn () => auth()->id()),
            Forms\Components\Hidden::make('status')->default('Menunggu'),

            FileUpload::make('bukti_pembayaran')
                ->label('Upload Bukti Pembayaran')
                ->image()
                ->disk('public')
                ->visibility('public')
                ->acceptedFileTypes(['image/*'])
                ->maxSize(8192)
                ->downloadable()
                ->helperText('PNG/JPG maksimal 8MB.')
                ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, $get) {
                    $nama = Str::slug(auth()->user()?->name ?? 'pemohon', '_');
                    $base = "proof_{$nama}.webp";
                    return ImageTransformer::toWebpFromUploaded(
                        uploaded: $file,
                        targetDisk: 'public',
                        targetDir: 'penerjemahan/images/payments',
                        quality: 85,
                        maxWidth: 1600,
                        maxHeight: null,
                        basename: $base
                    )['path'];
                }),

            // === TEKS SUMBER === (terlihat semua role)
            Forms\Components\Section::make('Abstrak')
                ->schema([
                    Forms\Components\RichEditor::make('source_text')
                        ->label('Masukan Abstrak Yang Ingin Diterjemahkan')
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'paragraph',
                            'undo',
                            'redo',
                        ])
                        ->columnSpanFull()
                        ->required()
                        ->disabled(function ($record) {
                            $u = auth()->user();
                            if ($u?->hasRole('pendaftar')) {
                                return filled($record); // pendaftar hanya isi saat create
                            }
                            return $u?->hasAnyRole(['Admin', 'Penerjemah', 'Staf Administrasi', 'Kepala Lembaga']);
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set) {
                            $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $state)));
                            $set('source_word_count', $plain === '' ? 0 : str_word_count(
                                $plain,
                                0,
                                'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'
                            ));
                        })
                        ->hint(function ($record) {
                            $u = auth()->user();
                            if ($u?->hasRole('pendaftar') && filled($record)) {
                                return 'Teks Abstrak terkunci setelah diajukan.';
                            }
                            if ($u?->hasAnyRole(['Admin', 'Penerjemah'])) {
                                return 'read-only';
                            }
                            return null;
                        }),

                    Forms\Components\Placeholder::make('source_word_count')
                        ->label('Jumlah Kata Yang Dimasukan')
                        ->content(fn (Get $get) => $get('source_word_count') ?? 0),
                ])->collapsible(),

            // === HASIL TERJEMAHAN === (disembunyikan dari pendaftar)
            Forms\Components\Section::make('Hasil Terjemahan')
                ->schema([
                    Forms\Components\RichEditor::make('translated_text')
                        ->label('Teks Terjemahan')
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'paragraph',
                            'undo',
                            'redo',
                        ])
                        ->columnSpanFull()
                        ->reactive()
                        ->disabled(function () {
                            $u = auth()->user();
                            return ! $u?->hasAnyRole(['Admin', 'Penerjemah']);
                        })
                        ->afterStateUpdated(function ($state, $set) {
                            $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $state)));
                            $count = $plain === '' ? 0 : str_word_count(
                                $plain,
                                0,
                                'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'
                            );
                            $set('translated_word_count', $count);
                            if ($count > 0) {
                                $set('completion_date', now());
                            }
                        })
                        ->hint(function () {
                            $u = auth()->user();
                            return $u?->hasAnyRole(['Admin', 'Penerjemah'])
                                ? 'Isi/ubah hasil terjemahan di sini.'
                                : null;
                        }),

                    Forms\Components\Placeholder::make('translated_word_count')
                        ->label('Jumlah Kata (Terjemahan)')
                        ->content(fn (Get $get) => $get('translated_word_count') ?? 0),
                ])
                ->collapsible()
                ->visible(fn () => ! auth()->user()?->hasRole('pendaftar')),

            // Tanggal
            Forms\Components\DateTimePicker::make('submission_date')
                ->label('Tanggal Pengajuan')
                ->default(now())
                ->disabled()
                ->dehydrated(),

            Forms\Components\DateTimePicker::make('completion_date')
                ->label('Tanggal Selesai')
                ->disabled()
                ->dehydrated(),

            // Info Status & Penerjemah
            Forms\Components\Placeholder::make('status_badge')
                ->label('Status')
                ->content(fn (Get $get) => $get('status') ?? '-'),

            Forms\Components\Placeholder::make('rejection_reason_info')
                ->label('Alasan Penolakan')
                ->content(fn ($record) => $record?->rejection_reason ?: '-')
                ->visible(fn ($record): bool => str_starts_with((string) $record?->status, 'Ditolak')),

            Forms\Components\Placeholder::make('translator_name')
                ->label('Nama Penerjemah')
                ->content(function (Get $get, $record) {
                    if ($get('translator_id')) {
                        if ($record?->translator) return $record->translator->name;
                        $tr = User::find($get('translator_id'));
                        return $tr?->name ?? '-';
                    }
                    return '-';
                })
                ->visible(fn (Get $get) => filled($get('translator_id'))),

            Forms\Components\Select::make('translator_id')
                ->label('Pilih Penerjemah')
                ->options(fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'Penerjemah'))->pluck('name', 'id'))
                ->searchable()
                ->placeholder('Pilih penerjemah...')
                ->visible(fn () => auth()->user()?->hasRole('Admin')),
        ])->columns(2);
    }

    /* -----------------------------------------------------------
    |  TABLE
    |----------------------------------------------------------- */
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('users.name')
                    ->label('Nama Pemohon')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nama disalin')
                    ->toggleable()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Penerjemah', 'Kepala Lembaga'])),

                Tables\Columns\TextColumn::make('users.srn')
                    ->label('NPM')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('NPM disalin')
                    ->toggleable()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Penerjemah', 'Kepala Lembaga'])),

                Tables\Columns\TextColumn::make('source_word_count')
                    ->label('Jumlah Kata')
                    ->numeric()
                    ->alignCenter()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Penerjemah', 'Kepala Lembaga'])),

                Tables\Columns\TextColumn::make('bukti_pembayaran')
                    ->label('Bukti')
                    ->formatStateUsing(fn ($state) => $state ? 'Lihat' : '-')
                    ->url(fn ($record) => $record->bukti_pembayaran ? Storage::url($record->bukti_pembayaran) : null)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->placeholder('-')
                    ->toggleable()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga'])),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => fn (string $state) => $state === 'Menunggu',
                        'info'    => fn (string $state) => in_array($state, ['Diproses', 'Disetujui'], true),
                        'success' => fn (string $state) => $state === 'Selesai',
                        'danger'  => fn (string $state) => str_contains($state, 'Tidak Valid'),
                    ])
                    ->icons([
                        'heroicon-s-clock'        => fn (string $state) => $state === 'Menunggu',
                        'heroicon-s-cog-6-tooth'  => fn (string $state) => $state === 'Diproses',
                        'heroicon-s-check'        => fn (string $state) => $state === 'Disetujui',
                        'heroicon-s-check-circle' => fn (string $state) => $state === 'Selesai',
                        'heroicon-s-x-circle'     => fn (string $state) => str_contains($state, 'Tidak Valid'),
                    ])
                    ->iconPosition('before')
                    ->formatStateUsing(function (string $state) {
                        return str_contains($state, 'Tidak Valid')
                            ? str_replace(['Ditolak - ', ' Tidak Valid'], ['Ditolak: ', ' Invalid'], $state)
                            : $state;
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('Alasan Ditolak')
                    ->wrap()
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('submission_date')
                    ->label('Pengajuan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('translator.name')
                    ->label('Penerjemah')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Penerjemah', 'Kepala Lembaga'])),

                Tables\Columns\TextColumn::make('completion_date')
                    ->label('Selesai')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Menunggu' => 'Menunggu',
                        'Disetujui' => 'Disetujui',
                        'Diproses'  => 'Diproses',
                        'Selesai'   => 'Selesai',
                        'Ditolak - Pembayaran Tidak Valid' => 'Ditolak - Pembayaran',
                        'Ditolak - Dokumen Tidak Valid'    => 'Ditolak - Dokumen',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Dari'),
                        Forms\Components\DatePicker::make('created_until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['created_until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga']))
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Dari tanggal')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Sampai tanggal')
                            ->required(),
                        Forms\Components\TextInput::make('period_label')
                            ->label('Label Triwulan')
                            ->placeholder('TRIWULAN 1 GANJIL 2025-2026')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('title_line')
                            ->label('Judul Rekap')
                            ->default('REKAPITULASI PENERJEMAHAN ABSTRAK')
                            ->maxLength(150),
                        Forms\Components\TextInput::make('keterangan')
                            ->label('Keterangan Kolom')
                            ->default('Abstrak')
                            ->maxLength(50),
                    ])
                    ->action(function (array $data) {
                        $start = Carbon::parse($data['start_date'])->startOfDay();
                        $end   = Carbon::parse($data['end_date'])->endOfDay();

                        if ($start->gt($end)) {
                            Notification::make()->title('Rentang tanggal tidak valid')->danger()->send();
                            return;
                        }

                        $title   = $data['title_line'] ?: 'REKAPITULASI PENERJEMAHAN ABSTRAK';
                        $period  = $data['period_label'] ?: self::formatPeriodLabel($start, $end);
                        $ket     = $data['keterangan'] ?: 'Abstrak';

                        $rows = Penerjemahan::query()
                            ->with('users')
                            ->whereBetween('submission_date', [$start, $end])
                            ->orderBy('submission_date')
                            ->get()
                            ->sortBy(fn ($row) => $row->users?->name ?? '', SORT_NATURAL | SORT_FLAG_CASE)
                            ->values();

                        return Excel::download(
                            new PenerjemahanTriwulanExport($rows, $title, $period, $ket),
                            'Rekap_Penerjemahan.xlsx'
                        );
                    }),
            ])
            ->actions([
                // ===== Unduh PDF (Admin/Staf/Kepala/Penerjemah melihat; pendaftar tidak di sini) =====
                Tables\Actions\Action::make('download_pdf')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(function (Penerjemahan $record) {
                        $status = strtolower((string) $record->status);
                        $okStatus = in_array($status, ['selesai', 'disetujui', 'completed', 'approved'], true);
                        $hasOutput = filled($record->translated_text) || filled($record->final_file_path);
                        return $okStatus && $hasOutput;
                    })
                    ->url(fn (Penerjemahan $record) => route('penerjemahan.pdf', [$record, 'dl' => 1]))
                    ->openUrlInNewTab(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('approve_pembayaran')
                        ->label('Approve')
                        ->icon('heroicon-o-check')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Pembayaran')
                        ->modalDescription('Yakin menyetujui pembayaran ini?')
                        ->visible(fn ($record) =>
                            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']) &&
                            !in_array($record->status, ['Disetujui', 'Diproses', 'Selesai'], true)
                        )
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'Disetujui',
                                'rejection_reason' => null,
                            ]);
                            Notification::make()->title("Pembayaran disetujui untuk {$record->users?->name}")->success()->send();
                        }),

                    Tables\Actions\Action::make('pilih_penerjemah')
                        ->label('Pilih Penerjemah')
                        ->icon('heroicon-o-user-plus')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('translator_id')
                                ->label('Pilih Penerjemah')
                                ->options(fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'Penerjemah'))->pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                        ])
                        ->visible(fn ($record) => auth()->user()?->hasRole('Admin') && $record->status === 'Disetujui')
                        ->action(function ($record, array $data) {
                            $translator = User::find($data['translator_id']);
                            
                            $record->update([
                                'status'        => 'Diproses',
                                'translator_id' => $data['translator_id'],
                            ]);
                            
                            // Notifikasi ke pemohon
                            $record->users?->notify(new \App\Notifications\PenerjemahanStatusNotification('Diproses'));
                            
                            // Notifikasi ke penerjemah
                            if ($translator) {
                                $translator->notify(new \App\Notifications\TranslatorAssignedNotification(
                                    pemohonName: $record->users?->name ?? 'Pemohon',
                                    wordCount: $record->source_word_count ?? 0,
                                    dashboardUrl: route('dashboard.penerjemah')
                                ));
                            }
                            
                            Notification::make()->title("Penerjemahan diproses. Notifikasi pemohon dan penerjemah diproses.")->success()->send();
                        }),

                    Tables\Actions\Action::make('tolak_pembayaran')
                        ->label('Reject - Pembayaran')
                        ->icon('heroicon-m-credit-card')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Pengajuan (Pembayaran Tidak Valid)')
                        ->modalDescription('Yakin menolak karena pembayaran tidak valid? Bukti pembayaran akan dihapus dan pemohon wajib upload ulang.')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->required()
                                ->rows(4)
                                ->maxLength(1000),
                        ])
                        ->visible(fn ($record) =>
                            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']) &&
                            !in_array($record->status, ['Disetujui', 'Diproses', 'Selesai'], true)
                        )
                        ->action(function ($record, array $data) {
                            // Hapus file bukti pembayaran dari storage
                            if ($record->bukti_pembayaran && Storage::disk('public')->exists($record->bukti_pembayaran)) {
                                Storage::disk('public')->delete($record->bukti_pembayaran);
                            }
                            
                            $record->update([
                                'status'            => 'Ditolak - Pembayaran Tidak Valid',
                                'rejection_reason'  => $data['rejection_reason'],
                                'translator_id'     => null,
                                'bukti_pembayaran'  => null, // Clear the path in database
                            ]);
                            $record->users?->notify(new \App\Notifications\PenerjemahanStatusNotification(
                                status: 'Ditolak - Pembayaran Tidak Valid',
                                rejectionReason: $data['rejection_reason'],
                            ));
                            Notification::make()->title('Ditolak, bukti pembayaran dihapus, dan notifikasi diproses.')->danger()->send();
                        }),

                    Tables\Actions\Action::make('tolak_dokumen')
                        ->label('Reject - Dokumen')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Pengajuan (Dokumen Tidak Valid)')
                        ->modalDescription('Yakin menolak karena dokumen tidak valid?')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->required()
                                ->rows(4)
                                ->maxLength(1000),
                        ])
                        ->visible(fn ($record) =>
                            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']) &&
                            !in_array($record->status, ['Diproses', 'Selesai'], true)
                        )
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status'           => 'Ditolak - Dokumen Tidak Valid',
                                'rejection_reason' => $data['rejection_reason'],
                                'translator_id'    => null,
                            ]);
                            $record->users?->notify(new \App\Notifications\PenerjemahanStatusNotification(
                                status: 'Ditolak - Dokumen Tidak Valid',
                                rejectionReason: $data['rejection_reason'],
                            ));
                            Notification::make()->title('Ditolak dan notifikasi diproses.')->danger()->send();
                        }),

                    Tables\Actions\Action::make('set_selesai')
                        ->label('Set Selesai')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) =>
                            auth()->user()?->hasRole('Admin') &&
                            $record->status !== 'Selesai'
                        )
                        ->action(function ($record) {
                            $record->ensureVerification();
                            $record->update([
                                'status'          => 'Selesai',
                                'completion_date' => now(),
                            ]);
                            $record->users?->notify(new \App\Notifications\PenerjemahanStatusNotification('Selesai', $record->verification_url));
                            Notification::make()->title('Status selesai dan notifikasi diproses.')->success()->send();
                        }),

                    // Link publik PDF (berdasar verification code)
                    Tables\Actions\Action::make('copy_public_pdf')
                        ->label('Salin Link Publik')
                        ->icon('heroicon-o-link')
                        ->visible(function (Penerjemahan $record) {
                            $status = strtolower((string) $record->status);
                            $okStatus = in_array($status, ['selesai', 'disetujui', 'completed', 'approved'], true);
                            $hasOutput = filled($record->translated_text) || filled($record->final_file_path);
                            return filled($record->verification_code) && $okStatus && $hasOutput;
                        })
                        ->action(function (Penerjemahan $record) {
                            $url = route('verification.penerjemahan.pdf', $record->verification_code);
                            Notification::make()->title('Link Publik PDF')->body($url)->success()->send();
                        }),
                ])
                ->label('Ubah Status')
                ->icon('heroicon-s-cog-6-tooth')
                ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi'])),

                // Aksi khusus Penerjemah: hanya Edit untuk mengisi terjemahan
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->label('Isi Terjemahan')->icon('heroicon-o-pencil-square'),
                ])
                ->label('Aksi Penerjemah')
                ->icon('heroicon-s-academic-cap')
                ->visible(fn ($record) => auth()->user()?->hasRole('Penerjemah') && $record->translator_id === auth()->id()),

                // Aksi Edit standar untuk role lain (mis. pendaftar tidak melihat action admin)
                Tables\Actions\EditAction::make()
                    ->visible(fn () => ! auth()->user()?->hasAnyRole(['Admin', 'Penerjemah', 'Staf Administrasi'])),
            ])
            ->bulkActions(
                auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi'])
                    ? [
                        Tables\Actions\BulkActionGroup::make([
                            Tables\Actions\DeleteBulkAction::make(),
                        ]),
                        
                        // Export PDF Bukti Pembayaran (dengan Preview)
                        Tables\Actions\BulkAction::make('export_pdf_bukti')
                            ->label('Export PDF Bukti')
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('info')
                            ->deselectRecordsAfterCompletion()
                            ->action(function (\Illuminate\Support\Collection $records) {
                                // Filter hanya yang punya bukti pembayaran
                                $filtered = $records->filter(fn ($r) => filled($r->bukti_pembayaran));
                                
                                if ($filtered->isEmpty()) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Tidak ada bukti pembayaran')
                                        ->body('Tidak ada record yang memiliki bukti pembayaran untuk diekspor.')
                                        ->send();
                                    return;
                                }

                                if ($filtered->count() > 8) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Maksimal 8 gambar')
                                        ->body('Pilih maksimal 8 data per export agar proses PDF tetap ringan di server.')
                                        ->send();
                                    return;
                                }
                                
                                // Redirect to preview page with selected IDs
                                $ids = $filtered->pluck('id')->implode(',');
                                return redirect()->to(route('admin.export-bukti.preview', ['ids' => $ids]));
                            }),
                    ]
                    : []
            );
    }

    /* -----------------------------------------------------------
    |  QUERY SCOPE (berdasarkan role)
    |----------------------------------------------------------- */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasRole('pendaftar')) {
            return parent::getEloquentQuery()->where('user_id', $user->id);
        }

        if ($user->hasRole('Penerjemah')) {
            return parent::getEloquentQuery()->where('translator_id', $user->id);
        }

        return parent::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Penerjemah'])) {
            return null;
        }
        $count = static::getModel()::where('status', 'Menunggu')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pemohon perlu ditinjau';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    private static function formatPeriodLabel(Carbon $start, Carbon $end): string
    {
        $startStr = $start->translatedFormat('j F Y');
        $endStr   = $end->translatedFormat('j F Y');
        return "{$startStr} s/d {$endStr}";
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPenerjemahans::route('/'),
            'create' => Pages\CreatePenerjemahan::route('/create'),
            'edit'   => Pages\EditPenerjemahan::route('/{record}/edit'),
            'crop'   => Pages\CropBukti::route('/{record}/crop'),
        ];
    }
}
