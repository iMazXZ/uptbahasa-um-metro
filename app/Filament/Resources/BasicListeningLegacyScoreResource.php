<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BasicListeningLegacyScoreResource\Pages;
use App\Models\BasicListeningLegacyScore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BasicListeningLegacyScoreResource extends BaseResource
{
    protected static ?string $model = BasicListeningLegacyScore::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';
    protected static ?string $navigationGroup = 'Sertifikat';
    protected static ?string $navigationLabel = 'Nilai Manual';
    protected static ?string $modelLabel = 'Nilai Manual';
    protected static ?string $pluralModelLabel = 'Nilai Manual';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas')
                ->columns(2)
                ->schema([
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
                ]),
            Forms\Components\Section::make('Nilai')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('score')
                        ->label('Nilai')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                    Forms\Components\TextInput::make('grade')
                        ->label('Grade')
                        ->disabled()
                        ->dehydrated(false),
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
                    ->label('Tahun')
                    ->options(fn () => BasicListeningLegacyScore::query()
                        ->whereNotNull('source_year')
                        ->distinct()
                        ->orderByDesc('source_year')
                        ->pluck('source_year', 'source_year')
                        ->all()),
                SelectFilter::make('study_program')
                    ->label('Program Studi')
                    ->options(fn () => BasicListeningLegacyScore::query()
                        ->whereNotNull('study_program')
                        ->where('study_program', '!=', '')
                        ->distinct()
                        ->orderBy('study_program')
                        ->pluck('study_program', 'study_program')
                        ->all())
                    ->searchable(),
                SelectFilter::make('grade')
                    ->options(fn () => BasicListeningLegacyScore::query()
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
            'index' => Pages\ListBasicListeningLegacyScores::route('/'),
            'create' => Pages\CreateBasicListeningLegacyScore::route('/create'),
            'edit' => Pages\EditBasicListeningLegacyScore::route('/{record}/edit'),
        ];
    }
}
