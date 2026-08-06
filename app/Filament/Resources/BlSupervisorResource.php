<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlSupervisorResource\Pages;
use App\Models\BasicListeningSupervisor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class BlSupervisorResource extends BaseResource
{
    protected static ?string $model = BasicListeningSupervisor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $slug = 'basic-listening/supervisors';
    public static ?string $label = 'Data Supervisor';
    protected static ?string $navigationParentItem = 'Manajemen User';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama')->required()->maxLength(255),
            Forms\Components\TextInput::make('position')->label('Jabatan')->maxLength(255),
            Forms\Components\TextInput::make('email')->email()->maxLength(255),
            Forms\Components\TextInput::make('phone')->tel()->maxLength(50),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('position')->label('Jabatan')->toggleable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->toggleable(),
                Tables\Columns\TextColumn::make('phone')->label('Phone')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBlSupervisors::route('/'),
            'create' => Pages\CreateBlSupervisor::route('/create'),
            'edit'   => Pages\EditBlSupervisor::route('/{record}/edit'),
        ];
    }
}
