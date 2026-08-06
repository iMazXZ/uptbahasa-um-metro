<x-filament-widgets::widget>
    <x-filament::card>
        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-1 mb-2">
            <x-heroicon-s-newspaper class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
            <span>Pengumuman Terbaru</span>
        </h2>

        <div class="mt-4 space-y-4">
            @forelse ($pengumumans as $pengumuman)
                <div class="p-4 bg-gray-100 rounded-lg dark:bg-gray-800">
                    <h3 class="font-bold text-md text-gray-900 dark:text-white">
                        {{ $pengumuman->judul }}
                    </h3>
                    <div class="mt-2 text-sm prose dark:prose-invert max-w-none">
                        {!! $pengumuman->isi !!}
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    Tidak ada pengumuman saat ini.
                </p>
            @endforelse
        </div>
    </x-filament::card>
</x-filament-widgets::widget>