<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Penerjemahan;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PenerjemahansRelationManager extends RelationManager
{
    protected static string $relationship = 'penerjemahans';
    protected static ?string $title = 'Penerjemahan';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('submission_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Menunggu' => 'warning',
                        'Diproses' => 'info',
                        'Selesai' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('submission_date')
                    ->label('Tgl Submit')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('source_word_count')
                    ->label('Kata')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('translator.name')
                    ->label('Penerjemah')
                    ->placeholder('—')
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('completion_date')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('verification_code')
                    ->label('Kode')
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Penerjemahan $record) =>
                        \App\Filament\Resources\PenerjemahanResource::getUrl('edit', ['record' => $record])
                    )
                    ->openUrlInNewTab(),
            ])
            ->headerActions([])
            ->emptyStateHeading('Belum ada penerjemahan');
    }
}
