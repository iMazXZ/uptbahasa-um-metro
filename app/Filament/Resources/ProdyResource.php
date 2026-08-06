<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProdyResource\Pages;
use App\Filament\Resources\ProdyResource\RelationManagers;
use App\Filament\Resources\ProdyResource\RelationManagers\UsersRelationManager;
use App\Models\Prody;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProdyResource extends BaseResource
{
    protected static ?string $model = Prody::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    public static ?string $label = 'Daftar Program Studi';

    protected static ?string $navigationParentItem = 'Manajemen User';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Program Studi')
                    ->placeholder('Masukkan nama program studi')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('users'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Program Studi')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Prody $record) => static::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false)
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('users_count')
                    ->label('Total User')
                    ->sortable()
                    ->badge()
                    ->colors([
                        'gray',
                        'warning' => fn ($state) => $state > 0 && $state < 10,
                        'success' => fn ($state) => $state >= 10,
                    ]),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Prodi Dihapus'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat Data Mahasiswa'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->successNotificationTitle('Prodi dihapus dan masuk ke Sampah.'),
                Tables\Actions\RestoreAction::make()
                    ->label('Pulihkan')
                    ->visible(fn ($record) => method_exists($record, 'trashed') && $record->trashed()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Yang Dipilih'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Pulihkan Terpilih'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProdies::route('/'),
            'create' => Pages\CreatePrody::route('/create'),
            'view' => Pages\ViewPrody::route('/{record}'),
            'edit' => Pages\EditPrody::route('/{record}/edit'),
        ];
    }
}
