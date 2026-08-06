<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateCategoryResource\Pages;
use App\Models\CertificateCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateCategoryResource extends BaseResource
{
    protected static ?string $model = CertificateCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Sertifikat';
    protected static ?string $navigationLabel = 'Kategori Sertifikat';
    protected static ?string $modelLabel = 'Kategori Sertifikat';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Kategori')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Kategori')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Sertifikat Interactive Class Bahasa Inggris'),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->maxLength(255)
                        ->placeholder('Auto-generate dari nama'),

                    Forms\Components\TextInput::make('code_prefix')
                        ->label('Kode Prefix Verifikasi')
                        ->maxLength(10)
                        ->placeholder('IE')
                        ->helperText('Contoh: IE untuk Interactive English → kode verifikasi jadi IE-{SRN}'),

                    Forms\Components\TextInput::make('number_format')
                        ->label('Format Nomor Sertifikat')
                        ->required()
                        ->helperText('Placeholders: {seq}, {seq3}, {semester}, {year}, {year_short}, {no_induk}, {no_induk3}, {group}, {absen}, {year_csv}, {year_plus_one}')
                        ->placeholder('{seq}.{semester}/II.3.AU/A/EPP.LB.{year}'),

                    Forms\Components\TextInput::make('last_sequence')
                        ->label('Nomor Urut Terakhir')
                        ->numeric()
                        ->default(0)
                        ->helperText('Akan auto-increment saat sertifikat baru dibuat'),

                    Forms\Components\TextInput::make('pdf_template')
                        ->label('Template PDF')
                        ->placeholder('epp-certificate')
                        ->helperText('Nama file blade tanpa .blade.php'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),

            Forms\Components\Section::make('Pengaturan Semester')
                ->schema([
                    Forms\Components\TagsInput::make('semesters')
                        ->label('Semester yang Tersedia')
                        ->placeholder('Contoh: 1, 2, 3, 4, 5, 6')
                        ->helperText('Kosongkan jika tidak menggunakan semester'),
                ]),

            Forms\Components\Section::make('Field Nilai')
                ->schema([
                    Forms\Components\TagsInput::make('score_fields')
                        ->label('Kolom Nilai')
                        ->placeholder('listening, speaking, reading, writing, phonetics, vocabulary, structure')
                        ->helperText('Nama field nilai yang akan muncul di form input sertifikat'),
                ]),

            Forms\Components\Section::make('Aturan Grade')
                ->description('Atur rumus penentuan grade berdasarkan nilai rata-rata. Urutkan dari tertinggi ke terendah.')
                ->schema([
                    Forms\Components\Repeater::make('grade_rules')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('min')
                                ->label('Nilai Min')
                                ->numeric()
                                ->step(0.5)
                                ->required()
                                ->placeholder('79.5'),
                            Forms\Components\TextInput::make('grade')
                                ->label('Grade')
                                ->required()
                                ->placeholder('A'),
                            Forms\Components\TextInput::make('level')
                                ->label('Level')
                                ->placeholder('Excellent'),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah Aturan')
                        ->helperText('Contoh untuk Interactive English: min=79.5 grade=A level=Excellent | min=76.5 grade=A- level=Very good | dst'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                Tables\Columns\TextColumn::make('number_format')
                    ->label('Format Nomor')
                    ->limit(30),

                Tables\Columns\TextColumn::make('last_sequence')
                    ->label('Urut Terakhir')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('certificates_count')
                    ->label('Jumlah Sertifikat')
                    ->counts('certificates')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateCategories::route('/'),
            'create' => Pages\CreateCertificateCategory::route('/create'),
            'edit' => Pages\EditCertificateCategory::route('/{record}/edit'),
        ];
    }
}
