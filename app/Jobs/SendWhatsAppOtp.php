<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Middleware\RateLimited;

class SendWhatsAppOtp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 200;

    public int $timeout = 50;

    public function __construct(
        public string $phone,
        public string $otp,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('wa-outbound')];
    }

    public function handle(WhatsAppService $waService): void
    {
        $sent = $waService->sendOtp($this->phone, $this->otp);

        if (!$sent) {
            // Log untuk observasi; bisa dihubungkan ke failed_jobs jika dibutuhkan.
            Log::warning("Failed to send WhatsApp OTP to {$this->phone}");
        }
    }
}
