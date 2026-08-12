<x-filament-panels::page>
    {{-- FILTER --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex flex-wrap items-end gap-x-6 gap-y-4">
            <div class="min-w-[220px]">
                <label class="block text-xs font-semibold text-slate-600 mb-2">Jenis Data</label>
                <select wire:model="dataset"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none">
                    @foreach($datasetOptions as $value => $label)
                        <option value="{{ $value }}" @selected($value === $dataset)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[180px]">
                <label class="block text-xs font-semibold text-slate-600 mb-2">Mode (EPT)</label>
                <select wire:model="mode"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none">
                    @foreach($modeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($value === $mode)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-2">Dari Tanggal</label>
                <input type="date" wire:model="from"
                       class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-2">Sampai Tanggal</label>
                <input type="date" wire:model="to"
                       class="rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none">
            </div>

            <div class="ml-auto flex items-center gap-3 pb-0.5">
                <x-filament::button wire:click="applyFilters" color="primary" icon="heroicon-o-funnel"
                                    wire:loading.attr="disabled" wire:target="applyFilters">
                    <span wire:loading.remove wire:target="applyFilters">Terapkan Filter</span>
                    <span wire:loading wire:target="applyFilters">Memuat...</span>
                </x-filament::button>
                <x-filament::button wire:click="export" color="success" icon="heroicon-o-arrow-down-tray">
                    Export Excel
                </x-filament::button>
            </div>
        </div>
    </div>

    {{-- RINGKASAN --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total</p>
            <p class="text-3xl font-black text-um-blue mt-2">{{ number_format($grandTotal) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Rata-rata / Bulan</p>
            <p class="text-3xl font-black text-emerald-700 mt-2">
                {{ count($monthCounts) > 0 ? number_format(array_sum($monthCounts) / count($monthCounts), 1) : 0 }}
            </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Bulan Tertinggi</p>
            @php
                $maxIdx = $monthCounts ? array_search(max($monthCounts), $monthCounts) : null;
            @endphp
            <p class="text-lg font-bold text-amber-700 mt-2 truncate">
                {{ $maxIdx !== null ? ($monthLabels[$maxIdx] ?? '-') : '-' }}
                <span class="text-sm font-semibold">({{ $maxIdx !== null ? number_format($monthCounts[$maxIdx]) : 0 }})</span>
            </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Jumlah Prodi</p>
            <p class="text-3xl font-black text-rose-600 mt-2">{{ count($prodiRows) }}</p>
        </div>
    </div>

    {{-- GRAFIK + TOP 10 --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mt-6">
        <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-bold text-slate-800">
                    {{ $chartMode === 'daily' ? 'Perkembangan per Hari' : 'Perkembangan per Bulan' }}
                </h3>
                <span class="text-xs text-slate-500">
                    {{ $chartMode === 'daily' ? count($monthLabels) . ' hari' : count($monthLabels) . ' bulan' }}
                </span>
            </div>
            <div wire:key="monthly-chart">
                <canvas id="statistik-monthly"
                        height="90"
                        data-labels='@json($monthLabels)'
                        data-counts='@json($monthCounts)'></canvas>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-bold text-slate-800 mb-5">Top 10 Prodi</h3>
            <div class="space-y-3">
                @foreach(array_slice($prodiRows, 0, 10) as $i => $row)
                    @php
                        $rank = $i + 1;
                        $maxTotal = max(array_column(array_slice($prodiRows, 0, 10), 'total')) ?: 1;
                        $barWidth = max(6, min(100, round(($row['total'] / $maxTotal) * 100)));
                        $rankStyle = match ($rank) {
                            1 => 'bg-amber-100 text-amber-700 border-amber-200',
                            2 => 'bg-slate-100 text-slate-600 border-slate-200',
                            3 => 'bg-orange-100 text-orange-700 border-orange-200',
                            default => 'bg-slate-50 text-slate-400 border-slate-100',
                        };
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-xs font-bold {{ $rankStyle }}">
                            {{ $rank }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <p class="truncate text-sm font-medium text-slate-700" title="{{ $row['prodi'] }}">
                                    {{ $row['prodi'] }}
                                </p>
                                <span class="shrink-0 text-xs font-bold text-um-blue">{{ number_format($row['total']) }}</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $rank === 1 ? 'bg-gradient-to-r from-amber-400 to-amber-500' : ($rank === 2 ? 'bg-slate-400' : ($rank === 3 ? 'bg-orange-400' : 'bg-um-blue')) }}"
                                     style="width: {{ $barWidth }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mt-6 overflow-hidden">
        <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
            <h3 class="font-bold text-slate-800">Rincian per Prodi</h3>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500">{{ count($prodiRows) }} prodi</span>
                <span class="text-xs font-semibold text-um-blue bg-blue-50 px-2.5 py-1 rounded-full">
                    {{ \Carbon\Carbon::parse($from)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($to)->translatedFormat('d M Y') }}
                </span>
            </div>
        </div>

        @if(count($prodiRows) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm" wire:key="prodi-table">
                <thead>
                    <tr class="border-b border-slate-200 text-left">
                        <th class="py-2 pr-3 font-semibold text-slate-500">#</th>
                        <th class="py-2 pr-3 font-semibold text-slate-500">Program Studi</th>
                        <th class="py-2 pr-3 font-semibold text-slate-500 text-right">Jumlah</th>
                        <th class="py-2 font-semibold text-slate-500 text-right w-48">Persentase</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($prodiRows as $i => $row)
                    <tr class="hover:bg-slate-50">
                        <td class="py-2 pr-3 text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 pr-3 font-medium text-slate-800">{{ $row['prodi'] }}</td>
                        <td class="py-2 pr-3 text-right font-semibold text-slate-800">{{ number_format($row['total']) }}</td>
                        <td class="py-2">
                            <div class="flex items-center gap-2 justify-end">
                                <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-um-blue" style="width: {{ min(100, $row['persen']) }}%"></div>
                                </div>
                                <span class="text-xs text-slate-500 w-12 text-right">{{ $row['persen'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-sm text-slate-400 text-center py-8">Tidak ada data pada rentang ini.</p>
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

            // Data dibaca dari atribut canvas — selalu segar setelah Livewire re-render
            let monthLabels = [];
            let monthCounts = [];
            try {
                monthLabels = JSON.parse(monthlyEl.dataset.labels || '[]');
                monthCounts = JSON.parse(monthlyEl.dataset.counts || '[]');
            } catch (e) { /* abaikan */ }

            // Skip re-render jika data tidak berubah (hindari refresh terus-menerus)
            const key = JSON.stringify(monthLabels) + '|' + JSON.stringify(monthCounts);
            if (!force && key === statistikLastKey) return;
            statistikLastKey = key;

            if (statistikChart) { statistikChart.destroy(); statistikChart = null; }

            if (monthLabels.length > 0) {
                statistikChart = new Chart(monthlyEl, {
                    type: 'line',
                    data: {
                        labels: monthLabels,
                        datasets: [{
                            label: 'Jumlah',
                            data: monthCounts,
                            backgroundColor: 'rgba(30, 64, 175, 0.15)',
                            borderColor: 'rgba(30, 64, 175, 0.8)',
                            borderWidth: 2,
                            pointBackgroundColor: 'rgba(30, 64, 175, 1)',
                            fill: true,
                            tension: 0.3,
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => initStatistikCharts(true));
        document.addEventListener('livewire:init', () => {
            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => {
                    // Tunggu DOM baru terpasang sebelum membaca atribut canvas
                    setTimeout(() => initStatistikCharts(true), 80);
                });
            });
        });
        // Fallback: jika canvas diganti oleh Livewire, pastikan chart tetap dibuat
        const observer = new MutationObserver(() => {
            if (document.getElementById('statistik-monthly')) {
                initStatistikCharts(true);
            }
        });
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('[wire\\:key="monthly-chart"]') || document.body;
            observer.observe(container, { childList: true, subtree: true });
        });
    </script>
    @endpush
</x-filament-panels::page>
