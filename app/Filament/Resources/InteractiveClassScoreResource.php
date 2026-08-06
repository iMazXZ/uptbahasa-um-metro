<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InteractiveClassScoreResource\Pages;
use App\Models\InteractiveClassScore;
use App\Support\InteractiveClassScores;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InteractiveClassScoreResource extends BaseResource
{
    protected static ?string $model = InteractiveClassScore::class;

    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static ?string $navigationGroup = 'Sertifikat';
    protected static ?string $navigationLabel = 'Nilai Interactive';
    protected static ?string $modelLabel = 'Nilai Interactive';
    protected static ?string $pluralModelLabel = 'Nilai Interactive';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('track')
                        ->label('Jenis Interactive')
                        ->options(InteractiveClassScore::trackOptions())
                        ->default(InteractiveClassScore::TRACK_ENGLISH)
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('srn')
                        ->label('NPM / SRN')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('name')
                        ->label('Nama')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('study_program')
                        ->label('Program Studi')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('source_year')
                        ->label('Tahun Data')
                        ->numeric()
                        ->minValue(2010)
                        ->maxValue((int) now()->year + 1),
                    Forms\Components\TextInput::make('semester')
                        ->label('Semester / Tahap')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(fn (Forms\Get $get): int => InteractiveClassScores::maxSemester((string) $get('track'))),
                ]),
            Forms\Components\Section::make('Nilai')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('score')
                        ->label('Nilai Average')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                    Forms\Components\TextInput::make('grade')
                        ->label('Grade')
                        ->maxLength(10),
                    Forms\Components\TextInput::make('source_file')
                        ->label('File Sumber')
                        ->maxLength(255),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('srn')
                    ->label('NPM')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('study_program')
                    ->label('Program Studi')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('track')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => InteractiveClassScores::trackLabel((string) $state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('semester')
                    ->label('Semester / Tahap')
                    ->badge()
                    ->formatStateUsing(fn ($state, InteractiveClassScore $record): string => InteractiveClassScores::semesterLabel((string) $record->track, is_numeric($state) ? (int) $state : null) ?? '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('source_year')
                    ->label('Tahun')
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('grade')
                    ->label('Grade')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('source_year')
                    ->label('Tahun Data')
                    ->options(fn () => InteractiveClassScore::query()
                        ->whereNotNull('source_year')
                        ->distinct()
                        ->orderByDesc('source_year')
                        ->pluck('source_year', 'source_year')
                        ->all()),
                SelectFilter::make('track')
                    ->label('Jenis Interactive')
                    ->options(InteractiveClassScore::trackOptions()),
                SelectFilter::make('semester')
                    ->label('Semester / Tahap')
                    ->options(fn () => InteractiveClassScore::query()
                        ->whereNotNull('semester')
                        ->distinct()
                        ->orderBy('semester')
                        ->pluck('semester', 'semester')
                        ->mapWithKeys(fn ($value) => [$value => 'Tahap ' . $value])
                        ->all()),
                SelectFilter::make('grade')
                    ->options(fn () => InteractiveClassScore::query()
                        ->whereNotNull('grade')
                        ->distinct()
                        ->orderBy('grade')
                        ->pluck('grade', 'grade')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInteractiveClassScores::route('/'),
            'create' => Pages\CreateInteractiveClassScore::route('/create'),
            'edit' => Pages\EditInteractiveClassScore::route('/{record}/edit'),
        ];
    }
}
