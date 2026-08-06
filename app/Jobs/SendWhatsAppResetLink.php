<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\RateLimited;

class SendWhatsAppResetLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 200;

    public int $timeout = 50;

    public function __construct(
        public string $phone,
        public string $resetUrl,
        public ?string $userName = null,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('wa-outbound')];
    }

    public function handle(WhatsAppService $waService): void
    {
        if (! $waService->isEnabled()) {
            return;
        }

        $waService->sendResetLink(
            phone: $this->phone,
            resetUrl: $this->resetUrl,
            userName: $this->userName
        );
    }
}
