<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EptOnlinePassageResource\Pages;
use App\Models\EptOnlineForm;
use App\Models\EptOnlinePassage;
use App\Models\EptOnlineSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EptOnlinePassageResource extends BaseResource
{
    protected static ?string $model = EptOnlinePassage::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'EPT';
    protected static ?string $navigationLabel = 'Passage Tes Online';
    protected static ?string $navigationParentItem = 'Paket Tes Online';
    protected static ?string $modelLabel = 'Passage Tes Online';
    protected static ?string $pluralModelLabel = 'Passage Tes Online';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('form_id')
                ->label('Paket Tes')
                ->relationship('form', 'code')
                ->getOptionLabelFromRecordUsing(fn (EptOnlineForm $record): string => $record->code . ' - ' . $record->title)
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('section_id')
                ->label('Section')
                ->relationship('section', 'title')
                ->getOptionLabelFromRecordUsing(fn (EptOnlineSection $record): string => strtoupper($record->type) . ' - ' . $record->title)
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('passage_code')
                ->label('Kode Passage')
                ->unique(
                    table: 'ept_online_passages',
                    column: 'passage_code',
                    ignoreRecord: true,
                    modifyRuleUsing: fn ($rule, $get) => $rule->where('form_id', $get('form_id'))
                )
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('title')
                ->label('Judul')
                ->maxLength(255),
            Forms\Components\TextInput::make('sort_order')
                ->label('Urutan')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\Textarea::make('content')
                ->label('Isi Passage')
                ->rows(14)
                ->helperText('Gunakan [br] untuk baris baru dan [p] untuk paragraf baru jika tidak ingin memakai line break Excel.')
                ->required()
                ->columnSpanFull(),
            Forms\Components\KeyValue::make('meta')
                ->label('Meta')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('questions'))
            ->columns([
                Tables\Columns\TextColumn::make('form.code')
                    ->label('Paket')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('section.type')
                    ->label('Section')
                    ->badge(),
                Tables\Columns\TextColumn::make('passage_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->placeholder('-')
                    ->limit(30),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Soal')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('form_id')
                    ->relationship('form', 'code')
                    ->label('Paket Tes'),
                Tables\Filters\SelectFilter::make('section_id')
                    ->relationship('section', 'title')
                    ->label('Section'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEptOnlinePassages::route('/'),
            'create' => Pages\CreateEptOnlinePassage::route('/create'),
            'edit' => Pages\EditEptOnlinePassage::route('/{record}/edit'),
        ];
    }
}
