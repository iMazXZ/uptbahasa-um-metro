<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;

class AttendanceResource extends BaseResource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    
    protected static ?string $navigationGroup = 'Absensi';
    
    protected static ?string $navigationLabel = 'Riwayat Absensi';
    
    protected static ?string $modelLabel = 'Absensi';
    
    protected static ?string $pluralModelLabel = 'Riwayat Absensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Absensi')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Karyawan')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('office_id')
                            ->label('Lokasi')
                            ->relationship('office', 'name')
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Waktu')
                    ->schema([
                        Forms\Components\DateTimePicker::make('clock_in')
                            ->label('Clock In')
                            ->required(),
                        Forms\Components\DateTimePicker::make('clock_out')
                            ->label('Clock Out'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('office.name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('clock_in')
                    ->label('Clock In')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('clock_out')
                    ->label('Clock Out')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('Belum checkout'),
                Tables\Columns\TextColumn::make('work_duration_formatted')
                    ->label('Durasi')
                    ->badge()
                    ->color('success'),
                Tables\Columns\ImageColumn::make('clock_in_photo')
                    ->label('Foto Masuk')
                    ->disk('public')
                    ->circular()
                    ->size(40),
                Tables\Columns\ImageColumn::make('clock_out_photo')
                    ->label('Foto Keluar')
                    ->disk('public')
                    ->circular()
                    ->size(40),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('clock_in', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('office')
                    ->label('Lokasi')
                    ->relationship('office', 'name'),
                Tables\Filters\Filter::make('clock_in')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('clock_in', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('clock_in', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Absensi')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Karyawan'),
                        Infolists\Components\TextEntry::make('office.name')
                            ->label('Lokasi'),
                    ])->columns(2),
                Infolists\Components\Section::make('Waktu & Durasi')
                    ->schema([
                        Infolists\Components\TextEntry::make('clock_in')
                            ->label('Clock In')
                            ->dateTime('d M Y H:i:s'),
                        Infolists\Components\TextEntry::make('clock_out')
                            ->label('Clock Out')
                            ->dateTime('d M Y H:i:s')
                            ->placeholder('Belum checkout'),
                        Infolists\Components\TextEntry::make('work_duration_formatted')
                            ->label('Durasi Kerja')
                            ->badge()
                            ->color('success'),
                    ])->columns(3),
                Infolists\Components\Section::make('Foto Absensi')
                    ->schema([
                        Infolists\Components\ImageEntry::make('clock_in_photo')
                            ->label('Foto Clock In')
                            ->disk('public')
                            ->height(200),
                        Infolists\Components\ImageEntry::make('clock_out_photo')
                            ->label('Foto Clock Out')
                            ->disk('public')
                            ->height(200)
                            ->placeholder('Belum ada foto'),
                    ])->columns(2),
                Infolists\Components\Section::make('Koordinat GPS')
                    ->schema([
                        Infolists\Components\TextEntry::make('clock_in_lat')
                            ->label('Lat Masuk'),
                        Infolists\Components\TextEntry::make('clock_in_long')
                            ->label('Long Masuk'),
                        Infolists\Components\TextEntry::make('clock_out_lat')
                            ->label('Lat Keluar')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('clock_out_long')
                            ->label('Long Keluar')
                            ->placeholder('-'),
                    ])->columns(4),
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'view' => Pages\ViewAttendance::route('/{record}'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
