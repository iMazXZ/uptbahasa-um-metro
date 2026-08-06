<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EptOnlineFormResource\Pages;
use App\Models\EptOnlineForm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EptOnlineFormResource extends BaseResource
{
    protected static ?string $model = EptOnlineForm::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'EPT';
    protected static ?string $navigationLabel = 'Paket Tes Online';
    protected static ?string $modelLabel = 'Paket Tes Online';
    protected static ?string $pluralModelLabel = 'Paket Tes Online';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Paket')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Kode Paket')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('title')
                        ->label('Judul Paket')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(EptOnlineForm::statusOptions())
                        ->default(EptOnlineForm::STATUS_DRAFT)
                        ->required()
                        ->native(false),
                    Forms\Components\Toggle::make('show_score_after_submit')
                        ->label('Tampilkan nilai langsung setelah submit')
                        ->helperText('Jika aktif, peserta langsung melihat skor akhir setelah tes selesai tanpa menunggu publish manual.')
                        ->default(true)
                        ->inline(false),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(3),
            Forms\Components\Section::make('Audio Listening')
                ->schema([
                    Forms\Components\FileUpload::make('listening_audio_path')
                        ->label('Audio Listening (1 file)')
                        ->disk('local')
                        ->directory('ept-online/audio')
                        ->visibility('private')
                        ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-m4a'])
                        ->helperText('Audio listening diupload terpisah dari workbook soal dan akan disajikan lewat stream private ke peserta.')
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Ringkasan Import')
                ->schema([
                    Forms\Components\Placeholder::make('imported_at_info')
                        ->label('Import Terakhir')
                        ->content(fn (?EptOnlineForm $record): string => $record?->imported_at?->translatedFormat('d M Y H:i') ?? 'Belum pernah import'),
                    Forms\Components\Placeholder::make('published_at_info')
                        ->label('Published At')
                        ->content(fn (?EptOnlineForm $record): string => $record?->published_at?->translatedFormat('d M Y H:i') ?? '-'),
                    Forms\Components\Placeholder::make('question_count_info')
                        ->label('Jumlah Soal')
                        ->content(fn (?EptOnlineForm $record): string => $record ? number_format((int) $record->questions()->count()) . ' soal' : '0 soal'),
                    Forms\Components\Placeholder::make('section_count_info')
                        ->label('Jumlah Section')
                        ->content(fn (?EptOnlineForm $record): string => $record ? number_format((int) $record->sections()->count()) . ' section' : '0 section'),
                ])
                ->columns(4)
                ->visible(fn (?EptOnlineForm $record): bool => $record !== null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount(['sections', 'passages', 'questions', 'accessTokens']))
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EptOnlineForm::STATUS_PUBLISHED => 'success',
                        EptOnlineForm::STATUS_ARCHIVED => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\IconColumn::make('listening_audio_path')
                    ->label('Audio')
                    ->boolean()
                    ->trueIcon('heroicon-o-speaker-wave')
                    ->falseIcon('heroicon-o-no-symbol'),
                Tables\Columns\TextColumn::make('sections_count')
                    ->label('Section')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Soal')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('access_tokens_count')
                    ->label('Token')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('imported_at')
                    ->label('Import')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(EptOnlineForm::statusOptions())
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEptOnlineForms::route('/'),
            'create' => Pages\CreateEptOnlineForm::route('/create'),
            'edit' => Pages\EditEptOnlineForm::route('/{record}/edit'),
        ];
    }
}
