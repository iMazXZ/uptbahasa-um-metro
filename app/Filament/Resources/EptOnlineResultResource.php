<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EptOnlineResultResource\Pages;
use App\Models\EptOnlineResult;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EptOnlineResultResource extends BaseResource
{
    protected static ?string $model = EptOnlineResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'EPT';
    protected static ?string $navigationLabel = 'Hasil Tes Online';
    protected static ?string $modelLabel = 'Hasil Tes Online';
    protected static ?string $pluralModelLabel = 'Hasil Tes Online';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Attempt')
                ->schema([
                    Forms\Components\Placeholder::make('attempt_summary')
                        ->label('Attempt')
                        ->content(fn (?EptOnlineResult $record): string => $record?->attempt
                            ? ('#' . $record->attempt->id . ' • ' . ($record->attempt->form?->code ?? 'Paket') . ' • ' . ($record->attempt->user?->name ?? 'Peserta'))
                            : '-'),
                    Forms\Components\Placeholder::make('submitted_at_info')
                        ->label('Waktu Submit')
                        ->content(fn (?EptOnlineResult $record): string => $record?->attempt?->submitted_at?->translatedFormat('d M Y H:i') ?? '-'),
                ])
                ->columns(2),
            Forms\Components\Section::make('Skor Mentah')
                ->schema([
                    Forms\Components\TextInput::make('listening_raw')
                        ->label('Listening Raw')
                        ->numeric()
                        ->disabled(),
                    Forms\Components\TextInput::make('structure_raw')
                        ->label('Structure Raw')
                        ->numeric()
                        ->disabled(),
                    Forms\Components\TextInput::make('reading_raw')
                        ->label('Reading Raw')
                        ->numeric()
                        ->disabled(),
                ])
                ->columns(3),
            Forms\Components\Section::make('Nilai Resmi')
                ->schema([
                    Forms\Components\TextInput::make('listening_scaled')
                        ->label('Listening Scaled')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(999),
                    Forms\Components\TextInput::make('structure_scaled')
                        ->label('Structure Scaled')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(999),
                    Forms\Components\TextInput::make('reading_scaled')
                        ->label('Reading Scaled')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(999),
                    Forms\Components\TextInput::make('total_scaled')
                        ->label('Nilai Akhir')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(999)
                        ->required(fn (Get $get): bool => (bool) $get('is_published')),
                    Forms\Components\TextInput::make('scale_version')
                        ->label('Versi Konversi')
                        ->maxLength(255)
                        ->placeholder('Contoh: EPT-UMM-2026'),
                    Forms\Components\Toggle::make('is_published')
                        ->label('Publikasikan ke peserta')
                        ->inline(false)
                        ->default(false),
                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Published At')
                        ->seconds(false)
                        ->visible(fn (Get $get): bool => (bool) $get('is_published')),
                    Forms\Components\Placeholder::make('cefr_summary')
                        ->label('CEFR')
                        ->content(function (?EptOnlineResult $record, Get $get): string {
                            $listeningState = $get('listening_scaled');
                            $structureState = $get('structure_scaled');
                            $readingState = $get('reading_scaled');
                            $totalState = $get('total_scaled');

                            $listening = $listeningState !== null && $listeningState !== ''
                                ? (int) $listeningState
                                : $record?->listening_scaled;
                            $structure = $structureState !== null && $structureState !== ''
                                ? (int) $structureState
                                : $record?->structure_scaled;
                            $reading = $readingState !== null && $readingState !== ''
                                ? (int) $readingState
                                : $record?->reading_scaled;
                            $total = $totalState !== null && $totalState !== ''
                                ? (int) $totalState
                                : $record?->total_scaled;

                            return collect([
                                'Overall: ' . (EptOnlineResult::totalCefrLevel($total) ?? '-'),
                                'Listening: ' . (EptOnlineResult::sectionCefrLevel('listening', $listening) ?? '-'),
                                'Structure: ' . (EptOnlineResult::sectionCefrLevel('structure', $structure) ?? '-'),
                                'Reading: ' . (EptOnlineResult::sectionCefrLevel('reading', $reading) ?? '-'),
                            ])->implode(' | ');
                        })
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['attempt.form', 'attempt.user']))
            ->columns([
                Tables\Columns\TextColumn::make('attempt.id')
                    ->label('Attempt #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('attempt.form.code')
                    ->label('Paket')
                    ->searchable(),
                Tables\Columns\TextColumn::make('attempt.user.name')
                    ->label('User')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('listening_raw')
                    ->label('L Raw')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('structure_raw')
                    ->label('S Raw')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('reading_raw')
                    ->label('R Raw')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_scaled')
                    ->label('Total')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cefr')
                    ->label('CEFR')
                    ->state(fn (EptOnlineResult $record): string => $record->overallCefrLevel() ?? '-')
                    ->badge()
                    ->color(fn (EptOnlineResult $record): string => match ($record->overallCefrLevel()) {
                        'C1' => 'success',
                        'B2' => 'info',
                        'B1' => 'warning',
                        'A2' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published At')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_publish')
                    ->label(fn (EptOnlineResult $record): string => $record->is_published ? 'Unpublish' : 'Publish')
                    ->icon(fn (EptOnlineResult $record): string => $record->is_published ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (EptOnlineResult $record): string => $record->is_published ? 'gray' : 'success')
                    ->disabled(fn (EptOnlineResult $record): bool => ! $record->is_published && blank($record->total_scaled))
                    ->tooltip(fn (EptOnlineResult $record): ?string => ! $record->is_published && blank($record->total_scaled)
                        ? 'Isi Nilai Akhir terlebih dahulu di halaman edit.'
                        : null)
                    ->requiresConfirmation()
                    ->action(function (EptOnlineResult $record): void {
                        $publishing = ! $record->is_published;

                        $record->forceFill([
                            'is_published' => $publishing,
                            'published_at' => $publishing ? now() : null,
                        ])->save();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEptOnlineResults::route('/'),
            'edit' => Pages\EditEptOnlineResult::route('/{record}/edit'),
        ];
    }
}
