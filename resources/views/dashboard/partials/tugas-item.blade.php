{{-- resources/views/dashboard/partials/tugas-item.blade.php --}}
@php
    $statusColor = match($item->status) {
        'Disetujui' => 'bg-amber-100 text-amber-700 border-amber-200',
        'Diproses' => 'bg-blue-100 text-blue-700 border-blue-200',
        'Selesai' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200'
    };
    $isUrgent = in_array($item->status, ['Disetujui', 'Diproses']);
    $hasDraft = !empty($item->translated_text);

    $deadlineText = null;
    $deadlineClass = null;
    if ($isUrgent && !$hasDraft && $item->updated_at) {
        $deadline = $item->updated_at->addDays(3);
        $now = now();
        $diffHours = (int) $now->diffInHours($deadline, false);
        $diffDays = (int) floor($diffHours / 24);

        if ($diffHours < 0) {
            $telatHours = abs($diffHours);
            $telatDays = (int) ceil($telatHours / 24);
            if ($telatDays == 1) {
                $deadlineText = 'Terlambat 1 hari, mohon diselesaikan';
            } else {
                $deadlineText = 'Terlambat ' . $telatDays . ' hari, mohon diselesaikan';
            }
            $deadlineClass = 'bg-rose-100 text-rose-700 border-rose-200';
        } elseif ($diffHours <= 24) {
            $deadlineText = 'Sisa waktu ' . $diffHours . ' jam';
            $deadlineClass = 'bg-orange-100 text-orange-700 border-orange-200';
        } elseif ($diffDays == 1) {
            $deadlineText = 'Sisa waktu 1 hari';
            $deadlineClass = 'bg-amber-100 text-amber-700 border-amber-200';
        } else {
            $deadlineText = 'Sisa waktu ' . $diffDays . ' hari';
            $deadlineClass = 'bg-sky-100 text-sky-700 border-sky-200';
        }
    }
@endphp

<div class="p-6 hover:bg-slate-50 transition-colors {{ $isUrgent && !$hasDraft ? 'bg-amber-50/30' : '' }}">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                @if($hasDraft && $item->status !== 'Selesai')
                    @if(filled($item->submitted_for_review_at))
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-sm font-bold border bg-purple-100 text-purple-700 border-purple-200">
                            <i class="fa-solid fa-paper-plane"></i>
                            Menunggu Verifikasi
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-sm font-bold border bg-amber-100 text-amber-700 border-amber-200">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Draft (Belum Dikirim)
                        </span>
                    @endif
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold border {{ $statusColor }}">
                        {{ $item->status }}
                    </span>
                    @if($deadlineText)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-sm font-medium border {{ $deadlineClass }}">
                            <i class="fa-solid fa-clock"></i>
                            {{ $deadlineText }}
                        </span>
                    @endif
                @endif
            </div>
            <p class="text-lg font-semibold text-slate-800 truncate">
                {{ $item->users->name ?? 'Pemohon' }}
            </p>
            <p class="text-base text-slate-500 mt-1">
                <i class="fa-solid fa-file-alt text-slate-400 mr-2"></i>
                {{ $item->source_word_count ?? 0 }} kata
            </p>
        </div>

        @if($isUrgent && !$hasDraft)
            <a href="{{ route('dashboard.penerjemah.edit', $item) }}"
               class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 text-white font-bold text-base hover:bg-indigo-700 transition-colors shadow-lg shrink-0">
                <i class="fa-solid fa-pen"></i>
                Kerjakan
            </a>
        @else
            <a href="{{ route('dashboard.penerjemah.edit', $item) }}"
               class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-slate-200 text-slate-700 font-medium text-base hover:bg-slate-300 transition-colors shrink-0">
                <i class="fa-solid fa-eye"></i>
                Lihat
            </a>
        @endif
    </div>
</div>