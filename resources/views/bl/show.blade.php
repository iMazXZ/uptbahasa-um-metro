@extends('layouts.front')

@section('title', $session->title)

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $html = $session->summary ?? '';

    // 1) Auto-embed YouTube - SUDAH BAGUS
    $html = preg_replace_callback(
        '~(?:https?://)?(?:www\.)?(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[^\s<>]*)?~i',
        function ($m) {
            $id = $m[1];
            return <<<HTML
<div class="my-6 aspect-video rounded-lg overflow-hidden shadow-lg">
  <iframe
    class="w-full h-full"
    src="https://www.youtube.com/embed/{$id}"
    title="YouTube video player"
    frameborder="0"
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
    allowfullscreen
    loading="lazy"
  ></iframe>
</div>
HTML;
        },
        $html
    );

    // 2) Auto-embed gambar: URL polos - PERBAIKI INI
    $html = preg_replace_callback(
        '~\b(https?://[^\s<>"\)\n]+?\.(?:png|jpe?g|gif|webp|svg|bmp)(?:\?[^\s<>"\)\n]*)?)\b~i',
        function ($m) {
            $src = htmlspecialchars(trim($m[1]), ENT_QUOTES, 'UTF-8');
            return '<img src="' . $src . '" alt="Materi Pembelajaran" class="my-4 rounded-lg shadow-md max-w-full h-auto mx-auto" loading="lazy" onerror="this.style.display=\'none\'">';
        },
        $html
    );

    // 3) Auto-embed gambar: Markdown ![alt](url) - PERBAIKI INI  
    $html = preg_replace_callback(
        '~!\[([^\]]*)\]\s*\(\s*([^)\s]+)\s*\)~i',
        function ($m) {
            $alt = htmlspecialchars($m[1] ?: 'Materi Pembelajaran', ENT_QUOTES, 'UTF-8');
            $src = htmlspecialchars(trim($m[2]), ENT_QUOTES, 'UTF-8');
            
            if (preg_match('/\.(png|jpe?g|gif|webp|svg|bmp)(?:\?.*)?$/i', $src)) {
                return '<img src="' . $src . '" alt="' . $alt . '" class="my-4 rounded-lg shadow-md max-w-full h-auto mx-auto" loading="lazy" onerror="this.style.display=\'none\'">';
            }
            
            return $m[0];
        },
        $html
    );

    // 4) Audio handling - TAMBAH error handling
    $audioSrc = null;
    $audioError = null;
    if (!empty($session->audio_url)) {
        if (Str::startsWith($session->audio_url, ['http://', 'https://'])) {
            $audioSrc = $session->audio_url;
        } else {
            try {
                $audioSrc = Storage::url($session->audio_url);
                // Cek jika file exists (untuk local storage)
                if (config('filesystems.default') === 'local' && !Storage::exists($session->audio_url)) {
                    $audioError = 'File audio tidak ditemukan';
                }
            } catch (Exception $e) {
                $audioError = 'Error loading audio file';
            }
        }
    }

    // 5) Check session status - OPTIMASI
    $now = now();
    $hasOpened = !$session->opens_at || $now->gte($session->opens_at);
    $hasClosed = $session->closes_at && $now->gt($session->closes_at);
    $isActive = $session->is_active ?? true;
    $isOpen = $isActive && $hasOpened && !$hasClosed;
@endphp

<div class="bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="text-center"> {{-- TAMBAHIN INI --}}
            <div class="mb-4"> {{-- UBAH mb-3 jadi mb-4 --}}
                <span class="inline-block px-3 py-1 text-xs sm:text-sm font-semibold uppercase tracking-wider bg-white/20 rounded-full">
                    Pertemuan {{ (int) $session->number === 6 ? 'UAS' : $session->number }}
                </span>
            </div>
            
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4 leading-tight">
                {{ $session->title }}
            </h1>

            {{-- Status dan waktu --}}
            <div class="flex flex-col items-center gap-3 text-sm text-blue-100"> {{-- UBAH jadi items-center --}}
                @if(!$hasOpened)
                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-yellow-500/30 rounded-full">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        Belum Dibuka
                    </span>
                @elseif($hasClosed)
                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-red-500/30 rounded-full">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        Sudah Ditutup
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-green-500/30 rounded-full">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Sedang Dibuka
                    </span>
                @endif

                @if($session->opens_at || $session->closes_at)
                    <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-4 text-xs sm:text-sm"> {{-- TAMBAH items-center --}}
                        @if($session->opens_at)
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Buka: {{ $session->opens_at->format('d M Y, H:i') }}
                            </span>
                        @endif
                        @if($session->closes_at)
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Tutup: {{ $session->closes_at->format('d M Y, H:i') }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-8"> {{-- REDUCE max-width & padding --}}
    {{-- Content Summary --}}
    @if($html && trim(strip_tags($html)) !== '')
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 mb-6">
            <div class="prose prose-sm sm:prose-base prose-blue max-w-none prose-headings:font-bold prose-a:text-blue-600 prose-img:rounded-lg prose-img:mx-auto">
                {!! $html !!}
            </div>
        </div>
    @endif

    {{-- Audio Player --}}
    @if($audioSrc && !$audioError)
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15.536a5 5 0 001.414-1.414m-1.414-2.829a5 5 0 010-7.072m2.829 2.828a5 5 0 000 7.072"/>
                </svg>
                Audio Materi
            </h3>
            <audio controls class="w-full" src="{{ $audioSrc }}" preload="metadata">
                Browser Anda tidak mendukung audio player.
            </audio>
            <p class="text-xs text-gray-500 mt-2">Opsional: Dengarkan audio materi untuk pembelajaran lebih baik</p>
        </div>
    @elseif($audioError)
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
            <p class="text-yellow-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                {{ $audioError }}
            </p>
        </div>
    @endif

    {{-- Materials Builder (JSON-based) --}}
    @if(property_exists($session, 'materials') && is_array($session->materials) && count($session->materials))
        <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8 mb-8">
            <h3 class="text-xl font-bold text-gray-900 mb-6">📚 Materi Tambahan</h3>
            <div class="space-y-6">
                @foreach($session->materials as $index => $block)
                    @switch($block['type'] ?? null)
                        @case('text')
                            <div class="prose max-w-none">
                                {!! $block['data']['content'] ?? '' !!}
                            </div>
                            @break

                        @case('image')
                            @php
                                $path = $block['data']['image'] ?? null;
                                $url = $path ? (Str::startsWith($path, ['http://', 'https://']) ? $path : Storage::url($path)) : null;
                            @endphp
                            @if($url)
                                <figure class="my-6">
                                    <img src="{{ $url }}" alt="{{ $block['data']['caption'] ?? '' }}" 
                                         class="rounded-lg shadow-md w-full" loading="lazy">
                                    @if(!empty($block['data']['caption']))
                                        <figcaption class="text-sm text-gray-600 mt-2 text-center">
                                            {{ $block['data']['caption'] }}
                                        </figcaption>
                                    @endif
                                </figure>
                            @endif
                            @break

                        @case('youtube')
                            @php
                                $url = $block['data']['url'] ?? '';
                                preg_match('~(?:v=|youtu\.be/)([A-Za-z0-9_\-]{11})~', $url, $m);
                                $id = $m[1] ?? null;
                            @endphp
                            @if($id)
                                <div class="aspect-video rounded-lg overflow-hidden shadow-lg">
                                    <iframe class="w-full h-full" 
                                            src="https://www.youtube.com/embed/{{ $id }}" 
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen
                                            loading="lazy"></iframe>
                                </div>
                            @endif
                            @break

                        @case('file')
                            @php
                                $path = $block['data']['file'] ?? null;
                                $label = $block['data']['label'] ?? 'Download file';
                                $url = $path ? (Str::startsWith($path, ['http://', 'https://']) ? $path : Storage::url($path)) : null;
                            @endphp
                            @if($url)
                                <a href="{{ $url }}" 
                                   target="_blank" 
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    {{ $label }}
                                </a>
                            @endif
                            @break
                    @endswitch
                @endforeach
            </div>
        </div>
    @endif

    {{-- Quiz Action Button --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-sm p-6 sm:p-8 mb-8">
    @if ($isOpen)
        @auth
        @php
            /** @var \App\Models\User $u */
            $u = auth()->user();

            // butuh lengkapi biodata?
            $needProfile = ! $u?->prody_id || ! $u?->srn || ! $u?->year;
            $next        = route('bl.code.form', $session); // tujuan setelah lengkap biodata
            $targetHref  = $needProfile
            ? route('bl.profile.complete', ['next' => $next])
            : $next;

            // cek attempt user di sesi ini
            $activeAttempt = \App\Models\BasicListeningAttempt::query()
            ->where('user_id', $u->id)
            ->where('session_id', $session->id)
            ->whereNull('submitted_at')
            ->first();

            $lastCompletedAttempt = \App\Models\BasicListeningAttempt::query()
            ->where('user_id', $u->id)
            ->where('session_id', $session->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->first();
        @endphp

        @if ($activeAttempt || $lastCompletedAttempt)
            {{-- SUDAH PERNAH / SEDANG MENGERJAKAN --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                @if ($activeAttempt)
                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                    Kamu sudah memulai quiz untuk pertemuan ini.
                </h3>
                <p class="text-sm text-gray-600">
                    Lanjutkan pengerjaan lewat halaman <strong>Riwayat Skor</strong>.
                </p>
                @else
                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                    Kamu sudah menyelesaikan quiz untuk pertemuan ini.
                </h3>
                <p class="text-sm text-gray-600">
                    Lihat detail jawaban dan skor di halaman <strong>Riwayat Skor</strong>.
                </p>
                @endif
            </div>

            <a href="{{ route('bl.history') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-white text-blue-700 font-semibold rounded-lg shadow-md hover:bg-blue-50 transition-all"
                aria-label="Lihat Riwayat Skor">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Lihat Riwayat Skor
            </a>
            </div>
        @else
            {{-- BELUM PERNAH MENGERJAKAN → TAMPILKAN "KERJAKAN QUIZ" --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Siap Mengerjakan Quiz?</h3>
                <p class="text-sm text-gray-600">Pastikan Anda sudah memahami materi sebelum memulai.</p>
                @if ($needProfile)
                <p class="text-xs text-amber-600 mt-1">
                    Lengkapi <strong>Prodi, SRN, Tahun</strong> terlebih dahulu.
                </p>
                @endif
            </div>

            <a href="{{ $targetHref }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition-all transform hover:scale-105"
                aria-label="Kerjakan Quiz">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Kerjakan Quiz
            </a>
            </div>
        @endif
        @else
        {{-- Belum login --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Login Diperlukan</h3>
            <p class="text-sm text-gray-600">Silakan login terlebih dahulu untuk mengerjakan quiz.</p>
            </div>
            <a href="{{ route('login') }}"
            class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition-all"
            aria-label="Login untuk Kerjakan Quiz">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Login untuk Kerjakan Quiz
            </a>
        </div>
        @endauth
    @else
        {{-- Sesi belum dibuka / sudah ditutup --}}
        <div class="text-center py-4">
        <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        <p class="text-gray-600 font-medium">
            @if (! $hasOpened)
            Sesi belum dibuka. Silakan tunggu hingga waktu pembukaan.
            @else
            Sesi sudah ditutup. Tidak dapat mengerjakan quiz.
            @endif
        </p>
        </div>
    @endif
    </div>

    {{-- Back Navigation --}}
    <div class="text-center">
        <a href="{{ route('bl.index') }}" 
           class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 font-medium transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar Pertemuan
        </a>
    </div>
</div>
@endsection
