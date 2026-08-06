{{-- resources/views/partials/navbar.blade.php --}}
@php
    // 1. Centralized Dashboard Logic (DRY Principle)
    $dashboardRoute = route('front.home');
    $user = Auth::user();
    
    if ($user) {
        if ($user->hasRole(['tutor', 'pendaftar'])) {
            $dashboardRoute = route('dashboard.pendaftar');
        } elseif ($user->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga', 'Penerjemah'])) {
            $dashboardRoute = route('filament.admin.pages.2'); // Dashboard admin
        }
    }

    // 2. Link Classes
    $baseLink = "text-gray-600 hover:text-um-blue font-medium transition-colors duration-200 text-sm lg:text-base";
    $activeLink = "text-um-blue font-bold";
@endphp

<nav 
    x-data="{ mobileOpen: false, scrolled: false }" 
    @scroll.window="scrolled = (window.pageYOffset > 20)"
    :class="{ 'shadow-md bg-white/95 backdrop-blur-md': scrolled, 'bg-white border-b': !scrolled }"
    class="sticky top-0 z-50 transition-all duration-300 w-full"
>
  <div class="max-w-7xl mx-auto px-4 lg:px-8">
    <div class="flex justify-between items-center py-3 lg:py-4">

      {{-- BRAND --}}
      <a href="{{ route('front.home') }}" class="group flex items-center gap-2 z-50 relative">
        <img src="{{ asset('images/logo-um.png') }}" alt="Logo" class="h-10 w-10 md:h-12 md:w-12 object-contain transition-transform group-hover:scale-105">
        <div class="leading-tight">
          <div class="font-extrabold tracking-tight text-[18px] md:text-[20px] text-slate-900 group-hover:text-um-blue transition-colors">
            <span>UPT </span><span class="text-um-gold">Bahasa</span>
          </div>
          <div class="text-slate-500 text-[10px] md:text-[11px] font-medium">Universitas Muhammadiyah Metro</div>
        </div>
      </a>

      {{-- DESKTOP MENU --}}
      <div class="hidden lg:flex items-center gap-8">
        <a href="{{ route('layanan.index') }}" class="{{ request()->routeIs('layanan.*') ? $activeLink : $baseLink }}">
            Layanan
        </a>
        <a href="{{ route('front.career') }}" class="{{ request()->routeIs('front.career*') ? $activeLink : $baseLink }}">
            Karier
        </a>
        <a href="{{ route('bl.index') }}" class="{{ request()->routeIs('bl.*') ? $activeLink : $baseLink }}">
            Basic Listening
        </a>
        <a href="{{ route('verification.index') }}" class="{{ request()->routeIs('verification.*') ? $activeLink : $baseLink }}">
            Verifikasi Dokumen
        </a>

        @guest
          <div class="flex items-center gap-3 pl-4 border-l border-gray-200">
            <a href="{{ route('register') }}" class="text-sm font-semibold text-um-blue hover:text-blue-800 transition">
              Daftar
            </a>
            <a href="{{ route('login') }}" class="px-5 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-um-gold to-orange-500 rounded-full shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
              <i class="fas fa-sign-in-alt mr-1"></i> Login
            </a>
          </div>
        @else
          {{-- Dropdown User (Desktop) --}}
          <div class="relative ml-4" x-data="{ open: false }">
            <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-2 focus:outline-none">
                <div class="text-right hidden md:block">
                    <div class="text-sm font-bold text-gray-800 leading-tight">{{ Str::limit($user->name, 15) }}</div>
                    <div class="text-[10px] text-gray-500">{{ $user->getRoleNames()->first() ?? 'User' }}</div>
                </div>
                <div class="h-9 w-9 rounded-full bg-gray-100 flex items-center justify-center border border-gray-200 text-um-blue">
                    <i class="fas fa-user"></i>
                </div>
                <i class="fas fa-chevron-down text-xs text-gray-400 ml-1 transition-transform duration-200" :class="{'rotate-180': open}"></i>
            </button>

            {{-- Dropdown Menu --}}
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50"
                 style="display: none;">
                
                <a href="{{ $dashboardRoute }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-um-blue">
                    <i class="fas fa-gauge-high w-5 text-center mr-1"></i> Dashboard
                </a>
                <div class="border-t border-gray-100 my-1"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                        <i class="fas fa-sign-out-alt w-5 text-center mr-1"></i> Logout
                    </button>
                </form>
            </div>
          </div>
        @endguest
      </div>

      {{-- MOBILE TOGGLE --}}
      <button @click="mobileOpen = !mobileOpen" class="lg:hidden p-2 text-gray-600 hover:text-um-blue transition focus:outline-none">
        <i class="fas fa-bars text-xl" x-show="!mobileOpen"></i>
        <i class="fas fa-times text-xl" x-show="mobileOpen" x-cloak></i>
      </button>
    </div>
  </div>

  {{-- MOBILE MENU (Alpine Transitions) --}}
  <div x-show="mobileOpen" 
       x-collapse 
       x-cloak
       class="lg:hidden bg-white border-t border-gray-100 shadow-lg overflow-hidden">
    <div class="px-4 pt-2 pb-6 space-y-1">
      <a href="{{ route('layanan.index') }}" class="block py-3 text-gray-600 hover:text-um-blue font-medium border-b border-gray-50">Layanan</a>
      <a href="{{ route('front.career') }}" class="block py-3 text-gray-600 hover:text-um-blue font-medium border-b border-gray-50">Karier</a>
      <a href="{{ route('bl.index') }}" class="block py-3 text-gray-600 hover:text-um-blue font-medium border-b border-gray-50">Basic Listening</a>
      <a href="{{ route('verification.index') }}" class="block py-3 text-gray-600 hover:text-um-blue font-medium border-b border-gray-50">Verifikasi Dokumen</a>
      
      <div class="pt-4 flex flex-col gap-3">
        @guest
            <a href="{{ route('login') }}" class="w-full text-center py-3 bg-um-blue text-white rounded-lg font-bold shadow-md">
                Login Akun
            </a>
            <a href="{{ route('register') }}" class="w-full text-center py-3 border border-gray-200 text-gray-600 rounded-lg font-semibold">
                Daftar Baru
            </a>
        @else
            <div class="bg-gray-50 p-4 rounded-lg flex items-center gap-3 mb-2">
                <div class="h-10 w-10 rounded-full bg-white border flex items-center justify-center text-um-blue font-bold text-lg">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <div>
                    <div class="font-bold text-gray-900">{{ Str::limit($user->name, 20) }}</div>
                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                </div>
            </div>
            <a href="{{ $dashboardRoute }}" class="block w-full text-center py-2.5 bg-um-blue text-white rounded-lg font-semibold shadow">
                Akses Dashboard
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-center py-2.5 text-red-600 font-semibold hover:bg-red-50 rounded-lg transition">
                    Logout
                </button>
            </form>
        @endguest
      </div>
    </div>
  </div>
</nav>
