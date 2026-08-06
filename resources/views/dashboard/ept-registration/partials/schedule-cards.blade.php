{{-- Schedule Cards --}}
<div class="grid gap-4">
    @php
        $singleAttempt = $registration->requiredGroupCount() === 1;
        $grups = array_slice([
            ['num' => 1, 'grup' => $registration->grup1, 'label' => $singleAttempt ? 'Tes EPT' : 'Tes Pertama'],
            ['num' => 2, 'grup' => $registration->grup2, 'label' => 'Tes Kedua'],
            ['num' => 3, 'grup' => $registration->grup3, 'label' => 'Tes Ketiga'],
            ['num' => 4, 'grup' => $registration->grup4, 'label' => 'Tes Keempat'],
        ], 0, $registration->requiredGroupCount());
    @endphp

    @foreach($grups as $item)
        @php $grup = $item['grup']; $num = $item['num']; @endphp
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4 p-4 border-b border-slate-100">
                <div class="w-12 h-12 {{ $grup?->jadwal ? 'bg-gradient-to-br from-blue-600 to-blue-700' : 'bg-slate-200' }} rounded-xl flex items-center justify-center text-white font-bold text-xl shrink-0">
                    {{ $num }}
                </div>
                <div class="min-w-0 flex-1">
                    @if($grup)
                        <h3 class="font-bold text-slate-900">Grup {{ $grup->name }}</h3>
                        <p class="text-xs text-slate-400">{{ $item['label'] }}</p>
                    @else
                        <p class="text-slate-400 text-sm">Belum ditentukan</p>
                    @endif
                </div>
            </div>

            <div class="p-4">
                @if($grup)
                    @if($grup->jadwal)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                            <div class="flex items-center gap-2 text-slate-600">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $grup->jadwal->translatedFormat('l, d M Y') }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-600">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>{{ $grup->jadwal->format('H:i') }} WIB</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-600">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>{{ $grup->lokasi }}</span>
                            </div>
                        </div>
                        <button type="button"
                                @click="downloadUrl = '{{ route('dashboard.ept-registration.kartu', ['jadwal' => $num]) }}'; showModal = true"
                                class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold shadow-sm transition-all">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download Kartu Peserta
                        </button>
                    @else
                        <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-lg text-amber-700">
                            <svg class="w-5 h-5 animate-pulse shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Menunggu penetapan jadwal dari admin</span>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- Download Modal --}}
<template x-teleport="body">
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @click.self="showModal = false">
        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="bg-gradient-to-r from-amber-500 to-orange-500 p-5 text-white">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">Informasi Penting</h3>
                        <p class="text-amber-100 text-sm">Harap perhatikan sebelum download</p>
                    </div>
                </div>
            </div>

            <div class="p-5">
                <ul class="space-y-3">
                    <li class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">Print Kartu Peserta</p>
                            <p class="text-sm text-slate-500">Bawa saat ujian untuk verifikasi</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">Bawa Identitas</p>
                            <p class="text-sm text-slate-500">KTP atau Kartu Mahasiswa</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">Hadir Tepat Waktu</p>
                            <p class="text-sm text-slate-500">15 menit sebelum jadwal dimulai</p>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="p-5 pt-0 flex gap-3">
                <button @click="showModal = false"
                        class="flex-1 px-4 py-3 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition">
                    Batal
                </button>
                <a :href="downloadUrl"
                   @click="showModal = false"
                   class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download
                </a>
            </div>
        </div>
    </div>
</template>
