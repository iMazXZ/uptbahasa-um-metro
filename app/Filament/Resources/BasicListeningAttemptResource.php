<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BasicListeningAttemptResource\Pages;
use App\Models\BasicListeningAttempt;
use App\Models\BasicListeningQuiz;
use App\Models\BasicListeningQuestion;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class BasicListeningAttemptResource extends BaseResource
{
    protected static ?string $model = BasicListeningAttempt::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Basic Listening';
    protected static ?string $pluralLabel     = 'Attempts (Hasil Kuis)';
    protected static ?string $modelLabel      = 'Attempt';

    // Cache sederhana untuk menyimpan data soal selama request berlangsung
    protected static array $questionCache = [];

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'user.prody',
                'session',
                'quiz.questions',
                'connectCode',
                'answers',
            ]);

        // Filter by BL period start date
        $startDate = \App\Models\SiteSetting::getBlPeriodStartDate();
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        $user = auth()->user();

        if ($user && $user->hasRole('Admin')) {
            return $query;
        }

        if ($user && $user->hasRole('tutor')) {
            $prodyIds = [];
            if (method_exists($user, 'assignedProdyIds')) {
                $prodyIds = $user->assignedProdyIds();
            }

            if (empty($prodyIds)) {
                return $query->whereRaw('1=0');
            }

            return $query->whereHas('user', fn (Builder $q) => $q->whereIn('prody_id', $prodyIds));
        }

        return $query->whereRaw('1=0');
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::getFormSchema());
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Ringkasan Attempt')
                ->columns(4)
                ->schema([
                    TextEntry::make('user.name')
                        ->label('Peserta')
                        ->weight('bold'),

                    TextEntry::make('user.srn')
                        ->label('SRN/NIM')
                        ->copyable(),

                    TextEntry::make('user.email')
                        ->label('Email')
                        ->copyable(),

                    TextEntry::make('user.prody.name')
                        ->label('Prodi'),

                    TextEntry::make('user.nomor_grup_bl')
                        ->label('Grup BL'),

                    TextEntry::make('session.title')
                        ->label('Sesi')
                        ->prefix(fn ($record) => 'Pert. ' . $record->session->number . ' - '),

                    TextEntry::make('score')
                        ->label('Skor Akhir')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->weight('black')
                        ->color(fn ($state) => $state >= 60 ? 'success' : 'danger')
                        ->formatStateUsing(fn ($state) => $state . '%'),

                    TextEntry::make('submitted_at')
                        ->label('Waktu Submit')
                        ->dateTime('d M Y, H:i'),

                    TextEntry::make('stats')
                        ->label('Statistik Jawaban')
                        ->state(function ($record) {
                            $total   = $record->answers->count();
                            $correct = $record->answers->where('is_correct', true)->count();
                            return "{$correct} Benar / {$total} Total";
                        }),

                    // ==========================
                    // BLOK ID TEKNIS
                    // ==========================
                    TextEntry::make('id')
                        ->label('ID Attempt')
                        ->copyable()
                        ->columnSpan(1),

                    TextEntry::make('user_id')
                        ->label('ID User')
                        ->copyable()
                        ->columnSpan(1),

                    TextEntry::make('user.prody_id')
                        ->label('ID Prodi')
                        ->copyable()
                        ->columnSpan(1)
                        ->placeholder('-'),

                    TextEntry::make('session_id')
                        ->label('ID Session')
                        ->copyable()
                        ->columnSpan(1),

                    TextEntry::make('quiz_id')
                        ->label('ID Quiz')
                        ->copyable()
                        ->columnSpan(1),

                    TextEntry::make('connect_code_id')
                        ->label('ID Connect Code')
                        ->copyable()
                        ->columnSpan(1)
                        ->placeholder('-'),
                ]),

            ViewEntry::make('answers_view')
                ->view('filament.attempts.answers-view')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Peserta')
                    ->description(fn ($record) => $record->user?->srn)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.prody.name')
                    ->label('Prodi')
                    ->sortable()
                    ->limit(20)
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('session.title')
                    ->label('Sesi')
                    ->limit(20)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('score')
                    ->label('Skor')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state < 60     => 'danger',
                        $state < 80     => 'warning',
                        default         => 'success',
                    })
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . '' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('connectCode.code_hint')
                    ->label('Kode')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submit')
                    ->dateTime('d M H:i')
                    ->sortable()
                    ->placeholder('Belum')
                    ->toggleable(),
            ])
            ->paginationPageOptions([5, 10, 25, 50])
            ->filters([
                Tables\Filters\SelectFilter::make('prody_id')
                    ->label('Prodi')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user?->hasRole('tutor')) {
                            $ids = method_exists($user, 'assignedProdyIds') ? (array) $user->assignedProdyIds() : [];
                            return \App\Models\Prody::whereIn('id', $ids)->pluck('name', 'id');
                        }
                        return \App\Models\Prody::pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('user', fn ($q) => $q->where('prody_id', $data['value']));
                        }
                    })
                    ->searchable()
                    ->preload(),

                Filter::make('score_zero_or_null')
                    ->label('Skor 0% atau Belum Dinilai')
                    ->toggle()
                    ->query(function (Builder $query) {
                        return $query->where(function (Builder $q) {
                            $q->where('score', 0)
                              ->orWhereNull('score');
                        });
                    }),

                Tables\Filters\SelectFilter::make('session')
                    ->label('Pertemuan')
                    ->relationship('session', 'number')
                    ->getOptionLabelFromRecordUsing(fn ($record) => 'Pert. ' . $record->number)
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('submitted_at')
                    ->label('Status Submit')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Submit')
                    ->falseLabel('Belum/Sedang Mengerjakan')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('submitted_at'),
                        false: fn ($q) => $q->whereNull('submitted_at'),
                    ),
            ])
            ->actions([
                \Filament\Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),

                    Tables\Actions\EditAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'tutor']))
                        ->authorize(fn () => auth()->user()?->hasAnyRole(['Admin', 'tutor'])),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-cog-6-tooth'),
            ])
            ->headerActions([
                Actions\Action::make('regradeByFilter')
                    ->label('Regrade Attempt (Filter)')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalHeading('Regrade Attempt Basic Listening')
                    ->modalSubmitActionLabel('Jalankan Regrade')
                    ->modalWidth('lg')
                    ->visible(fn () => auth()->user()?->hasRole('Admin')) // 🔒 Hanya Admin
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('attempt_id')
                                ->label('ID Attempt')
                                ->numeric()
                                ->helperText('Jika diisi, filter lain boleh kosong. Paling spesifik.'),

                            Forms\Components\TextInput::make('user_id')
                                ->label('ID User')
                                ->numeric()
                                ->helperText('Regrade semua attempt milik user ini.'),

                            Forms\Components\TextInput::make('connect_id')
                                ->label('ID Connect Code')
                                ->numeric()
                                ->helperText('Regrade semua attempt dari connect code ini.'),

                            Forms\Components\TextInput::make('session_id')
                                ->label('ID Session')
                                ->numeric()
                                ->helperText('Regrade attempt pada sesi tertentu.'),

                            Forms\Components\TextInput::make('prody_id')
                                ->label('ID Prodi (prody_id)')
                                ->numeric()
                                ->helperText('Filter berdasarkan prodi mahasiswa.'),

                            Forms\Components\Toggle::make('only_weird')
                                ->label('Hanya attempt "aneh" (score null/0/belum submit)')
                                ->default(true),
                        ]),
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan (opsional)')
                            ->rows(2)
                            ->helperText('Untuk pengingat internal, tidak mempengaruhi proses.'),
                    ])
                    ->action(function (array $data): void {
                        $params = [];

                        if (! empty($data['attempt_id'])) {
                            $params['--attempt'] = $data['attempt_id'];
                        }
                        if (! empty($data['user_id'])) {
                            $params['--user'] = $data['user_id'];
                        }
                        if (! empty($data['connect_id'])) {
                            $params['--connect'] = $data['connect_id'];
                        }
                        if (! empty($data['session_id'])) {
                            $params['--session'] = $data['session_id'];
                        }
                        if (! empty($data['prody_id'])) {
                            $params['--prody'] = $data['prody_id'];
                        }

                        $onlyWeird = ! empty($data['only_weird']);

                        // Kalau only_weird dimatikan dan semua filter kosong → cegah regrade full massal
                        if (! $onlyWeird &&
                            empty($data['attempt_id']) &&
                            empty($data['user_id']) &&
                            empty($data['connect_id']) &&
                            empty($data['session_id']) &&
                            empty($data['prody_id'])) {

                            Notification::make()
                                ->title('Regrade dibatalkan')
                                ->body('Jika "Hanya attempt aneh" dimatikan, minimal isi salah satu filter.')
                                ->warning()
                                ->send();

                            return;
                        }

                        // ⬅️ Ini yang sebelumnya hilang: selalu kirim flag only-weird ke command
                        $params['--only-weird'] = $onlyWeird ? 1 : 0;

                        Artisan::call('bl:regrade-attempts', $params);

                        Notification::make()
                            ->title('Proses regrade dijalankan')
                            ->body('Command bl:regrade-attempts sudah dipanggil dengan filter yang dipilih.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () =>
                            auth()->user()?->hasRole('Admin')
                        )
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->delete();

                            \Filament\Notifications\Notification::make()
                                ->title('Data berhasil dihapus')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    protected static function getFormSchema(): array
    {
        return [
            Section::make('Informasi Attempt')
                ->columns(12)
                ->schema([
                    Select::make('user_id')
                        ->label('Peserta')
                        ->relationship('user', 'name', fn (Builder $query) => $query->orderBy('name'))
                        ->getOptionLabelFromRecordUsing(fn (User $record) => $record->name . ' (' . ($record->srn ?? '-') . ')')
                        ->searchable(['name', 'srn'])
                        ->required(fn (string $operation) => $operation === 'create')
                        ->hidden(fn (string $operation) => $operation === 'edit')
                        ->columnSpan(5),

                    Placeholder::make('user_name')
                        ->label('Peserta')
                        ->content(fn ($record) => $record?->user?->name . ' (' . ($record?->user?->srn ?? '-') . ')')
                        ->columnSpan(5)
                        ->extraAttributes(['class' => 'text-gray-900 font-bold'])
                        ->hidden(fn (string $operation) => $operation === 'create'),

                    Placeholder::make('prody_name')
                        ->label('Prodi')
                        ->content(fn ($record) => $record?->user?->prody?->name ?? '-')
                        ->columnSpan(3)
                        ->hidden(fn (string $operation) => $operation === 'create'),

                    Select::make('quiz_id')
                        ->label('Paket Soal')
                        ->relationship('quiz', 'title', fn (Builder $query) => $query->with('session')->orderBy('session_id'))
                        ->getOptionLabelFromRecordUsing(fn (BasicListeningQuiz $record) => 'Pert. ' . ($record->session?->number ?? '?') . ' - ' . $record->title)
                        ->required(fn (string $operation) => $operation === 'create')
                        ->searchable(['title'])
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn ($state, Set $set) => static::syncQuizSelection($state, $set))
                        ->hidden(fn (string $operation) => $operation === 'edit')
                        ->columnSpan(4),

                    Placeholder::make('quiz_title')
                        ->label('Paket Soal')
                        ->content(fn ($record) => $record?->quiz?->title ?? '-')
                        ->columnSpan(4)
                        ->hidden(fn (string $operation) => $operation === 'create'),

                    Hidden::make('session_id'),

                    Placeholder::make('session_preview')
                        ->label('Sesi')
                        ->content(fn (Get $get) => static::getSessionLabelFromQuiz($get('quiz_id')))
                        ->columnSpan(3)
                        ->hidden(fn (string $operation) => $operation === 'edit'),

                    Grid::make(12)->schema([
                        TextInput::make('score')
                            ->label('Skor Akhir')
                            ->helperText('Skor dihitung ulang otomatis saat disimpan atau saat regrade.')
                            ->numeric()
                            ->suffix('%')
                            ->columnSpan(3),

                        DateTimePicker::make('submitted_at')
                            ->label('Waktu Submit')
                            ->helperText(fn (string $operation) => $operation === 'create' ? 'Default sekarang, boleh diubah jika butuh.' : null)
                            ->seconds(false)
                            ->native(false)
                            ->default(now())
                            ->columnSpan(4),

                        DateTimePicker::make('created_at')
                            ->label('Waktu Mulai')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(4)
                            ->hidden(fn (string $operation) => $operation === 'create'),
                    ])->columnSpan(12),
                ]),

            Section::make('Koreksi Jawaban')
                ->description(fn (string $operation) => $operation === 'create'
                    ? 'Pilih paket soal untuk menampilkan kunci & isian jawaban.'
                    : 'Koreksi manual jawaban siswa.')
                ->collapsible()
                ->schema([
                    static::answersRepeater(),
                ]),
        ];
    }

    /**
     * Komponen repeater jawaban — dipakai di create & edit.
     */
    protected static function answersRepeater(): Repeater
    {
        return Repeater::make('answers')
            ->relationship('answers')
            ->reorderable(false)
            ->addable(false)
            ->deletable(false)
            ->grid(1)
            ->default([])
            ->hidden(fn (Get $get, string $operation) => $operation === 'create' && ! $get('quiz_id'))
            ->schema([
                Hidden::make('id'),
                Hidden::make('question_id'),

                // Simpan index murni (0, 1, 2...) agar sinkron dengan controller & regrade
                Hidden::make('blank_index')
                    ->default(0),

                Grid::make(12)->schema([
                    Placeholder::make('blank_label')
                        ->label('#')
                        ->content(fn (Get $get) => 'Isian #'.((int)$get('blank_index') + 1))
                        ->extraAttributes(['class' => 'text-gray-500 font-mono text-sm'])
                        ->columnSpan(2),

                    Placeholder::make('question_preview')
                        ->label('Soal')
                        ->content(fn (Get $get) => static::questionPreview($get('question_id')))
                        ->columnSpan(4),

                    TextInput::make('answer')
                        ->label('Jawaban Siswa')
                        ->columnSpan(4),

                    // 🔍 Kunci Jawaban — sinkron dengan finalize() & regrade (array_values, 0-based)
                    Placeholder::make('correct_key')
                        ->label('Kunci Jawaban')
                        ->content(fn (Get $get) => static::correctKeyDisplay($get('question_id'), (int) $get('blank_index')))
                        ->extraAttributes(['class' => 'text-emerald-600 font-mono font-bold'])
                        ->columnSpan(2),

                    Select::make('is_correct')
                        ->label('Status')
                        ->options([
                            '1' => 'Benar',
                            '0' => 'Salah',
                        ])
                        ->selectablePlaceholder(false)
                        ->native(false)
                        ->columnSpan(2),
                ]),
            ])
            ->itemLabel(fn (array $state): ?string => static::formatAnswerLabel($state));
    }

    protected static function formatAnswerLabel(array $state): ?string
    {
        $label = isset($state['blank_index']) ? 'Isian #' . ((int) $state['blank_index'] + 1) : 'Soal';

        $q = isset($state['question_id']) ? static::getQuestionFromCache((int) $state['question_id']) : null;

        if ($q && $q->order) {
            return 'Q' . $q->order . ' - ' . $label;
        }

        return $label;
    }

    protected static function questionPreview(?int $questionId): string
    {
        if (! $questionId) {
            return '—';
        }

        $q = static::getQuestionFromCache($questionId);

        if (! $q) {
            return '?';
        }

        $text = $q->question ?? '';
        $plain = trim(Str::of($text)->stripTags()->squish()->toString());

        if ($q->type === 'fib_paragraph') {
            return 'Paragraf (FIB) — ' . Str::limit($plain, 60);
        }

        return Str::limit($plain, 60);
    }

    protected static function correctKeyDisplay(?int $questionId, int $blankIndex): string
    {
        if (! $questionId) {
            return '—';
        }

        $q = static::getQuestionFromCache($questionId);

        if (! $q) {
            return '?';
        }

        if ($q->type === 'fib_paragraph') {
            $keys = $q->fib_answer_key ?? [];

            // Sama seperti finalize(): pakai array_values → 0,1,2,...
            $normalizedKeys = array_values($keys);

            $key = $normalizedKeys[$blankIndex] ?? null;

            if (is_array($key)) {
                return implode(' / ', $key);
            }

            if (is_string($key) || is_numeric($key)) {
                return (string) $key;
            }

            return '—';
        }

        return $q->correct ?? '—';
    }

    protected static function getSessionLabelFromQuiz($quizId): string
    {
        if (! $quizId) {
            return '-';
        }

        $quiz = BasicListeningQuiz::with('session')->find($quizId);

        if (! $quiz) {
            return '-';
        }

        return 'Pert. ' . ($quiz->session?->number ?? '?') . ' - ' . ($quiz->session?->title ?? '');
    }

    protected static function syncQuizSelection($quizId, Set $set): void
    {
        if (! $quizId) {
            $set('session_id', null);
            $set('answers', []);

            return;
        }

        $quiz = BasicListeningQuiz::with(['questions' => fn ($q) => $q->orderBy('order')])->find($quizId);

        $set('session_id', $quiz?->session_id);

        if (! $quiz) {
            $set('answers', []);
            return;
        }

        foreach ($quiz->questions as $question) {
            static::$questionCache[$question->id] = $question;
        }

        $set('answers', static::buildAnswerDefaults($quiz->questions));
    }

    /**
     * Build default state untuk repeater jawaban saat create.
     */
    protected static function buildAnswerDefaults(iterable $questions): array
    {
        $items = [];

        foreach ($questions as $q) {
            if ($q->type === 'fib_paragraph') {
                $keys = array_values($q->fib_answer_key ?? []);
                $placeholders = $q->fib_placeholders ?? [];

                $blankCount = max(count($keys), count($placeholders), 1);

                for ($i = 0; $i < $blankCount; $i++) {
                    $items[] = [
                        'question_id' => $q->id,
                        'blank_index' => $i,
                        'answer'      => '',
                        'is_correct'  => null,
                    ];
                }
            } else {
                $items[] = [
                    'question_id' => $q->id,
                    'blank_index' => 0,
                    'answer'      => '',
                    'is_correct'  => null,
                ];
            }
        }

        return $items;
    }

    protected static function getQuestionFromCache(int $questionId): ?BasicListeningQuestion
    {
        if (! isset(static::$questionCache[$questionId])) {
            static::$questionCache[$questionId] = BasicListeningQuestion::find($questionId);
        }

        return static::$questionCache[$questionId] ?? null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBasicListeningAttempts::route('/'),
            'create' => Pages\CreateBasicListeningAttempt::route('/create'),
            'edit'  => Pages\EditBasicListeningAttempt::route('/{record}/edit'),
            'view'  => Pages\ViewBasicListeningAttempt::route('/{record}'),
        ];
    }
}
