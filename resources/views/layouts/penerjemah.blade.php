<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Dashboard') - UPT Bahasa UM Metro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    {{-- Google Fonts: Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'um-blue': '#1e40af',
                        'um-dark-blue': '#1e3a8a',
                        'um-green': '#059669',
                        'um-gold': '#f59e0b',
                    }
                }
            }
        }
    </script>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}">

    {{-- Alpine.js --}}
    <script src="{{ asset('vendor/alpine/alpine.min.js') }}" defer></script>

    {{-- Trix Editor --}}
    <link rel="stylesheet" href="{{ asset('vendor/trix/trix.css') }}">
    <script src="{{ asset('vendor/trix/trix.umd.min.js') }}" defer></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Trix styling for large fonts */
        trix-toolbar {
            border-radius: 0.75rem;
            border-color: rgb(226 232 240);
        }
        trix-editor {
            min-height: 200px;
            border-radius: 0.75rem;
            border-color: rgb(203 213 225);
            padding: 0.75rem 0.875rem;
            font-size: 1rem;
            line-height: 1.75rem;
            background-color: #ffffff;
        }
        trix-editor:focus {
            outline: none;
            box-shadow: 0 0 0 1px rgb(37 99 235 / 0.6);
            border-color: rgb(37 99 235);
        }
        /* Sembunyikan tombol attachment */
        trix-toolbar .trix-button-group--file-tools {
            display: none !important;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased">

@php
    $user = auth()->user();
@endphp

<div class="min-h-screen flex flex-col">

    {{-- SIMPLE HEADER --}}
    <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                {{-- Logo & Title --}}
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-um.png') }}" class="h-10 w-10 object-contain" alt="Logo">
                    <div>
                        <div class="font-bold text-lg text-slate-900 leading-tight">UPT Bahasa</div>
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Penerjemah</div>
                    </div>
                </div>

                {{-- Simple Nav --}}
                @php
                    $pendingCount = \App\Models\Penerjemahan::where('translator_id', $user->id)
                        ->whereIn('status', ['Disetujui', 'Diproses'])
                        ->where(function($q) {
                            $q->whereNull('translated_text')->orWhere('translated_text', '');
                        })
                        ->count();
                @endphp
                <nav class="flex items-center gap-2">
                    <a href="{{ route('dashboard.penerjemah') }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-base font-semibold transition-colors
                              {{ request()->routeIs('dashboard.penerjemah') && !request()->routeIs('dashboard.penerjemah.tugas*') && !request()->routeIs('dashboard.penerjemah.edit*') 
                                 ? 'bg-indigo-100 text-indigo-700' 
                                 : 'text-slate-600 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-home"></i>
                        <span class="hidden sm:inline">Beranda</span>
                    </a>
                    
                    <a href="{{ route('dashboard.penerjemah.tugas') }}" 
                       class="relative inline-flex items-center gap-2 px-4 py-2 rounded-xl text-base font-semibold transition-colors
                              {{ request()->routeIs('dashboard.penerjemah.tugas*') || request()->routeIs('dashboard.penerjemah.edit*')
                                 ? 'bg-indigo-100 text-indigo-700' 
                                 : 'text-slate-600 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-list-check"></i>
                        <span class="hidden sm:inline">Tugas</span>
                        @if($pendingCount > 0)
                            <span class="absolute -top-1 -right-1 min-w-[22px] h-[22px] flex items-center justify-center px-1.5 rounded-full bg-red-500 text-white text-xs font-bold shadow-lg">
                                {{ $pendingCount > 99 ? '99+' : $pendingCount }}
                            </span>
                        @endif
                    </a>
                </nav>

                {{-- User Menu --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" 
                            class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors">
                        <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-base">
                            {{ substr($user->name ?? 'U', 0, 1) }}
                        </div>
                        <span class="hidden md:block text-base font-medium text-slate-700">{{ $user->name }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                    </button>

                    {{-- Dropdown --}}
                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition
                         x-cloak
                         class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200 py-2 z-50">
                        <div class="px-4 py-2 border-b border-slate-100">
                            <div class="text-base font-semibold text-slate-800">{{ $user->name }}</div>
                            <div class="text-sm text-slate-500">Penerjemah</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" 
                                    class="w-full text-left px-4 py-3 text-base font-medium text-red-600 hover:bg-red-50 flex items-center gap-3">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- MAIN CONTENT --}}
    <main class="flex-1 py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @yield('content')
        </div>
    </main>

    {{-- SIMPLE FOOTER --}}
    <footer class="bg-white border-t border-slate-200 py-4">
        <div class="max-w-5xl mx-auto px-4 text-center text-sm text-slate-500">
            &copy; {{ date('Y') }} UPT Bahasa UM Metro
        </div>
    </footer>

</div>

</body>
</html>
