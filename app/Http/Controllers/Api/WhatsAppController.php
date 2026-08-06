<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppOtp;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Support\NormalizeWhatsAppNumber;
use App\Support\WhatsAppOutboundThrottle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    /**
     * Simpan nomor WA (legacy, tanpa OTP).
     */
    public function saveOnly(Request $request): JsonResponse
    {
        $request->validate([
            'whatsapp' => ['required', 'string', 'min:10', 'max:20'],
        ]);

        $user = $request->user();
        $whatsapp = NormalizeWhatsAppNumber::normalize($request->input('whatsapp'));

        if (! $whatsapp) {
            return response()->json([
                'success' => false,
                'message' => 'Format nomor WhatsApp tidak valid',
            ], 422);
        }

        $exists = User::where('whatsapp', $whatsapp)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp ini sudah terdaftar di akun lain.',
            ], 422);
        }

        $attributes = [
            'whatsapp' => $whatsapp,
            'whatsapp_otp' => null,
            'whatsapp_otp_expires_at' => null,
        ];

        if ($user->whatsapp !== $whatsapp) {
            $attributes['whatsapp_verified_at'] = null;
        }

        $user->update($attributes);

        return response()->json([
            'success' => true,
            'message' => 'Nomor WhatsApp berhasil disimpan!',
        ]);
    }

    /**
     * Kirim OTP (atau langsung verifikasi jika OTP dinonaktifkan).
     */
    public function sendOtp(Request $request, WhatsAppService $waService): JsonResponse
    {
        $request->validate([
            'whatsapp' => ['required', 'string', 'max:20'],
        ]);

        $normalized = NormalizeWhatsAppNumber::normalize($request->whatsapp);
        if (!$normalized) {
            return response()->json([
                'success' => false,
                'message' => 'Format nomor WhatsApp tidak valid',
            ], 422);
        }

        $user = $request->user();

        $existingUser = User::where('whatsapp', $normalized)
            ->where('id', '!=', $user->id)
            ->first();

        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp ini sudah terdaftar di akun lain.',
            ], 422);
        }

        if (SiteSetting::isOtpEnabled()) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(30);

            $user->update([
                'whatsapp' => $normalized,
                'whatsapp_otp' => $otp,
                'whatsapp_otp_expires_at' => $expiresAt,
                'whatsapp_verified_at' => null,
            ]);

            if (!$waService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Layanan WhatsApp tidak tersedia',
                ], 503);
            }

            // Offload pengiriman ke queue supaya respon cepat.
            SendWhatsAppOtp::dispatch($normalized, $otp)
                ->delay(WhatsAppOutboundThrottle::nextDelaySeconds());

            return response()->json([
                'success' => true,
                'message' => 'Kode OTP masuk ke antrean pengiriman WhatsApp. Mohon tunggu beberapa saat.',
                'skip_otp' => false,
            ]);
        }

        // OTP nonaktif: langsung verifikasi
        $user->update([
            'whatsapp' => $normalized,
            'whatsapp_otp' => null,
            'whatsapp_otp_expires_at' => null,
            'whatsapp_verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nomor WhatsApp berhasil disimpan!',
            'skip_otp' => true,
        ]);
    }

    /**
     * Verifikasi OTP.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $inputOtp = $request->input('otp');

        if (!$user->whatsapp_otp || !$user->whatsapp_otp_expires_at) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada OTP yang pending. Silakan kirim ulang OTP.',
            ], 400);
        }

        if (now()->isAfter($user->whatsapp_otp_expires_at)) {
            $user->update([
                'whatsapp_otp' => null,
                'whatsapp_otp_expires_at' => null,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP sudah kadaluarsa. Silakan kirim ulang.',
            ], 400);
        }

        if ($user->whatsapp_otp !== $inputOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak valid.',
            ], 400);
        }

        $user->update([
            'whatsapp_verified_at' => now(),
            'whatsapp_otp' => null,
            'whatsapp_otp_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nomor WhatsApp berhasil diverifikasi!',
        ]);
    }

    /**
     * Update nomor tanpa OTP (legacy).
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'whatsapp' => ['required', 'string', 'max:20'],
        ]);

        $normalized = NormalizeWhatsAppNumber::normalize($request->whatsapp);
        if (!$normalized) {
            return response()->json([
                'success' => false,
                'message' => 'Format nomor WhatsApp tidak valid',
            ], 422);
        }

        $user = $request->user();

        $exists = User::where('whatsapp', $normalized)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp ini sudah terdaftar di akun lain.',
            ], 422);
        }

        $attributes = ['whatsapp' => $normalized];

        if ($user->whatsapp !== $normalized) {
            $attributes['whatsapp_verified_at'] = null;
            $attributes['whatsapp_otp'] = null;
            $attributes['whatsapp_otp_expires_at'] = null;
        }

        $user->update($attributes);

        return response()->json([
            'success' => true,
            'message' => 'Nomor WhatsApp berhasil disimpan',
        ]);
    }

    /**
     * Hapus nomor WA.
     */
    public function delete(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'whatsapp' => null,
            'whatsapp_verified_at' => null,
            'whatsapp_otp' => null,
            'whatsapp_otp_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nomor WhatsApp berhasil dihapus.',
        ]);
    }

    /**
     * Reset biodata.
     */
    public function resetBiodata(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'srn' => null,
            'prody_id' => null,
            'year' => null,
            'nilaibasiclistening' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Biodata berhasil direset.',
        ]);
    }

    /**
     * Dismiss welcome modal.
     */
    public function dismissWelcome(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->has_seen_welcome = true;
        $user->save();

        return response()->json(['success' => true]);
    }
}
