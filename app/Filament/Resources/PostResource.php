<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Support\ImageTransformer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{
    TextInput, Select, Toggle, DateTimePicker, Hidden, 
    Section, Group, Textarea, Grid, FileUpload
};
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\{Get, Set};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PostResource extends BaseResource
{
    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static ?string $label = 'Posting Informasi';

    // Menambahkan badge jumlah post di sidebar navigasi
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // === KOLOM KIRI (KONTEN UTAMA) ===
            Group::make()->schema([
                Section::make('Konten Utama')->schema([
                    Forms\Components\Placeholder::make('ept_schedule_sync_notice')
                        ->hiddenLabel()
                        ->content(function (Get $get): HtmlString {
                            $groupId = (int) ($get('ept_group_id') ?? 0);
                            $groupUrl = $groupId > 0
                                ? EptGroupResource::getUrl('view', ['record' => $groupId])
                                : '#';

                            return new HtmlString(
                                '<div class="rounded-xl border border-info-200 bg-info-50 px-4 py-3 text-sm text-info-900">'
                                . '<div class="font-semibold">Post jadwal ini tersinkron otomatis dari Grup EPT.</div>'
                                . '<div class="mt-1">Ubah jadwal, ruangan, judul, isi, dan publikasi dari menu Grup EPT agar tetap konsisten.</div>'
                                . ($groupId > 0
                                    ? '<div class="mt-2"><a href="' . e($groupUrl) . '" class="font-medium underline">Buka Grup EPT terkait</a></div>'
                                    : '')
                                . '</div>'
                            );
                        })
                        ->visible(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                        ->columnSpanFull(),

                    TextInput::make('title')
                        ->label('Judul')
                        ->required()
                        ->maxLength(255)
                        ->disabled(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                        ->suffixAction(
                            FormAction::make('fill_schedule_title')
                                ->label('Auto')
                                ->icon('heroicon-o-sparkles')
                                ->tooltip('Isi judul otomatis dari nomor grup dan tanggal tes.')
                                ->visible(fn (Get $get): bool => $get('type') === 'schedule' && ! static::isSyncedEptSchedule($get))
                                ->disabled(fn (Get $get): bool => blank($get('group_number')) || blank($get('event_date')))
                                ->action(function (Get $get, Set $set): void {
                                    $title = static::generateScheduleTitle(
                                        $get('group_number'),
                                        $get('event_date'),
                                    );

                                    if ($title !== null) {
                                        $set('title', $title);
                                    }
                                })
                        )
                        ->helperText(fn (Get $get): ?string => $get('type') === 'schedule'
                            ? 'Untuk jadwal ujian, klik tombol Auto agar judul terisi dari nomor grup dan tanggal tes.'
                            : null),

                    TextInput::make('slug')
                        ->label('Slug URL')
                        ->disabled(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null)
                        ->dehydrated()
                        ->unique(ignoreRecord: true)
                        ->helperText('Boleh dikosongkan. Slug otomatis dibuat dari judul saat simpan.'),

                    Textarea::make('excerpt')
                        ->label('Ringkasan / Intro')
                        ->rows(3)
                        ->maxLength(180)
                        ->disabled(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                        ->columnSpanFull()
                        ->helperText('Teks singkat yang muncul di daftar posting (Maks. 180 karakter).'),

                    TiptapEditor::make('body')
                        ->label('Isi Konten')
                        ->required()
                        ->disabled(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                        ->columnSpanFull()
                        ->clearAfterStateUpdatedHooks()
                        ->formatStateUsing(fn ($state): string => static::normalizeEditorBody($state))
                        ->dehydrateStateUsing(fn ($state): string => static::sanitizeEditorBody(static::normalizeEditorBody($state)))
                        ->disableBubbleMenus()
                        ->disableFloatingMenus()
                        ->disk('public')
                        ->directory('posts/body')
                        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $set) {
                            $result = ImageTransformer::toWebpFromUploaded(
                                uploaded:   $file,
                                targetDisk: 'public',
                                targetDir:  'posts/body',
                                quality:    82,
                                maxWidth:   1600,
                                maxHeight:  1600,
                            );

                            $set('width', $result['width'] ?? null);
                            $set('height', $result['height'] ?? null);

                            return Storage::disk('public')->url($result['path']);
                        })
                        ->helperText('Tips: tombol "Hapus format" di toolbar kiri untuk reset format teks (isi tetap ada). Ikon penghapus di kanan atas untuk hapus semua konten. Gunakan Ctrl/Cmd+Shift+V untuk paste tanpa format.')
                        ->maxContentWidth('full') // Agar editor lebih luas
                        ->profile('default')
                        ->tools([
                            'heading', 'bullet-list', 'ordered-list', 'blockquote', 'hr', '|',
                            'bold', 'italic', 'underline', 'strike', 'color', 'highlight', '|',
                            ['button' => 'tiptap.tools.clear-format'],
                            'align-left', 'align-center', 'align-right', '|',
                            'link', 'media', 'table', 'code-block'
                        ]),
                ]),
            ])->columnSpan(['lg' => 2]), // 2/3 layar di desktop

            // === KOLOM KANAN (METADATA & MEDIA) ===
            Group::make()->schema([
                Section::make('Status & Publikasi')->schema([
                    Select::make('type')
                        ->label('Kategori')
                        ->options(\App\Models\Post::TYPES)
                        ->disabled(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                        ->default(function (): ?string {
                            $type = request()->query('type');

                            if (! is_string($type)) {
                                return null;
                            }

                            return array_key_exists($type, Post::TYPES) ? $type : null;
                        })
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->live(),

                    Select::make('news_category')
                        ->label('Kategori Berita')
                        ->options(fn (): array => Post::newsCategoryOptions(onlyActive: true))
                        ->default(fn (): string => Post::defaultNewsCategorySlug())
                        ->required(fn (Get $get): bool => $get('type') === 'news')
                        ->searchable()
                        ->native(false)
                        ->visible(fn (Get $get): bool => $get('type') === 'news')
                        ->afterStateHydrated(function (Set $set, Get $get, ?string $state): void {
                            if ($get('type') !== 'news') {
                                return;
                            }

                            if (! Post::isValidNewsCategory($state)) {
                                $set('news_category', Post::defaultNewsCategorySlug());
                            }
                        })
                        ->helperText('Pilih kategori berita agar mudah difilter di halaman publik.'),

                    FileUpload::make('cover_path')
                        ->label('Gambar Utama')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                        ->imagePreviewHeight('160')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(8192)
                        ->disk('public')
                        ->visibility('public')
                        ->downloadable()
                        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $get) {
                            $old = $get('cover_path');
                            if (is_array($old)) {
                                $old = $old['path'] ?? ($old[0]['path'] ?? null);
                            }

                            if (is_string($old) && $old !== '' && Storage::disk('public')->exists($old)) {
                                Storage::disk('public')->delete($old);
                            }

                            $title = (string) ($get('title') ?? 'cover');
                            $basename = Str::slug($title, '_');
                            if ($basename === '') {
                                $basename = 'cover';
                            }

                            return ImageTransformer::toWebpFromUploaded(
                                uploaded:   $file,
                                targetDisk: 'public',
                                targetDir:  'posts/covers',
                                quality:    82,
                                maxWidth:   1600,
                                maxHeight:  900,
                                basename:   $basename
                            )['path'];
                        })
                        ->deleteUploadedFileUsing(function (string $file): void {
                            if (Storage::disk('public')->exists($file)) {
                                Storage::disk('public')->delete($file);
                            }
                        })
                        ->helperText('Khusus kategori Berita dan Karier.')
                        ->visible(fn (Get $get): bool => in_array($get('type'), ['news', 'career'], true)),

                    Toggle::make('career_is_open')
                        ->label('Status Lowongan Dibuka')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(true)
                        ->visible(fn (Get $get): bool => $get('type') === 'career')
                        ->live(),

                    DateTimePicker::make('career_deadline')
                        ->label('Deadline Lamaran')
                        ->seconds(false)
                        ->native(false)
                        ->visible(fn (Get $get): bool => $get('type') === 'career')
                        ->helperText('Kosongkan jika lowongan tidak memiliki batas waktu.'),

                    TextInput::make('career_apply_url')
                        ->label('Link Daftar (CTA)')
                        ->url()
                        ->maxLength(500)
                        ->placeholder('https://...')
                        ->visible(fn (Get $get): bool => $get('type') === 'career')
                        ->required(fn (Get $get): bool => $get('type') === 'career')
                        ->helperText('Tautan tombol "Daftar Sekarang" di halaman karier.'),

                    // Grid untuk Nomor Grup dan Tanggal (2 kolom)
                    Grid::make(2)
                        ->schema([
                            TextInput::make('group_number')
                                ->label('Nomor Grup')
                                ->numeric()
                                ->required()
                                ->disabled(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                                ->minValue(1)
                                ->maxValue(999)
                                ->dehydrated(false)
                                ->afterStateHydrated(function (Set $set, Get $get, $state): void {
                                    if (filled($state) || $get('type') !== 'schedule') {
                                        return;
                                    }

                                    $groupNumber = static::extractScheduleGroupNumber((string) ($get('title') ?? ''));

                                    if ($groupNumber !== null) {
                                        $set('group_number', $groupNumber);
                                    }
                                }),

                            Forms\Components\DatePicker::make('event_date')
                                ->label('Tanggal Tes')
                                ->disabled(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                                ->native(false)
                                ->required(),

                            Forms\Components\TimePicker::make('event_time')
                                ->label('Waktu Tes')
                                ->disabled(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                                ->native(false)
                                ->seconds(false)
                                ->default('08:30'),

                            TextInput::make('event_location')
                                ->label('Lokasi/Ruangan')
                                ->disabled(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                                ->maxLength(255)
                                ->default('Kampus 3, Ruang Standford'),
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'schedule'),

                    // Field reaktif: Pilih Jadwal Terkait (hanya untuk tipe nilai)
                    Select::make('related_post_id')
                        ->label('Jadwal Terkait')
                        ->default(function (): ?int {
                            if (request()->query('type') !== 'scores') {
                                return null;
                            }

                            $relatedId = (int) request()->query('related_post_id');

                            return $relatedId > 0 ? $relatedId : null;
                        })
                        ->options(function (Get $get): array {
                            if ($get('type') !== 'scores') {
                                return [];
                            }

                            $selectedId = (int) ($get('related_post_id') ?? 0);

                            return Post::query()
                                ->where('type', 'schedule')
                                ->where(function (Builder $query) use ($selectedId): void {
                                    $query->whereDoesntHave('relatedScores');

                                    if ($selectedId > 0) {
                                        $query->orWhere('id', $selectedId);
                                    }
                                })
                                ->orderByDesc('created_at')
                                ->orderByDesc('id')
                                ->limit(20)
                                ->pluck('title', 'id')
                                ->all();
                        })
                        ->afterStateHydrated(function (Set $set, Get $get, ?int $state): void {
                            if (! $state || filled($get('title'))) {
                                return;
                            }

                            static::fillScoresTitleFromRelatedPost($set, $state);
                        })
                        ->getSearchResultsUsing(function (Get $get, string $search): array {
                            $selectedId = (int) ($get('related_post_id') ?? 0);

                            return Post::query()
                                ->where('type', 'schedule')
                                ->where(function (Builder $query) use ($selectedId): void {
                                    $query->whereDoesntHave('relatedScores');

                                    if ($selectedId > 0) {
                                        $query->orWhere('id', $selectedId);
                                    }
                                })
                                ->where('title', 'like', '%' . trim($search) . '%')
                                ->orderByDesc('created_at')
                                ->orderByDesc('id')
                                ->limit(20)
                                ->pluck('title', 'id')
                                ->all();
                        })
                        ->getOptionLabelUsing(fn ($value): ?string => Post::query()->whereKey($value)->value('title'))
                        ->searchable()
                        ->optionsLimit(20)
                        ->searchPrompt('Tampilkan 20 jadwal terbaru. Ketik untuk mencari yang lain.')
                        ->searchingMessage('Mencari jadwal...')
                        ->noSearchResultsMessage('Tidak ada jadwal yang cocok.')
                        ->required(fn (Get $get): bool => $get('type') === 'scores')
                        ->unique(
                            table: Post::class,
                            column: 'related_post_id',
                            ignoreRecord: true,
                            modifyRuleUsing: fn ($rule) => $rule->where('type', 'scores'),
                        )
                        ->validationMessages([
                            'unique' => 'Jadwal ini sudah memiliki post nilai.',
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'scores')
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?int $state) {
                            // Auto generate judul dari jadwal terkait
                            if ($state) {
                                static::fillScoresTitleFromRelatedPost($set, $state);
                            }
                        })
                        ->helperText('Judul akan otomatis terisi dari jadwal terkait'),

                    Toggle::make('is_published')
                        ->label('Terbitkan Sekarang')
                        ->disabled(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(false),

                    DateTimePicker::make('published_at')
                        ->label('Waktu Tayang')
                        ->disabled(fn (Get $get): bool => static::isSyncedEptSchedule($get))
                        ->seconds(false)
                        ->native(false)
                        ->default(now())
                        ->helperText('Dapat dijadwalkan untuk masa depan.'),
                ]),

                // Hidden field tetap ada
                Hidden::make('ept_group_id')
                    ->dehydrated(false),
                Hidden::make('author_id')
                    ->default(fn () => auth()->id()),
            ])->columnSpan(['lg' => 1]), // 1/3 layar di desktop
        ])->columns(3); // Total grid 3 kolom
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'relatedScores:id,related_post_id,slug,published_at',
                'relatedPost:id,title',
                'eptGroup:id,name',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(30)
                    ->wrap()
                    ->weight('bold')
                    // Tampilkan excerpt kecil di bawah judul
                    ->description(fn (Post $record): string => Str::limit($record->excerpt, 40)),

                Tables\Columns\TextColumn::make('type')
                    ->label('Kategori')
                    ->formatStateUsing(fn ($state) => \App\Models\Post::TYPES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'news'         => 'info',
                        'career'       => 'primary',
                        'schedule'     => 'warning',
                        'scores'       => 'success',
                        'service'      => 'primary',
                        'article'      => 'success',
                        'announcement' => 'danger',
                        default        => 'gray',
                    })
                    ->sortable()
                    ->visible(fn ($livewire): bool => static::shouldShowTableColumnForTab($livewire, ['all'])),

                Tables\Columns\TextColumn::make('schedule_source')
                    ->label('Sumber')
                    ->state(function (Post $record): string {
                        if ($record->type !== 'schedule') {
                            return '-';
                        }

                        return $record->ept_group_id ? 'Otomatis dari Grup EPT' : 'Manual';
                    })
                    ->description(fn (Post $record): ?string => $record->type === 'schedule'
                        ? ($record->eptGroup?->name ?: null)
                        : null)
                    ->badge()
                    ->wrap()
                    ->color(fn (string $state): string => match ($state) {
                        'Otomatis dari Grup EPT' => 'info',
                        'Manual' => 'gray',
                        default => 'gray',
                    })
                    ->visible(fn ($livewire): bool => static::shouldShowTableColumnForTab($livewire, ['all', 'schedule'])),

                Tables\Columns\TextColumn::make('career_status')
                    ->label('Status Karier')
                    ->state(function (Post $record): string {
                        if ($record->type !== 'career') {
                            return '-';
                        }

                        return $record->isCareerOpen() ? 'Dibuka' : 'Ditutup';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dibuka' => 'success',
                        'Ditutup' => 'danger',
                        default => 'gray',
                    })
                    ->visible(fn ($livewire): bool => static::shouldShowTableColumnForTab($livewire, ['career'])),

                Tables\Columns\TextColumn::make('news_category')
                    ->label('Kategori Berita')
                    ->formatStateUsing(fn (?string $state, Post $record): string => $record->type === 'news'
                        ? Post::newsCategoryLabel($state)
                        : '-')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn ($livewire): bool => static::shouldShowTableColumnForTab($livewire, ['news'])),

                Tables\Columns\TextColumn::make('scores_status')
                    ->label('Status Nilai')
                    ->state(function (Post $record): string {
                        if ($record->type !== 'schedule') {
                            return '-';
                        }

                        return static::hasScorePostForSchedule($record) ? 'Sudah Ada' : 'Belum Ada';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sudah Ada' => 'success',
                        'Belum Ada' => 'warning',
                        default => 'gray',
                    })
                    ->visible(fn ($livewire): bool => static::shouldShowTableColumnForTab($livewire, ['schedule'])),

                Tables\Columns\TextColumn::make('event_date')
                    ->label('Tanggal Tes')
                    ->date('d M Y')
                    ->sortable()
                    ->visible(fn ($livewire): bool => static::shouldShowTableColumnForTab($livewire, ['schedule'])),

                Tables\Columns\TextColumn::make('event_time')
                    ->label('Jam Tes')
                    ->time('H:i')
                    ->sortable()
                    ->visible(fn ($livewire): bool => static::shouldShowTableColumnForTab($livewire, ['schedule'])),

                Tables\Columns\TextColumn::make('event_location')
                    ->label('Ruangan')
                    ->limit(28)
                    ->wrap()
                    ->tooltip(fn (?string $state): ?string => filled($state) ? $state : null)
                    ->visible(fn ($livewire): bool => static::shouldShowTableColumnForTab($livewire, ['schedule'])),

                Tables\Columns\TextColumn::make('relatedPost.title')
                    ->label('Jadwal Terkait')
                    ->limit(32)
                    ->wrap()
                    ->tooltip(fn (?string $state): ?string => filled($state) ? $state : null)
                    ->placeholder('-')
                    ->visible(fn ($livewire): bool => static::shouldShowTableColumnForTab($livewire, ['scores'])),

                Tables\Columns\ToggleColumn::make('is_published')
                    ->label('Tayang'),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime('d M Y, H:i')
                    ->label('Dipublish')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('views')
                    ->label('Dilihat')
                    ->icon('heroicon-m-eye')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Status Publikasi')
                    ->placeholder('Semua')
                    ->trueLabel('Tayang')
                    ->falseLabel('Draft'),

                Tables\Filters\TernaryFilter::make('has_scores')
                    ->label('Status Nilai (Jadwal)')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Ada')
                    ->falseLabel('Belum Ada')
                    ->queries(
                        true: fn (Builder $query): Builder => $query
                            ->where('type', 'schedule')
                            ->whereHas('relatedScores'),
                        false: fn (Builder $query): Builder => $query
                            ->where('type', 'schedule')
                            ->whereDoesntHave('relatedScores'),
                        blank: fn (Builder $query): Builder => $query,
                    ),

                Tables\Filters\Filter::make('published_range')
                    ->label('Rentang Tayang')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if ($from && $until) {
                            return "Tayang: {$from} s/d {$until}";
                        }

                        if ($from) {
                            return "Tayang ≥ {$from}";
                        }

                        if ($until) {
                            return "Tayang ≤ {$until}";
                        }

                        return null;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if ($from) {
                            $query->whereDate('published_at', '>=', $from);
                        }

                        if ($until) {
                            $query->whereDate('published_at', '<=', $until);
                        }

                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('news_category')
                    ->label('Kategori Berita')
                    ->options(fn (): array => Post::newsCategoryOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! is_string($value) || ! Post::isValidNewsCategory($value)) {
                            return $query;
                        }

                        return $query
                            ->where('type', 'news')
                            ->where('news_category', $value);
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->persistFiltersInSession()
            ->groups([
                Tables\Grouping\Group::make('type')
                    ->label('Kategori')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn (Post $record): string => Post::TYPES[$record->type] ?? ucfirst((string) $record->type))
                    ->collapsible(),

                Tables\Grouping\Group::make('published_at')
                    ->label('Tanggal Tayang')
                    ->date()
                    ->collapsible(),
            ])
            ->defaultGroup('type')
            ->defaultSort('published_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('scores_post')
                        ->label(function (Post $record): string {
                            return static::hasScorePostForSchedule($record) ? 'Lihat Nilai' : 'Input Nilai';
                        })
                        ->icon(function (Post $record): string {
                            return static::hasScorePostForSchedule($record) ? 'heroicon-m-document-text' : 'heroicon-m-plus-circle';
                        })
                        ->color(function (Post $record): string {
                            return static::hasScorePostForSchedule($record) ? 'info' : 'success';
                        })
                        ->visible(fn (Post $record): bool => $record->type === 'schedule')
                        ->url(function (Post $record): string {
                            $scorePost = static::latestScorePostForSchedule($record);

                            if ($scorePost) {
                                return route('front.post.show', ['post' => $scorePost]);
                            }

                            return static::getUrl('create', [
                                'type' => 'scores',
                                'related_post_id' => $record->id,
                            ]);
                        })
                        ->openUrlInNewTab(fn (Post $record): bool => static::hasScorePostForSchedule($record)),
                Tables\Actions\Action::make('view_public')
                        ->label('Lihat Post')
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->url(function (Post $record): string {
                            if ($record->type === 'career') {
                                return route('front.career.show', ['post' => $record]);
                            }

                            return route('front.post.show', ['post' => $record]);
                        })
                        ->openUrlInNewTab(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    protected static function sanitizeEditorBody(string|array|null $html): string
    {
        $html = static::toEditorHtml($html);

        if ($html === '') {
            return '';
        }

        if (function_exists('clean')) {
            try {
                return (string) clean($html, 'post');
            } catch (\Throwable) {
                return $html;
            }
        }

        return $html;
    }

    protected static function normalizeEditorBody(string|array|null $html): string
    {
        $html = static::toEditorHtml($html);

        if ($html === '') {
            return '';
        }

        $normalized = preg_replace_callback('/<img\b([^>]*)>/i', function (array $match): string {
            $attributes = preg_replace('/\s(?:width|height)="[^"]*"/i', '', $match[1]);
            if (! is_string($attributes)) {
                $attributes = $match[1];
            }

            return '<img' . $attributes . '>';
        }, $html);

        return is_string($normalized) ? $normalized : $html;
    }

    protected static function toEditorHtml(string|array|null $content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        if (is_string($content)) {
            $trimmed = trim($content);

            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $decoded = json_decode($trimmed, true);

                if (is_array($decoded)) {
                    $content = $decoded;
                }
            }
        }

        if (is_array($content)) {
            try {
                return (string) tiptap_converter()->asHTML($content);
            } catch (\Throwable) {
                return '';
            }
        }

        return (string) $content;
    }

    protected static function generateScheduleTitle(mixed $groupNumber, mixed $eventDate): ?string
    {
        $groupNumber = filled($groupNumber) ? trim((string) $groupNumber) : null;

        if (($groupNumber === null) || blank($eventDate)) {
            return null;
        }

        try {
            $date = $eventDate instanceof \Carbon\CarbonInterface
                ? $eventDate
                : \Carbon\Carbon::parse($eventDate);
        } catch (\Throwable) {
            return null;
        }

        $formatted = $date->translatedFormat('l, d F Y');

        return "Jadwal Tes EPT Grup {$groupNumber} ({$formatted})";
    }

    protected static function extractScheduleGroupNumber(?string $title): ?string
    {
        if (! filled($title)) {
            return null;
        }

        preg_match('/Grup\s*(\d+)/i', $title, $matches);

        return $matches[1] ?? null;
    }

    protected static function getCurrentTableTab(mixed $livewire): string
    {
        if (is_object($livewire) && property_exists($livewire, 'activeTab')) {
            $activeTab = $livewire->activeTab;

            if (is_string($activeTab) && $activeTab !== '') {
                return $activeTab;
            }

            if (method_exists($livewire, 'getDefaultActiveTab')) {
                $defaultActiveTab = $livewire->getDefaultActiveTab();

                if (is_string($defaultActiveTab) && $defaultActiveTab !== '') {
                    return $defaultActiveTab;
                }
            }
        }

        return 'schedule';
    }

    protected static function shouldShowTableColumnForTab(mixed $livewire, array $tabs): bool
    {
        return in_array(static::getCurrentTableTab($livewire), $tabs, true);
    }

    protected static function fillScoresTitleFromRelatedPost(Set $set, int $relatedPostId): void
    {
        $related = Post::query()
            ->select(['id', 'title', 'event_date'])
            ->find($relatedPostId);

        if (! $related || ! $related->event_date) {
            return;
        }

        $formattedDate = $related->event_date->translatedFormat('l, d F Y');
        preg_match('/Grup\s*(\d+)/i', $related->title, $matches);
        $groupNum = $matches[1] ?? '';
        $groupLabel = $groupNum !== '' ? " {$groupNum}" : '';

        $set('title', "Nilai EPT Grup{$groupLabel} ({$formattedDate})");
    }

    protected static function isSyncedEptSchedule(Get $get): bool
    {
        return $get('type') === 'schedule' && filled($get('ept_group_id'));
    }

    protected static function latestScorePostForSchedule(Post $schedulePost): ?Post
    {
        if ($schedulePost->type !== 'schedule') {
            return null;
        }

        if ($schedulePost->relationLoaded('relatedScores')) {
            /** @var ?Post $scorePost */
            $scorePost = $schedulePost->relatedScores->first();

            return $scorePost;
        }

        return $schedulePost->relatedScores()
            ->select(['id', 'related_post_id', 'slug', 'published_at'])
            ->first();
    }

    protected static function hasScorePostForSchedule(Post $schedulePost): bool
    {
        return static::latestScorePostForSchedule($schedulePost) instanceof Post;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit'   => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
