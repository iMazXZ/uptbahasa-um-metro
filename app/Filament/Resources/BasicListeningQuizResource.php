<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BasicListeningQuizResource\Pages;
use App\Models\BasicListeningQuiz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class BasicListeningQuizResource extends BaseResource
{
    protected static ?string $model = BasicListeningQuiz::class;
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationGroup = 'Basic Listening';
    protected static ?string $pluralLabel = 'Buat Paket Soal';
    protected static ?string $navigationParentItem = 'Meeting';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Paket Soal';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('session_id')
                ->relationship('session', 'title')
                ->label('Untuk Meeting Berapa')
                ->required()
                ->searchable()
                ->preload()
                ->helperText('Satu quiz per session.')
                ->validationMessages([
                    'required' => 'Pilih session terlebih dahulu.',
                ]),
            
            Forms\Components\TextInput::make('title')
                ->required()
                ->label('Nama Paket Soal')
                ->unique(
                    table: 'basic_listening_quizzes',
                    column: 'title',
                    ignoreRecord: true, // Ini yang penting untuk edit
                    modifyRuleUsing: function ($rule, $get) {
                        return $rule->where('session_id', $get('session_id'));
                    }
                )
                ->validationMessages([
                    'required' => 'Judul quiz harus diisi.',
                    'unique' => 'Judul quiz sudah digunakan di session ini.',
                ]),
            
            Forms\Components\Toggle::make('is_active')
                ->default(true)
                ->label('Aktif')
                ->validationMessages([
                    'boolean' => 'Status aktif harus berupa true atau false.',
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session.number')
                    ->label('#')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('Nama Paket Soal')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('session.title')
                    ->label('Untuk Meeting')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->label('Dibuat')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('session_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBasicListeningQuizzes::route('/'),
            'create' => Pages\CreateBasicListeningQuiz::route('/create'),
            'edit' => Pages\EditBasicListeningQuiz::route('/{record}/edit'),
            'view' => Pages\ViewBasicListeningQuiz::route('/{record}'),
        ];
    }
}