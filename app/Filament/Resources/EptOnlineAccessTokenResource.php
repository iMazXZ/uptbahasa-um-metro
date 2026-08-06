<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EptOnlineAccessTokenResource\Pages;
use App\Models\EptOnlineAccessToken;
use App\Models\EptOnlineForm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class EptOnlineAccessTokenResource extends BaseResource
{
    protected static ?string $model = EptOnlineAccessToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'EPT';
    protected static ?string $navigationLabel = 'Token Tes Online';
    protected static ?string $modelLabel = 'Token Tes Online';
    protected static ?string $pluralModelLabel = 'Token Tes Online';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Target Paket')
                ->schema([
                    Forms\Components\Select::make('form_id')
                        ->label('Paket Tes')
                        ->relationship('form', 'code')
                        ->getOptionLabelFromRecordUsing(fn (EptOnlineForm $record): string => $record->code . ' - ' . $record->title)
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('user_id')
                        ->label('User Peserta (opsional)')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('ept_group_id')
                        ->label('Grup EPT (opsional)')
                        ->relationship('group', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('ept_registration_id')
                        ->label('ID Registrasi EPT (opsional)')
                        ->numeric(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Konfigurasi Token')
                ->schema([
                    Forms\Components\TextInput::make('plain_token')
                        ->label('Token Akses')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText('Token asli tidak disimpan, hanya hash-nya.'),
                    Forms\Components\TextInput::make('max_attempts')
                        ->label('Maksimal Attempt')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Mulai Berlaku')
                        ->native(false)
                        ->seconds(false),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Berakhir')
                        ->native(false)
                        ->seconds(false),
                    Forms\Components\KeyValue::make('meta')
                        ->label('Meta Tambahan')
                        ->helperText('Opsional untuk catatan teknis.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('form.code')
                    ->label('Paket')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('token_hint')
                    ->label('Hint Token')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Grup')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('max_attempts')
                    ->label('Max')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('used_attempts')
                    ->label('Terpakai')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Berakhir')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('form_id')
                    ->relationship('form', 'code')
                    ->label('Paket Tes'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function makeTokenHint(string $token): string
    {
        $token = trim($token);
        $len = mb_strlen($token);

        if ($len <= 4) {
            return mb_substr($token, 0, 1) . '••' . mb_substr($token, -1);
        }

        return mb_substr($token, 0, 2) . '•••' . mb_substr($token, -2);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEptOnlineAccessTokens::route('/'),
            'create' => Pages\CreateEptOnlineAccessToken::route('/create'),
            'edit' => Pages\EditEptOnlineAccessToken::route('/{record}/edit'),
        ];
    }
}
