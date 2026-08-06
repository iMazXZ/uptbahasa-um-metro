@props(['title' => 'Judul', 'items' => collect(), 'moreRoute' => '#', 'emptyText' => 'Belum ada data.'])

@php
  use Illuminate\Support\Str;
  use Carbon\Carbon;

  $sectionId = 'sec-' . Str::slug($title ?: 'section');
  $hasItems  = $items && $items->count() > 0;

  $featured  = $hasItems ? $items->first() : null;
  $others    = $hasItems ? $items->skip(1)->take(7) : collect();
@endphp

<section class="py-16 bg-slate-50/50" aria-labelledby="{{ $sectionId }}-title">
  <div class="max-w-7xl mx-auto px-4 lg:px-8">

    {{-- Section Header --}}
    <div class="text-center mb-12">
      <span class="text-blue-600 text-sm font-bold uppercase tracking-widest mb-3 block">Update Terbaru</span>
      <h2 id="{{ $sectionId }}-title" class="text-3xl lg:text-4xl font-extrabold text-slate-900 tracking-tight">
        {{ $title }}
      </h2>
      <div class="mt-4 mx-auto w-20 h-1.5 bg-blue-600 rounded-full"></div>
    </div>

    @if($hasItems)
      <div class="grid lg:grid-cols-12 gap-8 items-start">

        {{-- Featured Article --}}
        <div class="lg:col-span-7">
          @php
            $isSchedule = ($featured->type ?? null) === 'schedule';
            $featuredUrl = ($featured->type ?? null) === 'career'
              ? route('front.career.show', $featured->slug)
              : route('front.post.show', $featured->slug);
            $eventDate = $featured->event_date ?? null;
            $isUpcoming = $eventDate && Carbon::parse($eventDate)->isFuture();
            $daysLeft = $eventDate ? Carbon::now()->startOfDay()->diffInDays(Carbon::parse($eventDate)->startOfDay(), false) : null;
          @endphp

          <article class="relative group bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-500 overflow-hidden border border-slate-100">
            {{-- Image --}}
            <a href="{{ $featuredUrl }}" class="block relative aspect-video overflow-hidden">
              @php $cover = $featured->cover_url ?? ''; @endphp
              @if($cover)
                <img src="{{ $cover }}" alt="{{ $featured->title }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
              @else
                <div class="w-full h-full bg-gradient-to-br from-blue-600 to-indigo-700"></div>
              @endif
              <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </a>

            <div class="p-8">
              {{-- Meta --}}
              <div class="flex items-center gap-4 mb-5">
                @if($isSchedule && $isUpcoming && $daysLeft !== null && $daysLeft >= 0)
                  <span class="inline-flex items-center px-3 py-1 bg-emerald-500 text-white text-[11px] font-black uppercase tracking-wider rounded-md shadow-sm">
                    @if($daysLeft == 0) Hari Ini
                    @elseif($daysLeft == 1) Besok
                    @else {{ $daysLeft }} Hari Lagi
                    @endif
                  </span>
                @endif
                <div class="flex items-center gap-2 text-slate-400 text-xs font-bold uppercase tracking-widest">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  @if($isSchedule && $eventDate)
                    {{ Carbon::parse($eventDate)->translatedFormat('d M Y') }}
                  @elseif($featured->published_at)
                    {{ $featured->published_at->translatedFormat('d M Y') }}
                  @endif
                </div>
              </div>

              {{-- Title --}}
              <a href="{{ $featuredUrl }}"
                 class="block text-2xl lg:text-3xl font-bold text-slate-900 hover:text-blue-600 transition-colors mb-4 leading-tight tracking-tight">
                {{ $featured->title }}
              </a>

              {{-- Excerpt --}}
              @if(!empty($featured->excerpt))
                <p class="text-slate-500 text-base leading-relaxed mb-6 line-clamp-2">{{ $featured->excerpt }}</p>
              @endif

              <a href="{{ $featuredUrl }}"
                 class="inline-flex items-center gap-2 text-blue-600 font-bold hover:gap-3 transition-all group/btn">
                <span>Selengkapnya</span>
                <svg class="w-5 h-5 transform group-hover/btn:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
              </a>
            </div>
          </article>
        </div>

        {{-- Sidebar List --}}
        <div class="lg:col-span-5 flex flex-col gap-4">
          <div class="bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8">
            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-8 flex items-center gap-3">
              <span class="w-8 h-px bg-slate-200"></span>
              Lainnya
            </h3>

            <div class="space-y-8">
              @foreach($others as $index => $p)
                @php
                  $pIsSchedule = ($p->type ?? null) === 'schedule';
                  $pUrl = ($p->type ?? null) === 'career'
                    ? route('front.career.show', $p->slug)
                    : route('front.post.show', $p->slug);
                  $pEventDate = $p->event_date ?? null;
                  $pIsUpcoming = $pEventDate && Carbon::parse($pEventDate)->isFuture();
                  $pDaysLeft = $pEventDate ? Carbon::now()->startOfDay()->diffInDays(Carbon::parse($pEventDate)->startOfDay(), false) : null;
                @endphp

                <article class="group relative flex gap-4 items-start">
                  {{-- Date Box - Only for Schedule --}}
                  @php
                    $dDate = $pIsSchedule && $pEventDate ? Carbon::parse($pEventDate) : ($p->published_at ? $p->published_at : null);
                  @endphp

                  @if($pIsSchedule && $dDate)
                    <div class="flex-shrink-0 w-14 text-center">
                      <div class="flex flex-col rounded-xl overflow-hidden shadow-sm border border-slate-100 bg-slate-50 group-hover:bg-blue-600 group-hover:border-blue-600 transition-colors duration-300">
                        <span class="text-[9px] font-black uppercase py-0.5 bg-slate-100 text-slate-500 group-hover:bg-blue-700 group-hover:text-white">{{ $dDate->translatedFormat('M') }}</span>
                        <span class="text-lg font-black py-0.5 text-slate-700 group-hover:text-white">{{ $dDate->translatedFormat('d') }}</span>
                      </div>
                    </div>
                  @else
                    {{-- Minimalist number/bullet for non-schedule items --}}
                    <div class="flex-shrink-0 mt-1 h-8 w-8 flex items-center justify-center rounded-lg bg-slate-50 text-slate-400 group-hover:bg-blue-50 group-hover:text-blue-600 transition-all font-bold text-xs">
                      {{ $index + 2 }}
                    </div>
                  @endif

                  {{-- Content --}}
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-2">
                       @if($pIsSchedule && $pIsUpcoming && $pDaysLeft !== null && $pDaysLeft >= 0)
                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-tighter">
                          @if($pDaysLeft == 0) • Hari Ini
                          @elseif($pDaysLeft == 1) • Besok
                          @else • {{ $pDaysLeft }} Hari Lagi
                          @endif
                        </span>
                      @endif
                    </div>
                    <a href="{{ $pUrl }}"
                       class="block font-bold text-slate-800 group-hover:text-blue-600 transition-colors leading-snug tracking-tight">
                      {{ $p->title }}
                    </a>
                  </div>
                </article>
              @endforeach
            </div>
          </div>

          <div class="lg:hidden">
            <a href="{{ $moreRoute }}" class="flex items-center shadow-sm justify-center gap-3 bg-white border border-slate-100 p-4 rounded-xl text-slate-700 font-bold hover:text-blue-600 transition-colors">
              <span>Lihat Semua {{ $title }}</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
              </svg>
            </a>
          </div>
        </div>
      </div>

      {{-- Global View More for both desktop/mobile --}}
      <div class="hidden lg:flex justify-center mt-12">
        <a href="{{ $moreRoute }}" class="inline-flex items-center gap-3 bg-white border border-slate-200 px-8 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50 hover:border-blue-200 hover:text-blue-600 transition-all shadow-sm">
          <span>Lihat Semua {{ $title }}</span>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
        </a>
      </div>
    @else
      <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-16 text-center">
        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
          </svg>
        </div>
        <p class="text-slate-500 font-bold text-lg tracking-tight">{{ $emptyText }}</p>
      </div>
    @endif
  </div>
</section>
