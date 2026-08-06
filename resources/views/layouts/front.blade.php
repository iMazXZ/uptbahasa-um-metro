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
  <script src="https://cdn.tailwindcss.com?plugins=typography,line-clamp"></script>
  <link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="{{ asset('vendor/aos/aos.css') }}" rel="stylesheet">

  {{-- Alpine.js (Ringan & Powerful untuk UI Interaktif) --}}
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'um-blue':'#1e40af',
            'um-green':'#059669',
            'um-gold':'#f59e0b',
          },
          fontFamily: {
            sans: ['Inter', 'sans-serif'], // Tambahkan font modern jika mau
          }
        }
      }
    }
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
    // AOS Init
    document.addEventListener('DOMContentLoaded', () => {
      AOS.init({
        duration: 800,
        once: true,
        offset: 50,
        disable: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
      });
    });
    // JS Menu Toggle dihapus karena diganti Alpine.js di Navbar
  </script>

  @stack('scripts')
  @php($frontFooterScript = \App\Models\SiteSetting::get('front_footer_script'))
  @if(!empty($frontFooterScript))
    {!! $frontFooterScript !!}
  @endif
</body>
</html>
