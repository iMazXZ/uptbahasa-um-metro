<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenerjemahanResource\Pages;
use App\Models\Penerjemahan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
    protected static ?string $navigationGroup = 'Layanan UPT Bahasa';

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
        $exportBukti = Tables\Actions\BulkAction::make('export_pdf_bukti')
            ->label('Export PDF Bukti')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->deselectRecordsAfterCompletion()
            ->action(function (\Illuminate\Support\Collection $records) {
                $filtered = $records->filter(fn ($r) => filled($r->bukti_pembayaran));

                if ($filtered->isEmpty()) {
                    Notification::make()->warning()->title('Tidak ada bukti pembayaran')
                        ->body('Tidak ada record yang memiliki bukti pembayaran untuk diekspor.')
                        ->send();
                    return;
                }

                if ($filtered->count() > 8) {
                    Notification::make()->warning()->title('Maksimal 8 gambar')
                        ->body('Pilih maksimal 8 data per export agar proses PDF tetap ringan di server.')
                        ->send();
                    return;
                }

                $ids = $filtered->pluck('id')->implode(',');
                return redirect()->to(route('admin.export-bukti.preview', ['ids' => $ids]));
            });

        $user = auth()->user();
        $bulkActions = [];
        if ($user?->hasAnyRole(['Admin', 'Staf Administrasi'])) {
            $bulkActions[] = Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
            $bulkActions[] = $exportBukti;
        } elseif ($user?->hasRole('Kepala Lembaga')) {
            $bulkActions[] = $exportBukti;
        }

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
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga'])),

                Tables\Columns\TextColumn::make('users.srn')
                    ->label('NPM')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('NPM disalin')
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

                Tables\Columns\TextColumn::make('translator.name')
                    ->label('Penerjemah')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga'])),

                Tables\Columns\TextColumn::make('submission_date')
                    ->label('Pengajuan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bukti_pembayaran')
                    ->label('Bukti')
                    ->formatStateUsing(fn ($state) => $state ? 'Ada' : '-')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga'])),

                Tables\Columns\TextColumn::make('completion_date')
                    ->label('Selesai')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('Alasan Ditolak')
                    ->wrap()
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
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

                Tables\Filters\SelectFilter::make('translator_id')
                    ->label('Penerjemah')
                    ->options(fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'Penerjemah'))->pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga'])),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga']))
                    ->form([
                        Forms\Components\Select::make('penerjemah_ids')
                            ->label('Penerjemah')
                            ->options(fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'Penerjemah'))->orderBy('name')->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Kosongkan jika ingin semua penerjemah.'),

                        Forms\Components\Select::make('periode')
                            ->label('Periode')
                            ->placeholder('Pilih manual')
                            ->options([
                                'bulan_ini'   => 'Bulan ini',
                                'bulan_lalu'  => 'Bulan lalu',
                                'triwulan_1'  => 'Triwulan 1 (Jan - Mar)',
                                'triwulan_2'  => 'Triwulan 2 (Apr - Jun)',
                                'triwulan_3'  => 'Triwulan 3 (Jul - Sep)',
                                'triwulan_4'  => 'Triwulan 4 (Okt - Des)',
                                'tahun_ini'   => 'Tahun ini',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $now = now();
                                match ($state) {
                                    'bulan_ini' => [
                                        $set('start_date', $now->startOfMonth()->toDateString()),
                                        $set('end_date', $now->endOfMonth()->toDateString()),
                                    ],
                                    'bulan_lalu' => [
                                        $set('start_date', $now->subMonthNoOverflow()->startOfMonth()->toDateString()),
                                        $set('end_date', $now->subMonthNoOverflow()->endOfMonth()->toDateString()),
                                    ],
                                    'triwulan_1' => [
                                        $set('start_date', $now->startOfYear()->toDateString()),
                                        $set('end_date', $now->startOfYear()->addMonths(2)->endOfMonth()->toDateString()),
                                    ],
                                    'triwulan_2' => [
                                        $set('start_date', $now->startOfYear()->addMonths(3)->startOfMonth()->toDateString()),
                                        $set('end_date', $now->startOfYear()->addMonths(5)->endOfMonth()->toDateString()),
                                    ],
                                    'triwulan_3' => [
                                        $set('start_date', $now->startOfYear()->addMonths(6)->startOfMonth()->toDateString()),
                                        $set('end_date', $now->startOfYear()->addMonths(8)->endOfMonth()->toDateString()),
                                    ],
                                    'triwulan_4' => [
                                        $set('start_date', $now->startOfYear()->addMonths(9)->startOfMonth()->toDateString()),
                                        $set('end_date', $now->startOfYear()->addMonths(11)->endOfMonth()->toDateString()),
                                    ],
                                    'tahun_ini' => [
                                        $set('start_date', $now->startOfYear()->toDateString()),
                                        $set('end_date', $now->endOfYear()->toDateString()),
                                    ],
                                    default => null,
                                };
                            }),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Dari tanggal')
                            ->required()
                            ->live(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Sampai tanggal')
                            ->required()
                            ->live(),

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

                        $query = Penerjemahan::query()->with('users');

                        // Filter penerjemah (jika dipilih)
                        if (filled($data['penerjemah_ids'] ?? null)) {
                            $query->whereIn('translator_id', (array) $data['penerjemah_ids']);
                        }

                        // Exclude yang ditolak
                        $query->whereNotLike('status', '%Ditolak%');

                        $rows = $query
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
                // ===== Kelola (modal) — Admin/Staf aksi penuh, Kepala read-only =====
                Tables\Actions\Action::make('kelola_penerjemahan')
                    ->label(fn () => auth()->user()?->hasRole('Kepala Lembaga') ? 'Lihat Detail' : 'Kelola')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('gray')
                    ->modalHeading(fn (Penerjemahan $record): string => (auth()->user()?->hasRole('Kepala Lembaga') ? 'Detail' : 'Kelola') . ' Penerjemahan - ' . ($record->users?->name ?? ''))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('4xl')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga']))
                    ->modalContent(fn (Penerjemahan $record) => view('filament.components.penerjemahan-manage', ['record' => $record])),
            ])
            ->bulkActions($bulkActions);
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
        if (! auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga'])) {
            return null;
        }
        $count = static::getModel()::where('status', 'Menunggu')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ! auth()->user()?->hasAnyRole(['pendaftar', 'Penerjemah']);
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
