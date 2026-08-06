<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Dashboard') - UPT Bahasa UM Metro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    {{-- Google Fonts: Inter --}}

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

    {{-- Global utility --}}
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    {{-- Skala proporsional untuk layar 1024-1366px (monitor 720p / laptop kecil) --}}
    <style>
        @media (min-width: 1024px) and (max-width: 1366px) {
            html { font-size: 15px; }
        }
    </style>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/inter/inter.css') }}">

    {{-- Alpine.js --}}
    <script src="{{ asset('vendor/alpine/alpine.min.js') }}" defer></script>

    {{-- Trix Editor (untuk richtext di dashboard) --}}
    <link rel="stylesheet" href="{{ asset('vendor/trix/trix.css') }}">
    <script src="{{ asset('vendor/trix/trix.umd.min.js') }}" defer></script>

    <style>
        /* Styling Trix supaya nyatu dengan tema dashboard */
        trix-toolbar {
            border-radius: 0.75rem; /* rounded-xl */
            border-color: rgb(226 232 240); /* slate-200 */
        }

        trix-editor {
            min-height: 200px;
            border-radius: 0.75rem;          /* rounded-xl */
            border-color: rgb(203 213 225);  /* slate-300 */
            padding: 0.75rem 0.875rem;
            font-size: 0.875rem;             /* text-sm */
            line-height: 1.5rem;             /* leading-relaxed */
            background-color: #ffffff;
        }

        trix-editor:focus {
            outline: none;
            box-shadow: 0 0 0 1px rgb(37 99 235 / 0.6); /* mirip ring um-blue */
            border-color: rgb(37 99 235);
        }

        trix-editor .attachment__caption {
            font-size: 0.75rem;
        }

        /* Sembunyikan tombol attachment */
        trix-toolbar .trix-button-group--file-tools {
            display: none !important;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased">

{{-- 
    x-data logic:
    sidebarOpen: true secara default di layar besar (lg), false di mobile.
--}}
<div x-data="{ 
        sidebarOpen: window.innerWidth >= 1024, 
        sidebarHover: false,
        isMobile: window.innerWidth < 1024,
        swipeStartX: null,
        swipeStartY: null,
        startEdgeSwipe(event) {
            if (!this.isMobile || this.sidebarOpen) return;
            const touch = event.changedTouches?.[0];
            if (!touch) return;

            this.swipeStartX = touch.clientX;
            this.swipeStartY = touch.clientY;
        },
        openSidebarFromEdgeSwipe(event) {
            if (!this.isMobile || this.sidebarOpen || this.swipeStartX === null || this.swipeStartY === null) return;
            const touch = event.changedTouches?.[0];
            if (!touch) return;

            const dx = touch.clientX - this.swipeStartX;
            const dy = Math.abs(touch.clientY - this.swipeStartY);

            if (dx > 70 && dy < 45) {
                this.sidebarOpen = true;
            }

            this.swipeStartX = null;
            this.swipeStartY = null;
        }
     }"
     @resize.window="isMobile = window.innerWidth < 1024; if(!isMobile) sidebarOpen = true"
     class="min-h-screen flex overflow-hidden bg-slate-50">

    {{-- SIDEBAR --}}
    @include('layouts.partials.sidebar')

    {{-- Edge swipe area (mobile) --}}
    <div
        x-show="isMobile && !sidebarOpen"
        @touchstart="startEdgeSwipe($event)"
        @touchend="openSidebarFromEdgeSwipe($event)"
        class="fixed inset-y-0 left-0 z-30 w-6 lg:hidden"
        x-cloak
    ></div>

    {{-- MAIN CONTENT WRAPPER --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden transition-all duration-300 ease-in-out"
         :class="(!isMobile && sidebarOpen) ? 'lg:ml-64' : 'lg:ml-20'">
        
        {{-- TOP BAR --}}
        @include('layouts.partials.topbar')

        {{-- CONTENT SCROLLABLE AREA --}}
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 scroll-smooth">
            <div class="max-w-7xl mx-auto">
                @yield('content')
            </div>
        </main>
    </div>

    {{-- MOBILE BACKDROP (Overlay) --}}
    <div x-show="isMobile && sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 lg:hidden"
         x-cloak>
    </div>

</div>

</body>
</html>
