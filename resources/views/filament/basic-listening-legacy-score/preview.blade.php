@php
    $summary = is_array($preview['summary'] ?? null) ? $preview['summary'] : [];
    $readyRows = $summary['sample_ready_rows'] ?? [];
    $issueRows = $summary['sample_issue_rows'] ?? [];
    $conflictRows = $summary['conflict_details'] ?? [];
    $updateRows = $summary['update_details'] ?? [];
    $reasonCounts = $summary['reason_counts'] ?? [];
    $skipSampleRows = array_values(array_filter($issueRows, fn (array $row): bool => ($row['type'] ?? '') !== 'conflict'));
@endphp

@if (! is_array($preview ?? null))
    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">
        Upload file CSV lalu lanjut ke langkah preview.
    </div>
@else
    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-sm font-semibold text-slate-900">{{ $preview['file_name'] ?? '-' }}</div>
            <div class="mt-1 text-xs text-slate-500">
                Fallback tahun: {{ $preview['default_year'] ?? 'mengikuti kolom CSV / nama file' }}
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-6">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Baris</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ $summary['total_rows'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Siap Diproses</div>
                <div class="mt-2 text-2xl font-bold text-emerald-700">{{ $summary['valid_rows'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Dilewati</div>
                <div class="mt-2 text-2xl font-bold text-amber-700">{{ $summary['skipped_rows'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Baru</div>
                <div class="mt-2 text-2xl font-bold text-cyan-700">{{ $summary['ready_insert_rows'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">Sudah Ada di Web</div>
                <div class="mt-2 text-2xl font-bold text-blue-700">{{ $summary['ready_update_rows'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-rose-700">Konflik</div>
                <div class="mt-2 text-2xl font-bold text-rose-700">{{ $summary['conflict_rows'] ?? 0 }}</div>
            </div>
        </div>

        @if ($updateRows !== [])
            <div class="rounded-xl border border-blue-200 bg-white p-4">
                <div class="mb-3 text-sm font-semibold text-slate-900">Daftar Data Sudah Ada di Web</div>
                <div class="mb-4 text-xs text-slate-500">
                    Baris-baris ini bukan konflik. Saat import, data existing akan diupdate.
                </div>

                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <div class="max-h-96 overflow-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="sticky top-0 bg-blue-50 text-left text-slate-600">
                                <tr>
                                    <th class="px-3 py-2">Baris</th>
                                    <th class="px-3 py-2">Identitas</th>
                                    <th class="px-3 py-2">Data Existing</th>
                                    <th class="px-3 py-2">Data CSV</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white text-slate-700">
                                @foreach ($updateRows as $row)
                                    <tr>
                                        <td class="px-3 py-2 font-medium">{{ $row['row'] ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            <div class="font-medium text-slate-900">{{ $row['name'] ?? '-' }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ $row['srn'] ?? 'Tanpa NPM' }}
                                                @if (filled($row['study_program'] ?? null))
                                                    • {{ $row['study_program'] }}
                                                @endif
                                                @if (filled($row['source_year'] ?? null))
                                                    • {{ $row['source_year'] }}
                                                @endif
                                                @if (filled($row['semester'] ?? null))
                                                    • Semester {{ $row['semester'] }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="font-medium text-slate-900">#{{ $row['existing_id'] ?? '-' }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ $row['existing_score'] ?? '-' }}
                                                @if (filled($row['existing_grade'] ?? null))
                                                    ({{ $row['existing_grade'] }})
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="font-medium text-slate-900">
                                                {{ $row['incoming_score'] ?? '-' }}
                                                @if (filled($row['incoming_grade'] ?? null))
                                                    ({{ $row['incoming_grade'] }})
                                                @endif
                                            </div>
                                            <div class="text-xs text-blue-700">Akan diupdate</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if ($reasonCounts !== [])
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="mb-3 text-sm font-semibold text-slate-900">Alasan Baris Dilewati / Konflik</div>
                <div class="space-y-2 text-sm text-slate-600">
                    @foreach ($reasonCounts as $reason => $count)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <span>{{ $reason }}</span>
                            <span class="font-semibold text-slate-900">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($conflictRows !== [])
            <div class="rounded-xl border border-rose-200 bg-white p-4">
                <div class="mb-3 text-sm font-semibold text-slate-900">Daftar Konflik Lengkap</div>
                <div class="mb-4 text-xs text-slate-500">
                    Semua baris di bawah ini tidak akan ikut import sampai CSV dibersihkan.
                </div>

                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <div class="max-h-96 overflow-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="sticky top-0 bg-rose-50 text-left text-slate-600">
                                <tr>
                                    <th class="px-3 py-2">Baris</th>
                                    <th class="px-3 py-2">Identitas</th>
                                    <th class="px-3 py-2">Tahun</th>
                                    <th class="px-3 py-2">Nilai</th>
                                    <th class="px-3 py-2">Alasan</th>
                                    <th class="px-3 py-2">Bentrok Dengan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white text-slate-700">
                                @foreach ($conflictRows as $row)
                                    @php
                                        $context = is_array($row['context'] ?? null) ? $row['context'] : [];
                                        $conflictingWith = is_array($context['conflicting_with'] ?? null) ? $context['conflicting_with'] : null;
                                        $existingMatches = is_array($context['existing_matches'] ?? null) ? $context['existing_matches'] : [];
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 font-medium">{{ $row['row'] ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            <div class="font-medium text-slate-900">{{ $row['name'] ?? '-' }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ $row['srn'] ?? 'Tanpa NPM' }}
                                                @if (filled($row['study_program'] ?? null))
                                                    • {{ $row['study_program'] }}
                                                @endif
                                                @if (filled($row['semester'] ?? null))
                                                    • Semester {{ $row['semester'] }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">{{ $row['source_year'] ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $row['score'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-rose-700">{{ $row['reason'] ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            @if (($context['type'] ?? null) === 'csv_duplicate' && $conflictingWith)
                                                <div class="text-sm font-medium text-slate-900">
                                                    Baris {{ $conflictingWith['row'] ?? '-' }}
                                                </div>
                                                <div class="text-xs text-slate-500">
                                                    {{ $conflictingWith['name'] ?? '-' }}
                                                    @if (filled($conflictingWith['srn'] ?? null))
                                                        • {{ $conflictingWith['srn'] }}
                                                    @endif
                                                    @if (filled($conflictingWith['study_program'] ?? null))
                                                        • {{ $conflictingWith['study_program'] }}
                                                    @endif
                                                </div>
                                            @elseif (($context['type'] ?? null) === 'existing_ambiguous')
                                                <div class="text-sm font-medium text-slate-900">
                                                    Database ({{ $context['existing_match_count'] ?? count($existingMatches) }} data)
                                                </div>
                                                <div class="mt-1 space-y-1 text-xs text-slate-500">
                                                    @foreach ($existingMatches as $match)
                                                        <div>
                                                            #{{ $match['id'] ?? '-' }} {{ $match['name'] ?? '-' }}
                                                            @if (filled($match['srn'] ?? null))
                                                                • {{ $match['srn'] }}
                                                            @endif
                                                            @if (filled($match['score'] ?? null))
                                                                • {{ $match['score'] }}
                                                            @endif
                                                            @if (filled($match['semester'] ?? null))
                                                                • Semester {{ $match['semester'] }}
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="mb-3 text-sm font-semibold text-slate-900">Contoh Baris Siap Import</div>
                @if ($readyRows === [])
                    <div class="rounded-lg bg-slate-50 px-3 py-4 text-sm text-slate-500">Belum ada baris yang valid.</div>
                @else
                    <div class="overflow-hidden rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="px-3 py-2">Baris</th>
                                    <th class="px-3 py-2">Identitas</th>
                                    <th class="px-3 py-2">Nilai</th>
                                    <th class="px-3 py-2">Mode</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white text-slate-700">
                                @foreach ($readyRows as $row)
                                    <tr>
                                        <td class="px-3 py-2">{{ $row['row'] ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            <div class="font-medium text-slate-900">{{ $row['name'] ?? '-' }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ $row['srn'] ?? 'Tanpa NPM' }} • {{ $row['source_year'] ?? '-' }}
                                                @if (filled($row['semester'] ?? null))
                                                    • Semester {{ $row['semester'] }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">{{ $row['score'] ?? '-' }}{{ filled($row['grade'] ?? null) ? ' (' . $row['grade'] . ')' : '' }}</td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ ($row['mode'] ?? '') === 'update' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                {{ ($row['mode'] ?? '') === 'update' ? 'Update' : 'Baru' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="mb-3 text-sm font-semibold text-slate-900">Contoh Baris Dilewati</div>
                @if ($skipSampleRows === [])
                    <div class="rounded-lg bg-slate-50 px-3 py-4 text-sm text-slate-500">Tidak ada sample baris skip di luar konflik.</div>
                @else
                    <div class="space-y-3">
                        @foreach ($skipSampleRows as $row)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-slate-900">Baris {{ $row['row'] ?? '-' }}</div>
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold bg-amber-100 text-amber-700">
                                        Skip
                                    </span>
                                </div>
                                <div class="mt-2 text-sm text-slate-700">{{ $row['reason'] ?? '-' }}</div>
                                <div class="mt-2 text-xs text-slate-500">
                                    {{ $row['name'] ?? '-' }}
                                    @if (filled($row['srn'] ?? null))
                                        • {{ $row['srn'] }}
                                    @endif
                                    @if (filled($row['study_program'] ?? null))
                                        • {{ $row['study_program'] }}
                                    @endif
                                    @if (filled($row['source_year'] ?? null))
                                        • {{ $row['source_year'] }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
