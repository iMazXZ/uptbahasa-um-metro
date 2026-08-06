@extends('layouts.front')

@section('title', $title.' - Lembaga Bahasa')

@section('meta')
  @php
    $metaCategory = $category ?? 'news';
    $activeNewsCategory = $activeNewsCategory ?? null;
    $newsCategoryLabel = $metaCategory === 'news' && filled($activeNewsCategory)
        ? \App\Models\Post::newsCategoryLabel($activeNewsCategory)
        : null;

    $metaCanonical = match ($metaCategory) {
        'news' => $newsCategoryLabel
            ? route('front.news.category', ['newsCategory' => $activeNewsCategory])
            : route('front.news'),
        'career' => route('front.career'),
        'schedule' => route('front.schedule'),
        'scores'   => route('front.scores'),
        default    => route('front.news'),
    };

    $metaHasVariants = request()->has('page')
        || trim((string) request('q')) !== ''
        || request()->has('sort')
        || ($metaCategory === 'news' && trim((string) request('kategori')) !== '')
        || ($metaCategory === 'career' && trim((string) request('status')) !== '' && request('status') !== 'open');

    $metaRobots = in_array($metaCategory, ['news', 'career', 'schedule', 'scores'], true) && !$metaHasVariants
        ? 'index,follow'
        : 'noindex,follow';

    $metaDescription = match ($metaCategory) {
        'career' => 'Informasi lowongan dan peluang karier terbaru dari Lembaga Bahasa UM Metro.',
        'schedule' => 'Jadwal tes EPT terbaru dari Lembaga Bahasa UM Metro.',
        'scores'   => 'Informasi nilai tes EPT terbaru dari Lembaga Bahasa UM Metro.',
        default    => $newsCategoryLabel
            ? "Berita kategori {$newsCategoryLabel} dari Lembaga Bahasa UM Metro."
            : 'Berita dan informasi terbaru dari Lembaga Bahasa UM Metro.',
    };
  @endphp
  <meta name="description" content="{{ $metaDescription }}">
  <link rel="canonical" href="{{ $metaCanonical }}">
  <meta name="robots" content="{{ $metaRobots }}">
@endsection

@section('content')

  {{-- Hero (match homepage style) --}}
  @php
    $heroCategory = $category ?? '';
    $heroBadge = match ($heroCategory) {
        'career' => 'Karier',
        'schedule' => 'Jadwal EPT',
        'scores'   => 'Nilai EPT',
        default    => $newsCategoryLabel ? 'Kategori: ' . $newsCategoryLabel : 'Informasi Lembaga',
    };
    $heroSubtitle = match ($heroCategory) {
        'career' => 'Lihat lowongan terbaru, status rekrutmen, dan tautan pendaftaran.',
        'schedule' => 'Pantau jadwal tes EPT terbaru dan informasi pelaksanaan.',
        'scores'   => 'Cek pengumuman nilai EPT terbaru di sini.',
        default    => $newsCategoryLabel
            ? "Kumpulan berita kategori {$newsCategoryLabel} dari Lembaga Bahasa."
            : 'Temukan berita dan informasi terbaru dari Lembaga Bahasa.',
    };
  @endphp
  <div class="relative bg-slate-900 overflow-hidden">
    <div class="absolute inset-0">
      <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-indigo-900 opacity-90"></div>
      <div class="absolute inset-0" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px; opacity: 0.1;"></div>
      <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-blue-400 rounded-full blur-3xl opacity-20"></div>
      <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-indigo-400 rounded-full blur-3xl opacity-20"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 pt-10 pb-10 md:pt-14 md:pb-14">
      <div class="max-w-3xl">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-blue-100 text-xs font-medium mb-4 backdrop-blur-md">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
          </span>
          {{ $heroBadge }}
        </div>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-white tracking-tight mb-2 leading-tight">
          {{ $title }}
        </h1>
        <p class="text-blue-100/90 text-lg md:text-xl font-medium">
          {{ $heroSubtitle }}
        </p>
      </div>
    </div>
  </div>

  <section class="max-w-7xl mx-auto px-4 py-10">
    @if(($category ?? '') === 'news' && !empty($newsCategoryMenu ?? []))
      <div class="mb-6">
        <div class="flex flex-wrap gap-2">
          <a href="{{ route('front.news') }}"
             class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-sm font-medium transition {{ empty($activeNewsCategory) ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-300 text-gray-700 hover:border-blue-400 hover:text-blue-700' }}">
            Semua
          </a>

          @foreach($newsCategoryMenu as $item)
            <a href="{{ $item['url'] }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-sm font-medium transition {{ $item['active'] ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-300 text-gray-700 hover:border-blue-400 hover:text-blue-700' }}">
              <span>{{ $item['label'] }}</span>
              <span class="text-xs {{ $item['active'] ? 'text-blue-100' : 'text-gray-500' }}">({{ $item['count'] }})</span>
            </a>
          @endforeach
        </div>
      </div>
    @endif

    @if(($category ?? '') === 'career' && !empty($careerStatusMenu ?? []))
      <div class="mb-6">
        <div class="flex flex-wrap gap-2">
          @foreach($careerStatusMenu as $item)
            <a href="{{ $item['url'] }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-sm font-medium transition {{ $item['active'] ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-300 text-gray-700 hover:border-blue-400 hover:text-blue-700' }}">
              <span>{{ $item['label'] }}</span>
              <span class="text-xs {{ $item['active'] ? 'text-blue-100' : 'text-gray-500' }}">({{ $item['count'] }})</span>
            </a>
          @endforeach
        </div>
      </div>
    @endif

    {{-- FILTER BAR (q + sort). Tipe mengikuti route, jadi tidak dipilih ulang di sini --}}
    <form method="GET" class="mb-8">
      @if(($category ?? '') === 'career' && filled($careerStatus ?? null) && $careerStatus !== 'open')
        <input type="hidden" name="status" value="{{ $careerStatus }}">
      @endif

      <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        {{-- Cari --}}
        <div class="flex-1">
          <label class="block text-xs font-medium text-gray-500 mb-1">Cari</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
              </svg>
            </span>
            <input
              type="text"
              name="q"
              value="{{ request('q') }}"
              placeholder="Cari judul / isi…"
              class="w-full rounded-xl border-gray-300 pl-9 pr-3 py-2 focus:border-blue-500 focus:ring-blue-500"
            />
            @if(request('q'))
              {{-- tombol clear --}}
              <a href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => null]) }}"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                title="Bersihkan">
                ✕
              </a>
            @endif
          </div>
        </div>

        {{-- Urutkan --}}
        <div class="sm:w-56">
          <label class="block text-xs font-medium text-gray-500 mb-1">Urutkan</label>
          @php $sort = request('sort', 'new'); @endphp
          <select name="sort"
                  class="w-full rounded-xl border-gray-300 py-2 focus:border-blue-500 focus:ring-blue-500">
            <option value="new" {{ $sort === 'new' ? 'selected' : '' }}>Terbaru</option>
            <option value="old" {{ $sort === 'old' ? 'selected' : '' }}>Terlama</option>
            <option value="az"  {{ $sort === 'az'  ? 'selected' : '' }}>Judul A–Z</option>
          </select>
        </div>

        {{-- Tombol --}}
        <div class="sm:w-auto">
          <label class="block text-xs font-medium text-transparent mb-1">.&nbsp;</label>
          <button
            class="w-full sm:w-auto inline-flex justify-center rounded-xl bg-blue-600 px-5 py-2 font-semibold text-white hover:bg-blue-700">
            Terapkan
          </button>
        </div>
      </div>

      {{-- Chip info filter aktif (opsional) --}}
      @if(request('q') || request('sort') || (($category ?? '') === 'career' && filled($careerStatus ?? null)))
        <div class="mt-3 flex flex-wrap gap-2 text-xs">
          @if(request('q'))
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-50 text-blue-700">
              Query: “{{ request('q') }}”
            </span>
          @endif
          @if(request('sort'))
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-100 text-gray-700">
              Sort: {{ strtoupper(request('sort')) }}
            </span>
          @endif
          @if(($category ?? '') === 'career' && filled($careerStatus ?? null))
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-indigo-50 text-indigo-700">
              Status: {{ strtoupper($careerStatus) }}
            </span>
          @endif
        </div>
      @endif
    </form>


    @if ($posts->count())
      {{-- Stats --}}
      <div class="mb-6 text-sm text-gray-600">
        Menampilkan
        <span class="font-semibold text-gray-900">{{ $posts->firstItem() }}</span>
        –
        <span class="font-semibold text-gray-900">{{ $posts->lastItem() }}</span>
        dari
        <span class="font-semibold text-gray-900">{{ $posts->total() }}</span>
        hasil
      </div>

      {{-- Grid --}}
      @php
        // tampilkan versi compact (tanpa gambar & excerpt) untuk jadwal & nilai
        $compactList = in_array($category ?? '', ['schedule','scores'], true);
      @endphp

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach ($posts as $p)
          <x-post.card :post="$p" :compact="$compactList" />
        @endforeach
      </div>

      {{-- Pagination --}}
      <div class="mt-10">
        {{ $posts->onEachSide(1)->links() }}
      </div>
    @else
      {{-- Empty State --}}
      <div class="text-center py-20">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-6">
          <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
          </svg>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 mb-2">Belum Ada Data</h3>
        <p class="text-gray-600">Konten akan segera ditampilkan di sini.</p>
      </div>
    @endif
  </section>
@endsection
