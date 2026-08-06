<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\BasicListeningAttempt;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BasicListeningAttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'basicListeningAttempts';
    protected static ?string $title = 'Attempts (Hasil Kuis)';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('session.number')
                    ->label('Pert.')
                    ->sortable(),

                Tables\Columns\TextColumn::make('session.title')
                    ->label('Judul Sesi')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('quiz.questions.first.type')
                    ->label('Tipe')
                    ->state(fn ($record) => $record->quiz?->questions?->first()?->type ?? 'unknown')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'fib_paragraph'   => 'FIB',
                        'multiple_choice' => 'MC',
                        default           => (string) $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'fib_paragraph'   => 'warning',
                        'multiple_choice' => 'success',
                        default           => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('score')
                    ->label('Skor')
                    ->badge()
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state === 0    => 'danger',
                        $state >= 80    => 'success',
                        $state >= 60    => 'warning',
                        default         => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => $state === null ? '–' : $state),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Dimulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Dikumpul')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('submitted')
                    ->label('Sudah Submit?')
                    ->boolean()
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('submitted_at'),
                        false: fn (Builder $q) => $q->whereNull('submitted_at'),
                        blank: fn (Builder $q) => $q
                    ),
            ])
            ->actions([
                // Arahkan ke View di BasicListeningAttemptResource
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (BasicListeningAttempt $record) =>
                        \App\Filament\Resources\BasicListeningAttemptResource::getUrl('view', ['record' => $record])
                    )
                    ->openUrlInNewTab(),
            ])
            ->headerActions([]) // biasanya tidak perlu tambah/create dari sini
            ->emptyStateHeading('Belum ada attempt');
    }
}

