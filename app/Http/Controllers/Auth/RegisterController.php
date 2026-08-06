<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppOtp;
use App\Models\User;
use App\Models\SiteSetting;
use App\Support\NormalizeWhatsAppNumber;
use App\Support\WhatsAppOutboundThrottle;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /**
     * Tampilkan form registrasi
     */
    public function showRegistrationForm()
    {
        // Cek apakah registrasi diaktifkan
        if (!SiteSetting::isRegistrationEnabled()) {
            return redirect()->route('login')
                ->with('status', 'Maaf, pendaftaran akun baru sementara tidak tersedia. Silakan hubungi admin.');
        }

        return view('auth.register');
    }

    /**
     * Proses registrasi user baru
     */
    public function register(Request $request)
    {
        // Cek apakah registrasi diaktifkan
        if (!SiteSetting::isRegistrationEnabled()) {
            return redirect()->route('login')
                ->with('status', 'Maaf, pendaftaran akun baru sementara tidak tersedia.');
        }

        // Rate limiting - max 3 percobaan per menit per IP
        $throttleKey = 'register:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        // Validasi input
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        // Cek typo email (.con)
        $email = strtolower(trim($request->email));
        if (str_ends_with($email, '.con')) {
            throw ValidationException::withMessages([
                'email' => 'Sepertinya ada typo di email: gunakan ".com", bukan ".con".',
            ]);
        }

        // Normalisasi WhatsApp
        $whatsapp = NormalizeWhatsAppNumber::normalize($request->whatsapp);
        if (!$whatsapp) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Format nomor WhatsApp tidak valid.',
            ]);
        }

        // Cek apakah WhatsApp sudah terdaftar
        if (User::where('whatsapp', $whatsapp)->exists()) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'whatsapp' => 'Nomor WhatsApp ini sudah terdaftar di akun lain.',
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        // Buat user baru
        $user = User::create([
            'name' => mb_strtoupper(trim($request->name), 'UTF-8'),
            'email' => $email,
            'whatsapp' => $whatsapp,
            'password' => Hash::make($request->password),
        ]);

        // Assign role pendaftar
        $user->assignRole('pendaftar');

        // Fire event untuk email verification
        event(new Registered($user));

        // Kirim OTP WhatsApp jika OTP enabled
        $this->sendWhatsAppOtp($user);

        // Login user
        Auth::login($user);

        $request->session()->regenerate();

        RateLimiter::clear($throttleKey);

        // Redirect ke biodata atau dashboard
        $redirectUrl = !empty($user->whatsapp) && SiteSetting::isOtpEnabled()
            ? route('dashboard.biodata')
            : route('dashboard.pendaftar');

        return redirect()->intended($redirectUrl);
    }

    /**
     * Kirim OTP ke WhatsApp setelah registrasi
     */
    protected function sendWhatsAppOtp(User $user): void
    {
        // Cek apakah OTP diaktifkan
        if (!SiteSetting::isOtpEnabled()) {
            $user->update([
                'whatsapp_verified_at' => now(),
                'whatsapp_otp' => null,
                'whatsapp_otp_expires_at' => null,
            ]);
            return;
        }

        // Generate OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(30);

        $user->update([
            'whatsapp_otp' => $otp,
            'whatsapp_otp_expires_at' => $expiresAt,
        ]);

        if (app(\App\Services\WhatsAppService::class)->isEnabled()) {
            SendWhatsAppOtp::dispatch($user->whatsapp, $otp)
                ->delay(WhatsAppOutboundThrottle::nextDelaySeconds());
        }
    }
}
