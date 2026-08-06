@php
    $history = $getState();
@endphp

@if($history && count($history) > 0)
    <div class="space-y-3">
        @foreach(array_reverse($history) as $entry)
            <div class="rounded-lg border-l-4 border-blue-400 bg-blue-50 p-3">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="font-semibold text-slate-900">
                            {{ $entry['old_number'] }} 
                            <span class="text-sm text-slate-500">→</span> 
                            {{ $entry['new_number'] }}
                        </p>
                        <p class="mt-1 text-xs text-slate-600">
                            <strong>Oleh:</strong> {{ $entry['changed_by_name'] ?? 'Unknown' }}
                        </p>
                        @if(!empty($entry['reason']))
                            <p class="mt-1 text-xs text-slate-600">
                                <strong>Alasan:</strong> {{ $entry['reason'] }}
                            </p>
                        @endif
                    </div>
                    <div class="ml-3 flex-shrink-0">
                        <span class="rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700">
                            {{ \Carbon\Carbon::parse($entry['changed_at'] ?? now())->diffForHumans() }}
                        </span>
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    {{ \Carbon\Carbon::parse($entry['changed_at'] ?? now())->format('d M Y - H:i') }}
                </p>
            </div>
        @endforeach
    </div>
@else
    <div class="rounded-lg bg-slate-50 p-3 text-center">
        <p class="text-sm text-slate-500">Belum ada riwayat perubahan nomor surat</p>
    </div>
@endif
