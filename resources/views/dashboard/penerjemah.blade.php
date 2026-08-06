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
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Tugas Terkini</h2>
                <p class="text-base text-slate-500">5 tugas terbaru Anda</p>
            </div>
            <a href="{{ route('dashboard.penerjemah.tugas') }}" 
               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-200 text-slate-700 font-bold text-base hover:bg-slate-300 transition-colors">
                <i class="fa-solid fa-list"></i>
                Lihat Semua
            </a>
        </div>

        @if($tugasTerkini->isEmpty())
            <div class="p-12 text-center">
                <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-inbox text-3xl text-slate-400"></i>
                </div>
                <p class="text-xl font-medium text-slate-600">Belum ada tugas penerjemahan</p>
                <p class="text-base text-slate-500 mt-2">Tugas akan muncul ketika admin menugaskan Anda</p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($tugasTerkini as $item)
                    @php
                        $statusColor = match($item->status) {
                            'Disetujui' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'Diproses' => 'bg-blue-100 text-blue-700 border-blue-200',
                            'Selesai' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            default => 'bg-slate-100 text-slate-700 border-slate-200'
                        };
                        $isUrgent = in_array($item->status, ['Disetujui', 'Diproses']);
                        $hasDraft = !empty($item->translated_text);
                        
                        // Hitung deadline hanya jika belum ada draft
                        $deadlineText = null;
                        $deadlineClass = null;
                        if ($isUrgent && !$hasDraft && $item->updated_at) {
                            $deadline = $item->updated_at->addDays(3);
                            $now = now();
                            $diffHours = (int) $now->diffInHours($deadline, false);
                            $diffDays = (int) floor($diffHours / 24);
                            
                            if ($diffHours < 0) {
                                $telatHours = abs($diffHours);
                                $telatDays = (int) ceil($telatHours / 24);
                                if ($telatDays == 1) {
                                    $deadlineText = 'Terlambat 1 hari, mohon diselesaikan 🙏';
                                } else {
                                    $deadlineText = 'Terlambat ' . $telatDays . ' hari, mohon diselesaikan 🙏';
                                }
                                $deadlineClass = 'bg-rose-100 text-rose-700 border-rose-200';
                            } elseif ($diffHours <= 24) {
                                $deadlineText = 'Sisa waktu ' . $diffHours . ' jam';
                                $deadlineClass = 'bg-orange-100 text-orange-700 border-orange-200';
                            } elseif ($diffDays == 1) {
                                $deadlineText = 'Sisa waktu 1 hari';
                                $deadlineClass = 'bg-amber-100 text-amber-700 border-amber-200';
                            } else {
                                $deadlineText = 'Sisa waktu ' . $diffDays . ' hari';
                                $deadlineClass = 'bg-sky-100 text-sky-700 border-sky-200';
                            }
                        }
                    @endphp
                    <div class="p-6 hover:bg-slate-50 transition-colors {{ $isUrgent && !$hasDraft ? 'bg-amber-50/30' : '' }}">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    @if($hasDraft && $item->status !== 'Selesai')
                                        {{-- Sudah ada draft, menunggu verifikasi --}}
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-sm font-bold border bg-purple-100 text-purple-700 border-purple-200">
                                            <i class="fa-solid fa-hourglass-half"></i>
                                            Menunggu Verifikasi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold border {{ $statusColor }}">
                                            {{ $item->status }}
                                        </span>
                                        @if($deadlineText)
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-sm font-medium border {{ $deadlineClass }}">
                                                <i class="fa-solid fa-clock"></i>
                                                {{ $deadlineText }}
                                            </span>
                                        @endif
                                    @endif
                                </div>
                                <p class="text-lg font-semibold text-slate-800 truncate">
                                    {{ $item->users->name ?? 'Pemohon' }}
                                </p>
                                <p class="text-base text-slate-500 mt-1">
                                    <i class="fa-solid fa-file-alt text-slate-400 mr-2"></i>
                                    {{ $item->source_word_count ?? 0 }} kata
                                </p>
                            </div>
                            
                            @if($isUrgent && !$hasDraft)
                                {{-- Belum ada draft - tombol Kerjakan --}}
                                <a href="{{ route('dashboard.penerjemah.edit', $item) }}" 
                                   class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 text-white font-bold text-base hover:bg-indigo-700 transition-colors shadow-lg shrink-0">
                                    <i class="fa-solid fa-pen"></i>
                                    Kerjakan
                                </a>
                            @else
                                {{-- Sudah ada draft atau selesai - tombol Lihat --}}
                                <a href="{{ route('dashboard.penerjemah.edit', $item) }}" 
                                   class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-slate-200 text-slate-700 font-medium text-base hover:bg-slate-300 transition-colors shrink-0">
                                    <i class="fa-solid fa-eye"></i>
                                    Lihat
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

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
