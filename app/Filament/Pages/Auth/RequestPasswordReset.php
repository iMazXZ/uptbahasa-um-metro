<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\WhatsAppService;
use App\Support\NormalizeWhatsAppNumber;
use App\Support\WhatsAppOutboundThrottle;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use App\Jobs\SendWhatsAppResetLink;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public ?string $method = 'email';
    public ?string $identifier = null;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Section::make()
                    ->schema([
                        Radio::make('method')
                            ->hiddenLabel()
                            ->options([
                                'email' => 'Email',
                                'whatsapp' => 'WhatsApp',
                            ])
                            ->default('email')
                            ->inline()
                            ->inlineLabel(false)
                            ->live()
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->extraAttributes(['style' => 'text-align: center; display: flex; flex-direction: column; align-items: center;']),

                TextInput::make('identifier')
                    ->label(fn ($get) => $get('method') === 'whatsapp' ? 'Nomor WhatsApp' : 'Alamat Email')
                    ->placeholder(fn ($get) => $get('method') === 'whatsapp' ? '085712345678' : 'email@example.com')
                    ->required()
                    ->autocomplete()
                    ->autofocus(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getRequestFormAction(),
        ];
    }

    protected function getRequestFormAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('request')
            ->label('Kirim Tautan Reset')
            ->submit('request');
    }

    public function request(): void
    {
        // Prevent spam
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $e) {
            Notification::make()
                ->title('Terlalu Banyak Percobaan')
                ->body("Silakan coba lagi dalam {$e->secondsUntilAvailable} detik.")
                ->danger()
                ->send();
            return;
        }

        $data = $this->form->getState();
        $method = $data['method'] ?? 'email';
        $identifier = $data['identifier'] ?? null;

        if (empty($identifier)) {
            throw ValidationException::withMessages([
                'data.identifier' => $method === 'whatsapp' 
                    ? 'Nomor WhatsApp wajib diisi.' 
                    : 'Email wajib diisi.',
            ]);
        }

        // Cari user berdasarkan method
        if ($method === 'whatsapp') {
            $normalizedPhone = NormalizeWhatsAppNumber::normalize($identifier);
            
            if (!$normalizedPhone) {
                Notification::make()
                    ->title('Nomor Tidak Valid')
                    ->body('Format nomor WhatsApp tidak valid.')
                    ->danger()
                    ->send();
                return;
            }

            $user = User::where('whatsapp', $normalizedPhone)->first();

            if (!$user) {
                Notification::make()
                    ->title('Tidak Ditemukan')
                    ->body('Nomor WhatsApp tidak terdaftar di sistem.')
                    ->danger()
                    ->send();
                return;
            }

            // Generate token dan kirim via WhatsApp
            $token = Password::broker(Filament::getAuthPasswordBroker())->createToken($user);
            $resetUrl = Filament::getResetPasswordUrl($token, $user);

            $whatsAppService = app(WhatsAppService::class);

            if (!$whatsAppService->isEnabled()) {
                Notification::make()
                    ->title('Layanan Tidak Tersedia')
                    ->body('Layanan WhatsApp sedang tidak aktif. Silakan gunakan email.')
                    ->danger()
                    ->send();
                return;
            }

            // Antrikan lewat jalur outbound WA global.
            SendWhatsAppResetLink::dispatch(
                phone: $user->whatsapp,
                resetUrl: $resetUrl,
                userName: $user->name
            )->delay(WhatsAppOutboundThrottle::nextDelaySeconds());

            Notification::make()
                ->title('Tautan Reset Dalam Proses')
                ->body('Permintaan reset dikirim via WhatsApp. Jika belum masuk, coba lagi atau gunakan email.')
                ->success()
                ->send();
            $this->form->fill();
            return;

        } else {
            // Method: email
            $user = User::where('email', $identifier)->first();

            if (!$user) {
                Notification::make()
                    ->title('Gagal Mengirim Tautan Reset')
                    ->body('Email tidak ditemukan atau tidak valid.')
                    ->danger()
                    ->send();
                return;
            }

            $token = Password::broker(Filament::getAuthPasswordBroker())->createToken($user);
            $resetUrl = Filament::getResetPasswordUrl($token, $user);

            $notification = new ResetPasswordNotification($token);
            $notification->url = $resetUrl;
            $user->notify($notification);

            Notification::make()
                ->title('Tautan Reset Terkirim')
                ->body('Silakan cek email Anda (termasuk folder Spam).')
                ->success()
                ->send();
            $this->form->fill();
            return;
        }
    }
}
