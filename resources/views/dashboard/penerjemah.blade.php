{{-- resources/views/dashboard/penerjemah.blade.php --}}
@extends('layouts.penerjemah')

@section('title', 'Dashboard Penerjemah')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    
    $avatarUrl = $user->image
        ? Storage::url($user->image)
        : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=EBF4FF&color=1E40AF&bold=true&size=128';
@endphp

<div class="max-w-5xl mx-auto space-y-8">

    {{-- GREETING CARD --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 relative overflow-hidden">
        {{-- Decorative Background --}}
        <div class="absolute right-0 top-0 -mt-4 -mr-4 w-40 h-40 bg-gradient-to-br from-indigo-50 to-purple-100 rounded-full blur-2xl opacity-60 pointer-events-none"></div>
        
        <div class="relative z-10 flex items-center gap-6">
            <img src="{{ $avatarUrl }}" 
                 alt="Foto Profil" 
                 class="h-20 w-20 rounded-full object-cover border-4 border-white shadow-lg shrink-0">
            
            <div>
                <p class="text-lg text-slate-500 font-medium">Selamat Datang,</p>
                <h1 class="text-3xl font-bold text-slate-900">{{ $user->name }}</h1>
                <p class="text-base text-indigo-600 font-medium mt-1">
                    <i class="fa-solid fa-language mr-2"></i> Penerjemah
                </p>
            </div>
        </div>
    </div>

    {{-- STATISTIK TUGAS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Total Tugas --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-file-lines text-2xl text-indigo-600"></i>
            </div>
            <p class="text-4xl font-bold text-slate-900">{{ $totalTugas }}</p>
            <p class="text-lg text-slate-600 font-medium mt-1">Total Tugas</p>
        </div>

        {{-- Dalam Proses --}}
        <div class="bg-amber-50 rounded-2xl border border-amber-200 shadow-sm p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-hourglass-half text-2xl text-amber-600"></i>
            </div>
            <p class="text-4xl font-bold text-amber-700">{{ $dalamProses }}</p>
            <p class="text-lg text-amber-700 font-medium mt-1">Dalam Proses</p>
        </div>

        {{-- Selesai --}}
        <div class="bg-emerald-50 rounded-2xl border border-emerald-200 shadow-sm p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-check-circle text-2xl text-emerald-600"></i>
            </div>
            <p class="text-4xl font-bold text-emerald-700">{{ $selesai }}</p>
            <p class="text-lg text-emerald-700 font-medium mt-1">Selesai</p>
        </div>
    </div>

    {{-- TUGAS TERKINI --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Tugas Terkini</h2>
                    <p class="text-base text-slate-500">Tugas, draft, dan yang sudah selesai</p>
                </div>
                <a href="{{ route('dashboard.penerjemah.tugas') }}"
                   class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-200 text-slate-700 font-bold text-base hover:bg-slate-300 transition-colors">
                    <i class="fa-solid fa-list"></i>
                    Lihat Semua
                </a>
            </div>

            {{-- Tab buttons --}}
            <div class="flex gap-2 mt-4">
                <button type="button" data-tab="tugas" class="tab-btn active inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold bg-indigo-600 text-white">
                    <i class="fa-solid fa-briefcase"></i> Tugas
                </button>
                <button type="button" data-tab="draft" class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                    <i class="fa-solid fa-floppy-disk"></i> Draft
                </button>
                <button type="button" data-tab="selesai" class="tab-btn inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                    <i class="fa-solid fa-check-circle"></i> Selesai
                </button>
            </div>
        </div>

        {{-- Tab: Tugas (belum dikerjakan) --}}
        <div id="tab-tugas" class="tab-panel">
            @forelse($tugas as $item)
                @include('dashboard.partials.tugas-item', ['item' => $item])
            @empty
                <div class="p-12 text-center">
                    <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-briefcase text-3xl text-slate-400"></i>
                    </div>
                    <p class="text-xl font-medium text-slate-600">Tidak ada tugas yang perlu dikerjakan</p>
                    <p class="text-base text-slate-500 mt-2">Semua tugas sudah memiliki draft atau selesai</p>
                </div>
            @endforelse
        </div>

        {{-- Tab: Draft --}}
        <div id="tab-draft" class="tab-panel" style="display:none;">
            @forelse($draft as $item)
                @include('dashboard.partials.tugas-item', ['item' => $item])
            @empty
                <div class="p-12 text-center">
                    <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-floppy-disk text-3xl text-slate-400"></i>
                    </div>
                    <p class="text-xl font-medium text-slate-600">Belum ada draft</p>
                    <p class="text-base text-slate-500 mt-2">Draft muncul setelah Anda menyimpan hasil terjemahan</p>
                </div>
            @endforelse
        </div>

        {{-- Tab: Selesai --}}
        <div id="tab-selesai" class="tab-panel" style="display:none;">
            @forelse($selesaiList as $item)
                @include('dashboard.partials.tugas-item', ['item' => $item])
            @empty
                <div class="p-12 text-center">
                    <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-check-circle text-3xl text-slate-400"></i>
                    </div>
                    <p class="text-xl font-medium text-slate-600">Belum ada tugas selesai</p>
                </div>
            @endforelse
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.tab-btn').forEach(function (b) {
                    b.classList.remove('bg-indigo-600', 'text-white');
                    b.classList.add('bg-slate-100', 'text-slate-600');
                });
                this.classList.remove('bg-slate-100', 'text-slate-600');
                this.classList.add('bg-indigo-600', 'text-white');

                document.querySelectorAll('.tab-panel').forEach(function (p) {
                    p.style.display = 'none';
                });
                document.getElementById('tab-' + this.dataset.tab).style.display = 'block';
            });
        });
    </script>

    {{-- BANTUAN --}}
    <div class="bg-blue-50 rounded-2xl border border-blue-200 p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-circle-info text-xl text-blue-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-blue-900">Butuh Bantuan?</h3>
                <p class="text-base text-blue-700 mt-1">
                    Jika ada kendala atau pertanyaan terkait tugas penerjemahan, silakan hubungi admin di 
                    <a href="https://wa.me/6281234567890" class="font-bold underline">WhatsApp</a>.
                </p>
            </div>
        </div>
    </div>

</div>
@endsection
