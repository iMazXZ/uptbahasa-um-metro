<?php

namespace App\Support;

use App\Jobs\QueueHeartbeatPing;
use App\Jobs\SendWhatsAppMessage;
use App\Jobs\SendWhatsAppNotification;
use App\Jobs\SendWhatsAppOtp;
use App\Jobs\SendWhatsAppResetLink;
use Illuminate\Support\Facades\Cache;

class QueueMonitor
{
    public const HEARTBEAT_CACHE_KEY = 'queue-worker-heartbeat';

    public const HEARTBEAT_FRESH_SECONDS = 180;

    /**
     * @return array<class-string>
     */
    public static function waJobClasses(): array
    {
        return [
            SendWhatsAppMessage::class,
            SendWhatsAppNotification::class,
            SendWhatsAppOtp::class,
            SendWhatsAppResetLink::class,
        ];
    }

    /**
     * @return array<class-string>
     */
    public static function monitoredJobClasses(): array
    {
        return [
            QueueHeartbeatPing::class,
            ...static::waJobClasses(),
        ];
    }

    public static function extractCommandName(array $payload): ?string
    {
        $commandName = data_get($payload, 'data.commandName');

        return is_string($commandName) && $commandName !== '' ? $commandName : null;
    }

    public static function isMonitored(?string $commandName): bool
    {
        return is_string($commandName) && in_array($commandName, static::monitoredJobClasses(), true);
    }

    public static function touchHeartbeat(string $event, ?string $commandName = null, ?string $queue = null): void
    {
        Cache::forever(static::HEARTBEAT_CACHE_KEY, [
            'event' => $event,
            'job' => $commandName,
            'queue' => $queue,
            'last_seen_at' => now()->timestamp,
            'host' => gethostname() ?: php_uname('n'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function readHeartbeat(): array
    {
        $heartbeat = Cache::get(static::HEARTBEAT_CACHE_KEY, []);

        return is_array($heartbeat) ? $heartbeat : [];
    }
}
