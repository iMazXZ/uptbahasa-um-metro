<x-filament-panels::page>
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <form wire:submit="refreshData">
            {{ $this->form }}
        </form>
        <div class="shrink-0">
            <x-filament::button wire:click="export" color="success" icon="heroicon-o-arrow-down-tray">
                Export Excel
            </x-filament::button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Grafik per Bulan --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-800">Perkembangan per Bulan</h3>
                <span class="text-xs text-slate-500">{{ count($monthLabels) }} bulan</span>
            </div>
            <canvas id="statistik-monthly" height="120"></canvas>
        </div>

        {{-- Ringkasan --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h3 class="font-bold text-slate-800 mb-4">Ringkasan</h3>
            <div class="space-y-3">
                <div class="rounded-xl bg-blue-50 border border-blue-100 p-4">
                    <p class="text-xs font-medium text-blue-600 uppercase tracking-wide">Total</p>
                    <p class="text-3xl font-black text-blue-800 mt-1">{{ number_format($grandTotal ?? 0) }}</p>
                </div>
                <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4">
                    <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Rata-rata / Bulan</p>
                    <p class="text-3xl font-black text-emerald-800 mt-1">
                        {{ count($monthCounts) > 0 ? number_format(array_sum($monthCounts) / count($monthCounts), 1) : 0 }}
                    </p>
                </div>
                <div class="rounded-xl bg-amber-50 border border-amber-100 p-4">
                    <p class="text-xs font-medium text-amber-600 uppercase tracking-wide">Bulan Tertinggi</p>
                    @php($maxIdx = array_search(max($monthCounts ?: [0]), $monthCounts ?: [0]))
                    <p class="text-lg font-bold text-amber-800 mt-1">
                        {{ ($monthLabels[$maxIdx] ?? '-') }}
                        <span class="text-sm font-semibold">({{ number_format(max($monthCounts ?: [0])) }})</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        {{-- Grafik Top 10 Prodi --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h3 class="font-bold text-slate-800 mb-4">Top 10 Prodi</h3>
            <canvas id="statistik-prodi" height="300"></canvas>
        </div>

        {{-- Tabel per Prodi --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-800">Rincian per Prodi</h3>
                <span class="text-xs text-slate-500">{{ count($prodiRows) }} prodi</span>
            </div>

            @if(count($prodiRows) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left">
                            <th class="py-2 pr-3 font-semibold text-slate-500">#</th>
                            <th class="py-2 pr-3 font-semibold text-slate-500">Program Studi</th>
                            <th class="py-2 pr-3 font-semibold text-slate-500 text-right">Jumlah</th>
                            <th class="py-2 font-semibold text-slate-500 text-right w-40">Persentase</th>
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
    </div>

    @push('scripts')
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const monthlyEl = document.getElementById('statistik-monthly');
            const prodiEl = document.getElementById('statistik-prodi');
            if (!monthlyEl || !window.Chart) return;

            window.statistikCharts = window.statistikCharts || {};

            const monthLabels = @json($monthLabels);
            const monthCounts = @json($monthCounts);
            const prodiRows = @json(array_slice($prodiRows, 0, 10));

            if (window.statistikCharts.monthly) window.statistikCharts.monthly.destroy();
            if (window.statistikCharts.prodi) window.statistikCharts.prodi.destroy();

            if (monthLabels.length > 0) {
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
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
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
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });
            }
        });
    </script>
    @endpush

    <style>
        .filament-page-statistik { /* noop */ }
    </style>
</x-filament-panels::page>
