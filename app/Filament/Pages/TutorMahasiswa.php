<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Prody;
use App\Models\BasicListeningGrade;
use App\Models\BasicListeningManualScore;
use App\Support\BlCompute;
use App\Support\BlGrading;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Pages\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Filament\Tables\Actions\ActionGroup;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TutorMahasiswaTemplateExport;
use App\Exports\TutorMahasiswaDailyExport;
use App\Filament\Pages\TutorMahasiswaBulkInput;

class TutorMahasiswa extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield {
        canAccess as protected canAccessViaShield;
    }

    protected static ?string $navigationIcon   = 'heroicon-o-users';
    protected static ?string $navigationLabel  = 'Mahasiswa Binaan';
    protected static ?string $title            = 'Mahasiswa Binaan';
    protected static ?string $navigationGroup  = 'Basic Listening';
    protected static ?int    $navigationSort   = 2;

    protected static string $view = 'filament.pages.tutor-mahasiswa';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return static::canAccessViaShield() && ($user?->hasRole('Admin') || $user?->hasRole('tutor'));
    }

    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Pages\Widgets\TutorMahasiswaSummaryStats::class,
        ];
    }
    
    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            // === QUERY UTAMA (OPTIMIZED) ===
            // Kita load semua relasi di awal biar tidak N+1 Query
            ->query($this->baseQuery($user))
            ->defaultSort('srn', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('srn')
                    ->label('NPM')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('prody.name')
                    ->label('Prodi')
                    ->sortable()
                    ->limit(15)
                    ->badge()
                    ->tooltip(fn ($state) => $state),

                Tables\Columns\TextColumn::make('nomor_grup_bl')
                    ->label('Grup')
                    ->badge()
                    ->alignCenter()
                    ->width('5rem')
                    ->formatStateUsing(fn ($state) => $state ? "{$state}" : '—'),

                // Hitung status attempt dari collection (Memory)
                Tables\Columns\TextColumn::make('attempt_status')
                    ->label('Daily Online')
                    ->badge()
                    ->state(function (User $record) {
                        $count = $record->basicListeningAttempts
                            ->whereNotNull('submitted_at')
                            ->unique('session_id')
                            ->count();

                        if ($count === 0) return 'Belum attempt';
                        return $count . ' ' . Str::plural('daily', $count);
                    })
                    ->colors([
                        'gray'    => fn ($state) => str_starts_with(strtolower($state), 'belum'),
                        'success' => fn ($state) => ((int) (explode(' ', $state)[0] ?? 0)) === 5,
                        'warning' => fn ($state) => (($n = (int) (explode(' ', $state)[0] ?? 0)) > 0 && $n < 5),
                    ]),

                Tables\Columns\TextColumn::make('attendance_display')
                    ->label('Attd.')
                    ->alignCenter()
                    ->state(fn (User $record) => $record->basicListeningGrade?->attendance ?? '—'),

                // Hitung Rata-rata Daily di Memory
                Tables\Columns\TextColumn::make('daily_display')
                    ->label('Daily')
                    ->alignCenter()
                    ->state(fn (User $record) => $this->calculateDailyAvgInMemory($record)),

                Tables\Columns\TextColumn::make('final_test_display')
                    ->label('Final')
                    ->alignCenter()
                    ->state(function (User $record) {
                        $gradeFinal = $record->basicListeningGrade?->final_test;
                        if (is_numeric($gradeFinal)) {
                            return $gradeFinal;
                        }
                        return $this->getFinalTestFromAttempt($record) ?? '—';
                    }),

                // Hitung Total Score
                Tables\Columns\TextColumn::make('final_numeric')
                    ->label('Total')
                    ->alignCenter()
                    ->state(function (User $record) {
                        $g = $record->basicListeningGrade;
                        if ($g?->final_numeric_cached !== null) {
                            return number_format($g->final_numeric_cached, 2);
                        }
                        return $this->calculateFinalScoreInMemory($record);
                    }),

                Tables\Columns\TextColumn::make('final_letter')
                    ->label('Grade')
                    ->badge()
                    ->alignCenter()
                    ->state(function (User $record) {
                        $g = $record->basicListeningGrade;
                        if ($g?->final_letter_cached) return $g->final_letter_cached;
                        $score = $this->calculateFinalScoreInMemory($record, true); 
                        return is_numeric($score) ? BlGrading::letter((float)$score) : '—';
                    })
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['A', 'A-', 'B+', 'B'], true),
                        'warning' => fn ($state) => in_array($state, ['B-', 'C+', 'C'], true),
                        'danger'  => fn ($state) => in_array($state, ['C-', 'D', 'E'], true),
                    ]),
            ])
            ->paginationPageOptions([5, 10, 25, 50])
            ->filters([
                Filter::make('angkatan')
                    ->label('Angkatan (Prefix NPM)')
                    ->form([
                        Forms\Components\TextInput::make('prefix')
                            ->placeholder('mis. 25')
                            ->default(\App\Models\SiteSetting::get('bl_active_batch', now()->format('y')))
                            ->maxLength(20)
                            ->helperText('Diambil dari Pengaturan Situs.')
                            ->disabled(fn () => auth()->user()?->hasRole('tutor') && !auth()->user()?->hasRole('Admin')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $prefix = trim((string) ($data['prefix'] ?? ''));
                        if ($prefix !== '') {
                            $batches = array_map('trim', explode(',', $prefix));
                            $query->where(function ($q) use ($batches) {
                                foreach ($batches as $batch) {
                                    $q->orWhere('srn', 'like', $batch . '%');
                                }
                            });
                        }
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $prefix = trim((string) ($data['prefix'] ?? ''));
                        return $prefix !== '' ? 'Angkatan: ' . $prefix : null;
                    }),

                Tables\Filters\SelectFilter::make('prody_id')
                    ->label('Prodi')
                    ->options(fn() => $this->getProdyOptions($user))
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $query->where('prody_id', $data['value']);
                        }
                    }),

                Tables\Filters\SelectFilter::make('nomor_grup_bl')
                    ->label('Grup BL')
                    ->options(function () {
                        return User::query()
                            ->whereNotNull('nomor_grup_bl')
                            ->distinct()
                            ->orderBy('nomor_grup_bl')
                            ->pluck('nomor_grup_bl', 'nomor_grup_bl')
                            ->toArray();
                    })
                    ->searchable(),
            ])
            ->actions([
                ActionGroup::make([
                    // 1. ATUR GRUP
                    Tables\Actions\Action::make('atur_grup_bl')
                        ->label('Atur Grup')
                        ->icon('heroicon-o-user-group')
                        ->visible(fn (User $record) => $this->canManageStudent($record))
                        ->form([
                            Forms\Components\Select::make('nomor_grup_bl')
                                ->label('Pilih Grup')
                                ->options(array_combine(range(1, 4), range(1, 4)))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (User $record, array $data) {
                            $record->update(['nomor_grup_bl' => $data['nomor_grup_bl']]);
                            Notification::make()->title('Grup diupdate')->success()->send();
                        }),

                    // 2. EDIT DAILY (Single) - Gunakan schema helper agar sama
                    Tables\Actions\Action::make('edit_daily')
                        ->label('Edit Daily')
                        ->icon('heroicon-o-pencil')
                        ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'tutor']))
                        ->form(fn (User $record) => $this->getDailyScoreFormSchema($record))
                        ->action(fn (User $record, array $data) => $this->saveDailyScores($record, $data))
                        ->slideOver(),

                    // 3. EDIT FINAL (Single)
                    Tables\Actions\Action::make('edit_scores')
                        ->label('Insert Final')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn () => auth()->user()?->hasAnyRole(['Admin', 'tutor']))
                        ->form(fn (User $record) => [
                            Forms\Components\Placeholder::make('mhs')
                                ->label('Mahasiswa')
                                ->content($record->srn . ' — ' . $record->name),
                            TextInput::make('attendance')->label('Attendance')->numeric()->maxValue(100)->default($record->basicListeningGrade?->attendance),
                            TextInput::make('final_test')
                                ->label('Final Test')
                                ->numeric()
                                ->maxValue(100)
                                ->default($record->basicListeningGrade?->final_test ?? $this->getFinalTestFromAttempt($record))
                                ->placeholder($this->getFinalTestFromAttempt($record))
                                ->helperText(function () use ($record) {
                                    $attempt = $this->getFinalTestFromAttempt($record);
                                    return is_numeric($attempt)
                                        ? "Nilai Asli Final Exam: {$attempt}"
                                        : 'Belum mengerjakan Final Exam di Web';
                                }),
                        ])
                        ->action(function (User $record, array $data) {
                            $grade = BasicListeningGrade::firstOrCreate(['user_id' => $record->id, 'user_year' => $record->year]);
                            $grade->attendance = $data['attendance'];
                            $grade->final_test = $data['final_test'];
                            $grade->save();
                            Notification::make()->title('Nilai diperbarui')->success()->send();
                        })
                        ->slideOver(),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-cog-6-tooth'),
            ])
            ->bulkActions([
                // === BULK 1: EDIT DAILY ===
                Tables\Actions\BulkAction::make('bulk_edit_daily')
                    ->label('Edit Daily (Bulk)')
                    ->icon('heroicon-o-pencil')
                    ->form(function (Collection $records) {
                        $records->load(['basicListeningManualScores', 'basicListeningAttempts' => fn($q) => $q->whereNotNull('submitted_at')]);
                        
                        $studentsData = $records->map(function($u) {
                           $attempts = $u->basicListeningAttempts->sortByDesc('submitted_at')->groupBy('session_id')->map->first();
                           $manuals = $u->basicListeningManualScores->pluck('score', 'meeting');
                           
                           $row = [
                               'user_id' => $u->id,
                               'name' => $u->name,
                               'srn' => $u->srn,
                           ];

                           foreach(range(1,5) as $m) {
                               $row["m{$m}"] = $manuals[$m] ?? null;
                               $row["attempt_score_{$m}"] = $attempts[$m]?->score ?? null;
                               $row["attempt_{$m}"] = isset($attempts[$m]); 
                           }
                           return $row;
                        })->toArray();

                        return [
                            Forms\Components\Repeater::make('students')
                                ->label('Input Nilai Harian')
                                ->schema([
                                    Forms\Components\Hidden::make('user_id'),
                                    Forms\Components\Hidden::make('attempt_score_1'),
                                    Forms\Components\Hidden::make('attempt_score_2'),
                                    Forms\Components\Hidden::make('attempt_score_3'),
                                    Forms\Components\Hidden::make('attempt_score_4'),
                                    Forms\Components\Hidden::make('attempt_score_5'),
                                    Forms\Components\Grid::make(5)->schema($this->getBulkGridSchema())
                                ])
                                ->addable(false)->deletable(false)->reorderable(false)
                                ->itemLabel(fn($state) => $state['name'] . ' (' . $state['srn'] . ')')
                                ->default($studentsData)
                        ];
                    })
                    ->action(function (Collection $records, array $data) {
                        $count = 0;
                        foreach ($data['students'] as $item) {
                            $user = User::find($item['user_id']);
                            if(!$user) continue;
                            $this->saveDailyScores($user, $item, true); 
                            $count++;
                        }
                        Notification::make()->title("$count mahasiswa diperbarui")->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                // === BULK 2: SET GRUP BL ===
                Tables\Actions\BulkAction::make('bulk_set_group_bl')
                    ->label('Atur Grup BL')
                    ->icon('heroicon-o-user-group')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('nomor_grup_bl')
                            ->label('Pilih Grup')
                            ->options(array_combine(range(1, 4), range(1, 4)))
                            ->searchable()
                            ->required(),
                        Forms\Components\Toggle::make('only_empty')
                            ->label('Hanya yang belum punya grup')
                            ->default(true),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $targetGroup = $data['nomor_grup_bl'];
                        $onlyEmpty = $data['only_empty'];
                        $count = 0;

                        foreach ($records as $record) {
                            if ($onlyEmpty && filled($record->nomor_grup_bl)) continue;
                            $record->update(['nomor_grup_bl' => $targetGroup]);
                            $count++;
                        }
                        Notification::make()->title("$count mahasiswa diatur ke Grup $targetGroup")->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                // === BULK 3: INSERT FINAL (FIXED: variable $s -> $state) ===
                Tables\Actions\BulkAction::make('bulk_edit_scores')
                    ->label('Insert Final')
                    ->icon('heroicon-o-pencil-square')
                    ->form(function (Collection $records) {
                        $records->load([
                            'basicListeningGrade',
                            'basicListeningAttempts' => fn ($q) => $q->where('session_id', 6)->whereNotNull('submitted_at'),
                        ]);
                        $data = $records->map(fn($u) => [
                            'user_id' => $u->id,
                            'name' => $u->name,
                            'srn' => $u->srn,
                            'attendance' => $u->basicListeningGrade?->attendance,
                            'final_test' => $u->basicListeningGrade?->final_test ?? $this->getFinalTestFromAttempt($u),
                        ])->toArray();

                        return [
                            Forms\Components\Repeater::make('students')
                                ->label('Input Nilai Akhir')
                                ->schema([
                                    Forms\Components\Hidden::make('user_id'),
                                    Forms\Components\Grid::make(2)->schema([
                                        TextInput::make('attendance')->label('Attendance')->numeric()->maxValue(100),
                                        TextInput::make('final_test')->label('Final Test')->numeric()->maxValue(100),
                                    ])
                                ])
                                ->addable(false)->deletable(false)
                                ->itemLabel(fn($state) => ($state['name'] ?? '') . ' (' . ($state['srn'] ?? '') . ')') // <--- PERBAIKAN DI SINI
                                ->default($data)
                        ];
                    })
                    ->action(function (array $data) {
                        $count = 0;
                        foreach ($data['students'] as $item) {
                            $user = User::find($item['user_id']);
                            if(!$user) continue;
                            
                            $g = BasicListeningGrade::firstOrCreate(['user_id' => $user->id, 'user_year' => $user->year]);
                            $g->attendance = $item['attendance'];
                            $g->final_test = $item['final_test'];
                            $g->save();
                            $count++;
                        }
                        Notification::make()->title("$count nilai akhir disimpan")->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                // === BULK 4: RESET NILAI ===
                Tables\Actions\BulkAction::make('bulk_reset_scores')
                    ->label('Reset Nilai')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\CheckboxList::make('items')
                            ->options([
                                'daily' => 'Nilai Harian (Manual Override)',
                                'final' => 'Attendance & Final Test',
                            ])
                            ->required()
                    ])
                    ->action(function (Collection $records, array $data) {
                        $items = $data['items'];
                        foreach ($records as $record) {
                            if (in_array('daily', $items)) {
                                BasicListeningManualScore::where('user_id', $record->id)->delete();
                            }
                            if (in_array('final', $items)) {
                                if ($record->basicListeningGrade) {
                                    $record->basicListeningGrade->update(['attendance' => null, 'final_test' => null]);
                                }
                            }
                        }
                        Notification::make()->title('Reset berhasil')->success()->send();
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Select::make('prody_id')
                            ->label('Prodi')
                            ->options(Prody::pluck('name','id'))
                            ->searchable()
                            ->preload(),
                        Select::make('nomor_grup_bl')
                            ->label('Grup')
                            ->options(function () use ($user) {
                                $query = User::query()->whereNotNull('nomor_grup_bl');

                                // Batasi ke prodi tutor agar pilihannya relevan
                                if ($user?->hasRole('tutor')) {
                                    $ids = method_exists($user, 'assignedProdyIds') ? (array) $user->assignedProdyIds() : [];
                                    if (!empty($ids)) {
                                        $query->whereIn('prody_id', $ids);
                                    }
                                }

                                return $query
                                    ->distinct()
                                    ->orderBy('nomor_grup_bl')
                                    ->pluck('nomor_grup_bl', 'nomor_grup_bl')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),

                        Select::make('mode')
                            ->label('Jenis Export')
                            ->options([
                                'summary' => 'Rekap (Avg + Grade)',
                                'daily'   => 'Daily S1–S5',
                            ])
                            ->default('summary')
                            ->required(),
                        
                        // --- FITUR BARU: PILIH URUTAN ---
                        Select::make('sort_direction')
                            ->label('Urutan NPM (SRN)')
                            ->options([
                                'asc'  => 'Kecil ke Besar (Ascending)', // 25001 -> 25002
                                'desc' => 'Besar ke Kecil (Descending)', // 25002 -> 25001
                            ])
                            ->default('desc') // Default besar ke kecil
                            ->required()
                            ->visible(fn ($get) => ($get('mode') ?? 'summary') === 'summary'),
                        // --------------------------------

                        Toggle::make('only_complete')
                            ->label('Hanya Nilai Lengkap')
                            ->default(true)
                            ->visible(fn ($get) => ($get('mode') ?? 'summary') === 'summary'),
                    ])
                    ->action(function (array $data) {
                        // 1. Query Dasar
                        $query = $this->baseQuery(auth()->user(), limitYear: false); 

                        $prodyId = $data['prody_id'] ?? null;
                        $group   = $data['nomor_grup_bl'] ?? null;
                        $prodyName = null;
                        if (filled($prodyId)) {
                            $prodyName = Prody::find($prodyId)?->name;
                        }

                        if (filled($prodyId)) {
                            $query->where('prody_id', $prodyId);
                        }
                        if (filled($group)) {
                            $query->where('nomor_grup_bl', $group);
                        }

                        $users = $query->get();

                        $mode = $data['mode'] ?? 'summary';

                        // MODE SUMMARY (nilai + grade)
                        if ($mode === 'summary') {
                            // 2. Filter Kelengkapan (PHP Side)
                            if (!empty($data['only_complete'])) {
                                $users = $users->filter(function ($u) {
                                    $g = $u->basicListeningGrade;
                                    if (!is_numeric($g?->attendance) || !is_numeric($g?->final_test)) return false; 
                                    
                                    $manuals = $u->basicListeningManualScores->pluck('score', 'meeting');
                                    $attempts = $u->basicListeningAttempts
                                        ->whereNotNull('submitted_at')
                                        ->pluck('score', 'session_id');

                                    foreach(range(1,5) as $m) {
                                        $score = $manuals[$m] ?? $attempts[$m] ?? null;
                                        if (!is_numeric($score)) return false;
                                    }
                                    return true;
                                });
                            }

                            // 3. SORTING DINAMIS (Sesuai Pilihan User)
                            $direction = $data['sort_direction'] ?? 'asc';

                            $sortedUsers = $direction === 'desc'
                                ? $users->sortByDesc(fn ($u) => (int) $u->srn, SORT_REGULAR)->values()
                                : $users->sortBy(fn ($u) => (int) $u->srn, SORT_REGULAR)->values();
                            
                            return Excel::download(
                                new TutorMahasiswaTemplateExport($sortedUsers, $group, $prodyName), 
                                'Export_Nilai_BL.xlsx'
                            );
                        }

                        // MODE DAILY (S1–S5)
                        $direction = $data['sort_direction'] ?? 'desc';

                        return Excel::download(
                            new TutorMahasiswaDailyExport(
                                $direction === 'desc'
                                    ? $users->sortByDesc(fn ($u) => (int) $u->srn, SORT_REGULAR)->values()
                                    : $users->sortBy(fn ($u) => (int) $u->srn, SORT_REGULAR)->values(),
                                $group,
                                $prodyName
                            ),
                            'Export_Daily_Scores.xlsx'
                        );
                    })
            ])
            ->emptyStateHeading('Belum ada data');
    }

    // --- HELPER SCHEMA UNTUK BULK EDIT AGAR CANTIK ---
    private function getBulkGridSchema(): array 
    {
        $fields = [];
        foreach (range(1, 5) as $m) {
            $fields[] = TextInput::make("m{$m}")
                ->label("Meeting {$m}")
                ->numeric()->maxValue(100)
                ->placeholder(function ($get) use ($m) {
                    // Ambil nilai quiz dari hidden field di row yang sama
                    $quiz = $get("attempt_score_{$m}");
                    return is_numeric($quiz) ? "Quiz: {$quiz}" : '-';
                })
                ->helperText(function ($get) use ($m) {
                    $quiz = $get("attempt_score_{$m}");
                    return is_numeric($quiz) ? "Quiz: {$quiz}" : '';
                });
        }
        return $fields;
    }

    // --- QUERY UTAMA ---
    protected function baseQuery(?User $user, bool $limitYear = true): Builder
    {
        $query = User::query()
            ->with([
                'prody', 
                'basicListeningGrade', 
                'basicListeningManualScores',
                'basicListeningAttempts' => function($q) {
                    $q->select(['id', 'user_id', 'session_id', 'score', 'submitted_at', 'updated_at'])
                      ->whereNotNull('submitted_at');
                }
            ])
            ->whereNotNull('srn');

        // Admin: tampilkan semua tapi tidak ada limitasi prody
        if ($user?->hasRole('Admin')) {
            return $query;
        }

        // Tutor: batasi berdasarkan prody yang ditugaskan
        if ($user?->hasRole('tutor')) {
            $ids = method_exists($user, 'assignedProdyIds') ? $user->assignedProdyIds() : [];
            if (empty($ids)) return $query->whereRaw('1=0');
            return $query->whereIn('prody_id', $ids);
        }

        return $query->whereRaw('1=0');
    }

    // --- HELPERS LAIN (Memory Calculation & Single Edit Form) ---
    private function calculateDailyAvgInMemory(User $record): string
    {
        $manuals = $record->basicListeningManualScores->pluck('score', 'meeting');
        $attempts = $record->basicListeningAttempts->groupBy('session_id')->map(fn($g) => $g->sortByDesc('score')->first()->score);
        $scores = [];
        for ($i = 1; $i <= 5; $i++) {
            $val = $manuals[$i] ?? $attempts[$i] ?? null;
            if (is_numeric($val)) $scores[] = $val;
        }
        return empty($scores) ? '—' : number_format(array_sum($scores) / 5, 2);
    }

    private function calculateFinalScoreInMemory(User $record, bool $returnFloat = false): string|float|null
    {
        $g = $record->basicListeningGrade;
        $att = is_numeric($g?->attendance) ? (float)$g->attendance : null;
        $fin = is_numeric($g?->final_test) ? (float)$g->final_test : null;
        $dly = is_numeric($val = $this->calculateDailyAvgInMemory($record)) ? (float)$val : null;

        // Attendance wajib ada; kalau belum, jangan tampilkan nilai/grade.
        if ($att === null) {
            return $returnFloat ? null : '—';
        }

        // Komponen lain wajib numeric agar grade valid.
        if ($fin === null || $dly === null) {
            return $returnFloat ? null : '—';
        }

        $final = array_sum([$att, $dly, $fin]) / 3;
        return $returnFloat ? $final : number_format($final, 2);
    }

    private function getDailyScoreFormSchema(User $record): array
    {
        // Schema untuk Single Edit Action
        $attempts = $record->basicListeningAttempts->groupBy('session_id')->map(fn($g) => $g->sortByDesc('submitted_at')->first());
        $manuals = $record->basicListeningManualScores->pluck('score', 'meeting');
        $fields = [];
        foreach (range(1, 5) as $m) {
            $quizScore = $attempts[$m]?->score ?? null;
            $manualScore = $manuals[$m] ?? null;
            $helperText = "Quiz: " . ($quizScore ?? '-');
            if (is_numeric($manualScore)) $helperText .= " (Override: $manualScore)";
            $fields[] = TextInput::make("m{$m}")
                ->label("Meeting {$m}")
                ->numeric()->maxValue(100)
                ->placeholder($quizScore)
                ->helperText($helperText)
                ->default($manualScore);
        }
        return [
            Forms\Components\Placeholder::make('info')->content("Input nilai manual menimpa nilai Quiz.")->columnSpanFull(),
            Forms\Components\Grid::make(5)->schema($fields)
        ];
    }

    private function saveDailyScores(User $user, array $data, bool $bulk = false)
    {
        foreach (range(1, 5) as $m) {
            if (!array_key_exists("m{$m}", $data)) continue;
            $val = $data["m{$m}"];
            $row = BasicListeningManualScore::firstOrCreate(['user_id' => $user->id, 'user_year' => $user->year, 'meeting' => $m]);
            $row->score = ($val === '' || $val === null) ? null : (int) $val;
            $row->save();
        }
        $grade = BasicListeningGrade::firstOrCreate(['user_id' => $user->id, 'user_year' => $user->year]);
        $grade->touch(); 
        if (!$bulk) Notification::make()->title('Nilai harian tersimpan')->success()->send();
    }

    private function getProdyOptions($user): array
    {
        if ($user?->hasRole('tutor')) {
            $ids = method_exists($user, 'assignedProdyIds') ? (array) $user->assignedProdyIds() : [];
            return empty($ids) ? [] : Prody::whereIn('id', $ids)->orderBy('name')->pluck('name', 'id')->toArray();
        }
        return Prody::orderBy('name')->pluck('name', 'id')->toArray();
    }
    
    private function canManageStudent(User $student): bool
    {
        $user = auth()->user();
        if ($user->hasRole('Admin')) return true;
        if ($user->hasRole('tutor')) {
             $ids = method_exists($user, 'assignedProdyIds') ? (array) $user->assignedProdyIds() : [];
             return in_array($student->prody_id, $ids);
        }
        return false;
    }

    protected function getActions(): array
    {
        return [
            Action::make('dashboard')
                ->label('Kembali ke Dasbor')
                ->url(route('filament.admin.pages.2') ?? '#')
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulk_input_page')
                ->label('Input Nilai')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->url(TutorMahasiswaBulkInput::getUrl()),
        ];
    }

    private function getFinalTestFromAttempt(User $record): ?float
    {
        // Ambil skor attempt terbaru untuk session 6 (Final Exam)
        $attempt = $record->basicListeningAttempts
            ->where('session_id', 6)
            ->sortByDesc('submitted_at')
            ->first();

        return is_numeric($attempt?->score) ? (float) $attempt->score : null;
    }
}
