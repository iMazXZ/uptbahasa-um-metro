<?php

namespace App\Filament\Pages;

use App\Models\Prody;
use App\Models\BasicListeningGrade;
use App\Support\ImageTransformer;
use App\Support\BlGrading;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Biodata extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-s-cog-6-tooth';
    protected static ?string $navigationLabel = 'Biodata';
    protected static string $view = 'filament.pages.biodata';

    public $user;
    public ?array $data = [];

    public function mount(): void
    {
        $this->user = Auth::user();

        $this->form->fill([
            'name'                => $this->user->name,
            'email'               => $this->user->email,
            'srn'                 => $this->user->srn,
            'prody_id'            => $this->user->prody_id,
            'year'                => $this->user->year,
            'nilaibasiclistening' => $this->user->nilaibasiclistening,
            'image'               => $this->user->image,
        ]);
    }

    protected function getActions(): array
    {
        return [
            Action::make('dashboard')
                ->label('Kembali ke Dasbor')
                ->url(route('filament.admin.pages.2'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->schema([
                    TextInput::make('name')
                        ->required()
                        ->label('Nama Lengkap')
                        ->validationMessages([
                            'required' => 'Wajib Diisi.',
                        ])
                        // ->helperText(str('Isi dengan **nama lengkap** di sini.')->inlineMarkdown()->toHtmlString())
                        ->dehydrateStateUsing(fn (string $state): string => ucwords(strtolower($state))),

                    TextInput::make('email')
                        ->required()
                        ->email()
                        ->validationMessages([
                            'required' => 'Wajib Diisi.',
                        ])
                        ->helperText(str('Gunakan **email aktif**, dipakai untuk notifikasi dan reset password.')->inlineMarkdown()->toHtmlString()),

                    // TextInput::make('password')
                    //     ->password()
                    //     ->revealable(filament()->arePasswordsRevealable())
                    //     ->nullable()
                    //     ->hint('Lupa Password? Ganti di sini')
                    //     ->hintColor('danger'),

                    TextInput::make('srn')
                        ->label('NPM')
                        ->maxLength(255)
                        ->required()
                        ->rule(fn () => 'unique:users,srn,' . Auth::id())
                        ->validationMessages([
                            'unique' => 'NPM ini sudah terdaftar. Hubungi Admin jika ini memang NPM Anda.',
                        ]),

                    Select::make('prody_id')
                        ->label('Program Studi')
                        ->options(Prody::orderBy('name')->pluck('name', 'id')),
                        // ->searchable(),
                        // ->helperText(str('Pilih **Dosen** atau **Umum** jika bukan Mahasiswa.')->inlineMarkdown()->toHtmlString()),

                    // ===== DROPDOWN TAHUN DENGAN LOGIKA REAKTIF =====
                    Select::make('year')
                        ->label('Tahun Angkatan')
                        ->options(function () {
                            $now = (int) date('Y');
                            return collect(range(2020, $now + 1))
                                ->reverse()
                                ->mapWithKeys(fn ($y) => [$y => $y]);
                        })
                        ->placeholder('Pilih Tahun')
                        ->required()
                        ->reactive()
                        ->helperText('Pilih tahun angkatan Anda. '),
                            // . 'Jika bukan mahasiswa, pilih tahun sekarang.'),

                    // ===== KONDISIONAL NILAI BASIC LISTENING =====
                    TextInput::make('nilaibasiclistening')
                        ->label('Nilai Basic Listening (angkatan ≤ 2024)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->visible(fn (callable $get) => ($year = (int) $get('year')) && $year <= 2024)
                        ->required(fn (callable $get) => ($year = (int) $get('year')) && $year <= 2024)
                        ->helperText('Wajib diisi, untuk mengajukan Surat Rekomendasi dan Penerjemahan. '),
                            // . 'Angkatan 2025 ke atas diisi otomatis dari sistem.'),

                    FileUpload::make('image')
                        ->label('Foto Profil')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['1:1'])
                        ->imagePreviewHeight('200')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(8192)
                        ->disk('public')
                        ->visibility('public')
                        ->downloadable()
                        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $get) {
                            $old = $get('image');
                            if (is_array($old)) {
                                $old = $old['path'] ?? ($old[0]['path'] ?? null);
                            }
                            if (is_string($old) && $old !== '' && Storage::disk('public')->exists($old)) {
                                Storage::disk('public')->delete($old);
                            }

                            $base = 'avatar_' . str(Auth::id())->padLeft(6, '0') . '.webp';

                            $result = ImageTransformer::toWebpFromUploaded(
                                uploaded:   $file,
                                targetDisk: 'public',
                                targetDir:  'profile_pictures',
                                quality:    82,
                                maxWidth:   600,
                                maxHeight:  600,
                                basename:   $base
                            );

                            return $result['path'];
                        })
                        ->deleteUploadedFileUsing(function (string $file) {
                            if (Storage::disk('public')->exists($file)) {
                                Storage::disk('public')->delete($file);
                            }
                        })
                        ->helperText('Tidak Wajib'),
                ]),
            ])
            ->statePath('data');
    }

    public function edit(): void
    {
        $validated = $this->form->getState();

        $this->user->name                = $validated['name'];
        $this->user->email               = $validated['email'];
        $this->user->srn                 = $validated['srn'];
        $this->user->prody_id            = $validated['prody_id'];
        $this->user->year                = $validated['year'];
        $this->user->nilaibasiclistening = $validated['nilaibasiclistening'] ?? null;

        if (!empty($validated['password'])) {
            $this->user->password = Hash::make($validated['password']);
        }

        // Normalisasi dan hapus foto lama bila berbeda
        if (array_key_exists('image', $validated)) {
            $newImage = $validated['image'];
            if (is_array($newImage)) {
                $newImage = $newImage['path'] ?? ($newImage[0]['path'] ?? null);
            }

            if ($this->user->image && $this->user->image !== $newImage) {
                if (Storage::disk('public')->exists($this->user->image)) {
                    Storage::disk('public')->delete($this->user->image);
                }
            }

            if (is_string($newImage) && $newImage !== '') {
                $this->user->image = $newImage;
            }
        }

        $this->user->save();

        // ===== SINKRONISASI CACHE UNTUK ANGKATAN ≤ 2024 =====
        if ((int) $this->user->year <= 2024) {
            $num = is_numeric($this->user->nilaibasiclistening)
                ? (float) $this->user->nilaibasiclistening
                : null;

            if ($num !== null) {
                $grade = BasicListeningGrade::firstOrCreate([
                    'user_id'   => $this->user->id,
                    'user_year' => $this->user->year,
                ]);

                $grade->final_numeric_cached = $num;
                $grade->final_letter_cached  = BlGrading::letter($num);
                $grade->save();
            }
        }

        Notification::make()
            ->title('Informasi Terupdate')
            ->success()
            ->body('Informasi akun Anda telah diperbarui.')
            ->send();
    }

    public function getSubheading(): ?string
    {
        $user = Auth::user();

        if ($user->hasRole('pendaftar')) {
            $isComplete =
                ($user->prody !== null && $user->prody !== '') &&
                ($user->srn !== null && $user->srn !== '') &&
                ($user->year !== null && $user->year !== '') &&
                (
                    (int) $user->year <= 2024
                        ? is_numeric($user->nilaibasiclistening)
                        : true
                );

            if (!$isComplete) {
                return '⚠️ Silakan lengkapi terlebih dahulu data biodata Anda. '
                    . 'Pastikan seluruh data telah terisi dengan benar.';
            }
        }

        return '';
    }
}
