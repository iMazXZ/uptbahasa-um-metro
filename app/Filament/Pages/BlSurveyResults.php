<?php

namespace App\Filament\Pages;

use App\Models\BasicListeningSurvey;
use App\Models\BasicListeningSurveyAnswer;
use App\Models\BasicListeningSurveyResponse;
use App\Models\BasicListeningCategory;
use App\Models\BasicListeningSupervisor;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;

class BlSurveyResults extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use HasPageShield {
        canAccess as protected canAccessViaShield;
    }

    /** ====== Navigasi & metadata sidebar ====== */
    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Hasil Kuesioner';
    protected static ?string $title           = 'Hasil Kuesioner';
    protected static ?string $navigationGroup = 'Basic Listening';
    protected static ?string $navigationParentItem = 'Kuesioner';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug            = 'basic-listening/survey-results';

    /** Blade view untuk page ini */
    protected static string $view = 'filament.pages.bl-survey-results';

    public static function canAccess(): bool
    {
        return static::canAccessViaShield() && (auth()->user()?->hasAnyRole([
            'Admin',
            'Staf Administrasi',
            'Kepala Lembaga',
        ]) ?? false);
    }

    /** ====== State filter ====== */
    public ?string $category = 'tutor'; // default di-set ke kategori aktif pertama
    public ?int $tutorId = null;
    public ?int $supervisorId = null;

    public function mount(): void
    {
        $options = $this->categoryOptions();
        $this->category = array_key_first($options) ?? 'tutor';
        $this->tutorId = null;
        $this->supervisorId = null;
        $this->form->fill([
            'category'    => $this->category,
            'tutorId'     => $this->tutorId,
            'supervisorId'=> $this->supervisorId,
        ]);
    }

    /** =========================
     *  FORM (Filter di atas tabel)
     *  ========================= */
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(12)->schema([
                Forms\Components\Select::make('category')
                    ->label('Kategori')
                    ->options(fn () => $this->categoryOptions())
                    ->default(fn () => array_key_first($this->categoryOptions()))
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        // Reset child filter ketika kategori berubah
                        $set('tutorId', null);
                        $set('supervisorId', null);
                        $this->tutorId = null;
                        $this->supervisorId = null;
                        $this->feedbackLimit = 10; // Reset feedback pagination
                        $this->resetRespondentsModal();
                        $this->resetMissingEligibleModal();
                        if (method_exists($this, 'resetTable')) {
                            $this->resetTable();
                        }
                    })
                    ->columnSpan(12),

                Forms\Components\Select::make('tutorId')
                    ->label('Pilih Tutor')
                    ->options(fn () => $this->tutorOptionsWithCounts())
                    ->visible(fn (Get $get) => $get('category') === 'tutor')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->tutorId = $state ? (int) $state : null;
                        $this->feedbackLimit = 10; // Reset feedback pagination
                        $this->resetRespondentsModal();
                        $this->resetMissingEligibleModal();
                        if (method_exists($this, 'resetTable')) $this->resetTable();
                    })
                    ->columnSpan(6),

                Forms\Components\Select::make('supervisorId')
                    ->label('Pilih Supervisor')
                    ->options(fn () => $this->supervisorOptionsWithCounts())
                    ->visible(fn (Get $get) => $get('category') === 'supervisor')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->supervisorId = $state ? (int) $state : null;
                        $this->feedbackLimit = 10; // Reset feedback pagination
                        $this->resetRespondentsModal();
                        $this->resetMissingEligibleModal();
                        if (method_exists($this, 'resetTable')) $this->resetTable();
                    })
                    ->columnSpan(6),
            ]),
        ]);
    }

    /** =========================
     *  TABEL (Ringkasan per-pertanyaan)
     *  ========================= */
    public function table(Table $table): Table
    {
        return $table
            // Penting: pakai closure agar query dipanggil ulang saat state filter berubah.
            ->query(fn () => $this->buildAggregateQuery())
            ->columns([
                TextColumn::make('question_text')
                    ->label('Pertanyaan')
                    ->wrap()
                    ->limit(120)
                    ->sortable(),

                TextColumn::make('avg_score')
                    ->label('Rata-rata')
                    ->state(fn ($record) => number_format((float) $record->avg_score, 2))
                    ->sortable()
                    ->color(fn ($record) => $this->colorForAvg((float) $record->avg_score))
                    ->alignCenter(),

                TextColumn::make('responses_count')
                    ->label('Responden')
                    ->sortable()
                    ->alignRight(),
            ])
            ->defaultSort('id')
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('Belum ada data')
            ->emptyStateDescription('Atur kategori/filter di atas atau pastikan respon kuesioner sudah masuk.');
    }

    /** Warna indikator rata-rata */
    private function colorForAvg(float $avg): string
    {
        if ($avg >= 4.5) return 'success';
        if ($avg >= 3.5) return 'warning';
        return 'danger';
    }

    /** Ambil tanggal mulai periode BL (jika diset) */
    private function getBlPeriodStartDate(): ?string
    {
        return \App\Models\SiteSetting::getBlPeriodStartDate();
    }

    /** Ambil prefix angkatan aktif (bisa multi, dipisah koma) */
    private function getActiveBatchPrefixes(): array
    {
        $activeBatch = \App\Models\SiteSetting::get('bl_active_batch', now()->format('y'));
        $batches = array_map('trim', explode(',', (string) $activeBatch));
        return array_values(array_filter($batches, fn ($v) => $v !== ''));
    }

    /** Subquery user eligible: pendaftar + angkatan aktif + nilai lengkap */
    private function eligibleUsersSubquery(?int $tutorId = null): Builder
    {
        $startDate = $this->getBlPeriodStartDate();
        $batches = $this->getActiveBatchPrefixes();

        $q = User::query()
            ->select('users.id')
            ->join('basic_listening_grades as g', function ($join) {
                $join->on('g.user_id', '=', 'users.id')
                    ->on('g.user_year', '=', 'users.year');
            })
            ->whereNotNull('users.srn')
            ->whereHas('roles', fn ($r) => $r->where('name', 'pendaftar'))
            ->whereNotNull('g.attendance')
            ->whereNotNull('g.final_test');

        if ($startDate) {
            $q->where('g.created_at', '>=', $startDate);
        }

        if (! empty($batches)) {
            $q->where(function ($sub) use ($batches) {
                foreach ($batches as $batch) {
                    $sub->orWhere('users.srn', 'like', $batch . '%');
                }
            });
        }

        if ($tutorId) {
            $prodyIds = DB::table('tutor_prody')
                ->where('user_id', $tutorId)
                ->pluck('prody_id')
                ->all();

            if (empty($prodyIds)) {
                $q->whereRaw('1=0');
            } else {
                $q->whereIn('users.prody_id', $prodyIds);
            }
        }

        return $q;
    }

    /** Query agregasi rata-rata likert per pertanyaan */
    private function buildAggregateQuery(): Builder
    {
        // Ambil survey aktif terakhir untuk kategori yang dipilih
        $survey = BasicListeningSurvey::query()
            ->where('category', $this->category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        if (! $survey) {
            // Query kosong agar tabel tidak error (tidak ada survey aktif)
            return BasicListeningSurveyAnswer::query()->whereRaw('1=0');
        }

        $eligibleUsers = $this->eligibleUsersSubquery($this->category === 'tutor' ? $this->tutorId : null);

        $q = BasicListeningSurveyAnswer::query()
            ->select([
                // Filament butuh kolom 'id' untuk key record:
                'basic_listening_survey_questions.id as id',
                'basic_listening_survey_questions.question as question_text',
                DB::raw('AVG(basic_listening_survey_answers.likert_value) as avg_score'),
                DB::raw('COUNT(DISTINCT basic_listening_survey_responses.user_id) as responses_count'),
            ])
            ->join(
                'basic_listening_survey_responses',
                'basic_listening_survey_answers.response_id',
                '=',
                'basic_listening_survey_responses.id'
            )
            ->join(
                'basic_listening_survey_questions',
                'basic_listening_survey_answers.question_id',
                '=',
                'basic_listening_survey_questions.id'
            )
            ->where('basic_listening_survey_responses.survey_id', $survey->id)
            ->whereIn('basic_listening_survey_responses.user_id', $eligibleUsers)
            ->whereNotNull('basic_listening_survey_responses.submitted_at')
            ->whereNotNull('basic_listening_survey_answers.likert_value')
            ->groupBy('basic_listening_survey_questions.id', 'basic_listening_survey_questions.question')
            ->orderBy('basic_listening_survey_questions.id');

        $startDate = $this->getBlPeriodStartDate();
        if ($startDate) {
            $q->where('basic_listening_survey_responses.created_at', '>=', $startDate);
        }

        // Filter tambahan berdasarkan kategori terpilih
        if ($this->category === 'tutor') {
            if ($this->tutorId) {
                $q->where('basic_listening_survey_responses.tutor_id', $this->tutorId);
            }
        } elseif ($this->category === 'supervisor') {
            if ($this->supervisorId) {
                $q->where('basic_listening_survey_responses.supervisor_id', $this->supervisorId);
            }
        }

        return $q;
    }

    /** Ringkasan angka untuk ditampilkan di header (opsional, dipakai di Blade) */
    public function getTopStats(): array
    {
        $survey = BasicListeningSurvey::query()
            ->where('category', $this->category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        if (! $survey) {
            return [
                'avg' => null, 
                'respondents' => 0,
                'likertDistribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'lowScoreCount' => 0,
                'totalQuestions' => 0,
            ];
        }

        $startDate = $this->getBlPeriodStartDate();
        $eligibleUsers = $this->eligibleUsersSubquery($this->category === 'tutor' ? $this->tutorId : null);

        // Build base query untuk responses
        $respQuery = BasicListeningSurveyResponse::query()
            ->where('survey_id', $survey->id)
            ->whereNotNull('submitted_at');
        if ($startDate) {
            $respQuery->where('created_at', '>=', $startDate);
        }
        $respQuery->whereIn('user_id', $eligibleUsers);

        if ($this->category === 'tutor' && $this->tutorId) {
            $respQuery->where('tutor_id', $this->tutorId);
        }

        if ($this->category === 'supervisor' && $this->supervisorId) {
            $respQuery->where('supervisor_id', $this->supervisorId);
        }

        // Subquery untuk filter answers
        $responseSubquery = function ($query) use ($survey) {
            $query->select('id')
                ->from('basic_listening_survey_responses')
                ->where('survey_id', $survey->id);

            $startDate = $this->getBlPeriodStartDate();
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
            $query->whereNotNull('submitted_at');
            $query->whereIn('user_id', $this->eligibleUsersSubquery($this->category === 'tutor' ? $this->tutorId : null));

            if ($this->category === 'tutor' && $this->tutorId) {
                $query->where('tutor_id', $this->tutorId);
            }

            if ($this->category === 'supervisor' && $this->supervisorId) {
                $query->where('supervisor_id', $this->supervisorId);
            }
        };

        // Rata-rata keseluruhan
        $avg = BasicListeningSurveyAnswer::query()
            ->whereIn('response_id', $responseSubquery)
            ->avg('likert_value');

        // Distribusi Likert (1-5)
        $likertDistribution = BasicListeningSurveyAnswer::query()
            ->whereIn('response_id', $responseSubquery)
            ->whereNotNull('likert_value')
            ->selectRaw('likert_value, COUNT(*) as count')
            ->groupBy('likert_value')
            ->pluck('count', 'likert_value')
            ->all();

        // Pastikan semua nilai 1-5 ada
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $likertDistribution[$i] ?? 0;
        }

        // Hitung pertanyaan dengan rata-rata < 3.5
        $lowScoreQuestions = DB::table('basic_listening_survey_answers as a')
            ->join('basic_listening_survey_responses as r', 'a.response_id', '=', 'r.id')
            ->select('a.question_id')
            ->where('r.survey_id', $survey->id)
            ->whereNotNull('r.submitted_at')
            ->whereIn('r.user_id', $eligibleUsers)
            ->when($this->category === 'tutor' && $this->tutorId, fn($q) => $q->where('r.tutor_id', $this->tutorId))
            ->when($this->category === 'supervisor' && $this->supervisorId, fn($q) => $q->where('r.supervisor_id', $this->supervisorId))
            ->when($startDate, fn ($q) => $q->where('r.created_at', '>=', $startDate))
            ->whereNotNull('a.likert_value')
            ->groupBy('a.question_id')
            ->havingRaw('AVG(a.likert_value) < 3.5')
            ->get()
            ->count();

        $totalQuestions = $survey->questions()->count();

        return [
            'avg'               => $avg ? number_format((float) $avg, 2) : null,
            'respondents'       => (clone $respQuery)->distinct('user_id')->count('user_id'),
            'likertDistribution'=> $distribution,
            'lowScoreCount'     => $lowScoreQuestions,
            'totalQuestions'    => $totalQuestions,
        ];
    }

    /** Total peserta eligible (nilai lengkap) yang belum submit kuesioner */
    public function getMissingEligibleCount(): int
    {
        $query = $this->buildMissingEligibleQuery();
        if (! $query) {
            return 0;
        }

        return (clone $query)->distinct('users.id')->count('users.id');
    }

    /** State untuk modal daftar responden */
    public bool $showRespondentsModal = false;
    public array $respondents = [];
    public int $respondentsLimit = 20;
    public int $respondentsTotal = 0;

    /** State untuk modal daftar belum isi (nilai lengkap) */
    public bool $showMissingEligibleModal = false;
    public array $missingEligibleUsers = [];
    public int $missingEligibleLimit = 20;
    public int $missingEligibleTotal = 0;

    /** State untuk modal detail Likert */
    public bool $showLikertModal = false;
    public ?int $selectedLikertValue = null;
    public array $likertRespondents = [];
    public int $likertLimit = 20;
    public int $likertTotal = 0;

    /** State untuk feedback (kritik & saran) */
    public int $feedbackLimit = 10;
    public int $feedbackTotal = 0;

    /** Query user eligible yang belum submit (khusus kategori tutor) */
    private function buildMissingEligibleQuery(): ?Builder
    {
        if ($this->category !== 'tutor' || ! $this->tutorId) {
            return null;
        }

        $survey = BasicListeningSurvey::query()
            ->where('category', $this->category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        if (! $survey) {
            return null;
        }

        $startDate = $this->getBlPeriodStartDate();
        $sessionId = $survey->target === 'session' ? $survey->session_id : null;

        $eligibleUsers = $this->eligibleUsersSubquery($this->tutorId);

        return User::query()
            ->leftJoin('prodies as p', 'users.prody_id', '=', 'p.id')
            ->join('basic_listening_grades as g', function ($join) {
                $join->on('g.user_id', '=', 'users.id')
                    ->on('g.user_year', '=', 'users.year');
            })
            ->whereIn('users.id', $eligibleUsers)
            ->whereNotExists(function ($q) use ($survey, $startDate, $sessionId) {
                $q->select(DB::raw(1))
                    ->from('basic_listening_survey_responses as r')
                    ->whereColumn('r.user_id', 'users.id')
                    ->where('r.survey_id', $survey->id)
                    ->whereNotNull('r.submitted_at')
                    ->where('r.tutor_id', $this->tutorId)
                    ->when($startDate, fn ($sub) => $sub->where('r.created_at', '>=', $startDate));

                if ($sessionId) {
                    $q->where('r.session_id', $sessionId);
                } else {
                    $q->whereNull('r.session_id');
                }
            })
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.whatsapp',
                'users.srn',
                'p.name as prody_name',
                'g.attendance',
                'g.final_test',
                'g.final_numeric_cached',
                'g.final_letter_cached',
            ])
            ->orderBy('users.name');
    }

    /** Tampilkan modal daftar belum isi (nilai lengkap) */
    public function showMissingEligibleDetail(): void
    {
        $query = $this->buildMissingEligibleQuery();
        if (! $query) {
            $this->missingEligibleTotal = 0;
            $this->missingEligibleUsers = [];
            $this->showMissingEligibleModal = true;
            return;
        }

        $this->missingEligibleTotal = (clone $query)->distinct('users.id')->count('users.id');
        $this->missingEligibleUsers = (clone $query)
            ->limit($this->missingEligibleLimit)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'srn' => $u->srn,
                'prody_name' => $u->prody_name,
                'whatsapp' => $u->whatsapp,
                'email' => $u->email,
                'final_numeric' => $u->final_numeric_cached,
                'final_letter' => $u->final_letter_cached,
            ])
            ->toArray();

        $this->showMissingEligibleModal = true;
    }

    /** Load more list belum isi */
    public function loadMoreMissingEligible(): void
    {
        $this->missingEligibleLimit += 20;
        $this->showMissingEligibleDetail();
    }

    /** Tutup modal belum isi */
    public function closeMissingEligibleModal(): void
    {
        $this->resetMissingEligibleModal();
    }

    /** Reset state modal belum isi */
    private function resetMissingEligibleModal(): void
    {
        $this->showMissingEligibleModal = false;
        $this->missingEligibleUsers = [];
        $this->missingEligibleLimit = 20;
        $this->missingEligibleTotal = 0;
    }

    /** Tampilkan modal daftar responden */
    public function showRespondentsDetail(): void
    {
        $survey = BasicListeningSurvey::query()
            ->where('category', $this->category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        if (! $survey) {
            $this->respondents = [];
            $this->respondentsTotal = 0;
            $this->showRespondentsModal = true;
            return;
        }

        $startDate = $this->getBlPeriodStartDate();
        $eligibleUsers = $this->eligibleUsersSubquery($this->category === 'tutor' ? $this->tutorId : null);
        $responseBase = BasicListeningSurveyResponse::query()
            ->where('survey_id', $survey->id)
            ->whereNotNull('submitted_at');
        if ($startDate) {
            $responseBase->where('created_at', '>=', $startDate);
        }
        $responseBase->whereIn('user_id', $eligibleUsers);

        if ($this->category === 'tutor' && $this->tutorId) {
            $responseBase->where('tutor_id', $this->tutorId);
        }

        if ($this->category === 'supervisor' && $this->supervisorId) {
            $responseBase->where('supervisor_id', $this->supervisorId);
        }

        $this->respondentsTotal = (clone $responseBase)->distinct('user_id')->count('user_id');

        $respondentsQuery = User::query()
            ->join('basic_listening_survey_responses as r', 'r.user_id', '=', 'users.id')
            ->leftJoin('prodies as p', 'users.prody_id', '=', 'p.id')
            ->where('r.survey_id', $survey->id)
            ->whereNotNull('r.submitted_at')
            ->when($startDate, fn ($q) => $q->where('r.created_at', '>=', $startDate))
            ->whereIn('r.user_id', $eligibleUsers)
            ->when($this->category === 'tutor' && $this->tutorId, fn ($q) => $q->where('r.tutor_id', $this->tutorId))
            ->when($this->category === 'supervisor' && $this->supervisorId, fn ($q) => $q->where('r.supervisor_id', $this->supervisorId))
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.srn',
                'users.whatsapp',
                'p.name as prody_name',
            ])
            ->groupBy('users.id', 'users.name', 'users.email', 'users.srn', 'users.whatsapp', 'p.name')
            ->orderBy('users.name')
            ->limit($this->respondentsLimit);

        $this->respondents = $respondentsQuery
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'srn' => $user->srn,
                'whatsapp' => $user->whatsapp,
                'prody_name' => $user->prody_name,
            ])
            ->toArray();

        $this->showRespondentsModal = true;
    }

    /** Load more responden */
    public function loadMoreRespondents(): void
    {
        $this->respondentsLimit += 20;
        $this->showRespondentsDetail();
    }

    /** Tutup modal responden */
    public function closeRespondentsModal(): void
    {
        $this->resetRespondentsModal();
    }

    /** Reset state modal responden */
    private function resetRespondentsModal(): void
    {
        $this->showRespondentsModal = false;
        $this->respondents = [];
        $this->respondentsLimit = 20;
        $this->respondentsTotal = 0;
    }

    /** Tampilkan modal dengan daftar responden berdasarkan nilai Likert */
    public function showLikertDetail(int $likertValue): void
    {
        $survey = BasicListeningSurvey::query()
            ->where('category', $this->category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        if (! $survey) {
            $this->likertRespondents = [];
            return;
        }

        // Query untuk mendapatkan responden yang memberikan nilai tertentu
        $startDate = $this->getBlPeriodStartDate();
        $query = BasicListeningSurveyAnswer::query()
            ->join('basic_listening_survey_responses as r', 'basic_listening_survey_answers.response_id', '=', 'r.id')
            ->join('users as u', 'r.user_id', '=', 'u.id')
            ->join('basic_listening_survey_questions as q', 'basic_listening_survey_answers.question_id', '=', 'q.id')
            ->where('r.survey_id', $survey->id)
            ->whereNotNull('r.submitted_at')
            ->where('basic_listening_survey_answers.likert_value', $likertValue);
        $query->whereIn('r.user_id', $this->eligibleUsersSubquery($this->category === 'tutor' ? $this->tutorId : null));
        if ($startDate) {
            $query->where('r.created_at', '>=', $startDate);
        }

        if ($this->category === 'tutor' && $this->tutorId) {
            $query->where('r.tutor_id', $this->tutorId);
        }

        if ($this->category === 'supervisor' && $this->supervisorId) {
            $query->where('r.supervisor_id', $this->supervisorId);
        }

        // Hitung total dulu
        $this->likertTotal = (clone $query)->count();

        $this->likertRespondents = $query
            ->select([
                'u.name as respondent_name',
                'q.question as question_text',
                'basic_listening_survey_answers.likert_value',
            ])
            ->orderBy('u.name')
            ->limit($this->likertLimit)
            ->get()
            ->toArray();

        $this->selectedLikertValue = $likertValue;
        $this->showLikertModal = true;
    }

    /** Load more data */
    public function loadMoreLikert(): void
    {
        $this->likertLimit += 20;
        if ($this->selectedLikertValue) {
            $this->showLikertDetail($this->selectedLikertValue);
        }
    }

    /** Tutup modal */
    public function closeLikertModal(): void
    {
        $this->showLikertModal = false;
        $this->selectedLikertValue = null;
        $this->likertRespondents = [];
        $this->likertLimit = 20;
        $this->likertTotal = 0;
    }

    /** Ambil daftar kritik & saran (pertanyaan tipe text) */
    public function getTextFeedback(): array
    {
        $survey = BasicListeningSurvey::query()
            ->where('category', $this->category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        if (! $survey) {
            $this->feedbackTotal = 0;
            return [];
        }

        // Ambil pertanyaan tipe text
        $textQuestionIds = $survey->questions()
            ->where('type', 'text')
            ->pluck('id');

        if ($textQuestionIds->isEmpty()) {
            $this->feedbackTotal = 0;
            return [];
        }

        $startDate = $this->getBlPeriodStartDate();
        $query = BasicListeningSurveyAnswer::query()
            ->join('basic_listening_survey_responses as r', 'basic_listening_survey_answers.response_id', '=', 'r.id')
            ->join('users as u', 'r.user_id', '=', 'u.id')
            ->join('basic_listening_survey_questions as q', 'basic_listening_survey_answers.question_id', '=', 'q.id')
            ->leftJoin('users as tutor', 'r.tutor_id', '=', 'tutor.id')
            ->leftJoin('basic_listening_supervisors as supervisor', 'r.supervisor_id', '=', 'supervisor.id')
            ->where('r.survey_id', $survey->id)
            ->whereNotNull('r.submitted_at')
            ->whereIn('basic_listening_survey_answers.question_id', $textQuestionIds)
            ->whereNotNull('basic_listening_survey_answers.text_value')
            ->where('basic_listening_survey_answers.text_value', '!=', '');
        $query->whereIn('r.user_id', $this->eligibleUsersSubquery($this->category === 'tutor' ? $this->tutorId : null));
        if ($startDate) {
            $query->where('r.created_at', '>=', $startDate);
        }

        // Filter berdasarkan kategori
        if ($this->category === 'tutor' && $this->tutorId) {
            $query->where('r.tutor_id', $this->tutorId);
        }
        if ($this->category === 'supervisor' && $this->supervisorId) {
            $query->where('r.supervisor_id', $this->supervisorId);
        }

        // Hitung total
        $this->feedbackTotal = (clone $query)->count();

        return $query
            ->select([
                'u.name as respondent_name',
                'q.question as question_text',
                'basic_listening_survey_answers.text_value as feedback',
                'r.created_at',
                'tutor.name as tutor_name',
                'supervisor.name as supervisor_name',
            ])
            ->orderByDesc('r.created_at')
            ->limit($this->feedbackLimit)
            ->get()
            ->toArray();
    }

    /** Load more feedback */
    public function loadMoreFeedback(): void
    {
        $this->feedbackLimit += 10;
    }

    /** Reset feedback limit saat filter berubah */
    public function resetFeedbackLimit(): void
    {
        $this->feedbackLimit = 10;
    }

    /** Widget header - kosongkan karena stats dirender inline di blade */
    public function getHeaderWidgets(): array
    {
        return [];
    }

    /** Opsi tutor dengan jumlah respon (ikut filter period) */
    private function tutorOptionsWithCounts(): array
    {
        $startDate = $this->getBlPeriodStartDate();
        $survey = BasicListeningSurvey::query()
            ->where('category', 'tutor')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        $responseCounts = $survey
            ? BasicListeningSurveyResponse::query()
                ->where('survey_id', $survey->id)
                ->whereNotNull('tutor_id')
                ->whereNotNull('submitted_at')
                ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                ->selectRaw('tutor_id, COUNT(*) as count')
                ->groupBy('tutor_id')
                ->pluck('count', 'tutor_id')
                ->all()
            : [];

        return User::query()
            ->role('tutor')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($user) use ($responseCounts) {
                $count = $responseCounts[$user->id] ?? 0;
                $label = $count > 0 ? "{$user->name} ({$count})" : $user->name;
                return [$user->id => $label];
            })
            ->all();
    }

    /** Opsi supervisor dengan jumlah respon (ikut filter period) */
    private function supervisorOptionsWithCounts(): array
    {
        $startDate = $this->getBlPeriodStartDate();
        $survey = BasicListeningSurvey::query()
            ->where('category', 'supervisor')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        $responseCounts = $survey
            ? BasicListeningSurveyResponse::query()
                ->where('survey_id', $survey->id)
                ->whereNotNull('supervisor_id')
                ->whereNotNull('submitted_at')
                ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                ->selectRaw('supervisor_id, COUNT(*) as count')
                ->groupBy('supervisor_id')
                ->pluck('count', 'supervisor_id')
                ->all()
            : [];

        return BasicListeningSupervisor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($supervisor) use ($responseCounts) {
                $count = $responseCounts[$supervisor->id] ?? 0;
                $label = $count > 0 ? "{$supervisor->name} ({$count})" : $supervisor->name;
                return [$supervisor->id => $label];
            })
            ->all();
    }

    /** Tombol header */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-on-square')
                ->color('primary')
                ->modalWidth('lg')
                ->form([
                    Select::make('category')
                        ->label('Kategori')
                        ->options(fn () => $this->categoryOptions())
                        ->default(fn () => $this->category)
                        ->reactive(),

                    Forms\Components\ToggleButtons::make('mode')
                        ->label('Mode export')
                        ->options(fn (Get $get) => match ($get('category')) {
                            'tutor' => [
                                'per_entity' => 'Per Tutor (1 file, multi halaman)',
                                'single'     => 'Tutor tertentu saja',
                                'overall'    => 'Rekap keseluruhan',
                            ],
                            'supervisor' => [
                                'per_entity' => 'Per Supervisor (1 file, multi halaman)',
                                'single'     => 'Supervisor tertentu saja',
                                'overall'    => 'Rekap keseluruhan',
                            ],
                            default => [
                                'overall' => 'Rekap keseluruhan',
                            ],
                        })
                        ->default(fn (Get $get) => in_array($get('category'), ['tutor', 'supervisor'], true) ? 'per_entity' : 'overall')
                        ->live()
                        ->inline()
                        ->columnSpanFull()
                        ->helperText('Pilih “Per …” untuk satu file dengan halaman terpisah per entitas, atau pilih salah satu entitas saja.'),

                    Forms\Components\Toggle::make('all_suggestions')
                        ->label('Semua Saran')
                        ->helperText('Jika aktif, tampilkan semua saran (bukan hanya Top 10).')
                        ->default(false),

                    Select::make('tutor')
                        ->label('Tutor (opsional)')
                        ->options(fn () => $this->tutorOptionsWithCounts())
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => $get('category') === 'tutor' && $get('mode') === 'single'),

                    Select::make('supervisor')
                        ->label('Supervisor (opsional)')
                        ->options(fn () => $this->supervisorOptionsWithCounts())
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => $get('category') === 'supervisor' && $get('mode') === 'single'),
                ])
                ->action(function (array $data) {
                    $mode = $data['mode'] ?? 'overall';
                    $category = $data['category'] ?? 'tutor';
                    $tutor = $mode === 'single' && $category === 'tutor' ? ($data['tutor'] ?? null) : null;
                    $supervisor = $mode === 'single' && $category === 'supervisor' ? ($data['supervisor'] ?? null) : null;
                    $allSuggestions = ! empty($data['all_suggestions']);

                    return redirect()->route('bl.survey-results.export', [
                        'category'   => $category,
                        'mode'       => $mode,
                        'tutor'      => $tutor,
                        'supervisor' => $supervisor,
                        'all_suggestions' => $allSuggestions ? 1 : 0,
                    ]);
                }),
        ];
    }

    /** Ambil opsi kategori aktif, fallback ke default jika kosong */
    private function categoryOptions(): array
    {
        $options = BasicListeningCategory::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('name', 'slug')
            ->all();

        return $options ?: [
            'tutor'      => 'Tutor',
            'materi'     => 'Materi',
            'supervisor' => 'Supervisor',
            'institute'  => 'Lembaga',
        ];
    }
}
