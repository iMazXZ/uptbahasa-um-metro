{{-- resources/views/partials/status-reminder-modal.blade.php --}}
{{-- Modal pengingat pantau status setelah pengajuan berhasil --}}
@if(session('status_reminder'))
<div x-data="{ open: true }" x-cloak>
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
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative overflow-hidden">

            {{-- Header --}}
            <div class="bg-gradient-to-r from-um-blue to-um-dark-blue px-6 py-6 text-center relative">
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>
                <div class="relative">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white/20 mb-3">
                        <i class="fa-solid fa-circle-check text-3xl text-emerald-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">Pengajuan Berhasil Dikirim</h3>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5">
                <p class="text-sm text-slate-600 leading-relaxed">
                    Pengajuan Anda telah diterima dan sedang diproses. Pantau perkembangannya di halaman ini.
                </p>
            </div>

            {{-- Footer --}}
            <div class="px-6 pb-6">
                <button @click="open = false"
                        class="w-full py-3 px-4 rounded-xl bg-um-blue text-white text-sm font-bold hover:bg-um-dark-blue active:scale-[0.99] transition">
                    Mengerti
                </button>
            </div>
        </div>
    </div>
</div>
@endif
