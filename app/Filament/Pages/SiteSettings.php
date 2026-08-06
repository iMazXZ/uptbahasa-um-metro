<?php

namespace App\Filament\Pages;

use App\Jobs\QueueHeartbeatPing;
use App\Jobs\SendWhatsAppMessage;
use App\Jobs\SendWhatsAppNotification;
use App\Jobs\SendWhatsAppOtp;
use App\Jobs\SendWhatsAppResetLink;
use App\Models\SiteSetting;
use App\Models\Prody;
use App\Support\QueueMonitor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Pengaturan Situs';
    protected static ?string $title = 'Pengaturan Situs';
    protected static ?string $slug = 'site-settings';
    protected static ?int $navigationSort = 100;
    protected static ?string $navigationGroup = 'Sistem';

    protected static string $view = 'filament.pages.site-settings';

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    public ?array $data = [];
    public ?array $waStatus = null;
    public ?array $waLogs = [];
    public array $waQueue = [];
    public array $waQueueMeta = [];
    public array $waWorkerStatus = [];
    public ?string $waBaseUrl = null;
    public ?string $waMonitoringRefreshedAt = null;

    public function mount(): void
    {
        $this->waBaseUrl = rtrim(config('whatsapp.url', 'https://wa-api.lembagabahasa.site'), '/');
        $this->loadWaStatus();
        $this->loadWaLogs();
        $this->loadWaQueue();
        $this->loadWaWorkerStatus();
        $this->markWaMonitoringRefreshed();
        
        $this->form->fill([
            'maintenance_mode' => SiteSetting::isMaintenanceEnabled(),
            'registration_enabled' => SiteSetting::isRegistrationEnabled(),
            'otp_enabled' => SiteSetting::isOtpEnabled(),
            'wa_notification_enabled' => SiteSetting::isWaNotificationEnabled(),
            'bl_quiz_enabled' => SiteSetting::isBlQuizEnabled(),
            'bl_period_start_date' => SiteSetting::getBlPeriodStartDate(),
            'bl_active_batch' => SiteSetting::get('bl_active_batch', now()->format('y')),
            'ept_registration_open' => SiteSetting::isEptRegistrationOpen(),
            'ept_all_prody' => SiteSetting::get('ept_all_prody', false),
            'ept_allowed_prody_ids' => SiteSetting::get('ept_allowed_prody_ids', []),
            'ept_allowed_prody_prefixes' => SiteSetting::get('ept_allowed_prody_prefixes', ['S2']),
            'ept_require_whatsapp' => SiteSetting::get('ept_require_whatsapp', false),
            'ept_require_role_pendaftar' => SiteSetting::get('ept_require_role_pendaftar', false),
            'ept_require_biodata' => SiteSetting::get('ept_require_biodata', false),
            'front_head_script' => SiteSetting::get('front_head_script', ''),
            'front_body_script' => SiteSetting::get('front_body_script', ''),
            'front_footer_script' => SiteSetting::get('front_footer_script', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Umum')
                    ->description('Pengaturan umum sistem')
                    ->icon('heroicon-o-cog')
                    ->schema([
                        Toggle::make('maintenance_mode')
                            ->label('Maintenance Mode')
                            ->helperText('Jika aktif, tampilkan halaman maintenance untuk semua user (kecuali admin)')
                            ->onColor('danger')
                            ->offColor('gray'),

                        Toggle::make('registration_enabled')
                            ->label('Registrasi Terbuka')
                            ->helperText('Jika nonaktif, user baru tidak bisa mendaftar')
                            ->onColor('success')
                            ->offColor('danger'),
                    ])
                    ->columns(1),

                Section::make('WhatsApp & OTP')
                    ->description('Pengaturan integrasi WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Toggle::make('otp_enabled')
                            ->label('OTP WhatsApp')
                            ->helperText('Jika aktif, user harus verifikasi nomor via kode OTP yang dikirim ke WhatsApp. Matikan ini jika WhatsApp terkena limit/ban.')
                            ->onColor('success')
                            ->offColor('gray'),

                        Toggle::make('wa_notification_enabled')
                            ->label('Notifikasi WhatsApp')
                            ->helperText('Jika aktif, sistem mengirim notifikasi status (EPT, Penerjemahan) via WhatsApp')
                            ->onColor('success')
                            ->offColor('gray'),
                    ])
                    ->columns(1),

                Section::make('Basic Listening')
                    ->description('Pengaturan fitur Basic Listening')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Toggle::make('bl_quiz_enabled')
                            ->label('Fitur Quiz Aktif')
                            ->helperText('Jika nonaktif, user tidak bisa mengakses quiz Basic Listening')
                            ->onColor('success')
                            ->offColor('danger'),

                        \Filament\Forms\Components\DatePicker::make('bl_period_start_date')
                            ->label('Tanggal Mulai Periode BL')
                            ->helperText('Data BL yang dibuat sebelum tanggal ini akan difilter dari tampilan default di admin panel')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->nullable(),

                        \Filament\Forms\Components\TextInput::make('bl_active_batch')
                            ->label('Angkatan BL Aktif')
                            ->helperText('Prefix NPM yang ditampilkan ke tutor. Bisa lebih dari satu, pisah dengan koma (contoh: 25,26)')
                            ->default(now()->format('y'))
                            ->maxLength(20)
                            ->required(),
                    ])
                    ->columns(1),

                Section::make('EPT Registration')
                    ->description('Atur syarat pendaftaran EPT dari dashboard pendaftar')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Toggle::make('ept_registration_open')
                            ->label('Pendaftaran EPT Dibuka')
                            ->helperText('Jika nonaktif, user baru tidak bisa membuat pendaftaran EPT. User yang sudah pernah daftar tetap bisa melihat status pendaftarannya.')
                            ->onColor('success')
                            ->offColor('danger'),

                        Toggle::make('ept_all_prody')
                            ->label('Semua Prodi Boleh Daftar')
                            ->helperText('Jika aktif, semua prodi bisa mendaftar EPT.')
                            ->onColor('success')
                            ->offColor('gray'),

                        Select::make('ept_allowed_prody_ids')
                            ->label('Daftar Prodi yang Diizinkan')
                            ->helperText('Jika diisi, hanya prodi ini yang boleh mendaftar. Jika kosong, akan pakai prefix.')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => Prody::query()->orderBy('name')->pluck('name', 'id'))
                            ->disabled(fn (Get $get) => (bool) $get('ept_all_prody')),

                        TagsInput::make('ept_allowed_prody_prefixes')
                            ->label('Prefix Prodi yang Diizinkan')
                            ->helperText('Contoh: S2, Profesi, Magister. Akan diabaikan jika daftar prodi diisi.')
                            ->placeholder('Tambah prefix')
                            ->disabled(fn (Get $get) => (bool) $get('ept_all_prody')),

                        Toggle::make('ept_require_whatsapp')
                            ->label('WhatsApp Wajib')
                            ->helperText('Jika aktif, user wajib mengisi (dan verifikasi jika OTP aktif) nomor WhatsApp.')
                            ->onColor('success')
                            ->offColor('gray'),

                        Toggle::make('ept_require_role_pendaftar')
                            ->label('Role Pendaftar Saja')
                            ->helperText('Jika aktif, hanya role pendaftar yang boleh mendaftar EPT.')
                            ->onColor('success')
                            ->offColor('gray'),

                        Toggle::make('ept_require_biodata')
                            ->label('Biodata Wajib')
                            ->helperText('Jika aktif, biodata harus lengkap sebelum bisa mendaftar EPT.')
                            ->onColor('success')
                            ->offColor('gray'),
                    ])
                    ->columns(1),

                Section::make('Script Front Site')
                    ->description('Tambahkan script tracking untuk halaman front site.')
                    ->icon('heroicon-o-code-bracket-square')
                    ->schema([
                        Textarea::make('front_head_script')
                            ->label('Script Head')
                            ->helperText('Akan disisipkan di <head> (contoh: tag <script> tracking).')
                            ->rows(5)
                            ->autosize(),

                        Textarea::make('front_body_script')
                            ->label('Script Body (Top)')
                            ->helperText('Akan disisipkan tepat setelah <body>. Cocok untuk <noscript>.')
                            ->rows(5)
                            ->autosize(),

                        Textarea::make('front_footer_script')
                            ->label('Script Footer')
                            ->helperText('Akan disisipkan sebelum </body>.')
                            ->rows(5)
                            ->autosize(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::set('maintenance_mode', $data['maintenance_mode'] ?? false);
        SiteSetting::set('registration_enabled', $data['registration_enabled'] ?? true);
        SiteSetting::set('otp_enabled', $data['otp_enabled'] ?? false);
        SiteSetting::set('wa_notification_enabled', $data['wa_notification_enabled'] ?? true);
        SiteSetting::set('bl_quiz_enabled', $data['bl_quiz_enabled'] ?? true);
        SiteSetting::set('bl_period_start_date', $data['bl_period_start_date'] ?? null);
        SiteSetting::set('bl_active_batch', $data['bl_active_batch'] ?? now()->format('y'));
        SiteSetting::set('ept_registration_open', $data['ept_registration_open'] ?? true);
        SiteSetting::set('ept_all_prody', $data['ept_all_prody'] ?? false);
        SiteSetting::set('ept_allowed_prody_ids', $data['ept_allowed_prody_ids'] ?? []);
        SiteSetting::set('ept_allowed_prody_prefixes', $data['ept_allowed_prody_prefixes'] ?? []);
        SiteSetting::set('ept_require_whatsapp', $data['ept_require_whatsapp'] ?? false);
        SiteSetting::set('ept_require_role_pendaftar', $data['ept_require_role_pendaftar'] ?? false);
        SiteSetting::set('ept_require_biodata', $data['ept_require_biodata'] ?? false);
        SiteSetting::set('front_head_script', $data['front_head_script'] ?? '');
        SiteSetting::set('front_body_script', $data['front_body_script'] ?? '');
        SiteSetting::set('front_footer_script', $data['front_footer_script'] ?? '');

        // Clear all cache
        SiteSetting::clearCache();

        Notification::make()
            ->title('Pengaturan berhasil disimpan!')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function loadWaStatus(): void
    {
        $this->waBaseUrl = rtrim(config('whatsapp.url', 'https://wa-api.lembagabahasa.site'), '/');
        $apiKey = (string) config('whatsapp.api_key', '');
        $enabled = (bool) config('whatsapp.enabled', false);
        $timeout = (int) config('whatsapp.timeout', 30);

        if (! $enabled) {
            $this->waStatus = [
                'status' => 'disabled',
                'message' => 'WhatsApp service nonaktif di konfigurasi',
            ];
            return;
        }

        if (blank($apiKey)) {
            $this->waStatus = [
                'status' => 'error',
                'message' => 'API key WhatsApp belum diatur',
            ];
            return;
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['x-api-key' => $apiKey])
                ->get("{$this->waBaseUrl}/status");
            if ($response->successful()) {
                $data = $response->json();
                if (! is_array($data)) {
                    $this->waStatus = ['status' => 'error', 'message' => 'Format respons tidak valid'];
                    return;
                }

                if (! isset($data['status']) && array_key_exists('connected', $data)) {
                    $data['status'] = $data['connected'] ? 'connected' : 'disconnected';
                }

                $this->waStatus = $data;
            } else {
                $this->waStatus = [
                    'status' => 'error',
                    'message' => match ($response->status()) {
                        401 => 'Unauthorized (cek API key)',
                        404 => 'Endpoint status tidak ditemukan',
                        default => 'Gagal terhubung ke API',
                    },
                ];
            }
        } catch (\Exception $e) {
            $this->waStatus = ['status' => 'error', 'message' => 'Timeout atau error: ' . $e->getMessage()];
        }
    }

    public function refreshWaStatus(): void
    {
        $this->loadWaStatus();
        $this->loadWaLogs();
        $this->loadWaQueue();
        $this->loadWaWorkerStatus();
        $this->markWaMonitoringRefreshed();
        
        Notification::make()
            ->title('Status WhatsApp diperbarui')
            ->success()
            ->send();
    }

    public function pollWaMonitoring(): void
    {
        $this->loadWaStatus();
        $this->loadWaLogs();
        $this->loadWaQueue();
        $this->loadWaWorkerStatus();
        $this->markWaMonitoringRefreshed();
    }

    protected function markWaMonitoringRefreshed(): void
    {
        $this->waMonitoringRefreshedAt = now()->toDateTimeString();
    }

    public function loadWaLogs(): void
    {
        $this->waBaseUrl = rtrim(config('whatsapp.url', 'https://wa-api.lembagabahasa.site'), '/');
        $apiKey = (string) config('whatsapp.api_key', '');
        $enabled = (bool) config('whatsapp.enabled', false);
        $timeout = (int) config('whatsapp.timeout', 30);

        if (! $enabled || blank($apiKey)) {
            $this->waLogs = [];
            return;
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['x-api-key' => $apiKey])
                ->get("{$this->waBaseUrl}/logs");
            if ($response->successful()) {
                $this->waLogs = $response->json('logs') ?? [];
            } else {
                $this->waLogs = [];
            }
        } catch (\Exception $e) {
            $this->waLogs = [];
        }
    }

    public function loadWaQueue(): void
    {
        $defaultConnection = (string) config('queue.default', 'database');

        if ($defaultConnection !== 'database') {
            $this->waQueue = [];
            $this->waQueueMeta = [
                'supported' => false,
                'message' => "Monitoring antrean live hanya aktif untuk queue database. Saat ini: {$defaultConnection}.",
                'total' => 0,
                'queued' => 0,
                'processing' => 0,
            ];
            return;
        }

        $table = (string) config('queue.connections.database.table', 'jobs');
        $allowedJobs = QueueMonitor::waJobClasses();

        try {
            $rows = DB::table($table)
                ->orderBy('available_at')
                ->limit(100)
                ->get(['id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at']);

            $queue = [];
            $queuedCount = 0;
            $processingCount = 0;

            foreach ($rows as $row) {
                $payload = json_decode($row->payload, true);
                $commandName = data_get($payload, 'data.commandName');

                if (! in_array($commandName, $allowedJobs, true)) {
                    continue;
                }

                $command = $this->extractQueuedCommand($payload);
                $status = $row->reserved_at ? 'processing' : 'queued';
                $preview = $this->buildQueuePreview($commandName, $command);

                if ($status === 'processing') {
                    $processingCount++;
                } else {
                    $queuedCount++;
                }

                $queue[] = [
                    'id' => $row->id,
                    'queue' => $row->queue,
                    'status' => $status,
                    'phone' => $this->maskPhone($this->extractPhone($command)),
                    'type' => $this->describeWaJob($commandName, $command),
                    'preview' => $preview,
                    'attempts' => (int) $row->attempts,
                    'created_at' => $row->created_at ? (int) $row->created_at : null,
                    'available_at' => $row->available_at ? (int) $row->available_at : null,
                    'reserved_at' => $row->reserved_at ? (int) $row->reserved_at : null,
                ];
            }

            $this->waQueue = array_slice($queue, 0, 20);
            $this->waQueueMeta = [
                'supported' => true,
                'total' => $queuedCount + $processingCount,
                'queued' => $queuedCount,
                'processing' => $processingCount,
            ];
        } catch (\Throwable $e) {
            $this->waQueue = [];
            $this->waQueueMeta = [
                'supported' => false,
                'message' => 'Gagal memuat antrean WhatsApp: ' . $e->getMessage(),
                'total' => 0,
                'queued' => 0,
                'processing' => 0,
            ];
        }
    }

    public function loadWaWorkerStatus(): void
    {
        $defaultConnection = (string) config('queue.default', 'database');
        $retryAfter = (int) data_get(config('queue.connections'), "{$defaultConnection}.retry_after", 90);
        $heartbeat = QueueMonitor::readHeartbeat();
        $lastSeenAt = (int) ($heartbeat['last_seen_at'] ?? 0);
        $now = now()->timestamp;
        $heartbeatAge = $lastSeenAt > 0 ? max(0, $now - $lastSeenAt) : null;
        $stuckAfter = max($retryAfter + 30, QueueMonitor::HEARTBEAT_FRESH_SECONDS);

        $queuedCount = 0;
        $processingCount = 0;
        $staleReservedAt = null;
        $queueMessage = null;

        if ($defaultConnection === 'database') {
            try {
                $table = (string) config('queue.connections.database.table', 'jobs');
                $jobClasses = QueueMonitor::waJobClasses();
                $baseQuery = DB::table($table)->where(function ($query) use ($jobClasses) {
                    foreach ($jobClasses as $jobClass) {
                        $query->orWhere('payload', 'like', '%' . $jobClass . '%');
                    }
                });

                $queuedCount = (clone $baseQuery)->whereNull('reserved_at')->count();
                $processingCount = (clone $baseQuery)->whereNotNull('reserved_at')->count();
                $staleReservedAt = (clone $baseQuery)
                    ->whereNotNull('reserved_at')
                    ->orderBy('reserved_at')
                    ->value('reserved_at');
            } catch (\Throwable $e) {
                $queueMessage = 'Gagal membaca tabel jobs: ' . $e->getMessage();
            }
        } else {
            $queueMessage = "Deteksi worker detail hanya akurat untuk queue database. Saat ini: {$defaultConnection}.";
        }

        $failedCount = 0;
        $lastFailedAt = null;

        try {
            $failedDriver = (string) config('queue.failed.driver', 'database-uuids');

            if (str_starts_with($failedDriver, 'database')) {
                $failedTable = (string) config('queue.failed.table', 'failed_jobs');
                $jobClasses = QueueMonitor::monitoredJobClasses();
                $failedQuery = DB::table($failedTable)->where(function ($query) use ($jobClasses) {
                    foreach ($jobClasses as $jobClass) {
                        $query->orWhere('payload', 'like', '%' . $jobClass . '%');
                    }
                });

                $failedCount = (clone $failedQuery)->count();
                $lastFailedAt = (clone $failedQuery)->max('failed_at');
            } else {
                $queueMessage = $queueMessage ?? "Driver failed jobs saat ini: {$failedDriver}.";
            }
        } catch (\Throwable $e) {
            $queueMessage = $queueMessage ?? 'Gagal membaca failed jobs: ' . $e->getMessage();
        }

        $hasStuckProcessing = $staleReservedAt
            ? (($now - (int) $staleReservedAt) > $stuckAfter)
            : false;

        if ($hasStuckProcessing) {
            $state = 'stuck';
            $label = 'Worker macet';
            $color = 'warning';
            $message = 'Ada job yang sudah diambil worker terlalu lama dan belum selesai.';
        } elseif ($lastSeenAt > 0 && $heartbeatAge !== null && $heartbeatAge <= QueueMonitor::HEARTBEAT_FRESH_SECONDS) {
            $state = 'active';
            $label = 'Worker aktif';
            $color = 'success';
            $message = 'Heartbeat worker masih segar.';
        } elseif (($queuedCount + $processingCount) > 0) {
            $state = 'down';
            $label = 'Worker tidak berjalan';
            $color = 'danger';
            $message = 'Ada antrean, tetapi tidak ada heartbeat worker yang baru.';
        } else {
            $state = 'idle';
            $label = 'Belum ada aktivitas worker';
            $color = 'gray';
            $message = 'Belum ada antrean aktif atau heartbeat worker masih belum terdeteksi.';
        }

        if ($queueMessage) {
            $message .= ' ' . $queueMessage;
        }

        $this->waWorkerStatus = [
            'state' => $state,
            'label' => $label,
            'color' => $color,
            'message' => trim($message),
            'last_seen_at' => $lastSeenAt ?: null,
            'last_event' => $heartbeat['event'] ?? null,
            'last_job' => $heartbeat['job'] ?? null,
            'last_queue' => $heartbeat['queue'] ?? null,
            'host' => $heartbeat['host'] ?? null,
            'queued_count' => $queuedCount,
            'processing_count' => $processingCount,
            'stale_reserved_at' => $staleReservedAt ? (int) $staleReservedAt : null,
            'failed_count' => $failedCount,
            'last_failed_at' => $lastFailedAt,
            'has_failed_jobs' => $failedCount > 0,
        ];
    }

    protected function extractQueuedCommand(array $payload): mixed
    {
        $serialized = data_get($payload, 'data.command');

        if (! is_string($serialized) || $serialized === '') {
            return null;
        }

        try {
            return unserialize($serialized, ['allowed_classes' => true]);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function extractPhone(mixed $command): ?string
    {
        if (is_object($command) && property_exists($command, 'phone')) {
            return (string) $command->phone;
        }

        return null;
    }

    protected function describeWaJob(string $commandName, mixed $command): string
    {
        return match ($commandName) {
            SendWhatsAppOtp::class => 'OTP Verifikasi',
            SendWhatsAppResetLink::class => 'Reset Password',
            SendWhatsAppNotification::class => $this->describeNotificationJob($command),
            SendWhatsAppMessage::class => 'Pesan Custom',
            QueueHeartbeatPing::class => 'Heartbeat Worker',
            default => class_basename($commandName),
        };
    }

    protected function describeNotificationJob(mixed $command): string
    {
        if (! is_object($command) || ! property_exists($command, 'type')) {
            return 'Notifikasi';
        }

        $type = (string) $command->type;
        $status = property_exists($command, 'status') ? (string) $command->status : null;
        $label = match ($type) {
            'ept_status' => 'Notifikasi Surat Rekomendasi',
            'penerjemahan_status' => 'Notifikasi Penerjemahan',
            default => 'Notifikasi',
        };

        return $status ? "{$label} ({$status})" : $label;
    }

    protected function buildQueuePreview(string $commandName, mixed $command): ?string
    {
        return match ($commandName) {
            SendWhatsAppOtp::class => 'Kode OTP verifikasi nomor WhatsApp.',
            SendWhatsAppResetLink::class => 'Tautan reset password.',
            SendWhatsAppNotification::class => is_object($command) && property_exists($command, 'details')
                ? $this->truncatePreview((string) ($command->details ?? ''))
                : null,
            SendWhatsAppMessage::class => is_object($command) && property_exists($command, 'message')
                ? $this->truncatePreview((string) ($command->message ?? ''))
                : null,
            default => null,
        };
    }

    protected function truncatePreview(string $preview, int $limit = 100): ?string
    {
        $preview = trim(preg_replace('/\s+/', ' ', $preview));

        if ($preview === '') {
            return null;
        }

        if (mb_strlen($preview) <= $limit) {
            return $preview;
        }

        return mb_substr($preview, 0, $limit - 1) . '…';
    }

    protected function maskPhone(?string $phone): string
    {
        $clean = preg_replace('/\D+/', '', (string) $phone);

        if ($clean === '') {
            return 'unknown';
        }

        if (strlen($clean) <= 8) {
            return $clean;
        }

        return substr($clean, 0, 5) . '****' . substr($clean, -4);
    }
}
