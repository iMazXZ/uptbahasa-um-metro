<!DOCTYPE html>
<html lang="id" class="scroll-smooth {{ trim((string) $__env->yieldContent('translate_no')) === '1' ? 'notranslate' : '' }}" @if(trim((string) $__env->yieldContent('translate_no')) === '1') translate="no" @endif> {{-- Tambah scroll-smooth --}}
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  @if(trim((string) $__env->yieldContent('translate_no')) === '1')
    <meta name="google" content="notranslate">
    <meta name="googlebot" content="notranslate">
  @endif
  <title>@yield('title', 'UPT Bahasa UM Metro')</title>
  <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

  @yield('meta')

  {{-- CSS/Libs --}}
  <link rel="stylesheet" href="{{ asset('css/tailwind-build.css') }}" />
  <link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}" />
  <link rel="stylesheet" href="{{ asset('vendor/inter/inter.css') }}">
  <link href="{{ asset('vendor/aos/aos.css') }}" rel="stylesheet">

  {{-- Alpine.js (Ringan & Powerful untuk UI Interaktif) --}}
  <script defer src="{{ asset('vendor/alpine/alpine.min.js') }}"></script>

  {{-- Turbo (Hotwire): navigasi instan tanpa reload layout --}}
  <script defer src="{{ asset('vendor/turbo/turbo.min.js') }}"></script>

  <script>
    // Navigasi ke panel Filament (/admin) harus full reload normal,
    // karena Turbo tidak kompatibel dengan Livewire/Filament.
    document.addEventListener('turbo:click', (event) => {
      const url = event.detail.url || '';
      if (url.includes('/admin')) {
        event.preventDefault();
        window.location.href = url;
      }
    });
    // Re-inisialisasi Alpine & AOS setelah Turbo mengganti konten halaman.
    // Listener tunggal di layout (head tidak di-evaluasi ulang oleh Turbo).
    // Halaman yang punya script inline cukup mendaftarkan fungsi-nya ke window.__pageInit.
    document.addEventListener('turbo:load', () => {
      if (window.Alpine) {
        Alpine.initTree(document.body);
      }
      if (typeof window.__pageInit === 'function') {
        window.__pageInit();
      }
      if (window.AOS) {
        AOS.refreshHard();
      }
    });
  </script>

  @php($frontHeadScript = \App\Models\SiteSetting::get('front_head_script'))
  @if(!empty($frontHeadScript))
    {!! $frontHeadScript !!}
  @endif

  @stack('styles')
  <style>
    [x-cloak] { display: none !important; } /* Untuk AlpineJS loading */
    * , *::before, *::after { box-sizing: border-box; }
    html, body { overflow-x: hidden; }
    
    /* Custom Scrollbar yang lebih manis */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    /* Skala proporsional untuk layar 1024-1366px (monitor 720p / laptop kecil) */
    @media (min-width: 1024px) and (max-width: 1366px) {
      html { font-size: 15px; }
    }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .prose .tbl-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    @media (max-width: 640px) {
      .prose .tbl-wrap table { min-width: 1000px; }
      /* ... style table mobile kamu tetap disini ... */
    }
  </style>
</head>
<body class="bg-white text-gray-900 antialiased flex flex-col min-h-screen {{ trim((string) $__env->yieldContent('translate_no')) === '1' ? 'notranslate' : '' }}" @if(trim((string) $__env->yieldContent('translate_no')) === '1') translate="no" @endif> 
  @php($frontBodyScript = \App\Models\SiteSetting::get('front_body_script'))
  @php($hideNavbar = trim((string) $__env->yieldContent('hide_navbar')) === '1')
  @php($hideFooter = trim((string) $__env->yieldContent('hide_footer')) === '1')
  @if(!empty($frontBodyScript))
    {!! $frontBodyScript !!}
  @endif

  {{-- Navbar global --}}
  @unless($hideNavbar)
    @include('partials.navbar')
  @endunless

  {{-- Konten halaman --}}
  <main class="flex-grow"> {{-- flex-grow agar footer selalu di bawah meski konten sedikit --}}
    @yield('content')
  </main>

  {{-- Footer global --}}
  @unless($hideFooter)
    @include('partials.footer')
  @endunless

  {{-- JS global --}}
  <script src="{{ asset('vendor/aos/aos.js') }}"></script>
  <script>
    // AOS Init (dipicu saat load awal maupun navigasi Turbo)
    function initAOS() {
      if (window.AOS) {
        AOS.init({
          duration: 800,
          once: true,
          offset: 50,
          disable: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        });
      }
    }
    document.addEventListener('DOMContentLoaded', initAOS);
    document.addEventListener('turbo:load', initAOS);
    // JS Menu Toggle dihapus karena diganti Alpine.js di Navbar
  </script>

  @stack('scripts')
  @php($frontFooterScript = \App\Models\SiteSetting::get('front_footer_script'))
  @if(!empty($frontFooterScript))
    {!! $frontFooterScript !!}
  @endif
</body>
</html>
