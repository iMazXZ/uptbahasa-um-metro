{{-- resources/views/partials/status-reminder-modal.blade.php --}}
{{-- Modal pengingat pantau status setelah pengajuan berhasil --}}
@if(session('status_reminder'))
<div x-data="{ open: false, checked: false }"
     x-init="setTimeout(() => { open = true; setTimeout(() => checked = true, 350); }, 50)"
     x-cloak>
    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50"></div>

    {{-- Modal Content --}}
    <div x-show="open"
         x-transition:enter="ease-out duration-500"
         x-transition:enter-start="opacity-0 scale-75 translate-y-8"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90"
         x-transition:enter="ease-[cubic-bezier(0.34,1.56,0.64,1)]"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative overflow-hidden">

            {{-- Header --}}
            <div class="bg-gradient-to-r from-um-blue to-um-dark-blue px-6 py-7 text-center relative overflow-hidden">
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>

                {{-- Animasi lingkaran + centang --}}
                <div class="relative mx-auto mb-3 w-16 h-16">
                    {{-- Lingkaran berputar masuk --}}
                    <div x-show="open"
                         x-transition:enter="duration-500 ease-out"
                         x-transition:enter-start="scale-0 rotate-[-120deg] opacity-0"
                         x-transition:enter-end="scale-100 rotate-0 opacity-100"
                         class="absolute inset-0 rounded-full bg-white/20 border-2 border-white/40"></div>

                    {{-- Centang menggambar --}}
                    <svg x-show="checked"
                         x-transition:enter="duration-300 ease-out"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="absolute inset-0 w-16 h-16" viewBox="0 0 52 52" fill="none">
                        <circle cx="26" cy="26" r="24" class="fill-emerald-400 shadow-lg"/>
                        <path x-bind:style="checked ? 'stroke-dashoffset: 0' : ''"
                              d="M14 27 L23 36 L38 19"
                              stroke="white"
                              stroke-width="5"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              style="stroke-dasharray: 40; stroke-dashoffset: 40; transition: stroke-dashoffset 0.4s ease 0.15s;"/>
                    </svg>
                </div>

                <h3 class="relative text-lg font-bold text-white">Pengajuan Berhasil Dikirim</h3>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5">
                <p class="text-sm text-slate-600 leading-relaxed text-center">
                    Pengajuan Anda telah diterima dan sedang diproses. Pantau perkembangannya di halaman ini.
                </p>
            </div>

            {{-- Footer --}}
            <div class="px-6 pb-6">
                <button @click="open = false"
                        class="w-full py-3 px-4 rounded-xl bg-um-blue text-white text-sm font-bold hover:bg-um-dark-blue active:scale-[0.98] transition">
                    Mengerti
                </button>
            </div>
        </div>
    </div>
</div>
@endif
