{{-- resources/views/filament/pages/tutor-mahasiswa-bulk-input.blade.php --}}
<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white/60 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800/60">
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Pilih mahasiswa lalu isi nilai sekaligus.</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Attempt terbaru tampil sebagai placeholder.</div>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="rounded-full bg-primary-50 px-3 py-1 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                    Mahasiswa terpilih: <strong>{{ $this->selectedCount }}</strong>
                </span>
            </div>
        </div>

        {{-- Progress Bar --}}
        @if ($this->isProcessing)
            <div class="rounded-lg border border-primary-200 bg-primary-50 p-4 dark:border-primary-800 dark:bg-primary-900/30">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                        {{ $this->progressMessage }}
                    </span>
                    <span class="text-sm font-bold text-primary-700 dark:text-primary-300">
                        {{ $this->progress }}%
                    </span>
                </div>
                <div class="w-full bg-primary-200 rounded-full h-3 dark:bg-primary-800 overflow-hidden">
                    <div 
                        class="bg-primary-600 h-3 rounded-full transition-all duration-300 ease-out"
                        style="width: {{ $this->progress }}%"
                    ></div>
                </div>
            </div>
        @endif

        <form wire:submit.prevent="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end gap-3">
                @if ($this->showDownloadButton && $this->downloadUrl)
                    <x-filament::button tag="a" href="{{ $this->downloadUrl }}" target="_blank" icon="heroicon-o-arrow-down-tray" color="gray">
                        Download Excel
                    </x-filament::button>
                @endif
                <x-filament::button 
                    type="submit" 
                    icon="heroicon-o-check-circle"
                    :disabled="$this->isProcessing"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="save">Simpan Nilai</span>
                    <span wire:loading wire:target="save">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menyimpan...
                    </span>
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
