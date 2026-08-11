<x-filament-panels::page>
    {{-- FILTER --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="flex flex-wrap items-end gap-4">
            <div class="min-w-[200px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Jenis Data</label>
                <select wire:model.live="dataset"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none">
                    @foreach($this->datasetOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[160px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Mode (EPT)</label>
                <select wire:model.live="mode"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none">
                    @foreach($this->modeOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Dari Tanggal</label>
                <input type="date" wire:model.live="from"
                       class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Sampai Tanggal</label>
                <input type="date" wire:model.live="to"
                       class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none">
            </div>

            <div class="ml-auto flex items-center gap-3">
                <span wire:loading wire:target="applyFilters,updated"
                      class="text-xs text-slate-400 flex items-center gap-1.5">
                    <x-filament::loading-indicator class="h-4 w-4" />
                    Memuat...
                </span>
                <x-filament::button wire:click="export" color="success" icon="heroicon-o-arrow-down-tray">
                    Export Excel
                </x-filament::button>
            </div>
        </div>
    </div>

    {{-- RINGKASAN --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total</p>
            <p class="text-3xl font-black text-um-blue mt-1">{{ number_format($grandTotal) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Rata-rata / Bulan</p>
            <p class="text-3xl font-black text-emerald-700 mt-1">
                {{ count($monthCounts) > 0 ? number_format(array_sum($monthCounts) / count($monthCounts), 1) : 0 }}
            </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Bulan Tertinggi</p>
            @php
                $maxIdx = $monthCounts ? array_search(max($monthCounts), $monthCounts) : null;
            @endphp
            <p class="text-lg font-bold text-amber-700 mt-1 truncate">
                {{ $maxIdx !== null ? ($monthLabels[$maxIdx] ?? '-') : '-' }}
                <span class="text-sm font-semibold">({{ $maxIdx !== null ? number_format($monthCounts[$maxIdx]) : 0 }})</span>
            </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Jumlah Prodi</p>
            <p class="text-3xl font-black text-rose-600 mt-1">{{ count($prodiRows) }}</p>
        </div>
    </div>

    {{-- GRAFIK --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-800">Perkembangan per Bulan</h3>
                <span class="text-xs text-slate-500">{{ count($monthLabels) }} bulan</span>
            </div>
            <div wire:key="monthly-chart">
                <canvas id="statistik-monthly" height="120"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h3 class="font-bold text-slate-800 mb-4">Top 10 Prodi</h3>
            <div wire:key="prodi-chart">
                <canvas id="statistik-prodi" height="300"></canvas>
            </div>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mt-6 overflow-hidden">
        <div class="flex items-center justify-between mb-4">
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
        function initStatistikCharts() {
            const monthlyEl = document.getElementById('statistik-monthly');
            const prodiEl = document.getElementById('statistik-prodi');
            if (!window.Chart) return;

            window.statistikCharts = window.statistikCharts || {};

            const monthLabels = @json($monthLabels);
            const monthCounts = @json($monthCounts);
            const prodiRows = @json(array_slice($prodiRows, 0, 10));

            if (window.statistikCharts.monthly) { window.statistikCharts.monthly.destroy(); window.statistikCharts.monthly = null; }
            if (window.statistikCharts.prodi) { window.statistikCharts.prodi.destroy(); window.statistikCharts.prodi = null; }

            if (monthlyEl && monthLabels.length > 0) {
                window.statistikCharts.monthly = new Chart(monthlyEl, {
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

            if (prodiEl && prodiRows.length > 0) {
                window.statistikCharts.prodi = new Chart(prodiEl, {
                    type: 'bar',
                    data: {
                        labels: prodiRows.map(r => r.prodi.length > 18 ? r.prodi.slice(0, 17) + '…' : r.prodi),
                        datasets: [{
                            label: 'Jumlah',
                            data: prodiRows.map(r => r.total),
                            backgroundColor: 'rgba(30, 64, 175, 0.7)',
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', initStatistikCharts);
        document.addEventListener('livewire:init', () => {
            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => initStatistikCharts());
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
