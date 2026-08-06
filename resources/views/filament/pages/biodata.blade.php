<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Profile Header Card -->
        <x-filament::section>
            <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                <!-- Profile Picture -->
                <div class="flex-shrink-0">
                    @if($data['image'] ?? false)
                        <img src="{{ $user->image ? asset('storage/' . $user->image) : asset('images/default-user.png') }}"
                             class="w-32 h-32 rounded-full object-cover ring-4 ring-gray-100 dark:ring-gray-700"
                             alt="Profile Picture">
                    @else
                        <div class="w-32 h-32 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center ring-4 ring-gray-100 dark:ring-gray-700">
                            <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- User Information -->
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $data['name'] ?? 'Nama Belum Diisi' }}
                    </h2>
                    
                    <div class="space-y-1 text-gray-600 dark:text-gray-400">
                        <p class="flex items-center justify-center md:justify-start gap-2">
                            <x-filament::icon icon="heroicon-o-identification" class="w-5 h-5" />
                            <span>NPM: {{ $data['srn'] ?? '-' }}</span>
                        </p>
                        <p class="flex items-center justify-center md:justify-start gap-2">
                            <x-filament::icon icon="heroicon-o-building-library" class="w-5 h-5" />
                            <span>{{ $user->prody->name ?? 'Prodi Belum Dipilih' }}</span>
                        </p>
                        <p class="flex items-center justify-center md:justify-start gap-2">
                            <x-filament::icon icon="heroicon-o-calendar" class="w-5 h-5" />
                            <span>Angkatan {{ $data['year'] ?? '-' }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </x-filament::section>
        <!-- Password Section -->
        <div class="md:col-span-2 bg-white rounded-lg shadow p-6 dark:bg-gray-800 space-y-4">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 text-center">Informasi Data Akun Anda</h3>

            <div class="">
                {{ $this->form }}
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" wire:click="edit"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                Simpan Perubahan
            </button>
        </div>
    </div>
</x-filament-panels::page>