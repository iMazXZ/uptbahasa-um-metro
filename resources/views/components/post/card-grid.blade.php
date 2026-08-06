@props(['title' => 'Judul', 'items' => collect(), 'moreRoute' => '#', 'emptyText' => 'Belum ada data.', 'type' => 'default', 'maxItems' => 6])

@php
  use Illuminate\Support\Str;
  use Carbon\Carbon;

  $sectionId = 'sec-' . Str::slug($title ?: 'section');
  $hasItems  = $items && $items->count() > 0;
  $allItems  = $hasItems ? $items->take($maxItems) : collect();
  $isScheduleSection = $type === 'schedule';
  $isScoresSection = $type === 'scores';
  
  $typeColors = [
    'schedule' => ['badge' => 'bg-blue-600', 'accent' => 'text-blue-600', 'hover' => 'group-hover:border-blue-300'],
    'scores'   => ['badge' => 'bg-emerald-600', 'accent' => 'text-emerald-600', 'hover' => 'group-hover:border-emerald-300'],
    'default'  => ['badge' => 'bg-slate-600', 'accent' => 'text-slate-600', 'hover' => 'group-hover:border-slate-300'],
  ];
  $colors = $typeColors[$type] ?? $typeColors['default'];

@endphp

<section class="py-12 lg:py-16 bg-white" aria-labelledby="{{ $sectionId }}-title">
  <div class="max-w-7xl mx-auto px-4 lg:px-8">

    {{-- Section Header --}}
    <div class="text-center mb-10">
      <h2 id="{{ $sectionId }}-title" class="text-3xl lg:text-4xl font-extrabold text-slate-900 tracking-tight">
        {{ $title }}
      </h2>
      <div class="mt-4 mx-auto w-20 h-1.5 bg-blue-600 rounded-full"></div>
    </div>

    @if($hasItems)
      @if($isScheduleSection)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-5">
          @foreach($allItems as $item)
            <x-post.schedule-card :post="$item" />
          @endforeach
        </div>
      @elseif($isScoresSection)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-5">
          @foreach($allItems as $item)
            <x-post.score-card :post="$item" />
          @endforeach
        </div>
      @else
        {{-- Card Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

          @foreach($allItems as $item)
            @php
              $isSchedule = ($item->type ?? null) === 'schedule';
              $itemUrl = ($item->type ?? null) === 'career'
                ? route('front.career.show', $item->slug)
                : route('front.post.show', $item->slug);
              $eventDate = $item->event_date ?? null;
              $eventTime = $item->event_time ?? null;

              // Build full datetime for accurate comparison
              $eventDateTime = null;
              $eventEndTime = null; // +2 hours buffer
              if ($eventDate) {
                $eventDateTime = Carbon::parse($eventDate);
                if ($eventTime) {
                  $parsedTime = Carbon::parse($eventTime);
                  $eventDateTime->setTime($parsedTime->hour, $parsedTime->minute);
                } else {
                  // If no time, assume start of day
                  $eventDateTime->startOfDay();
                }
                // Event dianggap selesai 2 jam setelah waktu mulai
                $eventEndTime = $eventDateTime->copy()->addHours(1);
              }

              $isUpcoming = $eventDateTime && $eventDateTime->isFuture();
              $isToday = $eventDate && Carbon::parse($eventDate)->isToday();
              $daysLeft = $eventDate ? Carbon::now()->startOfDay()->diffInDays(Carbon::parse($eventDate)->startOfDay(), false) : null;
              // Baru "Selesai" setelah +2 jam dari waktu jadwal
              $isPast = $eventEndTime && $eventEndTime->isPast();

              $typeLabel = match($item->type ?? 'news') {
                'schedule' => 'JADWAL',
                'scores' => 'NILAI',
                default => 'BERITA'
              };

              // Card background based on status
              $cardBg = 'bg-slate-50 border-slate-200'; // default
              if ($isSchedule) {
                if ($isUpcoming) {
                  if ($isToday) {
                    // Hari Ini - paling mencolok
                    $cardBg = 'bg-gradient-to-br from-emerald-50 to-green-100 border-emerald-300';
                  } elseif ($daysLeft !== null && $daysLeft <= 3) {
                    // 1-3 hari lagi - cukup mencolok
                    $cardBg = 'bg-gradient-to-br from-blue-50 to-indigo-100 border-blue-300';
                  } else {
                    // Lebih dari 3 hari - sedikit warna
                    $cardBg = 'bg-gradient-to-br from-slate-50 to-blue-50 border-blue-200';
                  }
                } else {
                  // Selesai
                  $cardBg = 'bg-slate-100 border-slate-200 opacity-75';
                }
              }
            @endphp

            <article class="group flex flex-col {{ $cardBg }} rounded-2xl shadow-md hover:shadow-xl {{ $colors['hover'] }} transition-all duration-300">
              <div class="p-5 flex flex-col flex-1">

                {{-- Header: Badge + Date + Countdown --}}
                <div class="flex items-center gap-2 mb-4">
                  <span class="{{ $colors['badge'] }} text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-md">
                    {{ $typeLabel }}
                  </span>
                  <span class="text-slate-400 text-xs">•</span>
                  <span class="text-slate-500 text-xs font-medium">
                    @if($isSchedule && $eventDate)
                      {{ Carbon::parse($eventDate)->translatedFormat('d M Y') }}
                    @elseif($item->published_at)
                      {{ $item->published_at->translatedFormat('d M Y') }}
                    @endif
                  </span>
                  @if($isSchedule && $isUpcoming && $daysLeft !== null && $daysLeft >= 0)
                    <span class="ml-auto text-emerald-600 text-xs font-bold whitespace-nowrap">
                      <i class="fas fa-{{ $daysLeft == 0 ? 'fire' : ($daysLeft == 1 ? 'clock' : 'calendar-alt') }}"></i>
                      @if($daysLeft == 0) Hari Ini @elseif($daysLeft == 1) Besok @else {{ $daysLeft }} Hari @endif
                    </span>
                  @elseif($isSchedule && $isPast)
                    <span class="ml-auto text-slate-400 text-xs font-medium">
                      <i class="fas fa-check-circle"></i> Selesai
                    </span>
                  @endif
                </div>

                {{-- Title (strip date in parentheses for schedule) --}}
                <a href="{{ $itemUrl }}" class="block mb-3">
                  <h3 class="font-bold text-slate-900 text-lg leading-snug tracking-tight group-hover:{{ $colors['accent'] }} transition-colors line-clamp-2">
                    @if($isSchedule)
                      {{ preg_replace('/\s*\([^)]+\)\s*$/', '', $item->title) }}
                    @else
                      {{ $item->title }}
                    @endif
                  </h3>
                </a>

                {{-- Event Info (Schedule only - compact format) --}}
                @if($isSchedule && $eventDate)
                  <div class="text-sm text-slate-600 space-y-1 mb-3">
                    <div class="flex items-center gap-2">
                      <i class="fas fa-calendar-day text-blue-600 w-4 text-center"></i>
                      <span>{{ Carbon::parse($eventDate)->translatedFormat('l, d F Y') }}</span>
                      @if($item->event_time)
                        <span class="text-slate-300">|</span>
                        <i class="fas fa-clock text-blue-600"></i>
                        <span>{{ \Carbon\Carbon::parse($item->event_time)->format('H:i') }} WIB</span>
                      @endif
                    </div>
                    @if($item->event_location)
                      <div class="flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-blue-600 w-4 text-center"></i>
                        <span>{{ $item->event_location }}</span>
                      </div>
                    @endif
                  </div>
                @endif

                {{-- Excerpt (hide for schedule since we have date/time/location info) --}}
                @if(!$isSchedule && !empty($item->excerpt))
                  <p class="text-slate-500 text-sm leading-relaxed line-clamp-2 flex-1">
                    {{ $item->excerpt }}
                  </p>
                @else
                  <div class="flex-1"></div>
                @endif

                {{-- Footer: Link --}}
                <div class="mt-4 pt-4 border-t border-slate-200">
                  <a href="{{ $itemUrl }}"
                     class="inline-flex items-center gap-2 {{ $colors['accent'] }} text-sm font-bold hover:gap-3 transition-all">
                    <span>Lihat Daftar Peserta</span>
                    <i class="fas fa-arrow-right text-xs"></i>
                  </a>
                </div>

              </div>
            </article>
          @endforeach

        </div>
      @endif

      {{-- View More Button --}}
      <div class="flex justify-center mt-10">
        <a href="{{ $moreRoute }}" 
           class="inline-flex items-center gap-3 bg-slate-100 border border-slate-200 px-5 py-2.5 md:px-8 md:py-3 rounded-xl text-sm md:text-base text-slate-700 font-bold hover:bg-slate-200 hover:border-slate-300 transition-all">
          <span>{{ $isScheduleSection ? 'Lihat Semua Jadwal Tes EPT Offline' : 'Lihat Semua ' . $title }}</span>
          <i class="fas fa-arrow-right"></i>
        </a>
      </div>

    @else
      {{-- Empty State --}}
      <div class="bg-slate-50 rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
          <i class="fas fa-inbox text-2xl"></i>
        </div>
        <p class="text-slate-500 font-medium">{{ $emptyText }}</p>
      </div>
    @endif

  </div>
</section>
