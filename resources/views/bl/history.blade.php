@extends('layouts.front')
@section('title', 'Riwayat Skor')

@section('content')
@php
  // Logic Statistik
  $totalAttempts    = $attempts->count();
  $completedAttempts = $attempts->whereNotNull('submitted_at')->count();
  $avgScore         = $attempts->whereNotNull('score')->avg('score');
  $avgScoreFormatted = $avgScore ? number_format($avgScore, 1) : '0';
@endphp

{{-- 1. HERO SECTION: Stats & Overview --}}
<div class="relative bg-slate-900 pt-8 pb-24 overflow-hidden">
  {{-- Background Effects --}}
  <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-indigo-900 opacity-90"></div>
  <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-blue-500 rounded-full blur-3xl opacity-20"></div>
  <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-indigo-400 rounded-full blur-3xl opacity-20"></div>

  <div class="relative max-w-4xl mx-auto px-4">
    {{-- Navigation Back (Top) --}}
    <div class="mb-6">
      <a href="{{ route('bl.index') }}" 
         class="inline-flex items-center gap-2 text-white/80 hover:text-white text-sm font-medium transition-colors group">
        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-white/20 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </div>
        Kembali ke Menu Utama
      </a>
    </div>

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Riwayat Pembelajaran</h1>
            <p class="text-blue-100 text-sm md:text-base max-w-lg leading-relaxed">
                Pantau perkembangan skor Basic Listening Anda dan tinjau kembali jawaban di setiap sesi.
            </p>
        </div>
    </div>

    {{-- Stats Grid (Floating) --}}
    <div class="grid grid-cols-3 gap-4">
        {{-- Card 1: Rata-rata --}}
        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-white flex flex-col items-center justify-center text-center">
            <span class="text-xs text-blue-200 uppercase tracking-wider font-semibold mb-1">Rata-rata Skor</span>
            <div class="text-3xl font-bold tracking-tight">{{ $avgScoreFormatted }}</div>
        </div>
        
        {{-- Card 2: Selesai --}}
        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-white flex flex-col items-center justify-center text-center">
            <span class="text-xs text-blue-200 uppercase tracking-wider font-semibold mb-1">Tersubmit</span>
            <div class="text-3xl font-bold tracking-tight">{{ $completedAttempts }}</div>
        </div>

        {{-- Card 3: Total --}}
        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-white flex flex-col items-center justify-center text-center">
            <span class="text-xs text-blue-200 uppercase tracking-wider font-semibold mb-1">Total Sesi</span>
            <div class="text-3xl font-bold tracking-tight">{{ $totalAttempts }}</div>
        </div>
    </div>
  </div>
</div>

{{-- 2. CONTENT SECTION --}}
<div class="bg-slate-50 min-h-screen -mt-12 relative z-10">
  <div class="max-w-4xl mx-auto px-4 py-6">

    {{-- Tips Box (Cleaner Design) --}}
    <div class="mb-6 bg-white border border-blue-100 shadow-sm rounded-xl p-4 flex items-start gap-4">
        <div class="shrink-0 mt-0.5 w-8 h-8 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
        </div>
        <div>
            <h4 class="text-sm font-bold text-slate-800">Info Detail Jawaban</h4>
            <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                Klik pada kartu sesi di bawah untuk melihat <strong>kunci jawaban</strong> dan pembahasan detail dari kuis yang telah Anda kerjakan.
            </p>
        </div>
    </div>

    {{-- Attempts List --}}
    <div class="space-y-3">
      @forelse($attempts as $attempt)
        @php
          $isUAS    = (int)($attempt->session->number) === 6;
          $hasScore = !is_null($attempt->score);
          $score    = (int) $attempt->score;
          
          // Color Logic
          $statusColor = $hasScore 
              ? ($score >= 70 ? 'emerald' : ($score >= 50 ? 'amber' : 'rose')) 
              : 'slate';
        @endphp

        <a href="{{ route('bl.history.show', $attempt) }}"
           class="group relative block bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-300 transition-all duration-200 overflow-hidden">
          
          {{-- Status Indicator Strip (Left) --}}
          <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-{{ $statusColor }}-500"></div>

          <div class="flex items-center justify-between p-4 pl-5">
            
            {{-- LEFT SIDE: Title & Meta (Expanded) --}}
            <div class="flex-1 min-w-0 pr-3">
                {{-- Info Sesi pindah ke sini (Kecil di atas judul) --}}
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">
                    {{ $isUAS ? 'Ujian Akhir Semester' : 'Meeting ' . $attempt->session->number }}
                </p>

                {{-- Judul (Bisa 2 baris/wrap, tidak truncate) --}}
                <h3 class="text-sm font-bold text-slate-900 leading-snug mb-1.5 group-hover:text-blue-600 transition-colors">
                    {{ $attempt->session->title }}
                </h3>

                {{-- Status Badge --}}
                <div class="flex items-center">
                    @if($hasScore)
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                            <i class="fa-solid fa-check"></i> Selesai
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                            <i class="fa-regular fa-clock"></i> Belum Submit
                        </span>
                    @endif
                </div>
            </div>

            {{-- RIGHT SIDE: Score & Arrow (Fixed Width, Anti-Shrink) --}}
            <div class="flex items-center gap-3 shrink-0">
                @if($hasScore)
                    <div class="text-right">
                        <div class="text-[9px] uppercase font-bold text-slate-400 mb-0.5">Skor</div>
                        <div class="text-xl font-black text-{{ $statusColor }}-600 leading-none">
                            {{ $score }}
                        </div>
                    </div>
                @endif
                
                {{-- Arrow Icon --}}
                <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </div>
            </div>

          </div>
        </a>

      @empty
        {{-- Empty State --}}
        <div class="text-center py-12 px-4 bg-white rounded-xl border border-slate-200 border-dashed">
          <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center mx-auto mb-3">
            <i class="fa-solid fa-clipboard-list text-slate-300 text-xl"></i>
          </div>
          <h3 class="text-sm font-bold text-slate-900">Belum Ada Riwayat</h3>
          <p class="text-xs text-slate-500 mt-1">
            Mulai kerjakan sesi untuk melihat nilai.
          </p>
        </div>
      @endforelse
    </div>

    {{-- Bottom Action --}}
    @if($totalAttempts > 0)
        <div class="mt-8 text-center">
            <p class="text-xs text-slate-400">Menampilkan seluruh riwayat pengerjaan kuis.</p>
        </div>
    @endif

  </div>
</div>
@endsection
