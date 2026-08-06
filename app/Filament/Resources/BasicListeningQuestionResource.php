<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BasicListeningQuestionResource\Pages;
use App\Models\BasicListeningQuestion;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class BasicListeningQuestionResource extends BaseResource
{
    protected static ?string $model = BasicListeningQuestion::class;
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationGroup = 'Basic Listening';
    protected static ?string $pluralLabel = 'Bank Soal';
    protected static ?string $navigationParentItem = 'Meeting';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Soal';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // === 1. PENGATURAN UTAMA ===
            Forms\Components\Section::make('Konfigurasi Soal')
                ->schema([
                    Forms\Components\Select::make('quiz_id')
                        ->relationship('quiz', 'title')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->label('Paket Soal (Quiz)'),

                    Forms\Components\Select::make('type')
                        ->label('Tipe Soal')
                        ->options([
                            'multiple_choice' => 'Multiple Choice / True False',
                            'fib_paragraph'   => 'Fill in the Blank (Paragraph)',
                        ])
                        ->default('multiple_choice')
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('fib_placeholders', null))
                        ->required(),

                    Forms\Components\TextInput::make('order')
                        ->label('Nomor Urut')
                        ->numeric()
                        ->default(0)
                        ->helperText('Urutan tampilan soal dalam kuis.'),
                ])->columns(3),

            // === 2. EDITOR SOAL: MULTIPLE CHOICE ===
            Forms\Components\Section::make('Detail Multiple Choice')
                ->description('Isi pertanyaan dan opsi jawaban. Untuk True/False, cukup isi Opsi A dan B.')
                ->visible(fn (Get $get) => $get('type') === 'multiple_choice')
                ->schema([
                    Forms\Components\Textarea::make('question')
                        ->label('Pertanyaan')
                        ->rows(3)
                        ->required(),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Textarea::make('option_a')->label('Opsi A')->rows(2)->required(),
                        Forms\Components\Textarea::make('option_b')->label('Opsi B')->rows(2)->required(),
                        Forms\Components\Textarea::make('option_c')->label('Opsi C')->rows(2),
                        Forms\Components\Textarea::make('option_d')->label('Opsi D')->rows(2),
                    ]),

                    Forms\Components\Select::make('correct')
                        ->label('Kunci Jawaban Benar')
                        ->options([
                            'A' => 'A',
                            'B' => 'B',
                            'C' => 'C',
                            'D' => 'D',
                        ])
                        ->required()
                        ->native(false),
                ]),

            // === 3. EDITOR SOAL: FIB PARAGRAPH ===
            Forms\Components\Section::make('Detail Fill in the Blank')
                ->description('Tulis paragraf dengan penanda [[1]], [[2]], dst. Lalu isi kunci jawaban di bawah.')
                ->visible(fn (Get $get) => $get('type') === 'fib_paragraph')
                ->schema([
                    Forms\Components\Textarea::make('paragraph_text')
                        ->label('Paragraf Soal')
                        ->rows(10)
                        ->required()
                        ->live(onBlur: true) // Update regex saat user selesai mengetik (blur)
                        ->afterStateUpdated(function ($state, Set $set) {
                            // Regex untuk menangkap [[1]], [[2]], dst
                            preg_match_all('/\[\[(\d+)\]\]/', $state ?? '', $matches);
                            // Ambil angkanya saja, urutkan, dan unik
                            $placeholders = array_values(array_unique($matches[1] ?? []));
                            sort($placeholders); 
                            
                            $set('fib_placeholders', $placeholders);
                        })
                        ->helperText('Contoh: "Hello, my name is [[1]]. I am from [[2]]."'),

                    // Hidden field untuk menyimpan array placeholders yang terdeteksi
                    Forms\Components\Hidden::make('fib_placeholders'),

                    Forms\Components\KeyValue::make('fib_answer_key')
                        ->label('Kunci Jawaban')
                        ->helperText('Klik "Add Row". Key = Nomor Blank (misal 1), Value = Jawaban Benar.')
                        ->keyLabel('Nomor Blank (1, 2, ...)')
                        ->valueLabel('Jawaban Benar')
                        ->reorderable()
                        ->required(),
                    
                    Forms\Components\Group::make()->schema([
                        Forms\Components\KeyValue::make('fib_weights')
                            ->label('Bobot Nilai (Opsional)')
                            ->helperText('Jika kosong, semua blank dianggap setara.')
                            ->keyLabel('Nomor Blank')
                            ->valueLabel('Bobot'),

                        Forms\Components\KeyValue::make('fib_scoring')
                            ->label('Pengaturan Penilaian')
                            ->default([
                                'case_sensitive' => false,
                                'allow_trim' => true,
                                'strip_punctuation' => true,
                            ])
                            ->keyLabel('Setting')
                            ->valueLabel('Value (1/0/true/false)')
                            ->addable(false) // Kunci setting fix
                            ->deletable(false)
                            ->editableKeys(false),
                    ])->columns(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kolom ID/Urutan
                TextColumn::make('order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                // Kolom Informasi Quiz (Parent)
                TextColumn::make('quiz.title')
                    ->label('Paket')
                    ->description(fn (BasicListeningQuestion $record) => 'Sesi: ' . ($record->quiz?->session?->number ?? '-'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                // Kolom Tipe
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'fib_paragraph' => 'FIB',
                        'multiple_choice' => 'PG / TF',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'fib_paragraph' => 'warning',
                        'multiple_choice' => 'success',
                        default => 'gray',
                    }),

                // Kolom Pertanyaan (Conditional)
                TextColumn::make('question_preview')
                    ->label('Pertanyaan / Paragraf')
                    ->state(function (BasicListeningQuestion $record) {
                        if ($record->type === 'fib_paragraph') {
                            return strip_tags($record->paragraph_text); // Bersihkan tag HTML jika ada
                        }
                        return $record->question;
                    })
                    ->limit(50)
                    ->searchable(['question', 'paragraph_text']),

                // Kolom Kunci Jawaban (Conditional)
                TextColumn::make('answer_preview')
                    ->label('Kunci')
                    ->badge()
                    ->color('info')
                    ->state(function (BasicListeningQuestion $record) {
                        if ($record->type === 'fib_paragraph') {
                            $count = is_array($record->fib_answer_key) ? count($record->fib_answer_key) : 0;
                            return $count . ' Blanks';
                        }
                        return $record->correct;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('quiz_id')
                    ->relationship('quiz', 'title')
                    ->label('Filter Paket Soal')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'multiple_choice' => 'Multiple Choice',
                        'fib_paragraph' => 'Fill in the Blank',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('quiz_id', 'desc')
            ->poll('10s'); // Auto refresh table
    }

    public static function getRelations(): array
    {
        return [
            // Tambahkan RelationManager jika perlu
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBasicListeningQuestions::route('/'),
            'create' => Pages\CreateBasicListeningQuestion::route('/create'),
            'edit' => Pages\EditBasicListeningQuestion::route('/{record}/edit'),
        ];
    }
}