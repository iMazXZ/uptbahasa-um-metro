{{-- resources/views/bl/survey_success.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kuesioner Selesai</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #ffffff;
      min-height: 100vh;
    }
    .success-page {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 24px;
    }
    .content-wrap {
      width: 100%;
      max-width: 420px;
    }
    .btn-primary {
      background: #1e40af;
      color: white;
      transition: background 0.15s;
    }
    .btn-primary:hover {
      background: #1e3a8a;
    }
    .btn-secondary {
      background: white;
      color: #475569;
      border: 1px solid #d1d5db;
      transition: all 0.15s;
    }
    .btn-secondary:hover {
      background: #f8fafc;
      border-color: #9ca3af;
    }
    .grade-box {
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 14px;
      text-align: center;
    }
    .grade-box.highlight {
      background: #1e40af;
      border-color: #1e40af;
      color: white;
    }
  </style>
</head>
<body>
  
  @php
    use Illuminate\Support\Facades\Route as RouteFacade;
    $downloadUrl = RouteFacade::has('bl.certificate.download') ? route('bl.certificate.download') : null;
    $previewUrl  = $downloadUrl ? ($downloadUrl . '?inline=1') : null;
  @endphp

  <div class="success-page">
    <div class="content-wrap">
      
      {{-- Header --}}
      <div class="text-center mb-8">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
          <i class="fa-solid fa-check text-green-600 text-3xl"></i>
        </div>
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Kuesioner Selesai!</h1>
        <p class="text-sm text-slate-500">Semua kuesioner wajib telah berhasil diselesaikan.</p>
      </div>

      {{-- Grades Section --}}
      @if(isset($daily) || isset($finalTest) || isset($finalNumeric))
        <div class="mb-8">
          <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Hasil Nilai Akhir</h3>
          <div class="grid grid-cols-3 gap-3">
            <div class="grade-box">
              <div class="text-[10px] text-slate-400 uppercase font-medium mb-1">Daily</div>
              <div class="text-xl font-bold text-slate-800">{{ is_numeric($daily) ? number_format($daily, 1) : '-' }}</div>
            </div>
            <div class="grade-box">
              <div class="text-[10px] text-slate-400 uppercase font-medium mb-1">Final</div>
              <div class="text-xl font-bold text-slate-800">{{ is_numeric($finalTest) ? number_format($finalTest, 0) : '-' }}</div>
            </div>
            <div class="grade-box highlight">
              <div class="text-[10px] text-blue-200 uppercase font-medium mb-1">Total</div>
              <div class="text-xl font-bold">
                {{ is_numeric($finalNumeric) ? number_format($finalNumeric, 0) : '-' }}
                @if($finalLetter)
                  <span class="text-sm font-normal opacity-80">{{ $finalLetter }}</span>
                @endif
              </div>
            </div>
          </div>
          
          {{-- Status Badge --}}
          <div class="mt-4 text-center">
            @if($meetsPassing ?? false)
              <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-100 text-green-700 text-sm font-semibold rounded-full">
                <i class="fa-solid fa-circle-check"></i>
                Selamat, Anda Lulus!
              </span>
            @else
              <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-100 text-red-700 text-sm font-semibold rounded-full">
                <i class="fa-solid fa-circle-xmark"></i>
                Nilai belum mencapai kelulusan (min. 55)
              </span>
            @endif
          </div>
        </div>
      @endif

      {{-- Certificate Section --}}
      <div class="mb-6">
        @if($canDownloadCertificate && ($meetsPassing ?? false) && $downloadUrl)
          <div class="bg-green-50 border border-green-200 rounded-xl p-3 mb-4 text-center">
            <p class="text-sm font-medium text-green-800">
              <i class="fa-solid fa-certificate mr-1"></i>
              Sertifikat siap diunduh
            </p>
          </div>
          <div class="space-y-3">
            <a href="{{ $downloadUrl }}" class="w-full flex items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-200 transition-colors">
              <i class="fa-solid fa-download"></i>
              Unduh Sertifikat
            </a>
            <a href="{{ $previewUrl }}" target="_blank" class="btn-secondary w-full flex items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-sm font-semibold">
              <i class="fa-regular fa-file-pdf"></i>
              Preview di Browser
            </a>
          </div>
        @elseif(!($meetsPassing ?? false))
          <div class="bg-slate-100 rounded-xl p-5 text-center">
            <i class="fa-solid fa-file-circle-xmark text-slate-400 text-3xl mb-3"></i>
            <p class="text-sm font-medium text-slate-600 mb-1">Sertifikat tidak tersedia</p>
            <p class="text-xs text-slate-400">Nilai akhir belum memenuhi syarat kelulusan.</p>
          </div>
        @else
          <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-center">
            <i class="fa-solid fa-hourglass-half text-amber-500 text-3xl mb-3"></i>
            <p class="text-sm font-medium text-amber-800 mb-1">Sertifikat sedang diproses</p>
            <p class="text-xs text-amber-600">Hubungi admin jika ada pertanyaan.</p>
          </div>
        @endif
      </div>

      {{-- Footer Actions --}}
      <a href="{{ route('bl.index') }}" class="btn-secondary w-full flex items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-sm font-semibold">
        <i class="fa-solid fa-arrow-left"></i>
        Kembali ke Basic Listening
      </a>

    </div>
  </div>

  {{-- Confetti Animation --}}
  <style>
    .confetti-piece {
      position: fixed;
      top: -20px;
      width: 10px;
      height: 16px;
      opacity: 0.95;
      animation: confetti-fall linear forwards;
      z-index: 9999;
      pointer-events: none;
    }
    @keyframes confetti-fall {
      0% {
        transform: translateY(-20vh) rotate(0deg);
        opacity: 1;
      }
      100% {
        transform: translateY(120vh) rotate(720deg);
        opacity: 0.9;
      }
    }
  </style>
  <script>
    (function(){
      const colors = ['#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6','#14b8a6','#f97316','#ec4899'];
      const count = 100;

      function spawnPiece(){
        const el = document.createElement('div');
        el.className = 'confetti-piece';
        const size = Math.random() * 10 + 8;
        el.style.width = (size * 0.6) + 'px';
        el.style.height = size + 'px';
        el.style.left = Math.random() * 100 + 'vw';
        el.style.background = colors[Math.floor(Math.random() * colors.length)];
        el.style.animationDuration = (Math.random() * 3 + 4) + 's';
        el.style.animationDelay = (Math.random() * 0.5) + 's';
        el.style.borderRadius = (Math.random() > 0.5 ? '50%' : '2px');
        document.body.appendChild(el);
        el.addEventListener('animationend', () => el.remove());
      }

      // Initial burst
      for(let i = 0; i < count; i++) {
        setTimeout(() => spawnPiece(), i * 20);
      }
    })();
  </script>

</body>
</html>
