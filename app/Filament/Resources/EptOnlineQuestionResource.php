<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EptOnlineQuestionResource\Pages;
use App\Models\EptOnlineForm;
use App\Models\EptOnlinePassage;
use App\Models\EptOnlineQuestion;
use App\Models\EptOnlineSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class EptOnlineQuestionResource extends BaseResource
{
    protected static ?string $model = EptOnlineQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationGroup = 'EPT';
    protected static ?string $navigationLabel = 'Bank Soal Tes Online';
    protected static ?string $navigationParentItem = 'Paket Tes Online';
    protected static ?string $modelLabel = 'Soal Tes Online';
    protected static ?string $pluralModelLabel = 'Bank Soal Tes Online';
    protected static ?int $navigationSort = 9;

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
            Forms\Components\Select::make('passage_id')
                ->label('Passage (opsional)')
                ->relationship('passage', 'passage_code')
                ->getOptionLabelFromRecordUsing(fn (EptOnlinePassage $record): string => $record->passage_code . ($record->title ? ' - ' . $record->title : ''))
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('part_label')
                ->label('Part'),
            Forms\Components\TextInput::make('group_code')
                ->label('Group Code'),
            Forms\Components\TextInput::make('number_in_section')
                ->label('Nomor Soal')
                ->numeric()
                ->minValue(1)
                ->unique(
                    table: 'ept_online_questions',
                    column: 'number_in_section',
                    ignoreRecord: true,
                    modifyRuleUsing: fn ($rule, $get) => $rule
                        ->where('form_id', $get('form_id'))
                        ->where('section_id', $get('section_id'))
                )
                ->required(),
            Forms\Components\TextInput::make('sort_order')
                ->label('Urutan')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\Textarea::make('instruction')
                ->label('Instruksi')
                ->rows(3)
                ->helperText('Bisa pakai [br] untuk baris baru dan [p] untuk paragraf baru.')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('prompt')
                ->label('Prompt / Question Text')
                ->rows(4)
                ->required()
                ->helperText('Gunakan [u]teks[/u] untuk underline, [br] untuk baris baru, dan [p] untuk paragraf baru.')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('option_a')
                ->label('Option A')
                ->helperText('Bisa pakai [u]teks[/u], [br], dan [p].')
                ->required(),
            Forms\Components\Textarea::make('option_b')
                ->label('Option B')
                ->helperText('Bisa pakai [u]teks[/u], [br], dan [p].')
                ->required(),
            Forms\Components\Textarea::make('option_c')
                ->label('Option C')
                ->helperText('Bisa pakai [u]teks[/u], [br], dan [p].')
                ->required(),
            Forms\Components\Textarea::make('option_d')
                ->label('Option D')
                ->helperText('Bisa pakai [u]teks[/u], [br], dan [p].')
                ->required(),
            Forms\Components\Select::make('correct_option')
                ->label('Jawaban Benar')
                ->options([
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                    'D' => 'D',
                ])
                ->required()
                ->native(false),
            Forms\Components\KeyValue::make('meta')
                ->label('Meta')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('form.code')
                    ->label('Paket')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('section.type')
                    ->label('Section')
                    ->badge(),
                Tables\Columns\TextColumn::make('number_in_section')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('passage.passage_code')
                    ->label('Passage')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('prompt')
                    ->label('Prompt')
                    ->limit(60)
                    ->searchable(),
                Tables\Columns\TextColumn::make('correct_option')
                    ->label('Kunci')
                    ->badge()
                    ->color('success'),
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
            'index' => Pages\ListEptOnlineQuestions::route('/'),
            'create' => Pages\CreateEptOnlineQuestion::route('/create'),
            'edit' => Pages\EditEptOnlineQuestion::route('/{record}/edit'),
        ];
    }
}
