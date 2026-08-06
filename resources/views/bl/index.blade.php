{{-- resources/views/bl/index.blade.php --}}
@extends('layouts.front')
@section('title','Basic Listening')

@section('content')
@php
  // --- LOGIC BLOCK: USER & INITIAL DATA ---
  $user = auth()->user();
  $groupNumber = $user?->nomor_grup_bl;
  $prodyName = $user?->prody?->name;

  // --- LOGIC BLOCK: SCHEDULES ---
  $hasActiveSchedules = false;
  $liveSchedules = [];
  $upcomingSchedules = [];

  if ($user) {
      $now = \Carbon\Carbon::now('Asia/Jakarta');
      $dmap = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];
      $todayName = $dmap[$now->dayOfWeekIso] ?? '';
      $nowTime = $now->format('H:i:s');
      
      $todaySchedules = \App\Models\BasicListeningSchedule::with(['tutors:id,name','prody:id,name'])
          ->where('hari', $todayName)
          ->orderBy('jam_mulai')
          ->get();
      
      foreach ($todaySchedules as $schedule) {
          $isLive = $schedule->jam_mulai <= $nowTime && $schedule->jam_selesai >= $nowTime;
          if ($isLive) {
              $liveSchedules[] = $schedule;
          } else {
              $start = \Carbon\Carbon::createFromFormat('H:i:s', $schedule->jam_mulai, 'Asia/Jakarta');
              $diffMinutes = $start->diffInMinutes($now, false);
              // Upcoming logic: -30 menit s/d 0 menit
              if ($diffMinutes < 0 && abs($diffMinutes) <= 30) {
                  $upcomingSchedules[] = ['schedule' => $schedule, 'minutes' => (int) abs($diffMinutes)];
              }
          }
      }
      $hasActiveSchedules = !empty($liveSchedules) || !empty($upcomingSchedules);
  }

  // --- LOGIC BLOCK: GRADES & CERTIFICATE ---
  $showPanel = false;
  $daily = null; $finalTest = null; $finalNumeric = null; $finalLetter = null;
  $canDownload = false; $previewUrl = null; $downloadUrl = null;
  $surveyRequired = false; $surveyDone = false; $surveyUrl = null;

  if ($user) {
      $year = (int) ($user->year ?? 0);
      if ($year >= 2025) {
          $grade = \App\Models\BasicListeningGrade::query()->where('user_id', $user->id)->where('user_year', $year)->first();
          
          $attendance = is_numeric($grade?->attendance) ? (float) $grade->attendance : null;
          $finalTest  = is_numeric($grade?->final_test)  ? (float) $grade->final_test  : null;
          $daily      = \App\Support\BlCompute::dailyAvgForUser($user->id, $year);
          $daily      = is_numeric($daily) ? (float) $daily : null;

          $finalNumeric = $grade?->final_numeric_cached;
          $finalLetter  = $grade?->final_letter_cached;

          if ($finalNumeric === null && is_numeric($daily) && is_numeric($attendance) && is_numeric($finalTest)) {
              $finalNumeric = \App\Support\BlGrading::computeFinalNumeric([
                  'attendance' => $attendance, 'daily' => $daily, 'final_test' => $finalTest,
              ]);
              $finalLetter = $finalNumeric !== null ? \App\Support\BlGrading::toLetter($finalNumeric) : null;
          }

          $baseEligible = is_numeric($attendance) && is_numeric($finalTest);
          $meetsPassing = is_numeric($finalNumeric) && $finalNumeric >= 55;
          $meetsPassing = is_numeric($finalNumeric) && $finalNumeric >= 55;

          // Survey Logic
          $survey = \App\Models\BasicListeningSurvey::query()
              ->where('require_for_certificate', true)->where('target', 'final')->where('is_active', true)
              ->latest('id')->first();
          $surveyRequired = $survey ? $survey->isOpen() : false;
          if ($surveyRequired) {
              $surveyDone = \App\Models\BasicListeningSurveyResponse::where([
                  'survey_id' => $survey->id, 'user_id' => $user->id, 'session_id' => null,
              ])->whereNotNull('submitted_at')->exists();
          }

          $canDownload = $baseEligible && $meetsPassing && (!$surveyRequired || $surveyDone);
          $surveyUrl   = \Illuminate\Support\Facades\Route::has('bl.survey.required') ? route('bl.survey.required') : null;
          $downloadUrl = \Illuminate\Support\Facades\Route::has('bl.certificate.download') ? route('bl.certificate.download') : null;
          $previewUrl  = $downloadUrl ? ($downloadUrl . '?inline=1') : null;

          $showPanel = $grade !== null && $baseEligible;
      }
  }
@endphp

{{-- PART 1: HERO SECTION (Clean & Bold) --}}
<div class="relative bg-slate-900 overflow-hidden pb-24">
    {{-- Background Decor --}}
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-indigo-900 opacity-90"></div>
        {{-- Pattern dots --}}
        <div class="absolute inset-0" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px; opacity: 0.1;"></div>
        {{-- Glow effects --}}
        <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-blue-400 rounded-full blur-3xl opacity-20"></div>
        <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-indigo-400 rounded-full blur-3xl opacity-20"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 pt-10 pb-10 md:pt-16">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-blue-100 text-xs font-medium mb-4 backdrop-blur-md">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                    </span>
                    Platform Pembelajaran Bahasa Inggris
                </div>
                <h1 class="text-3xl md:text-5xl font-black text-white tracking-tight mb-3 leading-tight">
                    Basic Listening <br/> <span class="text-blue-300">Program</span>
                </h1>
                <p class="text-blue-100/80 text-base md:text-lg max-w-xl leading-relaxed">
                    Kegiatan wajib mahasiswa semester 1.
                </p>
            </div>
            
            {{-- Hero Action for Guest --}}
            @guest
            <div class="md:mb-2">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-blue-900 bg-white rounded-xl hover:bg-blue-50 transition-all shadow-lg shadow-blue-900/20 transform hover:-translate-y-0.5">
                    Login Mahasiswa <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
            </div>
            @endguest
        </div>
    </div>
</div>

{{-- PART 2: DASHBOARD GRID (Floating Cards) --}}
<div class="max-w-7xl mx-auto px-4 -mt-20 relative z-10 mb-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        @auth
            {{-- CARD 1: PROFILE & GROUP SETTINGS --}}
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 bg-gradient-to-b from-slate-50/50 to-white">
                    <div class="flex items-start gap-4">
                        @php $avatarUrl = \Filament\Facades\Filament::getUserAvatarUrl($user); @endphp
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="w-14 h-14 rounded-full object-cover border-2 border-white shadow-md">
                        @else
                            <div class="w-14 h-14 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl font-bold border-2 border-white shadow-md">
                                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                        
                        <div class="flex-1 min-w-0 pt-1">
                            <h3 class="text-base font-bold text-slate-900 truncate">{{ $user->name }}</h3>
                            <p class="text-xs text-slate-500 truncate mb-2">{{ $prodyName ?? 'Prodi belum diatur' }}</p>
                            
                            <div class="flex gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">
                                    {{ $user->srn ?? 'NPM Kosong' }}
                                </span>
                                <a href="{{ route('dashboard.biodata') }}" class="text-[10px] text-blue-600 hover:text-blue-700 underline decoration-blue-200 underline-offset-2">
                                    Edit Biodata
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-5 bg-white flex-1 flex flex-col justify-center">
                    @if (is_null($groupNumber))
                        {{-- Case: Belum Ada Grup --}}
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-2 text-amber-800">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span class="text-xs font-bold uppercase">Grup Belum Diatur</span>
                            </div>
                            <p class="text-xs text-amber-700/80 mb-3 leading-relaxed">
                                Anda wajib memilih grup untuk melihat riwayat nilai & sertifikat.
                            </p>
                            <form action="{{ route('bl.groupNumber.update') }}" method="POST" class="flex gap-2">
                                @csrf
                                <select name="nomor_grup_bl" required class="block w-full rounded-lg border-amber-300 text-xs focus:border-amber-500 focus:ring-amber-500 py-2 bg-white">
                                    <option value="">Pilih...</option>
                                    @foreach([1,2,3,4] as $g) <option value="{{$g}}">Grup {{$g}}</option> @endforeach
                                </select>
                                <button type="submit" class="px-3 py-2 bg-amber-600 text-white rounded-lg text-xs font-bold hover:bg-amber-700 shadow-sm">
                                    Simpan
                                </button>
                            </form>
                        </div>
                    @else
                        {{-- Case: Sudah Ada Grup --}}
                        <div class="flex flex-col gap-3">
                            
                            {{-- 1. Input Grup (Full Width) --}}
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 block">
                                    Grup Kelas Saat Ini
                                </label>
                                <div class="relative w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 flex items-center justify-between transition-colors hover:border-blue-300 group">
                                    
                                    {{-- Tampilan Teks --}}
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 shadow-sm">
                                            <span class="text-xs font-bold">{{ $groupNumber }}</span>
                                        </div>
                                        <span class="text-sm font-bold text-slate-700">Grup {{ $groupNumber }}</span>
                                    </div>

                                    {{-- Indikator Dropdown --}}
                                    <i class="fa-solid fa-chevron-down text-slate-400 text-xs group-hover:text-blue-500"></i>

                                    {{-- Form Select (Invisible Overlay) --}}
                                    <form action="{{ route('bl.groupNumber.update') }}" method="POST" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        @csrf
                                        <select name="nomor_grup_bl" onchange="this.form.submit()" class="w-full h-full cursor-pointer">
                                            @foreach([1,2,3,4] as $g) 
                                                <option value="{{$g}}" {{ $groupNumber == $g ? 'selected' : '' }}>Grup {{$g}}</option> 
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            </div>

                            {{-- 2. Tombol Riwayat (Full Width & Menonjol) --}}
                            <a href="{{ route('bl.history') }}" 
                               class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white shadow-md shadow-indigo-200 transition-all active:scale-95 hover:bg-indigo-700 hover:shadow-lg">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                Lihat Riwayat Skor
                            </a>

                        </div>
                    @endif
                </div>
            </div>

            {{-- CARD 2: LIVE SCHEDULE / STATUS (Only show if there are active schedules) --}}
            @if($hasActiveSchedules)
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden flex flex-col relative">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-regular fa-calendar"></i> Jadwal Hari Ini
                    </h3>
                    <a href="{{ route('bl.schedule') }}" class="text-[10px] font-semibold text-blue-600 hover:text-blue-800">Lihat Semua</a>
                </div>

                <div class="p-5 flex-1 overflow-y-auto max-h-[200px] space-y-3">
                    {{-- Live Items --}}
                    @foreach($liveSchedules as $schedule)
                        <div class="relative bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-start gap-3">
                            <div class="absolute top-3 right-3 flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </div>
                            <div class="mt-1 w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-video"></i>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide mb-0.5">Sedang Berlangsung</div>
                                <h4 class="text-sm font-bold text-emerald-900">{{ $schedule->prody?->name ?? 'General Class' }}</h4>
                                <p class="text-xs text-emerald-700 mt-1">
                                    {{ substr($schedule->jam_mulai, 0, 5) }} - {{ substr($schedule->jam_selesai, 0, 5) }} WIB
                                </p>
                            </div>
                        </div>
                    @endforeach

                    {{-- Upcoming Items --}}
                    @foreach($upcomingSchedules as $item)
                        @php $sch = $item['schedule']; $mins = $item['minutes']; @endphp
                        <div class="relative bg-amber-50 border border-amber-100 rounded-xl p-4 flex items-start gap-3">
                            <div class="mt-1 w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-hourglass-half"></i>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wide mb-0.5">Mulai {{ $mins }} Menit Lagi</div>
                                <h4 class="text-sm font-bold text-amber-900">{{ $sch->prody?->name }}</h4>
                                <p class="text-xs text-amber-700 mt-1">
                                    {{ substr($sch->jam_mulai, 0, 5) }} WIB
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- CARD 3: GRADES & CERTIFICATE (Conditional) --}}
            @if($showPanel)
                <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-white">
                        <h3 class="text-sm font-bold text-blue-900 flex items-center gap-2">
                            <i class="fa-solid fa-award text-blue-500"></i> Hasil Akhir
                        </h3>
                    </div>
                    
                    <div class="p-5 space-y-4">
                        @if(($surveyRequired ?? false) && !($surveyDone ?? false))
                            {{-- Kuesioner belum diisi: Sembunyikan nilai, tampilkan CTA untuk isi kuesioner --}}
                            <div class="bg-amber-50 p-4 rounded-xl border border-amber-100 text-center">
                                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fa-solid fa-clipboard-list text-amber-600 text-xl"></i>
                                </div>
                                <h4 class="text-sm font-bold text-amber-900 mb-1">Selamat! Pembelajaran Sudah Selesai</h4>
                                <p class="text-xs text-amber-700 mb-3">Silakan isi kuesioner terlebih dahulu untuk melihat nilai akhir Anda.</p>
                                <a href="{{ $surveyUrl }}" class="inline-flex items-center justify-center gap-2 w-full py-2.5 bg-amber-500 text-white rounded-xl text-xs font-bold hover:bg-amber-600 transition-all shadow-md shadow-amber-200">
                                    <i class="fa-solid fa-pen-to-square"></i> Isi Kuesioner
                                </a>
                            </div>
                        @else
                            {{-- Kuesioner sudah diisi atau tidak wajib: Tampilkan nilai --}}
                            {{-- Scores Grid --}}
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Daily</div>
                                    <div class="text-sm font-bold text-slate-700">{{ is_numeric($daily) ? number_format($daily, 1) : '-' }}</div>
                                </div>
                                <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Final</div>
                                    <div class="text-sm font-bold text-slate-700">{{ is_numeric($finalTest) ? number_format($finalTest, 0) : '-' }}</div>
                                </div>
                                <div class="p-2 bg-blue-600 rounded-lg shadow-md shadow-blue-200">
                                    <div class="text-[10px] text-blue-200 uppercase font-semibold">Total</div>
                                    <div class="text-lg font-black text-white">
                                        {{ is_numeric($finalNumeric) ? number_format($finalNumeric, 0) : '-' }}
                                        <span class="text-xs font-normal opacity-80">{{ $finalLetter }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="pt-2">
                                 @if($canDownload && !empty($downloadUrl))
                                    <a href="{{ $downloadUrl }}" class="flex items-center justify-center gap-2 w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors shadow-lg shadow-emerald-200">
                                        <i class="fa-solid fa-file-pdf"></i> Unduh Sertifikat
                                    </a>
                                @else
                                   <div class="text-center py-2 bg-slate-50 rounded-lg border border-slate-100 border-dashed">
                                        @if(!$baseEligible)
                                            <p class="text-xs text-slate-400 italic">Menunggu input nilai final.</p>
                                        @elseif(!$meetsPassing)
                                            <div class="bg-rose-50 p-3 rounded-lg border border-rose-200 text-center">
                                                <p class="text-sm font-semibold text-rose-700 mb-1 flex items-center justify-center gap-2">
                                                    <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                                                    Anda Belum Lulus
                                                </p>
                                                <p class="text-[11px] text-rose-600">Nilai akhir belum mencapai 55 untuk sertifikat.</p>
                                            </div>
                                        @endif
                                    </div>
                                 @endif
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- Placeholder Card for alignment if grades not ready --}}
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-xl shadow-blue-900/20 overflow-hidden flex flex-col items-center justify-center text-white p-6 text-center relative">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mb-3 backdrop-blur-sm">
                        <i class="fa-solid fa-graduation-cap text-xl"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1">Semangat Belajar!</h3>
                    <p class="text-xs text-blue-100 leading-relaxed">
                        Ikuti sesi secara rutin. Nilai akhir dan sertifikat akan muncul di sini setelah program selesai.
                    </p>
                </div>
            @endif

        @endauth

        @guest
            {{-- Card Promosi Login untuk Guest --}}
            <div class="lg:col-span-3 bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8 text-center">
                 <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 text-blue-600 mb-4">
                    <i class="fa-solid fa-lock text-2xl"></i>
                 </div>
                 <h2 class="text-xl font-bold text-slate-900">Akses Terbatas</h2>
                 <p class="text-slate-500 mt-2 mb-6 max-w-md mx-auto">Silakan login untuk mengakses Kuis Harian, melihat Jadwal personal, dan mengunduh Sertifikat.</p>
                 <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-md">
                    Login Sekarang
                 </a>
            </div>
        @endguest

    </div>
</div>

{{-- Content Section --}}
<div class="max-w-7xl mx-auto px-4 py-6">
  @php
    $warning = session('warning');
    $showGroupModal = $warning && is_null($groupNumber);
  @endphp

  @if($showGroupModal)
    <div class="fixed inset-0 z-40 flex items-center justify-center px-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
      <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-5 space-y-4">
        <div class="flex items-start gap-3">
          <div class="mt-0.5 flex-shrink-0">
            <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center">
              <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v4m0 4h.01M4.93 4.93l14.14 14.14M12 4a8 8 0 100 16 8 8 0 000-16z" />
              </svg>
            </div>
          </div>
          <div class="flex-1">
            <h2 class="text-base font-semibold text-gray-900 mb-1">
              Isi Nomor Grup Basic Listening
            </h2>
            <p class="text-sm text-gray-700 mb-1">
              {{ $warning }}
            </p>
          </div>
        </div>

        <form action="{{ route('bl.groupNumber.update') }}" method="POST" class="space-y-3">
          @csrf
          <div>
            <label for="modal_group" class="block text-xs font-medium text-gray-700 mb-1">
              Tanyakan Nomor Grup ke Tutor
            </label>
            <select id="modal_group" name="nomor_grup_bl" required
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="">Pilih Grup...</option>
              <option value="1">Grup 1</option>
              <option value="2">Grup 2</option>
              <option value="3">Grup 3</option>
              <option value="4">Grup 4</option>
            </select>
          </div>

          <div class="flex items-center justify-center gap-2 pt-1">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-sm">
              Simpan Nomor Grup
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

  {{-- Flash Messages --}}
  @if (session('success'))
    <div class="mb-4 rounded-lg border-l-4 border-emerald-500 bg-emerald-50 px-3 py-2.5 text-emerald-800 text-sm">
      {{ session('success') }}
    </div>
  @endif
  @if ($warning && ! $showGroupModal)
    <div class="mb-4 rounded-lg border-l-4 border-amber-500 bg-amber-50 px-3 py-2.5 text-amber-800 text-sm">
      {{ $warning }}
    </div>
  @endif
  @if ($errors->any())
    <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 px-3 py-2.5 text-red-800 text-sm">
      <ul class="list-disc pl-5 space-y-1">
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Sessions Grid --}}
  @if(!$quizEnabled)
    {{-- Quiz Disabled Banner --}}
    <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-5 flex items-start gap-4">
        <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
            <i class="fa-solid fa-pause-circle text-amber-600 text-xl"></i>
        </div>
        <div>
            <h4 class="text-base font-bold text-amber-800 mb-1">Quiz Sedang Dinonaktifkan</h4>
            <p class="text-sm text-amber-700">Quiz Basic Listening sementara tidak dapat diakses. Silakan hubungi admin atau tunggu pengumuman lebih lanjut.</p>
        </div>
    </div>
  @endif

  @if(isset($sessions) && count($sessions) > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 p-4">
      @foreach($sessions as $index => $s)
        @php
          $colors = [
            ['bg' => 'bg-amber-100',   'accent' => 'bg-amber-400',   'text' => 'text-amber-900',   'border' => 'border-amber-200'],
            ['bg' => 'bg-rose-100',    'accent' => 'bg-rose-400',    'text' => 'text-rose-900',    'border' => 'border-rose-200'],
            ['bg' => 'bg-sky-100',     'accent' => 'bg-sky-400',     'text' => 'text-sky-900',     'border' => 'border-sky-200'],
            ['bg' => 'bg-emerald-100', 'accent' => 'bg-emerald-400', 'text' => 'text-emerald-900', 'border' => 'border-emerald-200'],
            ['bg' => 'bg-violet-100',  'accent' => 'bg-violet-400',  'text' => 'text-violet-900',  'border' => 'border-violet-200'],
            ['bg' => 'bg-orange-100',  'accent' => 'bg-orange-400',  'text' => 'text-orange-900',  'border' => 'border-orange-200'],
          ];
          $color = $colors[$index % count($colors)];
          $rotations = ['rotate-2', '-rotate-1', 'rotate-1', '-rotate-2', 'rotate-3', '-rotate-3'];
          $rotate = $rotations[$index % count($rotations)];
        @endphp

        @if($quizEnabled)
        <a href="{{ route('bl.session.show', $s) }}"
          class="group relative block {{ $color['bg'] }} rounded-2xl p-6 {{ $rotate }} hover:rotate-0 transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-2xl hover:z-10 border-b-4 {{ $color['border'] }}">
        @else
        <div
          class="group relative block {{ $color['bg'] }} rounded-2xl p-6 {{ $rotate }} shadow-lg border-b-4 {{ $color['border'] }} opacity-60 cursor-not-allowed">
        @endif

          {{-- Decorative Tape Effect --}}
          <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 w-20 h-8 bg-white/40 backdrop-blur-sm rounded-sm shadow-md border border-white/60"
              style="transform: translateX(-50%) rotate(-2deg);">
          </div>

          {{-- Session Number Badge --}}
          <div class="absolute -top-3 -right-3 w-16 h-16 {{ $color['accent'] }} rounded-full shadow-lg flex items-center justify-center transform group-hover:rotate-12 transition-transform duration-300">
            <div class="text-center">
              <div class="text-white font-black text-lg leading-none">
                {{ (int) $s->number === 6 ? 'UAS' : $s->number }}
              </div>
              <div class="text-white/80 text-[10px] font-semibold uppercase">Sesi</div>
            </div>
          </div>

          {{-- Status Badge --}}
          <div class="mb-4 flex justify-between items-start">
            <div>
              @if($s->isOpen())
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-green-500 text-white text-xs font-bold shadow-md">
                  <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                  BUKA
                </span>
              @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-500 text-white text-xs font-bold shadow-md">
                  <span class="w-2 h-2 bg-white rounded-full"></span>
                  TUTUP
                </span>
              @endif
            </div>
          </div>

          {{-- Title --}}
          <h3 class="text-xl font-black {{ $color['text'] }} mb-4 line-clamp-3 min-h-[4.5rem] group-hover:scale-105 transition-transform leading-tight">
            {{ $s->title }}
          </h3>

          {{-- Divider --}}
          <div class="w-16 h-1 {{ $color['accent'] }} rounded-full mb-4 group-hover:w-24 transition-all"></div>

          {{-- Duration --}}
          <div class="flex items-center gap-2 mb-4 bg-white/50 rounded-lg px-3 py-2 backdrop-blur-sm">
            <svg class="w-5 h-5 {{ $color['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-bold {{ $color['text'] }}">{{ $s->duration_minutes }} menit</span>
          </div>

          {{-- Worked Stat (single metric) --}}
          <div class="mb-4">
            <div class="bg-white/60 backdrop-blur-sm rounded-lg px-3 py-2 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 {{ $color['accent'] }} rounded-md flex items-center justify-center shadow">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                          d="M9 12l2 2 4-4M7 20h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <span class="text-xs font-semibold {{ $color['text'] }} opacity-80 uppercase tracking-wide">
                  Total Peserta
                </span>
              </div>
              <span class="text-lg font-black {{ $color['text'] }}">
                {{ $s->worked_count ?? 0 }}
              </span>
            </div>
          </div>

          {{-- Schedule Info --}}
          @if($s->opens_at || $s->closes_at)
            <div class="space-y-2 bg-white/30 backdrop-blur-sm rounded-lg p-3">
              @if($s->opens_at)
                <div class="flex items-center gap-2 text-xs {{ $color['text'] }} font-semibold">
                  <svg class="w-4 h-4 {{ $color['text'] }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 11V7a4 4 0 10-8 0m4 4h8a2 2 0 012 2v5a2 2 0 01-2 2H8a2 2 0 01-2-2v-5a2 2 0 012-2h4z"/>
                  </svg>
                  <div class="flex-1">
                    <div class="text-[10px] opacity-70 uppercase">Dibuka</div>
                    <div>{{ $s->opens_at->format('d M Y, H:i') }}</div>
                  </div>
                </div>
              @endif
              @if($s->closes_at)
                <div class="flex items-center gap-2 text-xs {{ $color['text'] }} font-semibold">
                  <svg class="w-4 h-4 {{ $color['text'] }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 11V7a4 4 0 118 0v4m-8 0h8a2 2 0 012 2v5a2 2 0 01-2 2H8a2 2 0 01-2-2v-5a2 2 0 012-2z"/>
                  </svg>
                  <div class="flex-1">
                    <div class="text-[10px] opacity-70 uppercase">Ditutup</div>
                    <div>{{ $s->closes_at->format('d M Y, H:i') }}</div>
                  </div>
                </div>
              @endif
            </div>
          @endif

          {{-- Hover Arrow --}}
          <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all">
            <div class="w-8 h-8 {{ $color['accent'] }} rounded-full flex items-center justify-center shadow-md">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                      d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </div>
          </div>

          {{-- Corner Fold Effect --}}
          <div class="absolute top-0 right-0 w-0 h-0 border-t-[30px] border-r-[30px] border-t-transparent border-r-white/20 rounded-tr-2xl group-hover:border-t-[35px] group-hover:border-r-[35px] transition-all"></div>
        @if($quizEnabled)
        </a>
        @else
        </div>
        @endif
      @endforeach
    </div>
  @else
    <div class="text-center py-20">
      <div class="relative inline-block">
        <div class="text-8xl mb-6 animate-bounce">📋</div>
        <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-24 h-3 bg-gray-300/40 rounded-full blur-md"></div>
      </div>
      <h3 class="text-gray-800 text-2xl font-black mb-3">Belum Ada Pertemuan</h3>
      <p class="text-gray-600 text-base font-medium max-w-md mx-auto">
        Pertemuan akan muncul di sini setelah ditambahkan oleh admin. Pantau terus halaman ini! 🚀
      </p>
    </div>
  @endif
</div>

@endsection
