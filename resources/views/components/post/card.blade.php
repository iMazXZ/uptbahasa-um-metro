@props(['post', 'compact' => false])

@php
    $type = $post->type ?? $post->category ?? 'news';
    $newsCategorySlug = \App\Models\Post::normalizeNewsCategory($post->news_category ?? null);
    $newsCategoryLabel = \App\Models\Post::newsCategoryLabel($newsCategorySlug);
    $postUrl = $type === 'career'
        ? route('front.career.show', $post->slug)
        : route('front.post.show', $post->slug);
    $careerDeadline = null;
    if ($type === 'career' && !empty($post->career_deadline)) {
        $careerDeadline = $post->career_deadline instanceof \Carbon\CarbonInterface
            ? $post->career_deadline
            : \Carbon\Carbon::parse($post->career_deadline);
    }
    $careerApplyUrl = $type === 'career' ? trim((string) ($post->career_apply_url ?? '')) : '';
    $isCareerOpen = $type === 'career'
        ? ((bool) ($post->career_is_open ?? true) && ($careerDeadline === null || $careerDeadline->greaterThanOrEqualTo(now())))
        : false;
    $careerStatusLabel = $isCareerOpen ? 'Dibuka' : 'Ditutup';
    $careerStatusClass = $isCareerOpen
        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
        : 'bg-rose-50 text-rose-700 border-rose-200';

    $chipClass = match ($type) {
        'career'   => 'bg-amber-50 text-amber-700',
        'schedule' => 'bg-emerald-50 text-emerald-700',
        'scores'   => 'bg-violet-50 text-violet-700',
        default    => 'bg-blue-50 text-blue-700',
    };

    $chipLabel = match ($type) {
        'career'   => 'Karier',
        'schedule' => 'Jadwal',
        'scores'   => 'Nilai',
        default    => $newsCategoryLabel,
    };
@endphp

@if($compact && $type === 'schedule')
  <x-post.schedule-card :post="$post" />
@elseif($compact && $type === 'scores')
  <x-post.score-card :post="$post" />
@else
  <article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
    @unless($compact)
      <a href="{{ $postUrl }}"
         aria-label="Baca {{ $post->title }}"
         class="block relative h-56 overflow-hidden bg-gray-200">
        @if(!empty($post->cover_url))
          <img
            src="{{ $post->cover_url }}"
            alt="{{ $post->title }}"
            class="w-full h-full object-cover sm:group-hover:scale-105 transition-transform duration-500"
            loading="lazy"
            decoding="async"
            fetchpriority="low"
            sizes="(max-width: 1024px) 100vw, 33vw">
        @else
          <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-100" aria-hidden="true"></div>
        @endif

        {{-- Overlay gradient only on hover --}}
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 sm:group-hover:opacity-100 transition-opacity duration-300"></div>
      </a>
    @endunless

    <div class="p-6">
      {{-- CHIP + tanggal --}}
      <div class="flex items-center flex-wrap gap-2 text-xs text-gray-500 mb-3">
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-medium {{ $chipClass }}">
          {{ strtoupper($chipLabel) }}
        </span>

        @if($type === 'career')
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border font-medium {{ $careerStatusClass }}">
            {{ strtoupper($careerStatusLabel) }}
          </span>
        @endif

        @if($post->published_at)
          <span class="text-slate-400">•</span>
          <time datetime="{{ $post->published_at->toDateString() }}">
            {{ $post->published_at->translatedFormat('d M Y') }}
          </time>
        @endif
      </div>

      @if($type === 'career' && $careerDeadline)
        <div class="mb-3 text-xs font-medium {{ $isCareerOpen ? 'text-emerald-700' : 'text-rose-700' }}">
          Deadline: {{ $careerDeadline->translatedFormat('d M Y, H:i') }} WIB
        </div>
      @endif

      {{-- Title --}}
      <a href="{{ $postUrl }}"
         class="block text-lg lg:text-xl font-bold text-gray-900 mb-3 line-clamp-2 sm:group-hover:text-blue-600 transition-colors duration-300"
         aria-label="Baca {{ $post->title }}">
        {{ $post->title }}
      </a>

      {{-- Excerpt (optional) --}}
      @unless($compact)
        @if(!empty($post->excerpt))
          <p class="text-gray-600 text-sm mb-4 line-clamp-3">
            {{ $post->excerpt }}
          </p>
        @endif
      @endunless

      @if($type === 'career')
        <div class="flex flex-wrap items-center gap-2">
          @if($isCareerOpen && $careerApplyUrl !== '')
            <a href="{{ $careerApplyUrl }}"
               target="_blank"
               rel="noopener noreferrer nofollow"
               class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-white text-sm font-semibold hover:bg-emerald-700 transition-colors"
               aria-label="Daftar sekarang untuk {{ $post->title }}">
              <span>Daftar Sekarang</span>
            </a>
          @endif

          <a href="{{ $postUrl }}"
             class="inline-flex items-center gap-2 text-blue-600 font-semibold text-sm sm:hover:gap-3 transition-all duration-300"
             aria-label="Lihat detail {{ $post->title }}">
            <span>Lihat Detail</span>
            <svg class="w-4 h-4 sm:group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </a>
        </div>
      @else
        {{-- Read more --}}
        <a href="{{ $postUrl }}"
           class="inline-flex items-center gap-2 text-blue-600 font-semibold text-sm sm:hover:gap-3 transition-all duration-300"
           aria-label="Baca {{ $post->title }}">
          <span>Baca Selengkapnya</span>
          <svg class="w-4 h-4 sm:group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </a>
      @endif
    </div>
  </article>
@endif
