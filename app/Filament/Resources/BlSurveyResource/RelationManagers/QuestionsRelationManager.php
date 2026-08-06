<?php

namespace App\Filament\Resources\BlSurveyResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';
    protected static ?string $title = 'Pertanyaan';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label('Tipe')
                ->options([
                    'likert' => 'Likert (1–5)',
                    'text'   => 'Jawaban Teks',
                ])
                ->default('likert')
                ->required()
                ->columnSpan(2),

            Forms\Components\TextInput::make('order')
                ->label('Urutan')
                ->numeric()
                ->default(1)
                ->minValue(1)
                ->columnSpan(1),

            Forms\Components\Toggle::make('is_required')
                ->label('Wajib?')
                ->default(true)
                ->columnSpan(1),

            Forms\Components\Textarea::make('question')
                ->label('Pertanyaan')
                ->rows(3)
                ->required()
                ->columnSpanFull(),

            // Opsional: kalau ingin opsi custom per pertanyaan (misal label skala)
            Forms\Components\KeyValue::make('options')
                ->label('Options (opsional)')
                ->keyLabel('key')
                ->valueLabel('value')
                ->addButtonLabel('Tambah')
                ->columnSpanFull()
                ->helperText('Kosongkan jika menggunakan default.'),
        ])->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('Urut')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn ($state) => $state === 'likert' ? 'Likert' : 'Teks'),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('Wajib')
                    ->boolean(),

                Tables\Columns\TextColumn::make('question')
                    ->label('Pertanyaan')
                    ->wrap()
                    ->limit(80),
            ])
            ->reorderable('order')
            ->defaultSort('order')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Pertanyaan'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
