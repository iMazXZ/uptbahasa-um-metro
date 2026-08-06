<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use App\Support\EptScheduleNotificationTracker;
use App\Support\EptSubmissionNotificationTracker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 200;

    public int $timeout = 50;

    public function __construct(
        public string $phone,
        public string $message,
        public ?array $tracking = null,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('wa-outbound')];
    }

    public function handle(WhatsAppService $waService): void
    {
        if (! $waService->isEnabled()) {
            $this->markWhatsAppFailed('Service WhatsApp sedang nonaktif.');
            return;
        }

        $sent = $waService->sendMessage($this->phone, $this->message);

        if (! $sent) {
            Log::warning("Failed to queue-send WhatsApp message to {$this->phone}");
            $this->markWhatsAppFailed("Pesan WhatsApp ke {$this->phone} gagal dikirim oleh service.");
            return;
        }

        $this->markWhatsAppSent();
    }

    public function failed(Throwable $exception): void
    {
        $this->markWhatsAppFailed($exception->getMessage());
    }

    protected function markWhatsAppSent(): void
    {
        if (($this->tracking['kind'] ?? null) === 'ept_submission') {
            EptSubmissionNotificationTracker::markWhatsAppSent($this->tracking);

            return;
        }

        EptScheduleNotificationTracker::markWhatsAppSent($this->tracking);
    }

    protected function markWhatsAppFailed(?string $error = null): void
    {
        if (($this->tracking['kind'] ?? null) === 'ept_submission') {
            EptSubmissionNotificationTracker::markWhatsAppFailed($this->tracking, $error);

            return;
        }

        EptScheduleNotificationTracker::markWhatsAppFailed($this->tracking, $error);
    }
}
