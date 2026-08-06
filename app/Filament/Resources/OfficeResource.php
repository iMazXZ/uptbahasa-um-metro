<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeResource\Pages;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class OfficeResource extends BaseResource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationGroup = 'Absensi';
    
    protected static ?string $navigationLabel = 'Lokasi Kantor';
    
    protected static ?string $modelLabel = 'Lokasi Kantor';
    
    protected static ?string $pluralModelLabel = 'Lokasi Kantor';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Lokasi')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lokasi')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Kantor Pusat'),
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->placeholder('Alamat lengkap lokasi')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Koordinat GPS')
                    ->description('Masukkan koordinat lokasi untuk validasi GPS saat absensi')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->required()
                            ->numeric()
                            ->step(0.00000001)
                            ->placeholder('-6.12345678'),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->required()
                            ->numeric()
                            ->step(0.00000001)
                            ->placeholder('106.12345678'),
                        Forms\Components\TextInput::make('radius')
                            ->label('Radius (meter)')
                            ->required()
                            ->numeric()
                            ->default(150)
                            ->suffix('meter')
                            ->helperText('Jarak maksimal dari titik lokasi untuk bisa absen'),
                    ])->columns(3),
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Lokasi tidak aktif tidak akan muncul di daftar pilihan'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lokasi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('radius')
                    ->label('Radius')
                    ->suffix(' m')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('attendances_count')
                    ->label('Total Absensi')
                    ->counts('attendances')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}
