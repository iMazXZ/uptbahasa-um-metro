<x-filament-panels::page>
    <div wire:poll.15s.keep-alive="pollWaMonitoring" class="space-y-6">
        {{-- WhatsApp API Status --}}
        <x-filament::section icon="heroicon-o-signal">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @if($waStatus && $waStatus['status'] === 'connected')
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-success-500"></span>
                        </span>
                    @else
                        <span class="flex h-2.5 w-2.5 rounded-full bg-danger-500"></span>
                    @endif
                    Status WhatsApp API
                </div>
            </x-slot>

            <x-slot name="description">
                @php
                    $waUrl = $waBaseUrl ?? config('whatsapp.url', 'https://wa-api.lembagabahasa.site');
                    $waHost = parse_url($waUrl, PHP_URL_HOST) ?? $waUrl;
                @endphp
                {{ $waHost }}
            </x-slot>

            <x-slot name="headerEnd">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">Auto-refresh 15 detik</span>
                    <x-filament::button
                        wire:click="refreshWaStatus"
                        size="sm"
                        color="gray"
                        icon="heroicon-o-arrow-path"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="refreshWaStatus">Refresh</span>
                        <span wire:loading wire:target="refreshWaStatus">Loading...</span>
                    </x-filament::button>
                </div>
            </x-slot>

            @if($waStatus)
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    @if($waStatus['status'] === 'connected')
                        <x-filament::badge color="success" icon="heroicon-o-check-circle">
                            Terhubung
                        </x-filament::badge>
                    @elseif($waStatus['status'] === 'disconnected')
                        <x-filament::badge color="danger" icon="heroicon-o-x-circle">
                            Terputus
                        </x-filament::badge>
                    @elseif($waStatus['status'] === 'error')
                        <x-filament::badge color="danger" icon="heroicon-o-exclamation-triangle">
                            Error
                        </x-filament::badge>
                    @elseif($waStatus['status'] === 'disabled')
                        <x-filament::badge color="gray" icon="heroicon-o-minus-circle">
                            Nonaktif
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="warning" icon="heroicon-o-exclamation-triangle">
                            {{ ucfirst($waStatus['status'] ?? 'Unknown') }}
                        </x-filament::badge>
                    @endif

                    @if(!empty($waStatus['uptimeFormatted']))
                        <span class="text-gray-500 dark:text-gray-400">
                            Uptime: <strong>{{ $waStatus['uptimeFormatted'] }}</strong>
                        </span>
                    @endif

                    @if(!empty($waStatus['qrPending']) && $waStatus['qrPending'])
                        <x-filament::badge color="warning" icon="heroicon-o-qr-code">
                            Menunggu scan QR
                        </x-filament::badge>
                    @endif

                    @if(!empty($waStatus['message']))
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ $waStatus['message'] }}
                        </span>
                    @endif
                </div>
            @else
                <div class="flex items-center gap-2 text-gray-500">
                    <x-heroicon-o-exclamation-circle class="w-5 h-5" />
                    <span>Tidak dapat memuat status</span>
                </div>
            @endif
        </x-filament::section>

        {{-- WhatsApp Queue --}}
        <x-filament::section icon="heroicon-o-clock">
            <x-slot name="heading">
                Monitor Antrean WhatsApp
            </x-slot>

            <x-slot name="description">
                Pending queue live dari Laravel + log kirim aktual dari service WA.
            </x-slot>

            <x-slot name="headerEnd">
                <div class="flex flex-wrap items-center justify-end gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span>Auto-refresh 15 detik</span>
                    @if($waMonitoringRefreshedAt)
                        <span>Terakhir: {{ \Carbon\Carbon::parse($waMonitoringRefreshedAt)->format('H:i:s') }}</span>
                    @endif
                    <x-filament::button
                        wire:click="pollWaMonitoring"
                        size="sm"
                        color="gray"
                        icon="heroicon-o-arrow-path"
                        wire:loading.attr="disabled"
                        wire:target="pollWaMonitoring"
                    >
                        <span wire:loading.remove wire:target="pollWaMonitoring">Refresh</span>
                        <span wire:loading wire:target="pollWaMonitoring">Loading...</span>
                    </x-filament::button>
                </div>
            </x-slot>

            <div class="mb-4 space-y-3">
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <x-filament::badge color="{{ $waWorkerStatus['color'] ?? 'gray' }}">
                        {{ $waWorkerStatus['label'] ?? 'Status worker belum tersedia' }}
                    </x-filament::badge>

                    @if(!empty($waWorkerStatus['has_failed_jobs']))
                        <x-filament::badge color="danger">
                            Ada job gagal: {{ $waWorkerStatus['failed_count'] ?? 0 }}
                        </x-filament::badge>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                    @if(!empty($waWorkerStatus['message']))
                        <span>{{ $waWorkerStatus['message'] }}</span>
                    @endif
                    @if(!empty($waWorkerStatus['last_seen_at']))
                        <span>Heartbeat {{ \Carbon\Carbon::createFromTimestamp($waWorkerStatus['last_seen_at'])->diffForHumans() }}</span>
                    @endif
                    @if(!empty($waWorkerStatus['stale_reserved_at']))
                        <span>Job processing tertua {{ \Carbon\Carbon::createFromTimestamp($waWorkerStatus['stale_reserved_at'])->diffForHumans() }}</span>
                    @endif
                    @if(!empty($waWorkerStatus['last_failed_at']))
                        <span>Job gagal terakhir {{ \Carbon\Carbon::parse($waWorkerStatus['last_failed_at'])->diffForHumans() }}</span>
                    @endif
                    @if(!empty($waWorkerStatus['last_job']))
                        <span>Job terakhir {{ class_basename($waWorkerStatus['last_job']) }}</span>
                    @endif
                    @if(!empty($waWorkerStatus['host']))
                        <span>Host {{ $waWorkerStatus['host'] }}</span>
                    @endif
                </div>
            </div>

            @if(($waQueueMeta['supported'] ?? false) === false)
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <x-heroicon-o-exclamation-circle class="w-5 h-5" />
                    <span>{{ $waQueueMeta['message'] ?? 'Monitoring antrean tidak tersedia.' }}</span>
                </div>
            @else
                <div class="mb-4 flex flex-wrap items-center gap-3 text-sm">
                    <x-filament::badge color="gray">
                        Total: {{ $waQueueMeta['total'] ?? 0 }}
                    </x-filament::badge>
                    <x-filament::badge color="warning">
                        Queued: {{ $waQueueMeta['queued'] ?? 0 }}
                    </x-filament::badge>
                    <x-filament::badge color="info">
                        Processing: {{ $waQueueMeta['processing'] ?? 0 }}
                    </x-filament::badge>
                    <span class="text-gray-500 dark:text-gray-400">
                        {{ $waQueueMeta['message'] ?? '' }}
                    </span>
                </div>

                @if(!empty($waQueue))
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($waQueue as $item)
                            <div class="py-3 flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-medium">{{ $item['phone'] }}</span>
                                        <x-filament::badge size="sm" color="gray">{{ $item['type'] }}</x-filament::badge>
                                        @if($item['status'] === 'processing')
                                            <x-filament::badge size="sm" color="info">processing</x-filament::badge>
                                        @else
                                            <x-filament::badge size="sm" color="warning">queued</x-filament::badge>
                                        @endif
                                        <x-filament::badge size="sm" color="gray">attempt {{ $item['attempts'] }}</x-filament::badge>
                                    </div>

                                    @if(!empty($item['preview']))
                                        <p class="text-xs text-gray-500">{{ $item['preview'] }}</p>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-400">
                                        @if(!empty($item['created_at']))
                                            <span>Dibuat {{ \Carbon\Carbon::createFromTimestamp($item['created_at'])->diffForHumans() }}</span>
                                        @endif
                                        @if(!empty($item['available_at']))
                                            <span>Tersedia {{ \Carbon\Carbon::createFromTimestamp($item['available_at'])->diffForHumans() }}</span>
                                        @endif
                                        @if(!empty($item['reserved_at']))
                                            <span>Diambil worker {{ \Carbon\Carbon::createFromTimestamp($item['reserved_at'])->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>

                                <span class="text-xs text-gray-400">#{{ $item['id'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">Tidak ada antrean WA saat ini.</p>
                @endif
            @endif
        </x-filament::section>

        {{-- WhatsApp Message Logs --}}
        @if(!empty($waLogs))
        <x-filament::section collapsible collapsed icon="heroicon-o-document-text">
            <x-slot name="heading">
                Log Pesan Terakhir ({{ count($waLogs) }})
            </x-slot>

            <x-slot name="headerEnd">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Auto-refresh 15 detik
                    @if($waMonitoringRefreshedAt)
                        · terakhir {{ \Carbon\Carbon::parse($waMonitoringRefreshedAt)->format('H:i:s') }}
                    @endif
                </span>
            </x-slot>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($waLogs as $log)
                    <div class="py-2 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            @if($log['status'] === 'success')
                                <x-filament::badge color="success" size="sm">✓</x-filament::badge>
                            @else
                                <x-filament::badge color="danger" size="sm">✗</x-filament::badge>
                            @endif

                            <span class="text-sm font-medium">{{ $log['phone'] }}</span>
                            <x-filament::badge size="sm" color="gray">{{ $log['type'] }}</x-filament::badge>
                        </div>

                        <span class="text-xs text-gray-400">
                            {{ \Carbon\Carbon::parse($log['timestamp'])->diffForHumans() }}
                        </span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
        @endif
    </div>

    {{-- Settings Form --}}
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                Simpan Pengaturan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
