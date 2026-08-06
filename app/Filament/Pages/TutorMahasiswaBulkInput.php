<?php

namespace App\Filament\Pages;

use App\Models\BasicListeningGrade;
use App\Models\BasicListeningManualScore;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use App\Support\BlGrading;

class TutorMahasiswaBulkInput extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;
    use HasPageShield {
        canAccess as protected canAccessViaShield;
    }

    protected static ?string $navigationLabel = 'Input Nilai Mahasiswa';
    protected static ?string $navigationIcon  = 'heroicon-o-document-plus';
    protected static ?string $title           = 'Input Nilai Mahasiswa';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.tutor-mahasiswa-bulk-input';

    public bool $showDownloadButton = false;
    public bool $isProcessing = false;
    public int $progress = 0;
    public string $progressMessage = '';
    
    public array $data = [
        'student_ids' => [],
        'students'    => [],
    ];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return static::canAccessViaShield() && ($user?->hasAnyRole(['Admin', 'tutor']) ?? false);
    }

    public function mount(): void
    {
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Cari & Pilih Mahasiswa')
                    ->description('Cari berdasarkan SRN atau nama, pilih beberapa sekaligus lalu data otomatis ditarik ke tabel input.')
                    ->schema([
                        TextInput::make('range_input')
                            ->label('Bulk Add by NPM Range')
                            ->placeholder('contoh: 25430077-25430116')
                            ->helperText('Format: NPM_AWAL-NPM_AKHIR → Klik ikon + untuk preview')
                            ->columnSpan(1)
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('add_range')
                                    ->icon('heroicon-o-plus-circle')
                                    ->modalHeading('Preview Mahasiswa')
                                    ->modalDescription(fn ($state) => $this->getPreviewDescription($state))
                                    ->modalContent(fn ($state) => view('filament.components.range-preview', $this->getPreviewData($state)))
                                    ->modalSubmitActionLabel('Tambahkan Semua')
                                    ->modalCancelActionLabel('Batal')
                                    ->action(function ($state, callable $get, callable $set) {
                                        $this->addStudentsByRange($state, $get, $set);
                                    })
                            ),
                        Select::make('student_ids')
                            ->label('Mahasiswa')
                            ->multiple()
                            ->searchable()
                            ->placeholder('Ketik SRN atau nama...')
                            ->helperText('Pilih beberapa mahasiswa sekaligus untuk isi nilai bersama.')
                            ->getSearchResultsUsing(fn (string $search) => $this->searchStudents($search))
                            ->getOptionLabelsUsing(fn (array $values) => $this->getStudentLabels(collect($values)))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('students', $this->buildStudentsState((array) $state));
                                $this->showDownloadButton = false;
                            }),
                    ])
                    ->columns(1),

                Section::make('Input Nilai')
                    ->description('Placeholder menampilkan skor attempt terbaru. Kosongkan untuk memakai skor attempt.')
                    ->schema([
                        Repeater::make('students')
                            ->label('Nilai per Mahasiswa')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(1)
                            ->itemLabel(fn (array $state): string => ($state['srn'] ?? '-') . ' — ' . ($state['name'] ?? 'Mahasiswa') . (isset($state['prody_name']) ? ' (' . $state['prody_name'] . ')' : ''))
                            ->visible(fn (callable $get) => filled($get('students')))
                            ->schema([
                                Forms\Components\Hidden::make('user_id'),
                                Forms\Components\Hidden::make('final_attempt_score'),
                                Forms\Components\Hidden::make('attempt_score_1'),
                                Forms\Components\Hidden::make('attempt_score_2'),
                                Forms\Components\Hidden::make('attempt_score_3'),
                                Forms\Components\Hidden::make('attempt_score_4'),
                                Forms\Components\Hidden::make('attempt_score_5'),

                                Grid::make(5)->schema([
                                    Select::make('attendance_count')
                                        ->label('Kehadiran')
                                        ->options([
                                            6 => '6 dari 6 (100)',
                                            5 => '5 dari 6 (83.33)',
                                            4 => '4 dari 6 (66.67)',
                                            3 => '3 dari 6 (50)',
                                            2 => '2 dari 6 (33.33)',
                                            1 => '1 dari 6 (16.67)',
                                            0 => '0 dari 6 (0)',
                                        ])
                                        ->placeholder('Pilih kehadiran...')
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state !== null) {
                                                $score = round(((int) $state / 6) * 100, 2);
                                                $set('attendance', $score);
                                            }
                                        }),
                                    Forms\Components\Hidden::make('attendance'),
                                    TextInput::make('final_test')
                                        ->label('Final Exam')
                                        ->numeric()
                                        ->maxValue(100)
                                        ->live(debounce: 500)
                                        ->placeholder(fn ($get) => $get('final_attempt_score'))
                                        ->helperText(fn ($get) => $get('final_attempt_score') ? 'Attempt: ' . $get('final_attempt_score') : ''),
                                    Forms\Components\Placeholder::make('total_preview')
                                        ->label('Total Score')
                                        ->content(function ($get) {
                                            $total = $this->calculateTotal($get);
                                            return $total !== null 
                                                ? number_format($total, 2) 
                                                : '—';
                                        }),
                                    Forms\Components\Placeholder::make('grade_preview')
                                        ->label('Grade')
                                        ->content(function ($get) {
                                            $total = $this->calculateTotal($get);
                                            if ($total === null) return '—';
                                            $letter = BlGrading::letter($total);
                                            
                                            $bgColor = match(true) {
                                                in_array($letter, ['A', 'A-', 'B+', 'B']) => '#10b981',
                                                in_array($letter, ['B-', 'C+', 'C']) => '#f59e0b',
                                                default => '#ef4444',
                                            };
                                            
                                            return new HtmlString(
                                                '<span style="display: inline-flex; align-items: center; justify-content: center; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 700; border-radius: 9999px; color: white; background-color: ' . $bgColor . ';">' . $letter . '</span>'
                                            );
                                        }),
                                    Forms\Components\Placeholder::make('status_preview')
                                        ->label('Status')
                                        ->content(function ($get) {
                                            $total = $this->calculateTotal($get);
                                            if ($total === null) return '—';
                                            
                                            $isPass = $total >= 55;
                                            $label = $isPass ? 'LULUS' : 'TIDAK LULUS';
                                            $bgColor = $isPass ? '#10b981' : '#ef4444';
                                            
                                            return new HtmlString(
                                                '<span style="display: inline-flex; align-items: center; justify-content: center; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 700; border-radius: 9999px; color: white; background-color: ' . $bgColor . ';">' . $label . '</span>'
                                            );
                                        }),
                                ]),

                                Grid::make(5)->schema($this->dailyInputsSchema()),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $students = $state['students'] ?? [];
        
        if (empty($students)) {
            Notification::make()
                ->title('Tidak ada data')
                ->body('Pilih mahasiswa terlebih dahulu.')
                ->warning()
                ->send();
            return;
        }

        // Reset & start progress
        $this->isProcessing = true;
        $this->progress = 0;
        $this->progressMessage = 'Memulai proses...';
        
        $total = count($students);
        $saved = 0;

        try {
            // Wrap dalam transaction untuk data integrity
            \Illuminate\Support\Facades\DB::transaction(function () use ($students, $total, &$saved) {
                foreach ($students as $index => $row) {
                    $this->saveStudentRow($row);
                    $saved++;
                    $this->progress = (int) round(($saved / $total) * 100);
                    $this->progressMessage = "Menyimpan... ({$saved}/{$total})";
                }
            });
            
            $this->progress = 100;
            $this->progressMessage = 'Selesai!';
            
            $downloadUrl = $this->buildDownloadUrl($students);
            $this->showDownloadButton = (bool) $downloadUrl;

            Notification::make()
                ->title("{$total} mahasiswa berhasil disimpan")
                ->body('Ingin unduh Excel untuk data ini?')
                ->success()
                ->actions([
                    NotificationAction::make('download_excel')
                        ->label('Download Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url($downloadUrl)
                        ->openUrlInNewTab()
                        ->visible((bool) $downloadUrl),
                ])
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menyimpan')
                ->body('Terjadi error: ' . $e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isProcessing = false;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Nilai')
                ->submit('save')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->data['students'] ?? []);
    }

    public function getDownloadUrlProperty(): ?string
    {
        return $this->buildDownloadUrl($this->data['students'] ?? []);
    }

    private function buildDownloadUrl(array $students): ?string
    {
        $ids = collect($students)->pluck('user_id')->filter()->unique()->values()->all();
        if (empty($ids)) {
            return null;
        }

        return URL::temporarySignedRoute(
            'bl.tutor-mahasiswa.export',
            now()->addMinutes(10),
            [
                'ids' => implode(',', $ids),
            ]
        );
    }

    /**
     * Get preview description for modal
     */
    private function getPreviewDescription(?string $rangeInput): string
    {
        if (empty($rangeInput)) {
            return 'Masukkan range NPM terlebih dahulu';
        }
        return "Range: {$rangeInput}";
    }

    /**
     * Get preview data for modal
     */
    private function getPreviewData(?string $rangeInput): array
    {
        if (empty($rangeInput)) {
            return ['students' => [], 'skipped' => [], 'error' => 'Range kosong'];
        }

        $parts = explode('-', $rangeInput);
        if (count($parts) !== 2) {
            return ['students' => [], 'skipped' => [], 'error' => 'Format salah. Gunakan: NPM_AWAL-NPM_AKHIR'];
        }

        $start = trim($parts[0]);
        $end = trim($parts[1]);

        if ((int) $start > (int) $end) {
            [$start, $end] = [$end, $start];
        }

        $user = auth()->user();

        // Generate expected NPMs
        $expectedNpms = [];
        for ($i = (int) $start; $i <= (int) $end; $i++) {
            $expectedNpms[] = (string) $i;
        }

        // Build query
        $query = User::query()
            ->whereNotNull('srn')
            ->where('srn', '>=', $start)
            ->where('srn', '<=', $end);

        // Filter by tutor's prodi
        if (!$user?->hasRole('Admin')) {
            $prodyIds = method_exists($user, 'assignedProdyIds') 
                ? (array) $user->assignedProdyIds() 
                : [];
            if (!empty($prodyIds)) {
                $query->whereIn('prody_id', $prodyIds);
            }
        }

        $foundUsers = $query->orderByDesc('srn')->get(['id', 'srn', 'name']);
        $foundNpms = $foundUsers->pluck('srn')->toArray();
        $skippedNpms = array_diff($expectedNpms, $foundNpms);

        return [
            'students' => $foundUsers->toArray(),
            'skipped' => array_values($skippedNpms),
            'error' => null,
        ];
    }

    /**
     * Add students by NPM range (e.g., "25430077-25430116")
     */
    private function addStudentsByRange(?string $rangeInput, callable $get, callable $set): void
    {
        if (empty($rangeInput)) {
            Notification::make()
                ->title('Range kosong')
                ->body('Masukkan range NPM, contoh: 25430077-25430116')
                ->warning()
                ->send();
            return;
        }

        // Parse range
        $parts = explode('-', $rangeInput);
        if (count($parts) !== 2) {
            Notification::make()
                ->title('Format salah')
                ->body('Gunakan format: NPM_AWAL-NPM_AKHIR (contoh: 25430077-25430116)')
                ->danger()
                ->send();
            return;
        }

        $start = trim($parts[0]);
        $end = trim($parts[1]);

        // Ensure start is smaller than end
        if ((int) $start > (int) $end) {
            [$start, $end] = [$end, $start];
        }

        $user = auth()->user();

        // Generate all expected NPMs in range
        $expectedNpms = [];
        for ($i = (int) $start; $i <= (int) $end; $i++) {
            $expectedNpms[] = (string) $i;
        }

        // Build query
        $query = User::query()
            ->whereNotNull('srn')
            ->where('srn', '>=', $start)
            ->where('srn', '<=', $end);

        // Filter by tutor's prodi if not admin
        if (!$user?->hasRole('Admin')) {
            $prodyIds = method_exists($user, 'assignedProdyIds') 
                ? (array) $user->assignedProdyIds() 
                : [];
            
            if (empty($prodyIds)) {
                Notification::make()
                    ->title('Tidak ada prodi')
                    ->body('Anda tidak memiliki prodi yang ditugaskan.')
                    ->danger()
                    ->send();
                return;
            }
            
            $query->whereIn('prody_id', $prodyIds);
        }

        $foundUsers = $query->get(['id', 'srn']);
        $foundIds = $foundUsers->pluck('id')->toArray();
        $foundNpms = $foundUsers->pluck('srn')->toArray();

        if (empty($foundIds)) {
            Notification::make()
                ->title('Tidak ditemukan')
                ->body("Tidak ada mahasiswa dengan NPM antara {$start} s.d. {$end}")
                ->warning()
                ->send();
            return;
        }

        // Find skipped NPMs
        $skippedNpms = array_diff($expectedNpms, $foundNpms);

        // Merge with existing selection
        $existingIds = (array) $get('student_ids');
        $mergedIds = array_unique(array_merge($existingIds, $foundIds));

        // Update state
        $set('student_ids', array_values($mergedIds));
        $set('students', $this->buildStudentsState($mergedIds));
        $set('range_input', ''); // Clear input

        $count = count($foundIds);
        
        // Build notification body
        $body = "NPM {$start} s.d. {$end}";
        if (!empty($skippedNpms)) {
            $skippedCount = count($skippedNpms);
            $skippedList = implode(', ', array_slice($skippedNpms, 0, 10)); // Max 10 NPM
            if ($skippedCount > 10) {
                $skippedList .= ", ... (+" . ($skippedCount - 10) . " lainnya)";
            }
            $body .= "\n\n{$skippedCount} NPM tidak ditemukan: {$skippedList}";
        }
        
        Notification::make()
            ->title("{$count} mahasiswa ditambahkan")
            ->body($body)
            ->success()
            ->persistent()
            ->send();
    }

    private function searchStudents(string $term): array
    {
        if (trim($term) === '') {
            return [];
        }

        $user = auth()->user();

        $query = User::query()
            ->whereNotNull('srn')
            ->where(function ($q) use ($term) {
                $q->where('srn', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            });

        // Jika bukan Admin, filter berdasarkan prodi yang diampu tutor
        if (!$user?->hasRole('Admin')) {
            $prodyIds = method_exists($user, 'assignedProdyIds') 
                ? (array) $user->assignedProdyIds() 
                : [];
            
            if (empty($prodyIds)) {
                return []; // Tutor tanpa prodi tidak bisa cari siapa-siapa
            }
            
            $query->whereIn('prody_id', $prodyIds);
        }

        return $query
            ->orderBy('srn')
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (User $u) => [$u->id => "{$u->srn} — {$u->name}"])
            ->toArray();
    }

    private function getStudentLabels(Collection $ids): array
    {
        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('srn')
            ->get()
            ->mapWithKeys(fn (User $u) => [$u->id => "{$u->srn} — {$u->name}"])
            ->toArray();
    }

    private function buildStudentsState(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        /** @var EloquentCollection<int, User> $users */
        $users = User::query()
            ->with([
                'basicListeningGrade',
                'basicListeningManualScores',
                'basicListeningAttempts' => fn ($q) => $q->whereNotNull('submitted_at'),
                'prody',
            ])
            ->whereIn('id', $ids)
            ->orderByDesc('srn')
            ->get();

        return $users->map(function (User $u) {
            $manuals = $u->basicListeningManualScores->pluck('score', 'meeting');
            $attempts = $u->basicListeningAttempts
                ->groupBy('session_id')
                ->map(fn (Collection $g) => $g->sortByDesc('submitted_at')->first());

            $row = [
                'user_id'             => $u->id,
                'name'                => $u->name,
                'srn'                 => $u->srn,
                'prody_name'          => $u->prody?->name,
                'attendance'          => $u->basicListeningGrade?->attendance,
                'attendance_count'    => $u->basicListeningGrade?->attendance !== null 
                    ? (int) round(($u->basicListeningGrade->attendance / 100) * 6) 
                    : null,
                'final_attempt_score' => $this->attemptScore($attempts, 6),
                'final_test'          => $u->basicListeningGrade?->final_test ?? $this->attemptScore($attempts, 6),
            ];

            foreach (range(1, 5) as $m) {
                $row["m{$m}"] = $manuals[$m] ?? null;
                $row["attempt_score_{$m}"] = $this->attemptScore($attempts, $m);
            }

            return $row;
        })->values()->toArray();
    }

    private function attemptScore(Collection $attempts, int $session): ?float
    {
        $attempt = $attempts[$session] ?? null;
        $score = $attempt?->score ?? null;
        return is_numeric($score) ? (float) $score : null;
    }

    private function dailyInputsSchema(): array
    {
        $fields = [];
        foreach (range(1, 5) as $m) {
            $fields[] = TextInput::make("m{$m}")
                ->label("Daily {$m}")
                ->numeric()
                ->maxValue(100)
                ->live(debounce: 500)
                ->placeholder(fn ($get) => $get("attempt_score_{$m}"))
                ->helperText(fn ($get) => $get("attempt_score_{$m}") !== null ? 'Skor attempt: ' . $get("attempt_score_{$m}") : 'Belum ada attempt');
        }
        return $fields;
    }

    private function saveStudentRow(array $row): void
    {
        $user = User::find($row['user_id'] ?? null);
        if (! $user) {
            return;
        }

        foreach (range(1, 5) as $m) {
            if (! array_key_exists("m{$m}", $row)) {
                continue;
            }

            $val = $row["m{$m}"];
            $scoreRow = BasicListeningManualScore::firstOrCreate(
                ['user_id' => $user->id, 'user_year' => $user->year, 'meeting' => $m]
            );
            $scoreRow->score = ($val === '' || $val === null) ? null : (int) $val;
            $scoreRow->save();
        }

        $grade = BasicListeningGrade::firstOrCreate(['user_id' => $user->id, 'user_year' => $user->year]);
        $grade->attendance = $row['attendance'] ?? null;
        $grade->final_test = $row['final_test'] ?? null;
        $grade->save();
    }

    /**
     * Calculate total score for real-time preview
     * Formula: (Attendance + Daily Average + Final Test) / 3
     */
    private function calculateTotal(callable $get): ?float
    {
        $attendance = $get('attendance');
        $finalTest = $get('final_test') ?? $get('final_attempt_score');
        
        // Collect daily scores (manual input or attempt score)
        $dailyScores = [];
        foreach (range(1, 5) as $m) {
            $manual = $get("m{$m}");
            $attempt = $get("attempt_score_{$m}");
            $score = $manual !== null && $manual !== '' ? $manual : $attempt;
            if (is_numeric($score)) {
                $dailyScores[] = (float) $score;
            }
        }
        
        // Calculate daily average (always divide by 5)
        $dailyAvg = empty($dailyScores) ? null : array_sum($dailyScores) / 5;
        
        // All components must be numeric for valid calculation
        if (!is_numeric($attendance)) {
            return null;
        }
        
        if (!is_numeric($finalTest) || $dailyAvg === null) {
            return null;
        }
        
        return ((float)$attendance + (float)$dailyAvg + (float)$finalTest) / 3;
    }
}
