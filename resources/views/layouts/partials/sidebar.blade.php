{{-- resources/views/layouts/partials/sidebar.blade.php --}}
@php
    $u = auth()->user();
    
    // Check biodata status for badge
    $hasBasicInfo = $u && $u->prody_id && $u->srn && $u->year;
    $isS2 = $u && $u->prody && str_starts_with($u->prody->name ?? '', 'S2');
    $isPBI = $u && $u->prody && $u->prody->name === 'Pendidikan Bahasa Inggris';
    $prodiIslam = ['Komunikasi dan Penyiaran Islam', 'Pendidikan Agama Islam', 'Pendidikan Islam Anak Usia Dini'];
    $isProdiIslam = $u && $u->prody && in_array($u->prody->name, $prodiIslam);
    
    $biodataComplete = $u ? \App\Models\SiteSetting::isEptBiodataComplete($u) : false;
    $biodataNeedsBadge = !$biodataComplete;

    $eptEligibility = $u ? \App\Models\SiteSetting::checkEptEligibility($u) : [false, null];
    $canRegisterEpt = $eptEligibility[0] ?? false;
    $hasEptRegistration = $u ? \App\Models\EptRegistration::where('user_id', $u->id)->exists() : false;
    $showEptMenu = $canRegisterEpt || $hasEptRegistration;
@endphp

<aside
    class="fixed inset-y-0 left-0 z-50 bg-white border-r border-slate-200 shadow-lg lg:shadow-none transition-all duration-300 transform -translate-x-full lg:translate-x-0 w-64"
    :class="sidebarOpen ? '!translate-x-0 !w-64' : (isMobile ? '!-translate-x-full !w-64' : '!translate-x-0 !w-20')"
>
    {{-- Logo Area --}}
    <div class="flex items-center justify-between h-16 px-4 border-b border-slate-100">
        <div class="flex items-center gap-3 overflow-hidden whitespace-nowrap">
            <img src="{{ asset('images/logo-um.png') }}" class="h-8 w-8 object-contain shrink-0" alt="Logo">
            <div class="transition-opacity duration-200"
                 :class="sidebarOpen ? 'opacity-100' : 'opacity-0 lg:hidden'">
                <div class="font-bold text-sm text-slate-900 leading-tight">UPT Bahasa</div>
                <div class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">UM Metro</div>
            </div>
        </div>

        {{-- Toggle button (Desktop only) --}}
        <button @click="sidebarOpen = !sidebarOpen"
                class="hidden lg:flex p-1.5 rounded-md text-slate-400 hover:text-um-blue hover:bg-blue-50 transition-colors">
            <i class="fa-solid fa-bars-staggered text-sm"></i>
        </button>

        {{-- Close button (Mobile only) --}}
        <button @click="sidebarOpen = false"
                class="lg:hidden p-1.5 rounded-md text-slate-400 hover:text-red-500 hover:bg-red-50">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    {{-- Menu Navigation --}}
    <nav class="h-[calc(100vh-4rem-4rem)] overflow-y-auto px-3 py-4 space-y-6 custom-scrollbar">

        {{-- Section: Utama --}}
        <div class="space-y-1">
            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                      {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-um-blue shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-house w-5 text-center transition-transform group-hover:scale-110
                          {{ request()->routeIs('dashboard') ? 'text-um-blue' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">Dashboard</span>
            </a>

            {{-- Biodata --}}
            <a href="{{ route('dashboard.biodata') }}"
               class="group flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                      {{ request()->routeIs('dashboard.biodata') ? 'bg-blue-50 text-um-blue shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-user-gear w-5 text-center transition-transform group-hover:scale-110
                              {{ request()->routeIs('dashboard.biodata') ? 'text-um-blue' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                    <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">Biodata</span>
                </div>
                @if($biodataNeedsBadge)
                    <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0" :class="!sidebarOpen && 'lg:hidden'" title="Perlu dilengkapi"></span>
                @endif
            </a>
        </div>

        {{-- Section: Layanan --}}
        <div>
            <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 transition-opacity duration-200"
                 :class="!sidebarOpen && 'lg:hidden'">
                Layanan
            </div>
            <div class="space-y-1">
                {{-- Surat Rekomendasi --}}
                <a href="{{ route('dashboard.ept') }}"
                   class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                          {{ request()->routeIs('dashboard.ept') ? 'bg-blue-50 text-um-blue shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <i class="fa-solid fa-file-signature w-5 text-center transition-transform group-hover:scale-110
                              {{ request()->routeIs('dashboard.ept') ? 'text-um-blue' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                    <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">Surat Rekomendasi</span>
                </a>

                {{-- Terjemahan --}}
                <a href="{{ route('dashboard.translation') }}"
                   class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                          {{ request()->routeIs('dashboard.translation*') ? 'bg-blue-50 text-um-blue shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <i class="fa-solid fa-language w-5 text-center transition-transform group-hover:scale-110
                              {{ request()->routeIs('dashboard.translation*') ? 'text-um-blue' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                    <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">Terjemahan Abstrak</span>
                </a>

                {{-- Pendaftaran EPT --}}
                @if($showEptMenu)
                    <a href="{{ route('dashboard.ept-registration.index') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                              {{ request()->routeIs('dashboard.ept-registration*') ? 'bg-blue-50 text-um-blue shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <i class="fa-solid fa-clipboard-list w-5 text-center transition-transform group-hover:scale-110
                                  {{ request()->routeIs('dashboard.ept-registration*') ? 'text-um-blue' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                        <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">Pendaftaran EPT</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Section: Basic Listening (hanya untuk tahun 2025+, bukan S2, dan sudah isi biodata) --}}
        @php
            $showBasicListeningMenu = $hasBasicInfo && ((int)($u->year ?? 0) >= 2025) && !$isS2;
        @endphp
        @if($showBasicListeningMenu)
        <div>
            <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 transition-opacity duration-200"
                 :class="!sidebarOpen && 'lg:hidden'">
                Basic Listening
            </div>
            <div class="space-y-1">
                {{-- Daftar Sesi --}}
                <a href="{{ route('bl.index') }}"
                   class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                          {{ request()->routeIs('bl.index') ? 'bg-blue-50 text-um-blue shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <i class="fa-solid fa-headphones w-5 text-center transition-transform group-hover:scale-110
                              {{ request()->routeIs('bl.index') ? 'text-um-blue' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                    <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">Daftar Sesi</span>
                </a>

                {{-- Riwayat Quiz --}}
                <a href="{{ route('bl.history') }}"
                   class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                          {{ request()->routeIs('bl.history*') ? 'bg-blue-50 text-um-blue shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <i class="fa-solid fa-clock-rotate-left w-5 text-center transition-transform group-hover:scale-110
                              {{ request()->routeIs('bl.history*') ? 'text-um-blue' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                    <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">Riwayat Quiz</span>
                </a>

                {{-- Jadwal --}}
                <a href="{{ route('bl.schedule') }}"
                   class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                          {{ request()->routeIs('bl.schedule') ? 'bg-blue-50 text-um-blue shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <i class="fa-solid fa-calendar-days w-5 text-center transition-transform group-hover:scale-110
                              {{ request()->routeIs('bl.schedule') ? 'text-um-blue' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                    <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">Jadwal</span>
                </a>
            </div>
        </div>
        @endif

        {{-- Section: Admin (Conditional) --}}
        @if ($u && $u->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga', 'Penerjemah']))
            <div>
                <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 transition-opacity duration-200"
                     :class="!sidebarOpen && 'lg:hidden'">
                    Administrator
                </div>
                <div class="space-y-1">
                    <a href="{{ route('filament.admin.pages.2') }}"
                       class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                              text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                        <i class="fa-solid fa-screwdriver-wrench w-5 text-center transition-transform group-hover:scale-110 text-slate-400 group-hover:text-slate-600"></i>
                        <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">Panel Admin</span>
                    </a>
                </div>
            </div>
        @endif

        {{-- Section: EPT Online (hanya jika ada token aktif atau user punya attempt) --}}
        @php
            $showEptOnline = $u ? App\Models\EptOnlineAccessToken::query()
                ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $u->id))
                ->where('is_active', true)
                ->whereNull('revoked_at')
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->exists() || \App\Models\EptOnlineAttempt::where('user_id', $u->id)->exists() : false;
        @endphp
        @if($showEptOnline)
        <div class="pt-2">
            <a href="{{ route('ept-online.index') }}"
               title="EPT Online (Beta) — start or continue the online test session"
               class="group flex items-center justify-between gap-3 rounded-lg border border-dashed px-3 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('ept-online*') ? 'border-amber-200 bg-amber-50 text-amber-700 shadow-sm ring-1 ring-amber-100' : 'border-slate-200 text-slate-600 hover:border-amber-200 hover:bg-amber-50/70 hover:text-slate-900' }}">
                <div class="flex items-center gap-3 min-w-0">
                    <i class="fa-solid fa-laptop-file w-5 shrink-0 text-center transition-transform group-hover:scale-110 {{ request()->routeIs('ept-online*') ? 'text-amber-600' : 'text-slate-400 group-hover:text-amber-600' }}"></i>
                    <span :class="!sidebarOpen && 'lg:hidden'" class="truncate whitespace-nowrap">EPT Online</span>
                </div>
                <span :class="!sidebarOpen && 'lg:hidden'" class="inline-flex shrink-0 items-center rounded-full border border-amber-200 bg-amber-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-[0.18em] text-amber-700">
                    Beta
                </span>
            </a>
        </div>
        @endif
    </nav>

    {{-- Footer User Profile --}}
    <div class="absolute bottom-0 left-0 w-full border-t border-slate-100 bg-white p-3">
        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer group">
            <div class="relative shrink-0">
                <div class="w-9 h-9 rounded-full bg-blue-100 text-um-blue flex items-center justify-center font-bold text-sm">
                   {{ substr($u?->name ?? 'U', 0, 1) }}
                </div>
                <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white rounded-full"></div>
            </div>

            <div class="overflow-hidden" :class="!sidebarOpen && 'lg:hidden'">
                <div class="text-sm font-semibold text-slate-700 truncate group-hover:text-um-blue">
                    {{ $u?->name }}
                </div>
                <div class="text-xs text-slate-500 truncate">
                     @if($u && $u->getRoleNames()->isNotEmpty())
                        {{ $u->getRoleNames()->first() }}
                    @else
                        Pengguna
                    @endif
                </div>
            </div>
        </div>
    </div>
</aside>
