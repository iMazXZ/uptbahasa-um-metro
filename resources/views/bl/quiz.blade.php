@extends('layouts.front')
@section('title', 'Basic Listening Quiz')

@push('styles')
<style>
  /* Sembunyikan Navbar Utama saat Kuis */
  nav, header, footer { display: none !important; }
  body { padding-top: 0 !important; background: #f8fafc; }

  /* --- FIXED TOP BAR --- */
  .quiz-header-fixed {
    position: fixed; top: 0; left: 0; right: 0; height: 64px;
    background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px);
    border-bottom: 1px solid #e2e8f0; z-index: 50;
    display: flex; flex-direction: column; justify-content: flex-end;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  }
  .quiz-header-content {
    flex: 1; max-width: 960px; width: 100%; margin: 0 auto;
    display: flex; align-items: center; justify-content: space-between; padding: 0 1rem;
  }
  .progress-container { width: 100%; height: 4px; background: #f1f5f9; }
  .progress-fill {
    height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6);
    width: 0%; transition: width 0.4s ease;
  }
  .timer-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;
    padding: 4px 12px; border-radius: 999px; font-weight: 700; font-size: 0.9rem;
    font-variant-numeric: tabular-nums;
  }
  .info-badge { font-size: 0.9rem; font-weight: 700; color: #334155; }

  /* --- MAIN CONTENT --- */
  .quiz-main-container {
    max-width: 960px; margin: 0 auto; padding: 84px 1rem 4rem;
  }
  .question-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
    padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    animation: fadeIn .4s ease-out; position: relative;
  }
  @keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

  .answer-option{
    width:100%; text-align:left; background:#f8fafc; border:2px solid #e2e8f0; border-radius:12px;
    padding:1rem; font-size:1rem; font-weight:500; margin-bottom:0.75rem; transition:all .2s ease;
  }
  .answer-option:hover{background:#f1f5f9;border-color:#cbd5e1}
  .answer-option.is-selected{border-color:#6366f1;background:#eef2ff; color:#3730a3;}
  
  .fib-content { line-height: 2.2; font-size: 1.1rem; color: #1f2937; }

  .btn-nav{
    background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; border:none; padding:.75rem 1.25rem;
    border-radius:10px; font-weight:600; transition:filter .2s ease;
    text-decoration: none; display: inline-block; cursor: pointer; font-size: 0.9rem;
  }
  .btn-nav:hover{filter:brightness(.95)}
  .btn-submit{background:linear-gradient(135deg,#059669,#10b981)}
  
  .qgrid-wrap{ margin-bottom:1.5rem; padding: 1rem; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; }
  .qgrid{ display:grid; gap:.5rem; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); }
  .qbox{
    display:flex; align-items:center; justify-content:center;
    height:40px; border-radius:8px; font-weight:700; font-size: 0.9rem;
    border:2px solid #e2e8f0; user-select:none; transition:all .2s ease;
    text-decoration:none; color: #64748b;
  }
  .qbox.unanswered{ background:#fef2f2; border-color:#fecaca; color:#ef4444; }
  .qbox.answered{ background:#f0fdf4; border-color:#86efac; color:#166534; }
  .qbox.current{ border-color:#6366f1; background:#eef2ff; color:#4338ca; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2); }
  
  /* Auto-Save Indicator */
  .save-status {
    position: fixed; bottom: 1rem; left: 50%; transform: translateX(-50%);
    background: rgba(15, 23, 42, 0.85); color: white; 
    padding: 0.5rem 1.2rem; border-radius: 99px; 
    font-size: 0.75rem; opacity: 0; transition: opacity 0.3s;
    pointer-events: none; z-index: 100; backdrop-filter: blur(4px);
  }
  .save-status.show { opacity: 1; }
</style>
@endpush

@section('content')

@php
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Str;

  // Ambil ulang dari DB: daftar question_id yang sudah terjawab untuk attempt ini
  $answeredIds = $attempt->answers()
      ->whereNotNull('answer')
      ->where('answer', '!=', '')
      ->distinct('question_id')
      ->pluck('question_id')
      ->all();

  $progressPercentage = round((($currentIndex + 1) / max(1, $questions->count())) * 100, 2);
  $isLastQuestion = $currentIndex >= $questions->count() - 1;
  
  $qAudioSrc = null;
  if (!empty($question->audio_url ?? null)) {
      try {
          $qAudioSrc = Str::startsWith($question->audio_url, ['http://','https://'])
              ? $question->audio_url
              : (Storage::exists($question->audio_url) ? Storage::url($question->audio_url) : null);
      } catch (Exception $e) { $qAudioSrc = null; }
  }
@endphp

{{-- HEADER FIXED --}}
<div class="quiz-header-fixed">
    <div class="quiz-header-content">
        <div class="flex flex-col">
            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Pertanyaan</span>
            <div class="info-badge">
                <span class="text-indigo-600 text-lg">{{ $currentIndex + 1 }}</span>
                <span class="text-gray-400 text-xs font-medium">/ {{ $questions->count() }}</span>
            </div>
        </div>
        @if(!empty($remainingSeconds))
            <div class="timer-badge" id="timerBadge">
                <i class="fa-regular fa-clock"></i>
                <span id="timerText">--:--</span>
            </div>
        @endif
    </div>
    <div class="progress-container">
        <div class="progress-fill" id="progressFill" style="width: {{ $progressPercentage }}%"></div>
    </div>
</div>

{{-- AUTO SAVE NOTIF --}}
<div id="saveStatus" class="save-status"><i class="fa-solid fa-check-circle mr-1"></i> Tersimpan</div>

<div class="quiz-main-container">

  {{-- NAVIGASI SOAL (Hanya jika > 1 soal) --}}
  @if($questions->count() > 1)
    <div class="qgrid-wrap">
        <div class="flex justify-between items-center mb-3">
            <span class="text-xs font-bold text-gray-400 uppercase">Navigasi Soal</span>
            <div class="flex gap-2 text-[10px]">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-100 border border-green-400"></span> Isi</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-100 border border-red-400"></span> Kosong</span>
            </div>
        </div>
        <div class="qgrid">
        @foreach($questions as $i => $q)
            @php
            $isAnswered = in_array($q->id, $answeredIds);
            $classes = 'qbox ' . ($isAnswered ? 'answered' : 'unanswered') . ' ' . ($i === $currentIndex ? 'current' : '');
            @endphp
            <a href="{{ route('bl.quiz.show', [$attempt, 'q' => $i]) }}"
               class="{{ $classes }} qbox-link">{{ $i + 1 }}</a>
        @endforeach
        </div>
    </div>
  @endif

  {{-- AUDIO PLAYER --}}
  @if(!empty($qAudioSrc))
    <div class="mb-6 p-4 bg-blue-50 rounded-xl border border-blue-100 flex items-center gap-4 shadow-sm">
        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white shrink-0">
            <i class="fa-solid fa-volume-high"></i>
        </div>
        <div class="flex-1 min-w-0">
             <div class="text-xs font-bold text-blue-800 uppercase mb-1">Audio Pertanyaan</div>
             <audio controls class="w-full h-8">
                <source src="{{ $qAudioSrc }}" type="audio/mpeg">
             </audio>
        </div>
    </div>
  @endif

  {{-- KARTU SOAL --}}
  <div class="question-card">
    <div class="mb-4">
        <span class="inline-block px-2 py-1 text-[10px] font-bold rounded bg-gray-100 text-gray-600 uppercase tracking-wide border border-gray-200">
            @if($question->type === 'fib_paragraph') Fill in the Blank 
            @elseif($question->type === 'true_false') True or False
            @else Multiple Choice @endif
        </span>
    </div>

    <form method="POST" action="{{ route('bl.quiz.answer', $attempt) }}" id="answerForm">
      @csrf
      <input type="hidden" name="question_id" value="{{ $question->id }}">
      <input type="hidden" name="q" value="{{ $currentIndex }}">

      {{-- TIPE FIB --}}
      @if($question->type === 'fib_paragraph')
          <div class="fib-container">
              <p class="text-gray-600 mb-4 text-sm italic">
                  <i class="fa-solid fa-circle-info mr-1 text-blue-500"></i>
                  Ketik jawaban pada kolom. Jawaban tersimpan otomatis.
              </p>
              <div class="fib-content mb-6">
                  {!! $processedParagraph !!}
              </div>
              {{-- Tombol Lanjut (Bukan Akhir) --}}
              @if(!$isLastQuestion)
                  <div class="flex justify-end mt-4">
                      <button type="submit" class="btn-nav">Lanjut <i class="fa-solid fa-arrow-right ml-1"></i></button>
                  </div>
              @endif
          </div>

      {{-- TIPE MC / TF --}}
      @else
          <div class="text-gray-800 text-lg leading-relaxed mb-6 font-medium">
              {{ $question->question }}
          </div>
          <div class="space-y-3">
              @foreach(['A','B','C','D'] as $opt)
                @php $val = $question->{'option_'.strtolower($opt)}; @endphp
                @if($val)
                  <button type="submit" name="answer" value="{{ $opt }}"
                          class="answer-option {{ ($answer->answer ?? null) === $opt ? 'is-selected' : '' }}">
                    <div class="flex items-center gap-3">
                        <span class="w-7 h-7 rounded-full bg-white border border-gray-200 text-gray-500 flex items-center justify-center font-bold text-xs shadow-sm group-hover:border-indigo-300 transition-colors">{{ $opt }}</span>
                        <span>{{ $val }}</span>
                    </div>
                  </button>
                @endif
              @endforeach
          </div>
      @endif

      {{-- NAVIGASI FOOTER --}}
      @if($isLastQuestion)
        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between items-center">
            @if($currentIndex > 0)
                <a href="{{ route('bl.quiz.show', [$attempt, 'q' => $currentIndex - 1]) }}" class="btn-nav bg-gray-500 text-white">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
                </a>
            @else <span></span> @endif

            {{-- TOMBOL SELESAI --}}
            <button type="button" onclick="validateAndFinish()" class="btn-nav btn-submit">
                Selesai & Kumpulkan <i class="fa-solid fa-check ml-1"></i>
            </button>
        </div>
      @elseif($currentIndex > 0) 
        <div class="mt-8 pt-6 border-t border-gray-100">
             <a href="{{ route('bl.quiz.show', [$attempt, 'q' => $currentIndex - 1]) }}" class="btn-nav bg-gray-500 text-white">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
      @endif
    </form>
  </div>

  {{-- MODAL KONFIRMASI (Floating) --}}
  <div id="submitConfirmModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-[60] p-4">
      <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl transform scale-100 transition-transform border border-slate-200">
          <div class="text-center">
              <div class="w-14 h-14 mx-auto mb-4 bg-amber-100 rounded-full flex items-center justify-center shadow-sm border border-amber-200">
                  <i class="fa-solid fa-triangle-exclamation text-2xl text-amber-500"></i>
              </div>
              <h3 class="text-lg font-bold text-slate-900 mb-2">Masih ada yang kosong!</h3>
              <p class="text-slate-600 text-sm mb-6 leading-relaxed">
                  Terdeteksi <strong class="text-amber-600 text-base" id="emptyCountLabel">0</strong> soal belum diisi. Yakin ingin mengumpulkan sekarang?
              </p>
              <div class="flex gap-3 justify-center">
                  <button type="button" onclick="hideSubmitConfirm()" class="flex-1 px-4 py-2.5 border border-slate-300 rounded-xl text-slate-700 font-bold hover:bg-slate-50 transition-colors">
                      Cek Lagi
                  </button>
                  <button type="button" onclick="forceSubmit()" class="flex-1 px-4 py-2.5 bg-amber-500 text-white rounded-xl font-bold hover:bg-amber-600 shadow-lg shadow-amber-500/30 transition-all">
                      Ya, Kumpulkan
                  </button>
              </div>
          </div>
      </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  // --- KONFIGURASI ---
  const attemptId  = {{ $attempt->id }};
  const questionId = {{ $question->id }};
  const saveUrl    = "{{ route('bl.quiz.answer', $attempt) }}";
  const csrfToken  = "{{ csrf_token() }}";
  
  const statusEl   = document.getElementById('saveStatus');
  let saveTimeout;

  // --- 1. SIMPAN JAWABAN FIB KE SERVER (AJAX) ---
  function saveToServer() {
      const form = document.getElementById('answerForm');
      if (!form) return;

      // Tampilkan status "Menyimpan..."
      if (statusEl) {
          statusEl.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-1"></i> Menyimpan ke server...';
          statusEl.classList.add('show');
      }

      const formData = new FormData(form);

      fetch(saveUrl, {
          method: 'POST',
          headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json',
          },
          body: formData
      })
      .then(response => {
          if (response.ok) {
              if (statusEl) {
                  statusEl.innerHTML = '<i class="fa-solid fa-check-circle mr-1"></i> Tersimpan di Server';
                  setTimeout(() => statusEl.classList.remove('show'), 2000);
              }
          } else {
              if (statusEl) {
                  statusEl.innerHTML = '<i class="fa-solid fa-exclamation-circle text-red-400 mr-1"></i> Gagal menyimpan!';
              }
          }
      })
      .catch(error => {
          console.error('Save Error:', error);
          if (statusEl) {
              statusEl.innerHTML = '<i class="fa-solid fa-wifi text-red-400 mr-1"></i> Koneksi Error';
          }
      });
  }

  // --- 2. DEBOUNCE KETIKA KETIK DI FIB ---
  function debouncedSave() {
      clearTimeout(saveTimeout);
      if (statusEl) {
          statusEl.innerHTML = '<i class="fas fa-pen mr-1"></i> Mengetik...';
          statusEl.classList.add('show');
      }
      saveTimeout = setTimeout(saveToServer, 1000);
  }

  function triggerResize(input) {
      let w = (input.value.length > 0 ? input.value.length : (input.placeholder?.length || 3));
      input.style.width = (w + 3) + 'ch';
  }

  // --- 3. INIT FIB AUTOSAVE ---
  document.addEventListener('DOMContentLoaded', function() {
      // FIB input autosave
      document.querySelectorAll('.fib-input').forEach(input => {
          // Set lebar awal
          triggerResize(input);
          
          // Saat mengetik -> debounce save
          input.addEventListener('input', function() {
             triggerResize(this);
             debouncedSave();
          });

          // Saat pindah fokus -> langsung save
          input.addEventListener('blur', function() {
             saveToServer();
          });
      });

      // --- 3b. AUTOSAVE MC VIA AJAX ---
    document.querySelectorAll('.answer-option').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault(); // hentikan submit normal, kita simpan dulu via AJAX

            const form = document.getElementById('answerForm');
            if (!form) {
                this.closest('form')?.submit();
                return;
            }

            const fd = new FormData(form);
            fd.set('answer', this.value); // paksa jawaban sesuai tombol yang diklik

            fetch(saveUrl, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                },
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                // Kalau server kirim redirect (MC / TF), pindah ke URL itu
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    // fallback: reload
                    window.location.reload();
                }
            })
            .catch(() => {
                // Kalau koneksi error, fallback ke submit biasa
                form.submit();
            });
        });
    });
  });

  // --- 4. TIMER ---
  const total = {{ (int) ($remainingSeconds ?? 0) }};
  if (total > 0) {
    let secondsLeft = total;
    const timerText = document.getElementById('timerText');

    const tick = () => {
        const m = Math.floor(secondsLeft / 60);
        const s = secondsLeft % 60;

        if (timerText) {
            timerText.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        }
        
        if (secondsLeft < 60) {
            document.getElementById('timerBadge')?.classList.add('bg-red-100', 'text-red-600');
        }

        if (secondsLeft <= 0) {
            // Timer habis -> submit form (non-AJAX) biar controller bisa force finalize
            document.getElementById('answerForm')?.submit(); 
            return;
        }
        secondsLeft--;
    };

    setInterval(tick, 1000);
    tick();
  }

  // --- 4b. HEARTBEAT KE SERVER (ANTI FREEZE & ANTI HILANG JAWABAN) ---
  setInterval(() => {
      fetch("{{ route('bl.quiz.ping', $attempt) }}", {
          method: "POST",
          headers: {
              "X-CSRF-TOKEN": "{{ csrf_token() }}",
              "X-Requested-With": "XMLHttpRequest",
              "Accept": "application/json",
          }
      })
      .then(res => res.json())
      .then(data => {
          if (data.expired && data.redirect) {
              window.location.href = data.redirect;
          }
      })
      .catch(() => {
          // offline / error? tidak apa-apa.
          // server tetap akan finalize saat dapat request lain setelah deadline.
      });
  }, 20000); // ping tiap 20 detik

  // --- 5. FINISH & FORCE SUBMIT ---
  function validateAndFinish() {
      // Di versi ini kita langsung paksa submit (controller yang cek masih ada kosong/tidak)
      forceSubmit();
  }

  function forceSubmit() {
      const form = document.getElementById('answerForm');
      if (!form) return;

      const input = document.createElement('input');
      input.type  = 'hidden';
      input.name  = 'finish_attempt';
      input.value = '1';
      form.appendChild(input);
      form.submit();
  }

  function showSubmitConfirm() {
      const modal = document.getElementById('submitConfirmModal');
      if (!modal) return;
      modal.classList.remove('hidden');
      modal.classList.add('flex');
  }

  function hideSubmitConfirm() {
      const modal = document.getElementById('submitConfirmModal');
      if (!modal) return;
      modal.classList.add('hidden');
      modal.classList.remove('flex');
  }
</script>
@endpush
