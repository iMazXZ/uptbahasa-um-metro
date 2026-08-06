@props(['post'])

@php
    use Carbon\Carbon;

    $postUrl = route('front.post.show', $post->slug);
    $scorePost = $post->relationLoaded('relatedScores') ? $post->relatedScores->first() : null;
    $primaryUrl = $scorePost ? route('front.post.show', $scorePost->slug) : $postUrl;
    $primaryLabel = $scorePost ? 'Lihat Nilai' : 'Detail Jadwal';
    $primaryButtonClasses = $scorePost
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300'
        : 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:border-blue-300';
    $eventDate = filled($post->event_date) ? Carbon::parse($post->event_date) : null;
    $eventDateTime = $eventDate ? $eventDate->copy() : null;

    if ($eventDateTime && filled($post->event_time)) {
        $parsedTime = Carbon::parse($post->event_time);
        $eventDateTime->setTime($parsedTime->hour, $parsedTime->minute);
    } elseif ($eventDateTime) {
        $eventDateTime->startOfDay();
    }

    $eventEndTime = $eventDateTime ? $eventDateTime->copy()->addHour() : null;
    $daysLeft = $eventDate ? now()->startOfDay()->diffInDays($eventDate->copy()->startOfDay(), false) : null;
    $isPast = $eventEndTime && $eventEndTime->isPast();
    $isToday = $eventDate?->isToday() ?? false;
    $isTomorrow = $eventDate?->isTomorrow() ?? false;

    $scheduleTitle = preg_replace('/\s*\([^)]+\)\s*$/', '', $post->title);
    $scheduleTitle = preg_replace('/^Jadwal Tes EPT\s*/i', '', $scheduleTitle);

    if ($isPast) {
        $statusLabel = 'Selesai';
        $statusClass = 'bg-slate-100 text-slate-500 ring-slate-200';
        $decorPrimary = 'bg-slate-200/60';
        $decorSecondary = 'bg-slate-100/90';
        $decorAccent = 'bg-slate-200/70';
    } elseif ($isToday) {
        $statusLabel = 'Hari Ini';
        $statusClass = 'bg-emerald-100 text-emerald-700 ring-emerald-200';
        $decorPrimary = 'bg-emerald-200/55';
        $decorSecondary = 'bg-emerald-100/90';
        $decorAccent = 'bg-emerald-200/70';
    } elseif ($isTomorrow) {
        $statusLabel = 'Besok';
        $statusClass = 'bg-amber-100 text-amber-700 ring-amber-200';
        $decorPrimary = 'bg-amber-200/55';
        $decorSecondary = 'bg-amber-100/90';
        $decorAccent = 'bg-amber-200/70';
    } elseif ($daysLeft !== null && $daysLeft > 1) {
        $statusLabel = $daysLeft . ' Hari Lagi';
        $statusClass = 'bg-blue-100 text-blue-700 ring-blue-200';
        $decorPrimary = 'bg-blue-200/55';
        $decorSecondary = 'bg-blue-100/90';
        $decorAccent = 'bg-blue-200/70';
    } else {
        $statusLabel = 'Jadwal';
        $statusClass = 'bg-slate-100 text-slate-600 ring-slate-200';
        $decorPrimary = 'bg-slate-200/60';
        $decorSecondary = 'bg-slate-100/90';
        $decorAccent = 'bg-slate-200/70';
    }

    $cardClass = $isPast
        ? 'bg-slate-50 text-slate-900 border-slate-200 opacity-80'
        : 'bg-white text-slate-900 border-slate-200 shadow-sm hover:shadow-md hover:border-blue-200';
@endphp

<article class="group relative overflow-hidden rounded-2xl border transition-all duration-300 {{ $cardClass }}">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -top-8 -right-6 h-24 w-24 rounded-full blur-2xl {{ $decorPrimary }}"></div>
        <div class="absolute top-4 right-4 h-10 w-10 rounded-full ring-1 ring-white/70 {{ $decorSecondary }}"></div>
        <div class="absolute -bottom-6 -left-6 h-16 w-16 rounded-full blur-xl {{ $decorAccent }}"></div>
    </div>

    <div class="p-3.5 md:p-4 lg:p-5 h-full flex flex-col">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ $postUrl }}" class="block">
                    <h3 class="relative z-10 text-xl md:text-2xl font-black tracking-tight leading-none text-slate-900 group-hover:text-blue-700">
                        {{ $scheduleTitle }}
                    </h3>
                </a>
            </div>
            <span class="relative z-10 inline-flex shrink-0 items-center rounded-full px-2 py-0.5 md:px-2.5 md:py-1 text-[10px] md:text-[11px] font-bold ring-1 shadow-sm {{ $statusClass }}">
                {{ $statusLabel }}
            </span>
        </div>

        <div class="relative z-10 mt-3 md:mt-4 flex items-end justify-between gap-3">
            <div class="min-w-0 space-y-2 text-[13px] md:text-sm text-slate-600">
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar-day w-4 text-center text-blue-600"></i>
                    <span>{{ $eventDate ? $eventDate->translatedFormat('l, d F Y') : 'Tanggal belum ditetapkan' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-clock w-4 text-center text-blue-600"></i>
                    <span>{{ filled($post->event_time) ? Carbon::parse($post->event_time)->format('H:i') . ' WIB' : 'Waktu belum ditetapkan' }}</span>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1.5 md:gap-2 justify-end">
                <a href="{{ $primaryUrl }}"
                    class="inline-flex items-center justify-center rounded-full border px-3 py-1.5 md:px-4 md:py-2 text-xs md:text-sm font-bold transition-colors {{ $primaryButtonClasses }}">
                    <span>{{ $primaryLabel }}</span>
                </a>
            </div>
        </div>
    </div>
</article>
