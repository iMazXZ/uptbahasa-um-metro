@if ($reports === [])
    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
        Belum ada riwayat import yang tersimpan.
    </div>
@else
    <div class="space-y-4">
        @foreach ($reports as $report)
            @php
                $summary = is_array($report['report_summary'] ?? null) ? $report['report_summary'] : [];
                $reasonCounts = $summary['reason_counts'] ?? [];
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">{{ $report['file_name'] ?? '-' }}</div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $report['saved_at'] ?? ($report['finished_at'] ?? '-') }}
                            @if (filled($report['actor_name'] ?? null))
                                • {{ $report['actor_name'] }}
                            @endif
                            @if (filled($report['default_year'] ?? null))
                                • fallback tahun {{ $report['default_year'] }}
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs font-semibold md:grid-cols-4">
                        <span class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Total {{ $summary['total_rows'] ?? 0 }}</span>
                        <span class="rounded-lg bg-emerald-100 px-3 py-2 text-emerald-700">Valid {{ $summary['valid_rows'] ?? 0 }}</span>
                        <span class="rounded-lg bg-amber-100 px-3 py-2 text-amber-700">Skip {{ $summary['skipped_rows'] ?? 0 }}</span>
                        <span class="rounded-lg bg-blue-100 px-3 py-2 text-blue-700">Sync {{ $summary['synced_users'] ?? 0 }}</span>
                    </div>
                </div>

                @if ($reasonCounts !== [])
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach (array_slice($reasonCounts, 0, 6, true) as $reason => $count)
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">
                                {{ $reason }}: {{ $count }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
