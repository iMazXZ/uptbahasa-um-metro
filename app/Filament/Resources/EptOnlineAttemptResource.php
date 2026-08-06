<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EptOnlineAttemptResource\Pages;
use App\Models\EptOnlineAttempt;
use App\Models\EptOnlineForm;
use App\Support\EptOnlineAttemptFinalizer;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EptOnlineAttemptResource extends BaseResource
{
    protected static ?string $model = EptOnlineAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'EPT';
    protected static ?string $navigationLabel = 'Attempt Online';
    protected static ?string $modelLabel = 'Attempt Online';
    protected static ?string $pluralModelLabel = 'Attempt Online';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Ringkasan Attempt')
                ->columns(4)
                ->schema([
                    TextEntry::make('form.code')
                        ->label('Paket')
                        ->weight('bold')
                        ->placeholder('-'),
                    TextEntry::make('user.name')
                        ->label('Peserta')
                        ->placeholder('-'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            EptOnlineAttempt::STATUS_SUBMITTED => 'success',
                            EptOnlineAttempt::STATUS_EXPIRED => 'danger',
                            EptOnlineAttempt::STATUS_CANCELLED => 'gray',
                            EptOnlineAttempt::STATUS_IN_PROGRESS => 'warning',
                            default => 'info',
                        }),
                    TextEntry::make('current_section_type')
                        ->label('Section Terakhir')
                        ->badge()
                        ->placeholder('-'),
                    TextEntry::make('started_at')
                        ->label('Mulai')
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),
                    TextEntry::make('submitted_at')
                        ->label('Submit')
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),
                    TextEntry::make('answered_summary')
                        ->label('Jawaban Tersimpan')
                        ->state(function (EptOnlineAttempt $record): string {
                            $record->loadMissing('answers');
                            $answered = $record->answers
                                ->whereNotNull('selected_option')
                                ->where('selected_option', '!=', '')
                                ->count();

                            $total = $record->form?->questions()->count() ?? 0;

                            return $answered . ' / ' . $total;
                        }),
                    TextEntry::make('result_summary')
                        ->label('Skor Raw')
                        ->state(function (EptOnlineAttempt $record): string {
                            $result = $record->result;

                            if (! $result) {
                                return '-';
                            }

                            return collect([
                                'L ' . ($result->listening_raw ?? 0),
                                'S ' . ($result->structure_raw ?? 0),
                                'R ' . ($result->reading_raw ?? 0),
                            ])->implode(' • ');
                        }),
                ]),

            ViewEntry::make('answers_audit')
                ->view('filament.ept-online.attempt-answers-view')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('form.code')
                    ->label('Paket')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Grup')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EptOnlineAttempt::STATUS_SUBMITTED => 'success',
                        EptOnlineAttempt::STATUS_EXPIRED => 'danger',
                        EptOnlineAttempt::STATUS_CANCELLED => 'gray',
                        EptOnlineAttempt::STATUS_IN_PROGRESS => 'warning',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('current_section_type')
                    ->label('Section')
                    ->badge()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submit')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('form_id')
                    ->options(EptOnlineForm::query()->orderBy('code')->pluck('code', 'id')->all())
                    ->label('Paket Tes'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(EptOnlineAttempt::statusOptions())
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus Terpilih'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEptOnlineAttempts::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        app(EptOnlineAttemptFinalizer::class)->finalizeExpiredAttempts(100);

        return parent::getEloquentQuery()
            ->with([
                'form:id,code,title',
                'user:id,name',
                'group:id,name',
                'result',
            ]);
    }
}
