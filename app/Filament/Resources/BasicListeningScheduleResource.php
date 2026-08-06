<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BasicListeningScheduleResource\Pages;
use App\Models\BasicListeningSchedule;
use App\Models\BasicListeningSession;
use App\Models\Prody;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class BasicListeningScheduleResource extends BaseResource
{
    protected static ?string $model = BasicListeningSchedule::class;

    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Jadwal Basic Listening';
    protected static ?string $pluralModelLabel = 'Jadwal Basic Listening';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user?->hasPermissionTo('view_any_basic::listening::schedule') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('prody_id')
                ->label('Program Studi')
                ->options(\App\Models\Prody::pluck('name','id'))
                ->searchable()
                ->required(),

            Forms\Components\Select::make('tutors')   // array of user IDs
                ->label('Tutor / Asisten')
                ->multiple()
                ->preload()
                ->searchable()
                ->options(
                    User::query()
                        ->whereHas('roles', fn ($q) => $q->where('name', 'tutor'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->default(fn ($record) => $record?->tutors()->pluck('users.id')->all() ?? [])
                ->required(),

            Forms\Components\Select::make('hari')
                ->options(['Senin'=>'Senin','Selasa'=>'Selasa','Rabu'=>'Rabu','Kamis'=>'Kamis','Jumat'=>'Jumat'])
                ->required(),

            Forms\Components\TimePicker::make('jam_mulai')->seconds(false)->required(),
            Forms\Components\TimePicker::make('jam_selesai')->seconds(false)->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('prody.name')->label('Prodi')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('tutor_names')
                    ->label('Tutor')
                    ->getStateUsing(fn ($record) => $record->tutors->pluck('name')->join(', '))
                    ->wrap()
                    ->limit(80),
                Tables\Columns\TextColumn::make('hari')->sortable(),
                Tables\Columns\TextColumn::make('jam_mulai')->time('H:i'),
                Tables\Columns\TextColumn::make('jam_selesai')->time('H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('hari')
                    ->options(['Senin'=>'Senin','Selasa'=>'Selasa','Rabu'=>'Rabu','Kamis'=>'Kamis','Jumat'=>'Jumat']),
                Tables\Filters\SelectFilter::make('prody_id')->label('Prodi')
                    ->options(\App\Models\Prody::pluck('name','id')),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['tutors:id,name','prody:id,name']);
        
        // Filter by BL period start date
        $startDate = \App\Models\SiteSetting::getBlPeriodStartDate();
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBasicListeningSchedules::route('/'),
            'create' => Pages\CreateBasicListeningSchedule::route('/create'),
            'edit'   => Pages\EditBasicListeningSchedule::route('/{record}/edit'),
        ];
    }
}
