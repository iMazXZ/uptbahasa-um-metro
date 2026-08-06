<?php

namespace App\Filament\Pages;

use App\Models\BasicListeningSurveyResponse;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SurveyResponsesDetail extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield {
        canAccess as protected canAccessViaShield;
    }

    protected static ?string $navigationIcon = 'heroicon-o-eye';
    protected static ?string $navigationGroup = 'Basic Listening';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'Detail Response';
    protected static string $view = 'filament.pages.survey-responses-detail';

    public ?BasicListeningSurveyResponse $record = null;

    public function mount(): void
    {
        $id = (int) request()->query('record', 0);
        $this->record = BasicListeningSurveyResponse::with(['survey', 'user', 'answers.question'])
            ->withCount('answers')
            ->findOrFail($id);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                if (!$this->record) {
                    return \App\Models\BasicListeningSurveyAnswer::query()->whereRaw('0=1');
                }

                return $this->record->answers()->with('question')->getQuery();
            })
            ->columns([
                TextColumn::make('question.question')
                    ->label('Pertanyaan')
                    ->wrap(),

                TextColumn::make('likert_value')
                    ->label('Nilai Likert')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning', 
                        $state >= 1 => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state ?: '—')
                    ->alignCenter(),

                TextColumn::make('text_value')
                    ->label('Jawaban Teks')
                    ->wrap()
                    ->formatStateUsing(fn ($state) => $state ?: '—'),
            ])
            ->paginated(false);
    }

    public function getTitle(): string
    {
        return $this->record
            ? "Detail Response: {$this->record->id} - {$this->record->survey->title}"
            : (static::$title ?? 'Detail Response');
    }

    public static function canAccess(): bool
    {
        return static::canAccessViaShield() && (auth()->user()?->hasAnyRole([
            'Admin',
            'Staf Administrasi',
            'Kepala Lembaga',
        ]) ?? false);
    }
}
