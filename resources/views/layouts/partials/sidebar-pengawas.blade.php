{{-- resources/views/layouts/partials/sidebar-pengawas.blade.php --}}
@php
    $u = auth()->user();
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
        <button @click="toggleSidebar()"
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
            {{-- Dashboard Pengawas --}}
            <a href="{{ route('dashboard.pengawas-ept') }}"
               class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                      {{ request()->routeIs('dashboard.pengawas-ept') ? 'bg-blue-50 text-um-blue shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-user-shield w-5 text-center transition-transform group-hover:scale-110
                          {{ request()->routeIs('dashboard.pengawas-ept') ? 'text-um-blue' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">Dashboard Pengawas</span>
            </a>
        </div>

        {{-- Section: Sistem --}}
        <div class="pt-2">
            <a href="{{ route('ept-online.index') }}"
               title="EPT Online — mulai atau lanjutkan sesi tes"
               class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                      {{ request()->routeIs('ept-online*') ? 'bg-blue-50 text-um-blue shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-laptop-file w-5 text-center transition-transform group-hover:scale-110
                          {{ request()->routeIs('ept-online*') ? 'text-um-blue' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">EPT Online</span>
            </a>
        </div>

        {{-- Section: Home --}}
        <div class="pt-2">
            <a href="{{ url('/') }}"
               class="group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                <i class="fa-solid fa-house w-5 text-center transition-transform group-hover:scale-110 text-slate-400 group-hover:text-slate-600"></i>
                <span :class="!sidebarOpen && 'lg:hidden'" class="whitespace-nowrap">Beranda</span>
            </a>
        </div>
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
                    Pengawas EPT
                </div>
            </div>
        </div>
    </div>
</aside>
