<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EptOnlineSectionResource\Pages;
use App\Models\EptOnlineForm;
use App\Models\EptOnlineSection;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EptOnlineSectionResource extends BaseResource
{
    protected static ?string $model = EptOnlineSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'EPT';
    protected static ?string $navigationLabel = 'Section Tes Online';
    protected static ?string $navigationParentItem = 'Paket Tes Online';
    protected static ?string $modelLabel = 'Section Tes Online';
    protected static ?string $pluralModelLabel = 'Section Tes Online';
    protected static ?int $navigationSort = 7;

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
            Forms\Components\Select::make('type')
                ->label('Tipe Section')
                ->options(EptOnlineSection::typeOptions())
                ->unique(
                    table: 'ept_online_sections',
                    column: 'type',
                    ignoreRecord: true,
                    modifyRuleUsing: fn ($rule, $get) => $rule->where('form_id', $get('form_id'))
                )
                ->required()
                ->native(false),
            Forms\Components\TextInput::make('title')
                ->label('Judul')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('duration_minutes')
                ->label('Durasi (menit)')
                ->numeric()
                ->minValue(1)
                ->required(),
            Forms\Components\TextInput::make('sort_order')
                ->label('Urutan')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\FileUpload::make('audio_path')
                ->label('Audio Section')
                ->disk('local')
                ->directory('ept-online/audio')
                ->visibility('private')
                ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-m4a']),
            Forms\Components\TextInput::make('audio_duration_seconds')
                ->label('Durasi Audio (detik)')
                ->numeric()
                ->minValue(0),
            Forms\Components\Textarea::make('instructions')
                ->label('Instruksi')
                ->rows(5)
                ->columnSpanFull(),
            Forms\Components\Hidden::make('meta.source')
                ->default('manual')
                ->dehydrated(),
            Forms\Components\Section::make('Meta Listening')
                ->schema([
                    Forms\Components\Section::make('Intro Section')
                        ->schema([
                            Forms\Components\TextInput::make('meta.intro.heading')
                                ->label('Heading Intro')
                                ->maxLength(255)
                                ->default('Petunjuk Listening'),
                            Forms\Components\Textarea::make('meta.intro.text')
                                ->label('Teks Intro Listening')
                                ->rows(5)
                                ->columnSpanFull(),
                        ])
                        ->columns(1),
                    static::makeListeningPartSection('A'),
                    static::makeListeningPartSection('B'),
                    static::makeListeningPartSection('C'),
                ])
                ->visible(fn (Get $get): bool => $get('type') === EptOnlineSection::TYPE_LISTENING)
                ->columnSpanFull(),
            Forms\Components\KeyValue::make('meta')
                ->label('Meta')
                ->visible(fn (Get $get): bool => $get('type') !== EptOnlineSection::TYPE_LISTENING)
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
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Menit')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Soal')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('form_id')
                    ->relationship('form', 'code')
                    ->label('Paket Tes'),
                Tables\Filters\SelectFilter::make('type')
                    ->options(EptOnlineSection::typeOptions())
                    ->label('Tipe Section'),
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
            'index' => Pages\ListEptOnlineSections::route('/'),
            'create' => Pages\CreateEptOnlineSection::route('/create'),
            'edit' => Pages\EditEptOnlineSection::route('/{record}/edit'),
        ];
    }

    public static function normalizeFormData(array $data): array
    {
        if (isset($data['meta']) && is_array($data['meta'])) {
            $data['meta'] = static::filterNestedArray($data['meta']);
        }

        return $data;
    }

    public static function prepareFormDataForFill(array $data): array
    {
        if (($data['type'] ?? null) !== EptOnlineSection::TYPE_LISTENING) {
            return $data;
        }

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $intro = is_array($meta['intro'] ?? null) ? $meta['intro'] : [];
        $partInstructions = is_array($meta['part_instructions'] ?? null) ? $meta['part_instructions'] : [];
        $partExamples = is_array($meta['part_examples'] ?? null) ? $meta['part_examples'] : [];

        $normalizedExamples = [];

        foreach (['A', 'B', 'C'] as $part) {
            $value = $partExamples[$part] ?? [];

            if (! is_array($value) || $value === []) {
                $normalizedExamples[$part] = [];
                continue;
            }

            if (array_is_list($value)) {
                $normalizedExamples[$part] = collect($value)
                    ->filter(fn ($item): bool => is_array($item))
                    ->values()
                    ->all();
                continue;
            }

            $normalizedExamples[$part] = [$value];
        }

        $data['meta'] = [
            'source' => $meta['source'] ?? 'manual',
            'intro' => [
                'heading' => $intro['heading'] ?? null,
                'text' => $intro['text'] ?? null,
            ],
            'part_instructions' => [
                'A' => $partInstructions['A'] ?? null,
                'B' => $partInstructions['B'] ?? null,
                'C' => $partInstructions['C'] ?? null,
            ],
            'part_examples' => $normalizedExamples,
        ];

        return $data;
    }

    protected static function makeListeningPartSection(string $part): Forms\Components\Section
    {
        return Forms\Components\Section::make('Part ' . $part)
            ->schema([
                Forms\Components\Textarea::make("meta.part_instructions.{$part}")
                    ->label('Instruksi Part ' . $part)
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make("meta.part_examples.{$part}")
                    ->label('Example Part ' . $part)
                    ->schema(static::listeningExampleSchema())
                    ->defaultItems(0)
                    ->maxItems(5)
                    ->addActionLabel('Tambah Example')
                    ->collapsed()
                    ->cloneable()
                    ->itemLabel(fn (array $state): ?string => filled($state['title'] ?? null) ? $state['title'] : 'Example')
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    protected static function listeningExampleSchema(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label('Judul Example')
                ->maxLength(255),
            Forms\Components\TextInput::make('audio_label')
                ->label('Label Audio')
                ->maxLength(255)
                ->default('On the recording, you will hear:'),
            Forms\Components\Textarea::make('audio_text')
                ->label('Teks Audio')
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('book_label')
                ->label('Label Buku/Layar')
                ->maxLength(255)
                ->default('In your test book, you will read:'),
            Forms\Components\Textarea::make('book_text')
                ->label('Teks Buku/Layar')
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('explanation')
                ->label('Penjelasan')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    protected static function filterNestedArray(array $value): array
    {
        $filtered = [];

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $item = static::filterNestedArray($item);
            }

            $isEmptyString = is_string($item) && trim($item) === '';
            $isEmptyArray = is_array($item) && $item === [];

            if ($item === null || $isEmptyString || $isEmptyArray) {
                continue;
            }

            $filtered[$key] = $item;
        }

        return $filtered;
    }
}
