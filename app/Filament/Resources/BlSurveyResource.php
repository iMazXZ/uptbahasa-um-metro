<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlSurveyResource\Pages;
use App\Filament\Resources\BlSurveyResource\RelationManagers\QuestionsRelationManager;
use App\Models\BasicListeningCategory;
use App\Models\BasicListeningSurvey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BlSurveyResource extends BaseResource
{
    protected static ?string $model = BasicListeningSurvey::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Basic Listening';
    protected static ?string $label = 'Kuesioner';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Umum')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Judul Kuesioner')
                        ->placeholder('Contoh: Kuesioner Penilaian Tutor')
                        ->required()
                        ->maxLength(200),

                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi / Petunjuk')
                        ->rows(3)
                        ->placeholder('Tuliskan instruksi atau tujuan dari kuesioner ini...'),

                    Forms\Components\Select::make('category')
                        ->label('Kategori')
                        ->options(fn () => BasicListeningCategory::query()
                            ->orderBy('position')
                            ->orderBy('id')
                            ->pluck('name', 'slug'))
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('target')
                        ->label('Target')
                        ->options([
                            'final' => 'Kuesioner Akhir',
                            'session' => 'Kuesioner per Pertemuan',
                        ])
                        ->default('final')
                        ->required(),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Urutan Kuesioner')
                        ->numeric()
                        ->default(0)
                        ->helperText('Semakin kecil semakin prioritas jika ada kuesioner aktif dengan kategori yang sama.')
                        ->nullable(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Status & Pengaturan')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktifkan Kuesioner')
                        ->helperText('Jika aktif, kuesioner dapat diakses oleh peserta.'),

                    Forms\Components\Toggle::make('require_for_certificate')
                        ->label('Wajib untuk Sertifikat')
                        ->helperText('Peserta harus mengisi kuesioner ini sebelum dapat mengunduh sertifikat.'),
                ])
                ->columns(2)
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label('ID')
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                BadgeColumn::make('category')
                    ->label('Kategori')
                    ->colors([
                        'primary',
                        'success' => 'supervisor',
                        'info' => 'institute',
                    ])
                    ->formatStateUsing(fn($state, $record) => $record->category_label ?? Str::ucfirst($state)),

                TextColumn::make('target')
                    ->label('Target')
                    ->badge()
                    ->colors([
                        'gray' => 'final',
                        'warning' => 'session',
                    ])
                    ->formatStateUsing(fn($state) => $state === 'final' ? 'Final' : 'Per Pertemuan'),

                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable()
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),

                ToggleColumn::make('require_for_certificate')
                    ->label('Wajib untuk Sertifikat')
                    ->sortable(),

                TextColumn::make('questions_count')
                    ->label('Jumlah Pertanyaan')
                    ->counts('questions')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(fn () => BasicListeningCategory::query()
                        ->orderBy('position')
                        ->orderBy('id')
                        ->pluck('name', 'slug')),

                SelectFilter::make('target')
                    ->label('Target')
                    ->options([
                        'final' => 'Final',
                        'session' => 'Session',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (BasicListeningSurvey $record) {
                        $copy = $record->replicate();
                        $copy->title = $record->title . ' (Copy)';
                        $copy->is_active = false;
                        $copy->require_for_certificate = false;
                        $copy->save();

                        // Duplikat pertanyaan
                        foreach ($record->questions as $q) {
                            $qCopy = $q->replicate();
                            $qCopy->survey_id = $copy->id;
                            $qCopy->save();
                        }
                    })
                    ->successNotificationTitle('Kuesioner berhasil diduplikat.'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order')
            ->defaultPaginationPageOption(25);
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlSurveys::route('/'),
            'create' => Pages\CreateBlSurvey::route('/create'),
            'edit' => Pages\EditBlSurvey::route('/{record}/edit'),
        ];
    }
}
