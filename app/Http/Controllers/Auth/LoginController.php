<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\NormalizeWhatsAppNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Tampilkan form login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Proses login
     * Mendukung login via: Email, NPM (SRN), atau WhatsApp
     */
    public function login(Request $request)
    {
        // Validasi input
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email, NPM, atau WhatsApp wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        // Rate limiting - max 5 percobaan per menit per IP
        $throttleKey = 'login:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        $input = trim($request->input('email'));
        $user = null;

        // 1. Deteksi tipe input dan cari user
        if (str_contains($input, '@')) {
            // Input adalah Email
            $normalized = strtolower($input);
            $user = User::whereRaw('LOWER(email) = ?', [$normalized])->first();

            // Fallback: cek typo .com/.con
            if (!$user) {
                $user = $this->checkEmailTypo($normalized);
            }

            if (!$user) {
                RateLimiter::hit($throttleKey, 60);
                throw ValidationException::withMessages([
                    'email' => 'Email tidak ditemukan di sistem.',
                ]);
            }

        } elseif (preg_match('/^(0|62|\+62)?8\d{8,12}$/', preg_replace('/[\s\-]/', '', $input))) {
            // Input adalah nomor WhatsApp
            $normalized = NormalizeWhatsAppNumber::normalize($input);

            if (!$normalized) {
                throw ValidationException::withMessages([
                    'email' => 'Format nomor WhatsApp tidak valid.',
                ]);
            }

            $user = User::where('whatsapp', $normalized)->first();

            if (!$user) {
                RateLimiter::hit($throttleKey, 60);
                throw ValidationException::withMessages([
                    'email' => 'Nomor WhatsApp tidak terdaftar di sistem.',
                ]);
            }

        } else {
            // Input adalah SRN/NPM (angka atau alfanumerik)
            $user = User::where('srn', $input)->first();

            if (!$user) {
                RateLimiter::hit($throttleKey, 60);
                throw ValidationException::withMessages([
                    'email' => 'NPM tidak ditemukan di sistem.',
                ]);
            }
        }

        // 2. Verifikasi password
        if (!Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'password' => 'Kata sandi yang Anda masukkan salah.',
            ]);
        }

        // 3. Login user
        RateLimiter::clear($throttleKey);
        
        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        // 4. Redirect ke dashboard (RoleDashboardRedirectController akan handle role-based redirect)
        return redirect()->intended(route('dashboard'));
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
                    'email' => "Email tidak ditemukan. Mungkin maksud Anda: {$altUser->email}",
                ]);
            }
        }

        if (str_ends_with($normalized, '.con')) {
            $alt = preg_replace('/\.con$/i', '.com', $normalized);
            $altUser = User::whereRaw('LOWER(email) = ?', [$alt])->first();

            if ($altUser) {
                throw ValidationException::withMessages([
                    'email' => "Email tidak ditemukan. Mungkin maksud Anda: {$altUser->email}",
                ]);
            }
        }

        return null;
    }
}
