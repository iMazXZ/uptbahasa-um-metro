<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class WhatsAppOutboundThrottle
{
    private const CACHE_KEY = 'wa-outbound-next-available-at';

    private const LOCK_KEY = 'wa-outbound-scheduler-lock';

    public static function nextDelaySeconds(): int
    {
        $spacingSeconds = max(1, (int) config('whatsapp.outbound_spacing_seconds', 50));

        try {
            return Cache::lock(self::LOCK_KEY, 10)->block(3, fn () => self::reserveDelay($spacingSeconds));
        } catch (\Throwable) {
            return self::reserveDelay($spacingSeconds);
        }
    }

    private static function reserveDelay(int $spacingSeconds): int
    {
        $now = now()->timestamp;
        $nextAvailableAt = max($now, (int) Cache::get(self::CACHE_KEY, $now));

        Cache::put(self::CACHE_KEY, $nextAvailableAt + $spacingSeconds, now()->addDays(7));

        return max(0, $nextAvailableAt - $now);
    }
}
