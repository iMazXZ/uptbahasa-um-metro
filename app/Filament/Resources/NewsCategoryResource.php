<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsCategoryResource\Pages;
use App\Models\NewsCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class NewsCategoryResource extends BaseResource
{
    protected static ?string $model = NewsCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $label = 'Kategori Berita';
    protected static ?string $pluralLabel = 'Kategori Berita';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                        $currentSlug = (string) ($get('slug') ?? '');

                        if ($currentSlug !== '' || ! filled($state)) {
                            return;
                        }

                        $set('slug', Str::slug($state));
                    }),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->alphaDash()
                    ->maxLength(100)
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug($state) : null)
                    ->unique(ignoreRecord: true)
                    ->helperText('Dipakai di URL kategori berita. Contoh: /berita/kategori/pengumuman'),
            ]),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('position')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Semakin kecil, semakin awal ditampilkan.'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Jika nonaktif, kategori tidak muncul di filter publik.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('posts'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('posts_count')
                    ->label('Dipakai')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'info' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('position')
                    ->label('Urutan')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (NewsCategory $record): bool => (int) ($record->posts_count ?? 0) === 0),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageNewsCategories::route('/'),
        ];
    }
}
