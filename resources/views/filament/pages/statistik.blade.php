<x-filament-panels::page>
    {{-- ================= HEADER + FILTER ================= --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="h-1.5 bg-gradient-to-r from-um-blue via-blue-400 to-um-gold"></div>
        <div class="p-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-um-blue">
                        <i class="fa-solid fa-chart-line text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800">{{ $datasetOptions[$dataset] ?? 'Statistik' }}</h2>
                        <div class="mt-0.5 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">
                                <i class="fa-regular fa-calendar"></i>
                                {{ \Carbon\Carbon::parse($from)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($to)->translatedFormat('d M Y') }}
                            </span>
                            @if($mode)
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">
                                    {{ $modeOptions[$mode] ?? $mode }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <x-filament::button wire:click="applyFilters" color="primary" icon="heroicon-o-funnel"
                                        wire:loading.attr="disabled" wire:target="applyFilters">
                        <span wire:loading.remove wire:target="applyFilters">Terapkan</span>
                        <span wire:loading wire:target="applyFilters">Memuat...</span>
                    </x-filament::button>
                    <x-filament::button wire:click="export" color="success" icon="heroicon-o-arrow-down-tray" outlined>
                        Export
                    </x-filament::button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Jenis Data</label>
                    <select wire:model="dataset"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-medium text-slate-700 outline-none transition focus:border-um-blue focus:bg-white focus:ring-2 focus:ring-um-blue/20">
                        @foreach($datasetOptions as $value => $label)
                            <option value="{{ $value }}" @selected($value === $dataset)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Mode (EPT)</label>
                    <select wire:model="mode"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-medium text-slate-700 outline-none transition focus:border-um-blue focus:bg-white focus:ring-2 focus:ring-um-blue/20">
                        @foreach($modeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($value === $mode)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Dari</label>
                    <input type="date" wire:model="from"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-medium text-slate-700 outline-none transition focus:border-um-blue focus:bg-white focus:ring-2 focus:ring-um-blue/20">
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Sampai</label>
                    <input type="date" wire:model="to"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-medium text-slate-700 outline-none transition focus:border-um-blue focus:bg-white focus:ring-2 focus:ring-um-blue/20">
                </div>
            </div>
        </div>
    </div>

    {{-- ================= KARTU STATISTIK ================= --}}
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $maxIdx = $monthCounts ? array_search(max($monthCounts), $monthCounts) : null;
            $avg = count($monthCounts) > 0 ? array_sum($monthCounts) / count($monthCounts) : 0;
        @endphp

        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="absolute inset-x-0 top-0 h-0.5 bg-um-blue"></div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-um-blue">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <p class="mt-3 text-3xl font-black tabular-nums text-slate-900">{{ number_format($grandTotal) }}</p>
            <p class="mt-1 text-xs text-slate-400">pendaftar pada rentang terpilih</p>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="absolute inset-x-0 top-0 h-0.5 bg-emerald-500"></div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Rata-rata</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <i class="fa-solid fa-chart-simple"></i>
                </div>
            </div>
            <p class="mt-3 text-3xl font-black tabular-nums text-slate-900">{{ number_format($avg, 1) }}</p>
            <p class="mt-1 text-xs text-slate-400">per {{ $chartMode === 'daily' ? 'hari' : 'bulan' }}</p>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="absolute inset-x-0 top-0 h-0.5 bg-amber-400"></div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Puncak</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>
            <p class="mt-3 truncate text-xl font-black text-slate-900">
                {{ $maxIdx !== null ? ($monthLabels[$maxIdx] ?? '-') : '—' }}
            </p>
            <p class="mt-1 text-xs text-slate-400">
                {{ $maxIdx !== null ? number_format($monthCounts[$maxIdx]) . ' pendaftar' : 'belum ada data' }}
            </p>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="absolute inset-x-0 top-0 h-0.5 bg-rose-400"></div>
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Prodi</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>
            <p class="mt-3 text-3xl font-black tabular-nums text-slate-900">{{ count($prodiRows) }}</p>
            <p class="mt-1 text-xs text-slate-400">program studi terdaftar</p>
        </div>
    </div>

    {{-- ================= GRAFIK + TOP 10 ================= --}}
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-5 gap-6">
        {{-- Chart --}}
        <div class="lg:col-span-3 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-um-blue">
                        <i class="fa-solid fa-chart-area text-sm"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">
                        {{ $chartMode === 'daily' ? 'Perkembangan per Hari' : 'Perkembangan per Bulan' }}
                    </h3>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500">
                    {{ count($monthLabels) }} {{ $chartMode === 'daily' ? 'hari' : 'bulan' }}
                </span>
            </div>
            <div class="p-6" wire:key="monthly-chart">
                <canvas id="statistik-monthly"
                        height="110"
                        data-labels='@json($monthLabels)'
                        data-counts='@json($monthCounts)'></canvas>
            </div>
        </div>

        {{-- Top 10 --}}
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <i class="fa-solid fa-trophy text-sm"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Top 10 Prodi</h3>
                </div>
            </div>
            <div class="p-5">
                @if(count($prodiRows) > 0)
                <div class="space-y-4">
                    @foreach(array_slice($prodiRows, 0, 10) as $i => $row)
                        @php
                            $rank = $i + 1;
                            $maxTotal = max(array_column(array_slice($prodiRows, 0, 10), 'total')) ?: 1;
                            $barWidth = max(6, min(100, round(($row['total'] / $maxTotal) * 100)));
                            $rankStyle = match ($rank) {
                                1 => 'bg-gradient-to-br from-amber-300 to-amber-500 text-white shadow-sm',
                                2 => 'bg-gradient-to-br from-slate-300 to-slate-400 text-white shadow-sm',
                                3 => 'bg-gradient-to-br from-orange-300 to-orange-400 text-white shadow-sm',
                                default => 'bg-slate-100 text-slate-500',
                            };
                        @endphp
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-black {{ $rankStyle }}">
                                {{ $rank }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex items-baseline justify-between gap-2">
                                    <p class="truncate text-[13px] font-semibold text-slate-700" title="{{ $row['prodi'] }}">{{ $row['prodi'] }}</p>
                                    <span class="shrink-0 text-xs font-black tabular-nums text-um-blue">{{ number_format($row['total']) }}</span>
                                </div>
                                <div class="h-1 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-gradient-to-r from-um-blue to-blue-400" style="width: {{ $barWidth }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @else
                <div class="py-10 text-center">
                    <i class="fa-regular fa-folder-open text-3xl text-slate-300"></i>
                    <p class="mt-3 text-sm text-slate-400">Tidak ada data pada rentang ini.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ================= TABEL RINCIAN ================= --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                    <i class="fa-solid fa-table-list text-sm"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Rincian per Prodi</h3>
            </div>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500">
                {{ count($prodiRows) }} prodi
            </span>
        </div>

        @if(count($prodiRows) > 0)
        <div class="max-h-[480px] overflow-auto" wire:key="prodi-table">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50">
                    <tr class="text-left text-[11px] font-bold uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 w-12">#</th>
                        <th class="px-3 py-3">Program Studi</th>
                        <th class="px-3 py-3 text-right">Jumlah</th>
                        <th class="px-6 py-3 text-right w-56">Kontribusi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($prodiRows as $i => $row)
                    <tr class="transition hover:bg-blue-50/40 {{ $i % 2 === 1 ? 'bg-slate-50/50' : '' }}">
                        <td class="px-6 py-3 text-xs font-semibold text-slate-400">{{ $i + 1 }}</td>
                        <td class="px-3 py-3 font-medium text-slate-800">{{ $row['prodi'] }}</td>
                        <td class="px-3 py-3 text-right font-bold tabular-nums text-slate-800">{{ number_format($row['total']) }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <div class="h-1.5 w-28 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-gradient-to-r from-um-blue to-blue-400" style="width: {{ min(100, $row['persen']) }}%"></div>
                                </div>
                                <span class="w-12 text-right text-xs font-semibold tabular-nums text-slate-500">{{ $row['persen'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="py-12 text-center">
            <i class="fa-regular fa-folder-open text-3xl text-slate-300"></i>
            <p class="mt-3 text-sm text-slate-400">Tidak ada data pada rentang ini.</p>
        </div>
        @endif
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
                gradient.addColorStop(0, 'rgba(30, 64, 175, 0.25)');
                gradient.addColorStop(1, 'rgba(30, 64, 175, 0.02)');

                statistikChart = new Chart(monthlyEl, {
                    type: 'line',
                    data: {
                        labels: monthLabels,
                        datasets: [{
                            label: 'Jumlah',
                            data: monthCounts,
                            backgroundColor: gradient,
                            borderColor: '#1e40af',
                            borderWidth: 2,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#1e40af',
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
