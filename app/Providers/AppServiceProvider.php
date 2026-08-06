<?php

namespace App\Providers;

use App\Support\EptScheduleNotificationTracker;
use App\Support\EptSubmissionNotificationTracker;
use App\Support\QueueMonitor;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Cache\RateLimiting\Limit;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use App\Http\Responses\LogoutResponse as CustomLogoutResponse;

use App\Http\Responses\LoginResponse;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;

use App\Models\BasicListeningAttempt;
use App\Models\BasicListeningConnectCode;
use App\Models\InteractiveClassScore;
use App\Policies\BasicListeningAttemptPolicy;
use App\Policies\BasicListeningConnectCodePolicy;
use App\Policies\InteractiveClassScorePolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LogoutResponse::class, CustomLogoutResponse::class);

        $this->app->bind(
        LoginResponseContract::class,
        LoginResponse::class,
    );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        FilamentAsset::register([
            Css::make('custom-stylesheet', __DIR__ . '/../../resources/css/custom.css'),
        ]);

        // === Registrasi eksplisit policy (opsional jika auto-discovery sudah aktif) ===
        Gate::policy(BasicListeningAttempt::class, BasicListeningAttemptPolicy::class);
        Gate::policy(BasicListeningConnectCode::class, BasicListeningConnectCodePolicy::class);
        Gate::policy(InteractiveClassScore::class, InteractiveClassScorePolicy::class);

        // === Registrasi WhatsApp notification channel ===
        \Illuminate\Support\Facades\Notification::extend('whatsapp', function ($app) {
            return new \App\Channels\WhatsAppChannel();
        });

        Event::listen(NotificationSent::class, function (NotificationSent $event) {
            EptScheduleNotificationTracker::handleNotificationSent($event);
            EptSubmissionNotificationTracker::handleNotificationSent($event);
        });

        Queue::before(function (JobProcessing $event) {
            $payload = $event->job->payload();
            $commandName = QueueMonitor::extractCommandName($payload);

            if (! QueueMonitor::isMonitored($commandName)) {
                return;
            }

            QueueMonitor::touchHeartbeat('processing', $commandName, $event->job->getQueue());
        });

        Queue::after(function (JobProcessed $event) {
            $payload = $event->job->payload();
            $commandName = QueueMonitor::extractCommandName($payload);

            if (! QueueMonitor::isMonitored($commandName)) {
                return;
            }

            QueueMonitor::touchHeartbeat('processed', $commandName, $event->job->getQueue());
        });

        Queue::failing(function (JobFailed $event) {
            $payload = $event->job->payload();
            $queuedNotificationJob = EptScheduleNotificationTracker::jobFromPayload($payload);

            if ($queuedNotificationJob instanceof SendQueuedNotifications) {
                EptScheduleNotificationTracker::handleQueuedNotificationFailure(
                    $queuedNotificationJob,
                    $event->exception,
                );
                EptSubmissionNotificationTracker::handleQueuedNotificationFailure(
                    $queuedNotificationJob,
                    $event->exception,
                );
            }

            $commandName = QueueMonitor::extractCommandName($payload);

            if (! QueueMonitor::isMonitored($commandName)) {
                return;
            }

            QueueMonitor::touchHeartbeat('failed', $commandName, $event->job->getQueue());
        });

        // Cadangan throttle runtime jika worker menemukan lebih dari satu job WA siap proses.
        RateLimiter::for('wa-outbound', fn () => Limit::perSecond(
            1,
            max(1, (int) config('whatsapp.outbound_spacing_seconds', 50))
        )->by('wa-outbound'));
    }
}
