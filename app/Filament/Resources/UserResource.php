<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Prody;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Get;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

class UserResource extends BaseResource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-s-users';

    protected static ?string $navigationLabel = 'Manajemen User';

    public static ?string $label = 'Manajemen User';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255)
                    ->validationMessages([
                        'required' => 'Wajib Diisi',
                    ])
                    ->dehydrateStateUsing(fn (string $state): string => mb_strtoupper(trim($state), 'UTF-8')),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->validationMessages([
                        'required' => 'Wajib Diisi',
                    ]),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (Page $livewire): bool => $livewire instanceof CreateRecord)
                    ->maxLength(255),
                Forms\Components\TextInput::make('srn')
                    ->label('NPM')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'NPM ini sudah terdaftar pada pengguna lain.',
                    ]),
                Forms\Components\TextInput::make('whatsapp')
                    ->label('Nomor WhatsApp')
                    ->tel()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Nomor WhatsApp ini sudah terdaftar di akun lain.',
                    ]),
                Forms\Components\Toggle::make('whatsapp_verified_at')
                    ->label('Tandai WhatsApp Terverifikasi (manual)')
                    ->helperText('Centang jika nomor sudah dipastikan milik pengguna. Otomatis isi tanggal/verifikasi.')
                    ->default(false)
                    ->dehydrateStateUsing(fn (bool $state) => $state ? now() : null)
                    ->afterStateHydrated(fn (callable $set, $record) => $set('whatsapp_verified_at', filled($record?->whatsapp_verified_at)))
                    ->inline(false),
                Forms\Components\Select::make('prody_id')
                    ->label('Program Studi')
                    ->relationship('prody', 'name')
                    ->searchable()
                    ->preload()
                    ->validationMessages([
                        'required' => 'Wajib Diisi',
                    ]),
                Forms\Components\TextInput::make('year')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('nilaibasiclistening')
                    ->label('Nilai Basic Listening')
                    ->numeric()
                    ->default(null)
                    ->visible(fn (Get $get) => 
                        // Hide for Pendidikan Bahasa Inggris (uses Interactive Class instead)
                        $get('prody_id') && 
                        optional(\App\Models\Prody::find($get('prody_id')))->name !== 'Pendidikan Bahasa Inggris'
                    ),

                // Interactive Class (6 fields) - Only for Pendidikan Bahasa Inggris
                Forms\Components\Section::make('Nilai Interactive Class')
                    ->description('Khusus untuk prodi Pendidikan Bahasa Inggris')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('interactive_class_1')
                                    ->label('Semester 1')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100),
                                Forms\Components\TextInput::make('interactive_class_2')
                                    ->label('Semester 2')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100),
                                Forms\Components\TextInput::make('interactive_class_3')
                                    ->label('Semester 3')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100),
                                Forms\Components\TextInput::make('interactive_class_4')
                                    ->label('Semester 4')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100),
                                Forms\Components\TextInput::make('interactive_class_5')
                                    ->label('Semester 5')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100),
                                Forms\Components\TextInput::make('interactive_class_6')
                                    ->label('Semester 6')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100),
                            ]),
                    ])
                    ->collapsible()
                    ->visible(fn (Get $get) => 
                        $get('prody_id') && 
                        optional(\App\Models\Prody::find($get('prody_id')))->name === 'Pendidikan Bahasa Inggris'
                    ),

                // Interactive Bahasa Arab (2 fields) - Only for 3 Prodi Islam
                Forms\Components\Section::make('Nilai Interactive Bahasa Arab')
                    ->description('Khusus untuk Prodi Islam')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('interactive_bahasa_arab_1')
                                    ->label('Bahasa Arab 1')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100),
                                Forms\Components\TextInput::make('interactive_bahasa_arab_2')
                                    ->label('Bahasa Arab 2')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100),
                            ]),
                    ])
                    ->collapsible()
                    ->visible(fn (Get $get) => 
                        $get('prody_id') && 
                        in_array(
                            optional(\App\Models\Prody::find($get('prody_id')))->name, 
                            ['Komunikasi dan Penyiaran Islam', 'Pendidikan Agama Islam', 'Pendidikan Islam Anak Usia Dini']
                        )
                    ),

                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->live()
                    ->multiple()
                    ->searchable(),

                Forms\Components\FileUpload::make('image')
                    ->label('Foto Profil')
                    ->image()
                    ->default(null)
                    ->columnSpanFull(),

                Forms\Components\Section::make('Tugas Tutor')
                    ->description('Atur prodi yang diampu oleh tutor.')
                    ->schema([
                        Forms\Components\Select::make('tutorProdies')
                            ->label('Prodi yang Diampu')
                            ->relationship('tutorProdies', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Tutor bisa mengampu lebih dari satu prodi, dan satu prodi bisa diampu banyak tutor.')
                            // Wajib diisi hanya ketika role tutor dipilih
                            ->required(function (Forms\Get $get) {
                                $roles = $get('roles');

                                // Normalisasi ke array
                                $roles = is_array($roles) ? $roles : (filled($roles) ? [$roles] : []);

                                // Jika angka, berarti ID role -> ambil namanya
                                $roleNames = collect($roles)->map(function ($val) {
                                    if (is_numeric($val)) {
                                        return optional(\Spatie\Permission\Models\Role::find($val))->name;
                                    }
                                    return $val; // mungkin sudah nama
                                })->filter()->map(fn ($n) => strtolower($n))->all();

                                return in_array('tutor', $roleNames, true);
                            })
                            ->visible(function (Forms\Get $get) {
                                // Tampilkan input ini hanya saat Section tampil (admin + tutor)
                                $userIsAdmin = auth()->user()?->hasRole('Admin') === true;

                                $roles = $get('roles');
                                $roles = is_array($roles) ? $roles : (filled($roles) ? [$roles] : []);
                                $roleNames = collect($roles)->map(function ($val) {
                                    if (is_numeric($val)) {
                                        return optional(\Spatie\Permission\Models\Role::find($val))->name;
                                    }
                                    return $val;
                                })->filter()->map(fn ($n) => strtolower($n))->all();

                                return $userIsAdmin && in_array('tutor', $roleNames, true);
                            }),
                    ])
                    ->visible(function (Forms\Get $get) {
                        // Section hanya tampil kalau: (1) admin yang sedang login, dan (2) role tutor dipilih
                        $userIsAdmin = auth()->user()?->hasRole('Admin') === true;

                        $roles = $get('roles');
                        $roles = is_array($roles) ? $roles : (filled($roles) ? [$roles] : []);
                        $roleNames = collect($roles)->map(function ($val) {
                            if (is_numeric($val)) {
                                return optional(\Spatie\Permission\Models\Role::find($val))->name;
                            }
                            return $val;
                        })->filter()->map(fn ($n) => strtolower($n))->all();

                        return $userIsAdmin && in_array('tutor', $roleNames, true);
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('whatsapp')
                    ->label('WhatsApp')
                    ->copyable()
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $state 
                        ? ($record->whatsapp_verified_at ? "✓ {$state}" : $state)
                        : '-'
                    )
                    ->color(fn ($record) => $record->whatsapp_verified_at ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('srn')
                    ->label('SRN / NPM')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('prody.name')
                    ->label('Program Studi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nilaibasiclistening')
                    ->label('Score BL')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('interactive_class_summary')
                    ->label('Interactive Class')
                    ->getStateUsing(function ($record) {
                        $values = [];
                        for ($i = 1; $i <= 6; $i++) {
                            $val = $record->{"interactive_class_{$i}"};
                            if ($val !== null) $values[] = $val;
                        }
                        return $values ? implode(', ', $values) : '-';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('interactive_bahasa_arab_summary')
                    ->label('Bahasa Arab')
                    ->getStateUsing(function ($record) {
                        $v1 = $record->interactive_bahasa_arab_1;
                        $v2 = $record->interactive_bahasa_arab_2;
                        if ($v1 === null && $v2 === null) return '-';
                        return ($v1 ?? '-') . ', ' . ($v2 ?? '-');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Foto Profil')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('roles.name')
                    ->label('Role')
                    ->colors([
                        'gray' => 'pendaftar',
                        'success' => 'Admin',
                        'warning' => 'Staf Administrasi',
                        'info' => 'Penerjemah',
                        'danger' => 'Kepala Lembaga',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([5, 10, 25, 50])
            ->filters([
                // === Role (ambil dari Spatie Roles) ===
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options(fn () =>
                        \Spatie\Permission\Models\Role::query()
                            ->orderBy('name')
                            ->pluck('name', 'name') // pakai nama sbg key agar mudah
                    )
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $query->whereHas('roles', fn (Builder $rq) =>
                                $rq->where('name', $data['value'])
                            );
                        }
                    }),

                // === Prodi ===
                Tables\Filters\SelectFilter::make('prody_id')
                    ->label('Prodi')
                    ->options(fn () =>
                        \App\Models\Prody::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload(),

                // === Grup BL ===
                Tables\Filters\SelectFilter::make('nomor_grup_bl')
                    ->label('Grup BL')
                    ->options(fn () =>
                        \App\Models\User::query()
                            ->whereNotNull('nomor_grup_bl')
                            ->distinct()
                            ->orderBy('nomor_grup_bl')
                            ->pluck('nomor_grup_bl', 'nomor_grup_bl')
                    )
                    ->searchable()
                    ->preload(),

                // === Angkatan (range year) ===
                Tables\Filters\Filter::make('angkatan')
                    ->label('Angkatan')
                    ->form([
                        Forms\Components\TextInput::make('from')
                            ->numeric()->minValue(2000)->maxValue(2100)
                            ->placeholder('dari (mis. 2024)'),
                        Forms\Components\TextInput::make('to')
                            ->numeric()->minValue(2000)->maxValue(2100)
                            ->placeholder('sampai (mis. 2025)'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        $from = $data['from'] ?? null;
                        $to   = $data['to']   ?? null;
                        if ($from && $to)   return "Angkatan: {$from}–{$to}";
                        if ($from)          return "Angkatan ≥ {$from}";
                        if ($to)            return "Angkatan ≤ {$to}";
                        return null;
                    })
                    ->query(function (Builder $query, array $data) {
                        $from = $data['from'] ?? null;
                        $to   = $data['to']   ?? null;
                        if ($from) $query->where('year', '>=', (int) $from);
                        if ($to)   $query->where('year', '<=', (int) $to);
                    }),

                // === Ada Attempt BL? ===
                Tables\Filters\TernaryFilter::make('has_attempts')
                    ->label('Ada Attempt BL?')
                    ->boolean()
                    ->queries(
                        true:  fn (Builder $q) => $q->whereHas('basicListeningAttempts'),
                        false: fn (Builder $q) => $q->whereDoesntHave('basicListeningAttempts'),
                        blank: fn (Builder $q) => $q
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assignRole')
                        ->label('Terapkan Role')
                        ->icon('heroicon-o-user-plus')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Select::make('role_id')
                                ->label('Pilih Role')
                                ->options(fn () => Role::query()->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $role = Role::find($data['role_id'] ?? null);
                            if (! $role) {
                                Notification::make()
                                    ->title('Role tidak ditemukan.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            foreach ($records as $user) {
                                /** @var \App\Models\User $user */
                                $user->syncRoles([$role->name]); // pastikan hanya 1 role
                            }

                            Notification::make()
                                ->title('Role telah diset untuk pengguna terpilih.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => auth()->user()?->hasRole('Admin') === true),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\UserResource\RelationManagers\BasicListeningAttemptsRelationManager::class,
            \App\Filament\Resources\UserResource\RelationManagers\EptSubmissionsRelationManager::class,
            \App\Filament\Resources\UserResource\RelationManagers\PenerjemahansRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Jumlah User';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view'   => Pages\ViewUser::route('/{record}'),
        ];
    }
}
