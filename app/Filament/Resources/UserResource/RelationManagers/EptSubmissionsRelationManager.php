<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\EptSubmission;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EptSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'eptSubmissions';
    protected static ?string $title = 'Surat Rekomendasi EPT';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('surat_nomor')
                    ->label('No. Surat')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('nilai_tes_1')
                    ->label('Tes 1')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('nilai_tes_2')
                    ->label('Tes 2')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('nilai_tes_3')
                    ->label('Tes 3')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Disetujui')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (EptSubmission $record) =>
                        \App\Filament\Resources\EptSubmissionResource::getUrl('index') . '?tableSearch=' . $record->id
                    )
                    ->openUrlInNewTab(),
            ])
            ->headerActions([])
            ->emptyStateHeading('Belum ada pengajuan');
    }
}
