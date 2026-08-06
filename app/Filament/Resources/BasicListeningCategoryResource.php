<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BasicListeningCategoryResource\Pages;
use App\Models\BasicListeningCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

class BasicListeningCategoryResource extends BaseResource
{
    protected static ?string $model = BasicListeningCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Basic Listening';
    protected static ?string $navigationParentItem = 'Kuesioner';
    protected static ?string $navigationLabel = 'Kategori Kuesioner';
    protected static ?string $label = 'Kategori';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Dipakai sebagai kunci category pada kuesioner.')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),
            ]),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('position')
                    ->label('Urutan Tampilan')
                    ->numeric()
                    ->default(0)
                    ->helperText('Semakin kecil semakin awal diurutkan.'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('position')
                    ->label('Urutan')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),
            ])
            ->reorderable('position')
            ->defaultSort('position')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBasicListeningCategories::route('/'),
        ];
    }
}
