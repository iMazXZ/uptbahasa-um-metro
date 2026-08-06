<?php

namespace App\Filament\Pages;

use App\Models\BasicListeningSurveyResponse;
use App\Models\BasicListeningCategory;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SurveyResponses extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield {
        canAccess as protected canAccessViaShield;
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Survey Responses';
    protected static ?string $title = 'Survey Responses';
    protected static ?string $navigationGroup = 'Basic Listening';
    protected static ?string $navigationParentItem = 'Kuesioner';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.survey-responses';

    /** Ambil tanggal mulai periode BL (jika diset) */
    private function getBlPeriodStartDate(): ?string
    {
        return \App\Models\SiteSetting::getBlPeriodStartDate();
    }

    public function table(Table $table): Table
    {
        $query = BasicListeningSurveyResponse::query()
            ->with(['survey', 'user', 'tutor', 'supervisor'])
            ->withCount('answers')
            ->withAvg('answers', 'likert_value');

        $startDate = $this->getBlPeriodStartDate();
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        return $table
            ->query($query)
            ->columns([                
                TextColumn::make('survey.title')->label('Survey')->sortable()->searchable(),
                
                TextColumn::make('survey.category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record->survey?->category_label ?? ucfirst((string) $state))
                    ->color(fn ($state) => match ($state) {
                        'tutor' => 'primary', 'supervisor' => 'success', 'institute' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                TextColumn::make('user.name')->label('Mahasiswa')->sortable()->searchable(),
                
                TextColumn::make('tutor.name')->label('Tutor')->placeholder('—'),
                
                TextColumn::make('supervisor.name')->label('Supervisor')->placeholder('—'),
                
                TextColumn::make('answers_count')
                    ->label('Jumlah Jawaban')
                    ->sortable()
                    ->alignCenter(),
                    
                TextColumn::make('answers_avg_likert_value')
                    ->label('Rata-rata')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '—')
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning', 
                        $state >= 1 => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter()
                    ->sortable(),
                    
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->paginationPageOptions([5, 10, 25, 50])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('survey')
                    ->relationship('survey', 'title')
                    ->searchable()
                    ->preload(),
                    
                \Filament\Tables\Filters\SelectFilter::make('category')
                    ->form(fn() => [
                        \Filament\Forms\Components\Select::make('category')
                            ->options(fn () => $this->categoryOptions())
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['category'])) {
                            $query->whereHas('survey', fn($q) => $q->where('category', $data['category']));
                        }
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (BasicListeningSurveyResponse $record) =>
                        \App\Filament\Pages\SurveyResponsesDetail::getUrl(['record' => $record->id])
                    ),
                    
                \Filament\Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Response?')
                    ->modalDescription('Jawaban (answers) yang terkait juga akan dihapus.'),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Response Terpilih?')
                    ->modalDescription('Semua jawaban (answers) yang terkait juga akan dihapus.'),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->emptyStateHeading('Belum ada data response')
            ->emptyStateDescription('Data akan muncul setelah mahasiswa mengisi kuesioner.');
    }

    public static function canAccess(): bool
    {
        return static::canAccessViaShield() && (auth()->user()?->hasAnyRole([
            'Admin',
            'Staf Administrasi',
            'Kepala Lembaga',
        ]) ?? false);
    }

    /** Statistik untuk header widget */
    public function getStats(): array
    {
        $startDate = $this->getBlPeriodStartDate();
        $baseQuery = BasicListeningSurveyResponse::query();
        if ($startDate) {
            $baseQuery->where('created_at', '>=', $startDate);
        }

        $totalResponses = (clone $baseQuery)->whereNotNull('submitted_at')->count();
        
        $avgScore = (clone $baseQuery)
            ->whereNotNull('submitted_at')
            ->withAvg('answers', 'likert_value')
            ->get()
            ->avg('answers_avg_likert_value');

        $todayResponses = (clone $baseQuery)
            ->whereNotNull('submitted_at')
            ->whereDate('submitted_at', today())
            ->count();

        $pendingResponses = (clone $baseQuery)->whereNull('submitted_at')->count();

        return [
            'total' => $totalResponses,
            'avg' => $avgScore ? number_format($avgScore, 2) : '—',
            'today' => $todayResponses,
            'pending' => $pendingResponses,
        ];
    }

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
            'supervisor' => 'Supervisor',
            'institute'  => 'Lembaga',
        ];
    }

    /** State untuk modal pending */
    public bool $showPendingModal = false;
    public array $pendingResponses = [];
    public int $pendingLimit = 20;
    public int $pendingTotal = 0;

    /** Tampilkan modal pending responses */
    public function showPendingDetail(): void
    {
        $query = BasicListeningSurveyResponse::query()
            ->whereNull('submitted_at')
            ->with(['user', 'survey']);

        $startDate = $this->getBlPeriodStartDate();
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        $this->pendingTotal = (clone $query)->count();

        $this->pendingResponses = $query
            ->select(['id', 'user_id', 'survey_id', 'created_at'])
            ->orderByDesc('created_at')
            ->limit($this->pendingLimit)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'user_name' => $r->user?->name ?? '—',
                'survey_title' => $r->survey?->title ?? '—',
                'created_at' => $r->created_at?->format('d M Y, H:i') ?? '—',
            ])
            ->toArray();

        $this->showPendingModal = true;
    }

    /** Load more pending */
    public function loadMorePending(): void
    {
        $this->pendingLimit += 20;
        $this->showPendingDetail();
    }

    /** Tutup modal */
    public function closePendingModal(): void
    {
        $this->showPendingModal = false;
        $this->pendingResponses = [];
        $this->pendingLimit = 20;
        $this->pendingTotal = 0;
    }
}
