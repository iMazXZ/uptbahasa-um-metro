<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 200;

    public int $timeout = 50;

    public function __construct(
        public string $phone,
        public string $type,
        public string $status,
        public ?string $userName = null,
        public ?string $details = null,
        public ?string $actionUrl = null,
    ) {}

    public function middleware(): array
    {
        return [new \Illuminate\Queue\Middleware\RateLimited('wa-outbound')];
    }

    public function handle(WhatsAppService $waService): void
    {
        // Jika service disabled, biarkan gagal senyap agar fallback email (notification) tetap jalan.
        if (! $waService->isEnabled()) {
            return;
        }

        $waService->sendNotification(
            phone: $this->phone,
            type: $this->type,
            status: $this->status,
            userName: $this->userName,
            details: $this->details,
            actionUrl: $this->actionUrl
        );
    }
}
