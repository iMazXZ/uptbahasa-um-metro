<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessage;
use App\Support\WhatsAppOutboundThrottle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;
    protected int $retry;
    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = config('whatsapp.enabled', false);
        $this->baseUrl = (string) (config('whatsapp.url', 'https://wa-api.lembagabahasa.site') ?? 'https://wa-api.lembagabahasa.site');
        $this->apiKey = (string) (config('whatsapp.api_key', '') ?? '');
        $this->timeout = (int) config('whatsapp.timeout', 30);
        $this->retry = (int) config('whatsapp.retry', 2);
    }

    /**
     * Cek apakah WhatsApp service enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->apiKey);
    }

    /**
     * Cek status koneksi WhatsApp
     */
    public function isConnected(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['x-api-key' => $this->apiKey])
                ->get("{$this->baseUrl}/status");

            return $response->successful() && ($response->json('connected') === true);
        } catch (\Exception $e) {
            Log::warning('WhatsApp status check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim pesan reset password via WhatsApp
     *
     * @param string $phone Nomor WA (format: 6285xxx)
     * @param string $resetUrl URL reset password
     * @param string|null $userName Nama user (optional)
     * @return bool
     */
    public function sendResetLink(string $phone, string $resetUrl, ?string $userName = null): bool
    {
        if (!$this->isEnabled()) {
            Log::info('WhatsApp service disabled, skipping send');
            return false;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retry, 1000)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->apiKey,
                ])
                ->post("{$this->baseUrl}/send-reset", [
                    'phone' => $phone,
                    'resetUrl' => $resetUrl,
                    'userName' => $userName,
                ]);

            if ($response->successful() && $response->json('success') === true) {
                Log::info("WhatsApp reset link sent to {$phone}");
                return true;
            }

            Log::warning("WhatsApp send failed: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim pesan custom via WhatsApp
     *
     * @param string $phone Nomor WA (format: 6285xxx)
     * @param string $message Isi pesan
     * @return bool
     */
    public function sendMessage(string $phone, string $message): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retry, 1000)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->apiKey,
                ])
                ->post("{$this->baseUrl}/send-message", [
                    'phone' => $phone,
                    'message' => $message,
                ]);

            return $response->successful() && $response->json('success') === true;

        } catch (\Exception $e) {
            Log::error('WhatsApp send message error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Antrekan pesan custom ke jalur outbound WA tunggal.
     */
    public function queueMessage(string $phone, string $message, ?array $tracking = null): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        SendWhatsAppMessage::dispatch($phone, $message, $tracking)
            ->delay(WhatsAppOutboundThrottle::nextDelaySeconds());

        return true;
    }

    /**
     * Kirim OTP verifikasi via WhatsApp
     *
     * @param string $phone Nomor WA (format: 6285xxx)
     * @param string $otp Kode OTP 6 digit
     * @return bool
     */
    public function sendOtp(string $phone, string $otp): bool
    {
        if (!$this->isEnabled()) {
            Log::info('WhatsApp service disabled, skipping OTP send');
            return false;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retry, 1000)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->apiKey,
                ])
                ->post("{$this->baseUrl}/send-otp", [
                    'phone' => $phone,
                    'otp' => $otp,
                    'appName' => config('app.name', 'Lembaga Bahasa'),
                ]);

            if ($response->successful() && $response->json('success') === true) {
                Log::info("WhatsApp OTP sent to {$phone}");
                return true;
            }

            Log::warning("WhatsApp OTP failed: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp OTP error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim notifikasi status via WhatsApp
     *
     * @param string $phone Nomor WA (format: 6285xxx)
     * @param string $type Tipe notifikasi (ept_status, penerjemahan_status)
     * @param string $status Status (approved, rejected, pending, Selesai, Diproses, etc)
     * @param string|null $userName Nama user
     * @param string|null $details Detail tambahan
     * @param string|null $actionUrl URL dashboard
     * @return bool
     */
    public function sendNotification(
        string $phone,
        string $type,
        string $status,
        ?string $userName = null,
        ?string $details = null,
        ?string $actionUrl = null
    ): bool {
        if (!$this->isEnabled()) {
            Log::info('WhatsApp service disabled, skipping notification');
            return false;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retry, 1000)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->apiKey,
                ])
                ->post("{$this->baseUrl}/send-notification", [
                    'phone' => $phone,
                    'type' => $type,
                    'status' => $status,
                    'userName' => $userName,
                    'details' => $details,
                    'actionUrl' => $actionUrl,
                ]);

            if ($response->successful() && $response->json('success') === true) {
                Log::info("WhatsApp notification ({$type}) sent to {$phone}");
                return true;
            }

            Log::warning("WhatsApp notification failed: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp notification error: ' . $e->getMessage());
            return false;
        }
    }
}
