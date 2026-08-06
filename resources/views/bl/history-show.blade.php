@extends('layouts.front')
@section('title', 'Detail Hasil')

@section('content')

{{-- 1. LOGIC BLOCK (Perhitungan Skor) --}}
@php
    $isSubmitted = !is_null($attempt->submitted_at);
    
    // Default Stats
    $totalUnits     = 0;
    $correctUnits   = 0;
    $incorrectUnits = 0;
    $accuracy       = 0;

    if ($isSubmitted) {
        foreach ($questions as $q) {
            $type = $q->type ?? 'multiple_choice';

            if ($type === 'fib_paragraph') {
                // Logic FIB
                $keys      = (array) ($q->fib_answer_key ?? []);
                // Hitung unit berdasarkan jumlah kunci jawaban
                $unitCount = count($keys);

                if ($unitCount <= 0) {
                    // Fallback jika kunci kosong, hitung dari placeholder/input user
                    $unitCount = $answers->where('question_id', $q->id)->count();
                }

                $unitCount   = max(1, (int) $unitCount);
                $totalUnits += $unitCount;

                $ansCorrect   = $answers->where('question_id', $q->id)->where('is_correct', true)->count();
                $correctUnits += min($unitCount, $ansCorrect);
            } else {
                // Logic PG / True False
                $totalUnits += 1;
                $ans = $answers->firstWhere('question_id', $q->id);
                if ($ans && $ans->is_correct) {
                    $correctUnits += 1;
                }
            }
        }

        $incorrectUnits = max(0, $totalUnits - $correctUnits);
        $accuracy       = $totalUnits > 0 ? round(($correctUnits / $totalUnits) * 100, 0) : 0;
    }
@endphp

{{-- 2. HERO SECTION --}}
<div class="relative bg-slate-900 pt-10 pb-24 overflow-hidden">
    {{-- Background Decor --}}
    <div class="absolute inset-0 bg-gradient-to-br from-blue-900 to-slate-900 opacity-90"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-600 rounded-full blur-3xl opacity-20"></div>
    <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-indigo-500 rounded-full blur-3xl opacity-20"></div>

    <div class="relative max-w-4xl mx-auto px-4 text-center">
        {{-- Nav Back --}}
        <div class="absolute top-0 left-4 hidden md:block">
            <a href="{{ route('bl.history') }}" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-sm font-medium">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>

        {{-- Session Info --}}
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-white/10 bg-white/5 backdrop-blur-md mb-4">
            @php $isUAS = (int)($attempt->session->number ?? 0) === 6; @endphp
            <span class="h-2 w-2 rounded-full {{ $isUAS ? 'bg-pink-500' : 'bg-blue-500' }}"></span>
            <span class="text-xs font-bold text-slate-200 uppercase tracking-wide">
                {{ $isUAS ? 'Final Exam' : 'Meeting ' . ($attempt->session->number ?? '-') }}
            </span>
        </div>

        <h1 class="text-2xl md:text-4xl font-bold text-white mb-2 leading-tight">
            {{ $attempt->session->title ?? 'Basic Listening' }}
        </h1>

        @if($isSubmitted)
            <p class="text-slate-400 text-sm flex items-center justify-center gap-2">
                <i class="fa-regular fa-clock"></i>
                Dikumpulkan {{ $attempt->submitted_at->translatedFormat('d M Y, H:i') }} WIB
            </p>
        @else
            <p class="text-amber-400 text-sm flex items-center justify-center gap-2 font-medium">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Status: Belum Disubmit
            </p>
        @endif
    </div>
</div>

{{-- 3. STATS & CONTENT --}}
<div class="relative z-10 -mt-16 pb-12 px-4">
    <div class="max-w-4xl mx-auto">
        
        @if($isSubmitted)
            {{-- FLOATING STATS CARD --}}
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-900/10 border border-slate-100 p-6 mb-8 grid grid-cols-2 md:grid-cols-4 gap-6 text-center divide-x divide-slate-100">
                {{-- Final Score --}}
                <div class="col-span-2 md:col-span-1 flex flex-col items-center justify-center">
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mb-1">Nilai Akhir</span>
                    <div class="text-4xl font-black {{ $accuracy >= 70 ? 'text-emerald-600' : ($accuracy >= 50 ? 'text-amber-500' : 'text-rose-500') }}">
                        {{ (int) $attempt->score }}
                    </div>
                </div>

                {{-- Accuracy --}}
                <div class="hidden md:flex flex-col items-center justify-center">
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mb-1">Akurasi</span>
                    <div class="text-xl font-bold text-slate-700">{{ $accuracy }}%</div>
                </div>

                {{-- Correct --}}
                <div class="flex flex-col items-center justify-center">
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mb-1">Jawaban Benar</span>
                    <div class="text-xl font-bold text-emerald-600 flex items-center gap-1">
                        <i class="fa-solid fa-check text-sm"></i> {{ $correctUnits }}
                    </div>
                </div>

                {{-- Incorrect --}}
                <div class="flex flex-col items-center justify-center border-r-0">
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mb-1">Jawaban Salah</span>
                    <div class="text-xl font-bold text-rose-500 flex items-center gap-1">
                        <i class="fa-solid fa-xmark text-sm"></i> {{ $incorrectUnits }}
                    </div>
                </div>
            </div>

            {{-- QUESTIONS LIST --}}
            <div class="space-y-6">
                <div class="flex items-center justify-between mb-4 px-1">
                    <h2 class="text-lg font-bold text-slate-800">Pembahasan Detail</h2>
                    <div class="flex gap-3 text-[10px] font-semibold uppercase text-slate-500">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Benar</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Salah</span>
                    </div>
                </div>

                @foreach($questions as $idx => $q)
                    @php $qType = $q->type ?? 'multiple_choice'; @endphp

                    {{-- TYPE: MULTIPLE CHOICE / TRUE FALSE --}}
                    @if($qType !== 'fib_paragraph')
                        @php
                            $ans       = $answers->firstWhere('question_id', $q->id);
                            $chosen    = $ans->answer ?? null;
                            $isCorrect = (bool) ($ans->is_correct ?? false);
                        @endphp

                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden group hover:border-blue-300 transition-colors">
                            {{-- Header Soal --}}
                            <div class="p-5 border-b border-slate-50 bg-slate-50/50">
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-lg {{ $isCorrect ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }} flex items-center justify-center font-bold text-sm">
                                        {{ $idx + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm md:text-base font-medium text-slate-900 leading-relaxed">
                                            {{ $q->question }}
                                        </div>
                                        {{-- Status Badge Mobile --}}
                                        <div class="mt-2 md:hidden">
                                             @if($isCorrect)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded">
                                                    <i class="fa-solid fa-check"></i> Benar
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-1 rounded">
                                                    <i class="fa-solid fa-xmark"></i> Salah
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    {{-- Status Icon Desktop --}}
                                    <div class="hidden md:block">
                                        @if($isCorrect)
                                            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                                <i class="fa-solid fa-check"></i>
                                            </div>
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center">
                                                <i class="fa-solid fa-xmark"></i>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Options --}}
                            <div class="p-5 space-y-2.5">
                                @foreach(['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $key => $text)
                                    {{-- Skip jika option kosong (misal untuk True/False yg cuma A/B) --}}
                                    @if(empty($text)) @continue @endif
                                    
                                    @php
                                        $isUserChoice = ($key === $chosen);
                                        
                                        // default row style (tidak men-spill kunci)
                                        $rowClass = "border-slate-200 bg-white hover:bg-slate-50";
                                        $icon     = null;

                                        if ($isUserChoice) {
                                            if ($isCorrect) {
                                                // Jawaban Anda & benar → hijau
                                                $rowClass = "border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500 z-10";
                                                $icon     = '<i class="fa-solid fa-circle-check text-emerald-600"></i>';
                                            } else {
                                                // Jawaban Anda & salah → merah
                                                $rowClass = "border-rose-500 bg-rose-50 ring-1 ring-rose-500 z-10";
                                                $icon     = '<i class="fa-solid fa-circle-xmark text-rose-600"></i>';
                                            }
                                        }
                                    @endphp

                                    <div class="relative flex items-start gap-3 p-3 rounded-lg border {{ $rowClass }} transition-all">
                                        <div class="flex-shrink-0 w-6 h-6 rounded bg-white border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-500 shadow-sm">
                                            {{ $key }}
                                        </div>
                                        <div class="flex-1 text-sm text-slate-700 leading-snug pt-0.5">
                                            {{ $text }}
                                        </div>
                                        @if($icon)
                                            <div class="flex-shrink-0 ml-2 mt-0.5">
                                                {!! $icon !!}
                                            </div>
                                        @endif
                                        
                                        {{-- Label Jawaban Anda --}}
                                        @if($isUserChoice)
                                            <div class="absolute -top-2 right-2 bg-white px-2 py-0.5 rounded-full border shadow-sm text-[9px] font-bold uppercase tracking-wide {{ $isCorrect ? 'text-emerald-600 border-emerald-200' : 'text-rose-600 border-rose-200' }}">
                                                Jawaban Anda
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    {{-- TYPE: FILL IN THE BLANK --}}
                    @else
                        @php
                            // Semua jawaban untuk question ini
                            $ansRows   = $answers->where('question_id', $q->id);
                            $paragraph = $q->paragraph_text ?? '';
                            
                            // Counter untuk Sequential Index (0, 1, 2...)
                            // Logic ini harus sama persis dengan Controller & Command Regrade
                            $seqCounter = 0;

                            $rendered = preg_replace_callback(
                                '/\[\[(\d+)\]\]|\[blank\]/',
                                function ($m) use (&$seqCounter, $ansRows) {
                                    $idx = $seqCounter++;

                                    // Ambil jawaban berdasarkan blank_index sequential
                                    $row = $ansRows->firstWhere('blank_index', $idx);
                                    $val = trim((string) ($row->answer ?? ''));
                                    $ok  = (bool) ($row->is_correct ?? false);

                                    $label = $val !== '' ? e($val) : '<span class="opacity-50">...</span>';

                                    $baseCls = "inline-flex items-center px-2 py-0.5 rounded mx-0.5 text-sm font-bold border-b-2 shadow-sm align-middle transition-all ";
                                    $cls     = $ok 
                                        ? "bg-emerald-100 text-emerald-800 border-emerald-300" 
                                        : "bg-rose-100 text-rose-800 border-rose-300 decoration-wavy decoration-rose-400";

                                    return '<span class="' . $baseCls . $cls . '">' . $label . '</span>';
                                },
                                e($paragraph)
                            );

                            $allCorrect = $ansRows->isNotEmpty() && $ansRows->where('is_correct', false)->isEmpty();
                        @endphp

                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                             <div class="p-5 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-solid fa-paragraph"></i>
                                    </div>
                                    <h3 class="font-bold text-slate-800 text-sm">Fill in the Blank</h3>
                                </div>
                                @if($allCorrect)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase">
                                        <i class="fa-solid fa-check-double"></i> Sempurna
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-amber-100 text-amber-700 text-[10px] font-bold uppercase">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Ada Salah
                                    </span>
                                @endif
                            </div>
                            <div class="p-6">
                                <div class="prose prose-slate max-w-none prose-p:leading-loose text-gray-800 text-base bg-slate-50/50 p-5 rounded-lg border border-slate-200 whitespace-pre-line leading-8">
                                    {!! $rendered !!}
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Clear LocalStorage Script --}}
            <script>
                (function(){
                    try {
                        // Hapus data autosave jika sudah submit
                        const prefix = 'BL_QUIZ_A{{ (int) $attempt->id }}';
                        for (let i = localStorage.length - 1; i >= 0; i--) {
                            const k = localStorage.key(i);
                            if (k && k.startsWith(prefix)) {
                                localStorage.removeItem(k);
                            }
                        }
                    } catch(e) {
                        console.log('LS cleanup error', e);
                    }
                })();
            </script>

        @else
            {{-- STATE: NOT SUBMITTED --}}
            <div class="bg-white rounded-2xl shadow-lg border border-amber-100 p-8 md:p-12 text-center max-w-2xl mx-auto mt-8">
                <div class="relative mb-6 inline-block">
                    <div class="absolute inset-0 bg-amber-200 rounded-full blur-xl opacity-50 animate-pulse"></div>
                    <div class="relative w-20 h-20 rounded-full bg-amber-50 text-amber-500 border border-amber-200 flex items-center justify-center">
                        <i class="fa-solid fa-file-pen text-3xl"></i>
                    </div>
                </div>
                
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Sesi Belum Disubmit</h2>
                <p class="text-slate-500 mb-8 leading-relaxed">
                    Anda belum menyelesaikan sesi ini. Silakan lanjutkan pengerjaan quiz untuk mendapatkan nilai dan pembahasan.
                </p>

                {{-- PERBAIKAN ROUTE: Selalu arahkan ke bl.quiz.show --}}
                <a href="{{ route('bl.quiz.show', $attempt->id) }}" class="inline-flex items-center gap-2 px-8 py-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-600/20 transition-all transform hover:-translate-y-1">
                    Lanjutkan Mengerjakan <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        @endif

        {{-- Back Button (Bottom) --}}
        <div class="flex justify-center mt-12">
            <a href="{{ route('bl.history') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 rounded-full font-semibold text-slate-600 hover:bg-slate-50 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm">
                <i class="fa-solid fa-chevron-left text-xs"></i> Kembali ke Riwayat
            </a>
        </div>
        
    </div>
</div>

{{-- KONFETI EFEK --}}
@if($isSubmitted && (int) $attempt->score >= 30)
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function shoot() {
            var duration = 5 * 1000;
            var animationEnd = Date.now() + duration;
            var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 50 };

            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }

            var interval = setInterval(function() {
                var timeLeft = animationEnd - Date.now();

                if (timeLeft <= 0) {
                    return clearInterval(interval);
                }

                var particleCount = 50 * (timeLeft / duration);
                
                // Tembak dari Kiri
                confetti(Object.assign({}, defaults, { 
                    particleCount, 
                    origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } 
                }));
                
                // Tembak dari Kanan
                confetti(Object.assign({}, defaults, { 
                    particleCount, 
                    origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } 
                }));
            }, 250);
        }

        // Jalankan efek
        shoot();
    });
</script>
@endif

@endsection
