@php
    $topbarUser = auth()->user();
    $topbarNotifications = $topbarUser?->notifications()->limit(8)->get() ?? collect();
    $topbarUnreadCount = $topbarUser?->unreadNotifications()->count() ?? 0;
@endphp

<header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200 h-16 px-4 sm:px-6 lg:px-8 flex items-center justify-between transition-all duration-300">
    <div class="flex items-center gap-4">
        {{-- Mobile Toggle Button --}}
        <button
            @click="sidebarOpen = true"
            class="lg:hidden -ml-2 p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-um-blue"
        >
            <span class="sr-only">Open sidebar</span>
            <i class="fa-solid fa-bars text-xl"></i>
        </button>

        <h1 class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight">
            @yield('page-title', 'Dashboard')
        </h1>
    </div>

    <div class="flex items-center gap-4" x-data="{ notifOpen: false }">
        {{-- Tanggal (Hidden di HP kecil) --}}
        <div class="hidden md:flex flex-col items-end mr-2">
            <span class="text-xs font-semibold text-slate-700">{{ now()->translatedFormat('l') }}</span>
            <span class="text-[10px] text-slate-500">{{ now()->translatedFormat('d F Y') }}</span>
        </div>

        {{-- Divider --}}
        <div class="h-8 w-px bg-slate-200 hidden md:block"></div>

        <div class="relative">
            <button
                type="button"
                @click="notifOpen = !notifOpen"
                class="relative flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition"
            >
                <i class="fa-regular fa-bell text-sm"></i>
                @if($topbarUnreadCount > 0)
                    <span class="absolute -right-1 -top-1 min-w-[1.25rem] rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                        {{ $topbarUnreadCount > 9 ? '9+' : $topbarUnreadCount }}
                    </span>
                @endif
            </button>

            <div
                x-show="notifOpen"
                x-transition
                @click.outside="notifOpen = false"
                x-cloak
                class="absolute right-0 mt-3 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
            >
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <div>
                        <p class="text-sm font-bold text-slate-800">Notifikasi</p>
                        <p class="text-[11px] text-slate-500">{{ $topbarUnreadCount }} belum dibaca</p>
                    </div>
                    @if($topbarUnreadCount > 0)
                        <form method="POST" action="{{ route('dashboard.notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="text-[11px] font-semibold text-um-blue hover:underline">
                                Tandai semua
                            </button>
                        </form>
                    @endif
                </div>

                <div class="max-h-[24rem] overflow-y-auto">
                    @forelse($topbarNotifications as $notification)
                        @php
                            $data = $notification->data ?? [];
                            $isUnread = blank($notification->read_at);
                        @endphp
                        <a
                            href="{{ route('dashboard.notifications.open', $notification->id) }}"
                            class="flex gap-3 border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50 {{ $isUnread ? 'bg-blue-50/40' : '' }}"
                        >
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                                <i class="{{ $data['icon'] ?? 'fa-regular fa-bell' }}"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-800">{{ $data['title'] ?? 'Notifikasi Baru' }}</p>
                                    @if($isUnread)
                                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-um-blue"></span>
                                    @endif
                                </div>
                                <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $data['body'] ?? 'Ada pembaruan pada akun Anda.' }}</p>
                                <p class="mt-2 text-[11px] text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-slate-500">
                            Belum ada notifikasi dashboard.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Logout Button --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="group flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all duration-200 border border-red-100 hover:shadow-md">
                <span>Logout</span>
                <i class="fa-solid fa-arrow-right-from-bracket transition-transform group-hover:translate-x-1"></i>
            </button>
        </form>
    </div>
</header>
