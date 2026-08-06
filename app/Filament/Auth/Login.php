<?php

namespace App\Filament\Auth;

use App\Models\User;
use App\Support\NormalizeWhatsAppNumber;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        // Tampilkan notifikasi jika redirect dari halaman register yang ditutup
        if (session('registrationClosed')) {
            Notification::make()
                ->title('Registrasi Ditutup')
                ->body('Maaf, pendaftaran akun baru sementara tidak tersedia. Silakan hubungi admin.')
                ->danger()
                ->persistent()
                ->send();
        }
    }
    /**
     * Override form untuk mengubah label & placeholder.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email')
                    ->placeholder('Masukkan email, NPM, atau nomor WhatsApp')
                    ->required()
                    ->autocomplete()
                    ->autofocus(),

                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    /**
     * Ambil kredensial dari form login.
     * Mendukung login via: Email, SRN (NPM), atau WhatsApp.
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $input = trim($data['email'] ?? '');
        $user  = null;

        // 1. Deteksi tipe input
        if (str_contains($input, '@')) {
            // Input adalah Email
            $normalized = strtolower($input);
            $user = User::whereRaw('LOWER(email) = ?', [$normalized])->first();

            // Fallback: cek typo .com/.con
            if (!$user) {
                $user = $this->checkEmailTypo($normalized);
            }

            if (!$user) {
                throw ValidationException::withMessages([
                    'data.email' => 'Email tidak ditemukan di sistem.',
                ]);
            }

        } elseif (preg_match('/^(0|62|\+62)?8\d{8,12}$/', preg_replace('/[\s\-]/', '', $input))) {
            // Input adalah nomor WhatsApp
            $normalized = NormalizeWhatsAppNumber::normalize($input);

            if (!$normalized) {
                throw ValidationException::withMessages([
                    'data.email' => 'Format nomor WhatsApp tidak valid.',
                ]);
            }

            $user = User::where('whatsapp', $normalized)->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'data.email' => 'Nomor WhatsApp tidak terdaftar di sistem.',
                ]);
            }

        } else {
            // Input adalah SRN/NPM (angka atau alfanumerik)
            $user = User::where('srn', $input)->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'data.email' => 'NPM tidak ditemukan di sistem.',
                ]);
            }
        }

        // 2. Verifikasi password
        if (!Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.password' => 'Kata sandi yang Anda masukkan salah.',
            ]);
        }

        // 3. Return kredensial dengan email asli dari database
        return [
            'email'    => $user->email,
            'password' => $data['password'],
        ];
    }

    /**
     * Cek typo umum .com vs .con
     */
    private function checkEmailTypo(string $normalized): ?User
    {
        if (str_ends_with($normalized, '.com')) {
            $alt = preg_replace('/\.com$/i', '.con', $normalized);
            $altUser = User::whereRaw('LOWER(email) = ?', [$alt])->first();

            if ($altUser) {
                throw ValidationException::withMessages([
                    'data.email' => "Email tidak ditemukan. Mungkin maksud Anda: {$altUser->email}",
                ]);
            }
        }

        if (str_ends_with($normalized, '.con')) {
            $alt = preg_replace('/\.con$/i', '.com', $normalized);
            $altUser = User::whereRaw('LOWER(email) = ?', [$alt])->first();

            if ($altUser) {
                throw ValidationException::withMessages([
                    'data.email' => "Email tidak ditemukan. Mungkin maksud Anda: {$altUser->email}",
                ]);
            }
        }

        return null;
    }

    /**
     * Setelah login sukses, arahkan ke /dashboard
     */
    protected function getRedirectUrl(): string
    {
        return route('dashboard');
    }
}
