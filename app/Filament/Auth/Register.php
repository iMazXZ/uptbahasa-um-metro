<?php

namespace App\Filament\Auth;

use App\Jobs\SendWhatsAppOtp;
use App\Support\WhatsAppOutboundThrottle;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Register as AuthRegister;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;
use Filament\Events\Auth\Registered;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Notifications\Notification;
use Filament\Notifications\Auth\VerifyEmail;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse as RegistrationResponseContract;

class Register extends AuthRegister
{
    public function mount(): void
    {
        parent::mount();

        // Jika registrasi ditutup, redirect ke login dengan pesan
        if (!\App\Models\SiteSetting::isRegistrationEnabled()) {
            session()->flash('registrationClosed', true);
            $this->redirect(route('filament.admin.auth.login'));
        }
    }

    public function register(): ?RegistrationResponseContract
    {
        // Cek apakah registrasi diaktifkan dari pengaturan situs
        if (!\App\Models\SiteSetting::isRegistrationEnabled()) {
            Notification::make()
                ->title('Registrasi Ditutup')
                ->body('Maaf, pendaftaran akun baru sementara tidak tersedia. Silakan hubungi admin.')
                ->danger()
                ->send();

            return null;
        }

        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function () {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        $this->sendEmailVerificationNotification($user);

        // Kirim OTP WhatsApp jika nomor diisi
        $hasWhatsApp = !empty($user->whatsapp);
        if ($hasWhatsApp) {
            $this->sendWhatsAppOtp($user);
        }

        Filament::auth()->login($user);

        session()->regenerate();

        // Redirect ke biodata jika ada WA (untuk verifikasi OTP), atau ke dashboard
        $redirectUrl = $hasWhatsApp 
            ? route('dashboard.biodata') 
            : route('dashboard.pendaftar');

        return new class($redirectUrl) implements RegistrationResponseContract {
            public function __construct(private string $url) {}
            
            public function toResponse($request)
            {
                return redirect()->intended($this->url);
            }
        };
    }

    /**
     * Kirim OTP ke WhatsApp setelah registrasi (cek setting dari database)
     */
    protected function sendWhatsAppOtp($user): void
    {
        // Cek apakah OTP diaktifkan dari pengaturan situs
        if (!\App\Models\SiteSetting::isOtpEnabled()) {
            // OTP nonaktif - langsung verifikasi
            $user->update([
                'whatsapp_verified_at' => now(),
                'whatsapp_otp' => null,
                'whatsapp_otp_expires_at' => null,
            ]);
            return;
        }

        // OTP aktif - generate dan kirim
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(30);

        $user->update([
            'whatsapp_otp' => $otp,
            'whatsapp_otp_expires_at' => $expiresAt,
        ]);

        if (app(\App\Services\WhatsAppService::class)->isEnabled()) {
            SendWhatsAppOtp::dispatch($user->whatsapp, $otp)
                ->delay(WhatsAppOutboundThrottle::nextDelaySeconds());

            Notification::make()
                ->title('OTP Dalam Antrean')
                ->body('Kode verifikasi WhatsApp sedang diproses. Mohon tunggu beberapa saat.')
                ->success()
                ->send();
        }
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeform()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getWhatsAppFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    /**
     * Komponen input nama lengkap
     */
    protected function getNameFormComponent(): TextInput
    {
        return parent::getNameFormComponent()
            ->label('Nama Lengkap')
            ->placeholder('Masukkan nama lengkap sesuai KTP');
    }

    /**
     * Komponen input kata sandi
     */
    protected function getPasswordFormComponent(): TextInput
    {
        return parent::getPasswordFormComponent()
            ->label('Kata Sandi')
            ->placeholder('Minimal 8 karakter');
    }

    /**
     * Komponen konfirmasi kata sandi
     */
    protected function getPasswordConfirmationFormComponent(): TextInput
    {
        return parent::getPasswordConfirmationFormComponent()
            ->label('Konfirmasi Kata Sandi')
            ->placeholder('Ulangi kata sandi');
    }

    /**
     * Komponen input nomor WhatsApp (wajib)
     */
    protected function getWhatsAppFormComponent(): TextInput
    {
        return TextInput::make('whatsapp')
            ->label('Nomor WhatsApp Aktif')
            ->tel()
            ->required()
            ->maxLength(20)
            ->unique('users', 'whatsapp', ignoreRecord: true)
            ->validationMessages([
                'unique' => 'Nomor WhatsApp ini sudah terdaftar di akun lain.',
                'required' => 'Nomor WhatsApp wajib diisi.',
            ])
            ->helperText('Untuk menerima notifikasi dan verifikasi akun');
    }

    /**
     * Custom komponen email: tambahkan validasi typo ".con"
     */
    protected function getEmailFormComponent(): TextInput
    {
        // mulai dari versi bawaan AuthRegister (sudah ada ->email(), ->required(), ->unique(), dll)
        return parent::getEmailFormComponent()
            ->label('Alamat Email')
            ->rule(function () {
                return function (string $attribute, $value, Closure $fail) {
                    $value = strtolower(trim($value));

                    // Kalau berakhiran .con → kemungkinan typo
                    if (str_ends_with($value, '.con')) {
                        $fail('Sepertinya ada typo di email: gunakan ".com", bukan ".con".');
                    }
                };
            });
    }

    protected function handleRegistration(array $data): Model
    {
        // Normalisasi nama ke UPPERCASE
        if (!empty($data['name'])) {
            $data['name'] = mb_strtoupper(trim($data['name']), 'UTF-8');
        }

        // Normalisasi nomor WhatsApp
        if (!empty($data['whatsapp'])) {
            $data['whatsapp'] = \App\Support\NormalizeWhatsAppNumber::normalize($data['whatsapp']);
        }

        $user = $this->getUserModel()::create($data);

        // default role pendaftar
        $user->assignRole('pendaftar');

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        return route('dashboard.pendaftar');
    }
}
