<x-filament-panels::page>
    {{-- ================= FILTER ================= --}}
    <x-filament::section icon="heroicon-o-adjustments-horizontal">
        <x-slot name="heading">
            Filter
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model="dataset">
                        @foreach($datasetOptions as $value => $label)
                            <option value="{{ $value }}" @selected($value === $dataset)>{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            <div>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model="mode">
                        @foreach($modeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($value === $mode)>{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            <div>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model="from" />
                </x-filament::input.wrapper>
            </div>
            <div>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model="to" />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <x-filament::icon icon="heroicon-o-calendar" class="h-4 w-4" />
                <span>{{ \Carbon\Carbon::parse($from)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($to)->translatedFormat('d M Y') }}</span>
                @if($mode)
                    <x-filament::badge color="success">{{ $modeOptions[$mode] ?? $mode }}</x-filament::badge>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <x-filament::button wire:click="applyFilters" icon="heroicon-o-funnel"
                                    wire:loading.attr="disabled" wire:target="applyFilters">
                    <span wire:loading.remove wire:target="applyFilters">Terapkan Filter</span>
                    <span wire:loading wire:target="applyFilters">Memuat...</span>
                </x-filament::button>
                <x-filament::button wire:click="export" color="success" icon="heroicon-o-arrow-down-tray" outlined>
                    Export Excel
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    {{-- ================= KARTU STATISTIK ================= --}}
    @php
        $maxIdx = $monthCounts ? array_search(max($monthCounts), $monthCounts) : null;
        $avg = count($monthCounts) > 0 ? array_sum($monthCounts) / count($monthCounts) : 0;
    @endphp

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-filament::section compact>
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-500">Total</span>
                <x-filament::icon icon="heroicon-o-users" class="h-5 w-5 text-primary-600" />
            </div>
            <p class="mt-2 text-3xl font-bold tabular-nums text-gray-900">{{ number_format($grandTotal) }}</p>
            <p class="mt-1 text-xs text-gray-400">pendaftar terpilih</p>
        </x-filament::section>

        <x-filament::section compact>
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-500">Rata-rata</span>
                <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5 text-success-600" />
            </div>
            <p class="mt-2 text-3xl font-bold tabular-nums text-gray-900">{{ number_format($avg, 1) }}</p>
            <p class="mt-1 text-xs text-gray-400">per {{ $chartMode === 'daily' ? 'hari' : 'bulan' }}</p>
        </x-filament::section>

        <x-filament::section compact>
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-500">Puncak</span>
                <x-filament::icon icon="heroicon-o-arrow-trending-up" class="h-5 w-5 text-warning-600" />
            </div>
            <p class="mt-2 truncate text-xl font-bold text-gray-900">{{ $maxIdx !== null ? ($monthLabels[$maxIdx] ?? '—') : '—' }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ $maxIdx !== null ? number_format($monthCounts[$maxIdx]) . ' pendaftar' : 'belum ada data' }}</p>
        </x-filament::section>

        <x-filament::section compact>
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-500">Prodi</span>
                <x-filament::icon icon="heroicon-o-academic-cap" class="h-5 w-5 text-danger-600" />
            </div>
            <p class="mt-2 text-3xl font-bold tabular-nums text-gray-900">{{ count($prodiRows) }}</p>
            <p class="mt-1 text-xs text-gray-400">program studi</p>
        </x-filament::section>
    </div>

    {{-- ================= GRAFIK + TOP 10 ================= --}}
    <div class="mt-6 grid grid-cols-1 xl:grid-cols-5 gap-6">
        <div class="xl:col-span-3">
            <x-filament::section icon="heroicon-o-chart-line">
                <x-slot name="heading">
                    {{ $chartMode === 'daily' ? 'Perkembangan per Hari' : 'Perkembangan per Bulan' }}
                </x-slot>
                <x-slot name="headerEnd">
                    <x-filament::badge color="gray">
                        {{ count($monthLabels) }} {{ $chartMode === 'daily' ? 'hari' : 'bulan' }}
                    </x-filament::badge>
                </x-slot>

                <div wire:key="monthly-chart">
                    <canvas id="statistik-monthly"
                            height="110"
                            data-labels='@json($monthLabels)'
                            data-counts='@json($monthCounts)'></canvas>
                </div>
            </x-filament::section>
        </div>

        <div class="xl:col-span-2">
            <x-filament::section icon="heroicon-o-trophy">
                <x-slot name="heading">
                    Top 10 Prodi
                </x-slot>

                @if(count($prodiRows) > 0)
                <div class="space-y-3">
                    @foreach(array_slice($prodiRows, 0, 10) as $i => $row)
                        @php
                            $rank = $i + 1;
                            $maxTotal = max(array_column(array_slice($prodiRows, 0, 10), 'total')) ?: 1;
                            $barWidth = max(6, min(100, round(($row['total'] / $maxTotal) * 100)));
                            $rankStyle = match ($rank) {
                                1 => 'bg-amber-400 text-white',
                                2 => 'bg-slate-400 text-white',
                                3 => 'bg-orange-400 text-white',
                                default => 'bg-gray-100 text-gray-500',
                            };
                        @endphp
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $rankStyle }}">{{ $rank }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex items-baseline justify-between gap-2">
                                    <p class="truncate text-[13px] font-medium text-gray-700" title="{{ $row['prodi'] }}">{{ $row['prodi'] }}</p>
                                    <span class="shrink-0 text-xs font-bold tabular-nums text-primary-600">{{ number_format($row['total']) }}</span>
                                </div>
                                <div class="h-1 w-full overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-full rounded-full bg-primary-500" style="width: {{ $barWidth }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @else
                <div class="py-10 text-center">
                    <x-filament::icon icon="heroicon-o-folder-open" class="mx-auto h-8 w-8 text-gray-300" />
                    <p class="mt-3 text-sm text-gray-400">Tidak ada data pada rentang ini.</p>
                </div>
                @endif
            </x-filament::section>
        </div>
    </div>

    {{-- ================= TABEL RINCIAN ================= --}}
    <div class="mt-6">
        <x-filament::section icon="heroicon-o-table-cells">
            <x-slot name="heading">
                Rincian per Prodi
            </x-slot>
            <x-slot name="headerEnd">
                <x-filament::badge color="gray">{{ count($prodiRows) }} prodi</x-filament::badge>
            </x-slot>

            @if(count($prodiRows) > 0)
            <div class="-mx-4 overflow-auto" wire:key="prodi-table">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <th class="px-4 py-3 w-10">#</th>
                            <th class="px-4 py-3">Program Studi</th>
                            <th class="px-4 py-3 text-right">Jumlah</th>
                            <th class="px-4 py-3 text-right w-56">Kontribusi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($prodiRows as $i => $row)
                        <tr class="transition hover:bg-gray-50">
                            <td class="px-4 py-3 text-xs font-semibold text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $row['prodi'] }}</td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums text-gray-800">{{ number_format($row['total']) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <div class="h-1.5 w-28 overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full bg-primary-500" style="width: {{ min(100, $row['persen']) }}%"></div>
                                    </div>
                                    <span class="w-12 text-right text-xs font-semibold tabular-nums text-gray-500">{{ $row['persen'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="py-12 text-center">
                <x-filament::icon icon="heroicon-o-folder-open" class="mx-auto h-8 w-8 text-gray-300" />
                <p class="mt-3 text-sm text-gray-400">Tidak ada data pada rentang ini.</p>
            </div>
            @endif
        </x-filament::section>
    </div>

    @push('scripts')
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
    <script>
        let statistikChart = null;
        let statistikLastKey = '';

        function initStatistikCharts(force = false) {
            const monthlyEl = document.getElementById('statistik-monthly');
            if (!monthlyEl || !window.Chart) return;

            let monthLabels = [];
            let monthCounts = [];
            try {
                monthLabels = JSON.parse(monthlyEl.dataset.labels || '[]');
                monthCounts = JSON.parse(monthlyEl.dataset.counts || '[]');
            } catch (e) { /* abaikan */ }

            const key = JSON.stringify(monthLabels) + '|' + JSON.stringify(monthCounts);
            if (!force && key === statistikLastKey) return;
            statistikLastKey = key;

            if (statistikChart) { statistikChart.destroy(); statistikChart = null; }

            if (monthLabels.length > 0) {
                const ctx = monthlyEl.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 220);
                gradient.addColorStop(0, 'rgba(14, 165, 233, 0.25)');
                gradient.addColorStop(1, 'rgba(14, 165, 233, 0.02)');

                statistikChart = new Chart(monthlyEl, {
                    type: 'line',
                    data: {
                        labels: monthLabels,
                        datasets: [{
                            label: 'Jumlah',
                            data: monthCounts,
                            backgroundColor: gradient,
                            borderColor: '#0ea5e9',
                            borderWidth: 2,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#0ea5e9',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: true,
                            tension: 0.35,
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148,163,184,0.12)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => initStatistikCharts(true));
        document.addEventListener('livewire:init', () => {
            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => setTimeout(() => initStatistikCharts(true), 80));
            });
        });
        const observer = new MutationObserver(() => {
            if (document.getElementById('statistik-monthly')) initStatistikCharts(true);
        });
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('[wire\\:key="monthly-chart"]') || document.body;
            observer.observe(container, { childList: true, subtree: true });
        });
    </script>
    @endpush
</x-filament-panels::page>
