{{-- resources/views/dashboard/penerjemah-tugas.blade.php --}}
@extends('layouts.penerjemah')

@section('title', 'Daftar Tugas')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Daftar Tugas Penerjemahan</h1>
            <p class="text-lg text-slate-500 mt-1">Kelola semua tugas penerjemahan Anda</p>
        </div>
        <a href="{{ route('dashboard.penerjemah') }}" 
           class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-200 text-slate-700 font-bold text-base hover:bg-slate-300 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali
        </a>
    </div>

    {{-- FILTER TABS --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-2">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.penerjemah.tugas', ['filter' => 'semua']) }}" 
               class="flex-1 md:flex-none px-6 py-3 rounded-xl text-center font-bold text-base transition-colors
                      {{ $filter === 'semua' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <i class="fa-solid fa-list mr-2"></i>
                Semua
            </a>
            <a href="{{ route('dashboard.penerjemah.tugas', ['filter' => 'belum']) }}" 
               class="flex-1 md:flex-none px-6 py-3 rounded-xl text-center font-bold text-base transition-colors
                      {{ $filter === 'belum' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <i class="fa-solid fa-clock mr-2"></i>
                Belum Dikerjakan
            </a>
            <a href="{{ route('dashboard.penerjemah.tugas', ['filter' => 'selesai']) }}" 
               class="flex-1 md:flex-none px-6 py-3 rounded-xl text-center font-bold text-base transition-colors
                      {{ $filter === 'selesai' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <i class="fa-solid fa-check mr-2"></i>
                Selesai
            </a>
        </div>
    </div>

    {{-- DAFTAR TUGAS --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($tugas->isEmpty())
            <div class="p-12 text-center">
                <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-inbox text-3xl text-slate-400"></i>
                </div>
                <p class="text-xl font-medium text-slate-600">Tidak ada tugas</p>
                <p class="text-base text-slate-500 mt-2">
                    @if($filter === 'belum')
                        Semua tugas sudah dikerjakan!
                    @elseif($filter === 'selesai')
                        Belum ada tugas yang selesai
                    @else
                        Belum ada tugas penerjemahan untuk Anda
                    @endif
                </p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($tugas as $item)
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
                                // Sudah lewat deadline - hitung berapa hari telat
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
                                    @if($item->completion_date)
                                        <span class="mx-2">•</span>
                                        <i class="fa-solid fa-calendar-check text-slate-400 mr-1"></i>
                                        Selesai: {{ $item->completion_date->translatedFormat('d M Y') }}
                                    @endif
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

            {{-- PAGINATION --}}
            @if($tugas->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $tugas->appends(['filter' => $filter])->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
