@props(['post'])

@php
    use Carbon\Carbon;

    $scoreUrl = route('front.post.show', $post->slug);
    $relatedSchedule = $post->relationLoaded('relatedPost') ? $post->relatedPost : null;

    $scoreTitle = preg_replace('/\s*\([^)]+\)\s*$/', '', $post->title);
    $scoreTitle = preg_replace('/^Nilai EPT\s*/i', '', $scoreTitle);

    $scoreDateLabel = null;

    if ($relatedSchedule && filled($relatedSchedule->event_date)) {
        $scoreDateLabel = Carbon::parse($relatedSchedule->event_date)->translatedFormat('l, d F Y');
    } elseif (preg_match('/\(([^)]+)\)\s*$/', $post->title, $matches) === 1) {
        $scoreDateLabel = trim($matches[1]);
    } elseif ($post->published_at) {
        $scoreDateLabel = $post->published_at->translatedFormat('d M Y');
    }

    $publishedLabel = $post->published_at
        ? $post->published_at->translatedFormat('d M Y')
        : null;

    $isFresh = $post->published_at && $post->published_at->greaterThanOrEqualTo(now()->subDays(2));
    $statusLabel = $isFresh ? 'Baru' : 'Tersedia';
    $statusClass = $isFresh
        ? 'bg-emerald-100 text-emerald-700 ring-emerald-200'
        : 'bg-slate-100 text-slate-600 ring-slate-200';
@endphp

<article class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:border-emerald-200 hover:shadow-md">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -top-8 -right-6 h-24 w-24 rounded-full bg-emerald-200/40 blur-2xl"></div>
        <div class="absolute top-4 right-4 h-10 w-10 rounded-full bg-white/70 ring-1 ring-emerald-100"></div>
        <div class="absolute -bottom-6 -left-6 h-16 w-16 rounded-full bg-emerald-100/60 blur-xl"></div>
    </div>

    <div class="relative z-10 flex h-full flex-col p-3.5 md:p-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ $scoreUrl }}" class="block">
                    <h3 class="text-xl md:text-2xl font-black tracking-tight leading-none text-slate-900 transition-colors group-hover:text-emerald-700">
                        {{ $scoreTitle }}
                    </h3>
                </a>
            </div>

            <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 md:px-2.5 md:py-1 text-[10px] md:text-[11px] font-bold ring-1 shadow-sm {{ $statusClass }}">
                {{ $statusLabel }}
            </span>
        </div>

        <div class="mt-3 md:mt-4 flex items-end justify-between gap-3">
            <div class="min-w-0 space-y-2 text-[13px] md:text-sm text-slate-600">
                @if($scoreDateLabel)
                    <div class="flex items-center gap-2">
                        <i class="fas fa-calendar-day w-4 text-center text-emerald-600"></i>
                        <span>{{ $scoreDateLabel }}</span>
                    </div>
                @endif

                @if($publishedLabel)
                    <div class="flex items-center gap-2 text-[12px] md:text-xs text-slate-400">
                        <i class="fas fa-calendar-check w-4 text-center text-emerald-500"></i>
                        <span>Dipublikasikan {{ $publishedLabel }}</span>
                    </div>
                @endif
            </div>

            <a href="{{ $scoreUrl }}"
                class="inline-flex shrink-0 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 md:px-4 md:py-2 text-xs md:text-sm font-bold text-emerald-700 transition-colors hover:bg-emerald-100 hover:border-emerald-300">
                <span>Lihat Nilai</span>
            </a>
        </div>
    </div>
</article>
