{{-- resources/views/dashboard/pendaftar.blade.php --}}
@extends('layouts.dashboard')

@section('title', 'Dashboard Pendaftar')
@section('page-title', 'Overview')

@section('content')
@php
    /** @var \App\Models\User $user */
    use Illuminate\Support\Facades\Storage;

    $avatarUrl = $user->image
        ? Storage::url($user->image)
        : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=EBF4FF&color=1E40AF&bold=true';

    // Logic Biodata
    $hasBasicInfo = $user->prody_id && $user->srn && $user->year;
    $yearInt      = (int) $user->year;
    $isS2         = $user->prody && str_starts_with($user->prody->name ?? '', 'S2');
    $isPBI        = $user->prody && $user->prody->name === 'Pendidikan Bahasa Inggris';
    $prodiIslam   = ['Komunikasi dan Penyiaran Islam', 'Pendidikan Agama Islam', 'Pendidikan Islam Anak Usia Dini'];
    $isProdiIslam = $user->prody && in_array($user->prody->name, $prodiIslam);
    $needsNilai   = $yearInt && $yearInt <= 2024 && !$isS2;

    $biodataLengkap = \App\Models\SiteSetting::isEptBiodataComplete($user);

    // Logic untuk popup WhatsApp - tampilkan jika belum ada nomor WA
    $showWhatsAppModal = empty($user->whatsapp);

    // Logic untuk menampilkan Basic Listening menu
    // Tampilkan hanya jika: tahun 2025+, bukan S2, dan sudah isi biodata dasar
    $showBasicListening = $hasBasicInfo && $yearInt >= 2025 && !$isS2;

    // EPT Registration eligibility (configurable via SiteSettings)
    $eptRegistrationOpen = \App\Models\SiteSetting::isEptRegistrationOpen();
    $eptEligibility = \App\Models\SiteSetting::checkEptEligibility($user);
    $canRegisterEpt = $eptEligibility[0] ?? false;
    $eptRegistration = \App\Models\EptRegistration::with(['grup1', 'grup2', 'grup3', 'grup4'])
        ->where('user_id', $user->id)
        ->latest()
        ->first();
    $showEptWidget = ($eptRegistrationOpen && $canRegisterEpt) || ! $eptRegistrationOpen || $eptRegistration;
@endphp

<div class="space-y-6">

    @php
        $dashboardNotifications = $latestNotifications ?? collect();
        $dashboardUnread = $unreadNotificationsCount ?? 0;
        $dashboardReadCount = $dashboardNotifications->whereNotNull('read_at')->count();
    @endphp

    @if($dashboardNotifications->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-full bg-um-blue/10 flex items-center justify-center shrink-0">
                        <i class="fa-regular fa-bell text-um-blue"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-slate-800 leading-tight">Notifikasi Terbaru</h3>
                        <p class="text-xs text-slate-500">{{ $dashboardUnread }} belum dibaca</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    @if($dashboardReadCount > 0)
                        <form method="POST" action="{{ route('dashboard.notifications.destroy-read') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">
                                Hapus yang dibaca
                            </button>
                        </form>
                    @endif
                    @if($dashboardUnread > 0)
                        <form method="POST" action="{{ route('dashboard.notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-um-blue transition hover:bg-blue-100">
                                Tandai semua dibaca
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($dashboardNotifications as $notification)
                    @php
                        $data = $notification->data ?? [];
                        $isUnread = blank($notification->read_at);
                    @endphp
                    <div class="flex items-start gap-3 px-4 py-4 transition hover:bg-slate-50 sm:gap-4 sm:px-5 {{ $isUnread ? 'bg-blue-50/40' : '' }}">
                        <div class="w-11 h-11 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                            <i class="{{ $data['icon'] ?? 'fa-regular fa-bell' }}"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <a href="{{ route('dashboard.notifications.open', $notification->id) }}" class="min-w-0 flex-1">
                                    <h4 class="text-sm font-bold text-slate-800 leading-5 line-clamp-2 sm:line-clamp-1">{{ $data['title'] ?? 'Notifikasi Baru' }}</h4>
                                    <p class="mt-1 text-sm leading-6 text-slate-500 line-clamp-3 sm:line-clamp-2">{{ $data['body'] ?? 'Ada pembaruan pada akun Anda.' }}</p>
                                </a>
                                <div class="flex items-center justify-between gap-3 sm:w-auto sm:flex-col sm:items-end sm:justify-start">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-400">
                                        @if($isUnread)
                                            <span class="inline-flex items-center gap-1 font-semibold text-um-blue">
                                                <span class="h-2.5 w-2.5 rounded-full bg-um-blue"></span>
                                                Baru
                                            </span>
                                        @endif
                                        <span class="whitespace-nowrap">{{ $notification->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <form method="POST" action="{{ route('dashboard.notifications.destroy', $notification->id) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" title="Hapus notifikasi">
                                            <i class="fa-regular fa-trash-can text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ONBOARDING CHECKLIST WIDGET --}}
    @php
        $otpEnabled = \App\Models\SiteSetting::isOtpEnabled();
        
        // Jika OTP disabled, step WA verification dianggap sudah selesai
        $waVerified = $otpEnabled ? !empty($user->whatsapp_verified_at) : !empty($user->whatsapp);
        
        // Total steps: 2 jika OTP disabled, 3 jika OTP enabled
        $totalSteps = $otpEnabled ? 3 : 2;
        
        $stepsDone = 1; // Akun terdaftar selalu done
        if ($waVerified) $stepsDone++;
        if (!$otpEnabled && $biodataLengkap) {
            // Jika OTP disabled, biodata adalah step 2
            $stepsDone = $biodataLengkap ? 2 : 1;
        } elseif ($otpEnabled) {
            if ($waVerified) $stepsDone++;
            if ($biodataLengkap) $stepsDone++;
        }
        
        // Recalculate
        $stepsDone = 1;
        if ($otpEnabled) {
            if ($waVerified) $stepsDone++;
            if ($biodataLengkap) $stepsDone++;
        } else {
            // OTP disabled: skip WA verification step, hanya 2 step
            if ($biodataLengkap) $stepsDone++;
        }
        
        $allDone = ($stepsDone === $totalSteps);
        $progressPercent = ($stepsDone / $totalSteps) * 100;
    @endphp
    
    @if(!$allDone)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Header --}}
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-um-blue/10 flex items-center justify-center">
                        <i class="fa-solid fa-list-check text-um-blue"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800">Lengkapi Akun Anda</h3>
                        <p class="text-xs text-slate-500">{{ $stepsDone }}/{{ $totalSteps }} langkah selesai</p>
                    </div>
                </div>
                <span class="text-sm font-bold text-um-blue">{{ round($progressPercent) }}%</span>
            </div>
            
            {{-- Progress Bar --}}
            <div class="h-1.5 bg-slate-100">
                <div class="h-full bg-um-blue transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
            </div>
            
            {{-- Steps --}}
            <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Step 1: Akun Terdaftar --}}
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-check text-emerald-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Akun Terdaftar</p>
                        <p class="text-xs text-emerald-600">Selesai</p>
                    </div>
                </div>
                
                {{-- Step 2: Verifikasi WhatsApp (hanya tampil jika OTP enabled) --}}
                @if($otpEnabled)
                <div class="flex items-start gap-3">
                    @if($waVerified)
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-check text-emerald-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">WhatsApp Terverifikasi</p>
                            <p class="text-xs text-emerald-600">Selesai</p>
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                            <span class="text-amber-600 font-bold text-sm">2</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Verifikasi WhatsApp</p>
                            <a href="{{ route('dashboard.biodata') }}" class="text-xs text-um-blue hover:underline">
                                Verifikasi sekarang →
                            </a>
                        </div>
                    @endif
                </div>
                @endif
                
                {{-- Step 3 (atau 2 jika OTP disabled): Lengkapi Biodata --}}
                <div class="flex items-start gap-3">
                    @if($biodataLengkap)
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-check text-emerald-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Biodata Lengkap</p>
                            <p class="text-xs text-emerald-600">Selesai</p>
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                            <span class="text-amber-600 font-bold text-sm">{{ $otpEnabled ? '3' : '2' }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Lengkapi Biodata</p>
                            <a href="{{ route('dashboard.biodata') }}" class="text-xs text-um-blue hover:underline">
                                Lengkapi sekarang →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- SECTION 1: Welcome & Biodata Status --}}
    <div class="grid grid-cols-1 {{ $biodataLengkap ? 'lg:grid-cols-1' : 'lg:grid-cols-3' }} gap-6">
        
        {{-- Welcome Card --}}
        <div class="{{ $biodataLengkap ? '' : 'lg:col-span-2' }} bg-white rounded-xl border border-slate-200 shadow-sm p-4 lg:p-8 flex items-center gap-4 lg:gap-8 relative overflow-hidden group">
            
            {{-- [BARU] Tombol Home di Pojok Kanan Atas --}}
            <a href="{{ url('/') }}" 
               class="absolute top-3 right-4 z-20 text-slate-400 hover:text-blue-600 border border-transparent transition-all" 
               title="Kembali ke Halaman Utama">
                <i class="fa-solid fa-house text-sm"></i>
            </a>
            {{-- [END BARU] --}}

            {{-- Dekorasi Background --}}
            <div class="hidden lg:block absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-gradient-to-br from-blue-50 to-blue-100 rounded-full blur-2xl opacity-60 pointer-events-none"></div>
            
            {{-- ... Sisa kode Foto Profil dan Info User biarkan sama ... --}}
             <img src="{{ $avatarUrl }}" 
                 alt="Foto Profil" 
                 class="h-12 w-12 lg:h-24 lg:w-24 rounded-full object-cover border border-slate-200 shadow-sm shrink-0 z-10 transition-all duration-300">
            
            {{-- Info User (Sama seperti sebelumnya) --}}
            <div class="flex-1 min-w-0 z-10 space-y-1 lg:space-y-3">
               {{-- ... content info user ... --}}
               <div>
                    <h2 class="text-base lg:text-3xl font-bold text-slate-900 truncate leading-tight">
                        Hi, <span class="lg:hidden">{{ explode(' ', $user->name)[0] }}!</span>
                        <span class="hidden lg:inline">{{ $user->name }}!</span>
                    </h2>
                    <p class="text-xs lg:text-base text-slate-500 truncate">
                        {{ $user->email }}
                    </p>
                </div>
                 {{-- ... badge prodi & tahun ... --}}
                 <div class="hidden lg:flex flex-wrap gap-3">
                    @if ($user->prody)
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-blue-50 text-blue-700 text-sm font-medium border border-blue-100">
                            <i class="fa-solid fa-graduation-cap"></i>
                            {{ $user->prody->name }}
                        </span>
                    @endif
                    @if ($user->year)
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-slate-50 text-slate-700 text-sm font-medium border border-slate-200">
                            <i class="fa-solid fa-calendar-days"></i>
                            Angkatan {{ $user->year }}
                        </span>
                    @endif
                </div>
                
                 {{-- TAMPILAN HP --}}
                <div class="lg:hidden flex items-center gap-2 text-[11px] font-medium text-slate-600">
                    @if ($user->prody)
                        <span class="flex items-center gap-1 truncate">
                            <i class="fa-solid fa-graduation-cap text-slate-400"></i>
                            {{ $user->prody->name }}
                        </span>
                    @endif
                    @if ($user->year)
                        <span class="text-slate-300 mx-1">•</span>
                        <span class="flex items-center gap-1 whitespace-nowrap">{{ $user->year }}</span>
                    @endif
                </div>
            </div>
        </div>

        @unless($biodataLengkap)
            {{-- Biodata Action Card --}}
            <div class="rounded-xl border shadow-sm p-6 flex flex-col justify-center relative overflow-hidden bg-amber-50 border-amber-100">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600">
                            Status Akun
                        </p>
                        <h3 class="text-lg font-bold text-amber-800">
                            Lengkapi Data
                        </h3>
                    </div>
                    <div class="h-10 w-10 rounded-full flex items-center justify-center bg-amber-100 text-amber-600">
                        <i class="fa-solid fa-exclamation text-lg"></i>
                    </div>
                </div>

                <p class="text-sm text-amber-700 mb-4 leading-relaxed">
                    Mohon lengkapi biodata {{ $needsNilai ? 'dan nilai yang diperlukan' : '' }} agar dapat mengakses layanan.
                </p>

                <a href="{{ route('dashboard.biodata') }}"
                   class="inline-flex items-center justify-center gap-2 w-full py-2 rounded-lg text-sm font-semibold transition-colors bg-amber-600 text-white hover:bg-amber-700 shadow-sm">
                    Lengkapi Sekarang
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        @endunless
    </div>

    {{-- Menggunakan variabel $user yang sudah didefinisikan di atas --}}
    @if ($user && $user->hasRole('tutor'))
        <div class="group relative bg-white rounded-xl border border-indigo-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
            
            {{-- Aksen garis biru di kiri --}}
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-indigo-500"></div>

            {{-- Hiasan Background Halus --}}
            <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-50 rounded-bl-full opacity-50 pointer-events-none"></div>

            <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
                
                {{-- Kiri: Icon & Teks --}}
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wider font-bold text-indigo-600 mb-0.5">
                            Akses Khusus
                        </p>
                        <h3 class="text-sm font-bold text-slate-800">
                            Panel Tutor Basic Listening
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Kelola mahasiswa binaan & input nilai di sini.
                        </p>
                    </div>
                </div>

                {{-- Kanan: Tombol Action (Lebih Soft) --}}
                <div class="flex-shrink-0">
                    <a href="{{ route('filament.admin.pages.2') }}" 
                       class="inline-flex w-full sm:w-auto justify-center items-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors shadow-indigo-200 shadow-sm">
                        <span>Buka Panel</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- SECTION 2: Quick Actions (Fixed Layout) --}}
    <div>
        <h3 class="text-sm font-bold text-slate-900 mb-3 px-1">Menu Cepat</h3>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">

            {{-- 1. Basic Listening (hanya untuk tahun 2025+ dan bukan S2) --}}
            @if($showBasicListening)
            {{-- col-span-2 (HP) -> col-span-1 (Laptop/Tablet) --}}
            <a href="{{ route('bl.index') }}" 
               title="Ikuti kelas Basic Listening dan dapatkan sertifikat"
               class="col-span-2 md:col-span-1 group relative overflow-hidden bg-violet-600 rounded-xl shadow-md shadow-violet-200 transition-all duration-200 hover:shadow-lg hover:bg-violet-700 flex items-center p-3.5 gap-3">
                
                {{-- Dekorasi Background --}}
                <div class="absolute right-0 top-0 -mt-2 -mr-2 w-16 h-16 bg-white opacity-10 rounded-full blur-xl pointer-events-none"></div>
                
                <div class="shrink-0 h-10 w-10 rounded-lg bg-white/20 flex items-center justify-center text-white border border-white/10 group-hover:scale-105 transition-transform">
                    <i class="fa-solid fa-headphones text-lg"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-bold text-white truncate">Basic Listening</div>
                    <div class="text-[10px] text-violet-100/90 truncate mt-0.5">Kelas & Sertifikat</div>
                </div>
                {{-- Icon Panah (Hanya muncul di HP biar tidak sempit di laptop) --}}
                <i class="fa-solid fa-chevron-right text-white/50 text-xs hidden sm:block"></i>
            </a>
            @endif

            {{-- Helper Style --}}
            @php
                $cardClass = "group bg-white border border-slate-200 rounded-xl p-3.5 flex items-center gap-3 shadow-sm hover:border-blue-300 hover:shadow-md transition-all duration-200";
                $iconBase  = "shrink-0 h-10 w-10 rounded-lg flex items-center justify-center text-lg transition-transform group-hover:scale-105";
            @endphp

            {{-- 2. Surat Rekomendasi --}}
            <a href="{{ route('dashboard.ept') }}" title="Ajukan surat rekomendasi setelah 3x tes EPT" class="{{ $cardClass }}">
                <div class="{{ $iconBase }} bg-blue-50 text-blue-600">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-slate-800 group-hover:text-blue-600 truncate">Rekomendasi</div>
                    <div class="text-[10px] text-slate-500 truncate mt-0.5">Ajukan surat</div>
                </div>
            </a>

            {{-- 3. Penerjemahan --}}
            <a href="{{ route('dashboard.translation') }}" title="Layanan penerjemahan abstrak untuk jurnal ilmiah" class="{{ $cardClass }} hover:border-indigo-300">
                <div class="{{ $iconBase }} bg-indigo-50 text-indigo-600">
                    <i class="fa-solid fa-language"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-slate-800 group-hover:text-indigo-600 truncate">Penerjemahan</div>
                    <div class="text-[10px] text-slate-500 truncate mt-0.5">Abstrak/Dokumen</div>
                </div>
            </a>

            {{-- 4. Cek Nilai EPT --}}
            <a href="{{ route('front.scores') }}" title="Lihat skor EPT Anda berdasarkan NPM" class="{{ $cardClass }} hover:border-emerald-300">
                <div class="{{ $iconBase }} bg-emerald-50 text-emerald-600">
                    <i class="fa-solid fa-square-poll-vertical"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-slate-800 group-hover:text-emerald-600 truncate">Cek Nilai EPT</div>
                    <div class="text-[10px] text-slate-500 truncate mt-0.5">Lihat skor</div>
                </div>
            </a>

            {{-- 5. Cek Jadwal EPT --}}
            <a href="{{ route('front.schedule') }}" title="Lihat jadwal tes EPT yang tersedia" class="{{ $cardClass }} hover:border-amber-300">
                <div class="{{ $iconBase }} bg-amber-50 text-amber-600">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-slate-800 group-hover:text-amber-600 truncate">Jadwal EPT</div>
                    <div class="text-[10px] text-slate-500 truncate mt-0.5">Info pelaksanaan</div>
                </div>
            </a>

        </div>
    </div>

    {{-- SECTION: EPT Registration Widget --}}
    @if($showEptWidget)
        @if(!$eptRegistration)
            @if(!$eptRegistrationOpen)
                <div class="relative overflow-hidden bg-slate-700 rounded-2xl shadow-lg p-6 lg:p-8 mb-6">
                    <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-10 rounded-full"></div>
                    <div class="absolute left-0 bottom-0 -mb-8 -ml-8 w-32 h-32 bg-white opacity-5 rounded-full"></div>

                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center text-white shrink-0">
                                <i class="fa-solid fa-lock text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold text-white mb-1">Sementara Pendaftaran EPT Ditutup</h4>
                                <p class="text-slate-200 text-sm leading-relaxed max-w-md">
                                    Pendaftaran baru sedang tidak dibuka. Silakan tunggu informasi berikutnya dari Lembaga Bahasa.
                                </p>
                            </div>
                        </div>
                        <span class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-white/10 text-white font-bold text-sm border border-white/15 shrink-0">
                            <i class="fa-solid fa-clock"></i>
                            Menunggu Dibuka
                        </span>
                    </div>
                </div>
            @else
                {{-- Belum Daftar - Banner Biru Mencolok --}}
                <div class="relative overflow-hidden bg-um-blue rounded-2xl shadow-lg p-6 lg:p-8 mb-6">
                    <div class="absolute right-0 top-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-10 rounded-full"></div>
                    <div class="absolute left-0 bottom-0 -mb-8 -ml-8 w-32 h-32 bg-white opacity-5 rounded-full"></div>

                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-white shrink-0">
                                <i class="fa-solid fa-clipboard-list text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="text-xl font-bold text-white mb-1">Pendaftaran Tes EPT</h4>
                                <p class="text-blue-100 text-sm leading-relaxed max-w-md">
                                    Daftarkan diri Anda untuk mengikuti Tes EPT dengan mengunggah bukti pembayaran.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('dashboard.ept-registration.index') }}"
                           class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-white text-um-blue font-bold text-sm shadow-lg hover:bg-blue-50 transition-all hover:scale-[1.02] shrink-0">
                            Daftar Sekarang
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @endif

        @elseif($eptRegistration->status === 'pending')
            {{-- Status: Menunggu Verifikasi --}}
            <div class="relative overflow-hidden bg-amber-500 rounded-2xl shadow-lg p-6 lg:p-8 mb-6">
                <div class="absolute right-0 top-0 -mt-8 -mr-8 w-32 h-32 bg-white opacity-10 rounded-full"></div>
                
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-white shrink-0">
                            <i class="fa-solid fa-clock text-2xl"></i>
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-white text-xs font-bold mb-2">
                                <i class="fa-solid fa-hourglass-half"></i> Menunggu Verifikasi
                            </div>
                            <h4 class="text-lg font-bold text-white">Pendaftaran Sedang Diproses</h4>
                            <p class="text-amber-100 text-sm mt-1">
                                Diajukan pada {{ $eptRegistration->created_at->translatedFormat('d F Y, H:i') }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard.ept-registration.index') }}"
                       class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-white text-amber-600 font-semibold text-sm hover:bg-amber-50 transition shrink-0">
                        Lihat Detail
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

        @elseif($eptRegistration->status === 'rejected')
            {{-- Status: Ditolak --}}
            <div class="relative overflow-hidden bg-red-500 rounded-2xl shadow-lg p-6 lg:p-8 mb-6">
                <div class="absolute right-0 top-0 -mt-8 -mr-8 w-32 h-32 bg-white opacity-10 rounded-full"></div>
                
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-white shrink-0">
                            <i class="fa-solid fa-xmark text-2xl"></i>
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-white text-xs font-bold mb-2">
                                <i class="fa-solid fa-circle-xmark"></i> Ditolak
                            </div>
                            <h4 class="text-lg font-bold text-white">Pendaftaran Ditolak</h4>
                            <p class="text-red-100 text-sm mt-1 max-w-md">
                                {{ Str::limit($eptRegistration->rejection_reason ?? 'Silakan upload ulang bukti pembayaran.', 80) }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard.ept-registration.index') }}"
                       class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-white text-red-600 font-semibold text-sm hover:bg-red-50 transition shrink-0">
                        <i class="fa-solid {{ $canRegisterEpt ? 'fa-redo' : 'fa-arrow-right' }}"></i>
                        {{ $canRegisterEpt ? 'Daftar Ulang' : 'Lihat Detail' }}
                    </a>
                </div>
            </div>

        @elseif($eptRegistration->status === 'approved')
            {{-- Status: Disetujui --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-emerald-100 text-xs font-medium">Status Pendaftaran</p>
                            <h4 class="text-white font-bold">EPT Disetujui</h4>
                        </div>
                        <a href="{{ route('dashboard.ept-registration.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white/20 hover:bg-white/30 text-white text-xs font-semibold transition">
                            <span>Lihat Detail</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
                
                {{-- Jadwal Grid --}}
                <div class="p-4">
                    @php
                        $grups = array_slice([
                            1 => $eptRegistration->grup1,
                            2 => $eptRegistration->grup2,
                            3 => $eptRegistration->grup3,
                            4 => $eptRegistration->grup4,
                        ], 0, $eptRegistration->requiredGroupCount(), true);
                    @endphp
                    <div class="space-y-2">
                        @foreach($grups as $i => $grup)
                            <div class="flex items-center gap-3 p-3 rounded-xl {{ $grup?->jadwal ? 'bg-slate-50 border border-slate-100' : 'bg-amber-50 border border-amber-100' }}">
                                <span class="w-8 h-8 rounded-lg {{ $grup?->jadwal ? 'bg-blue-600' : 'bg-slate-300' }} text-white text-sm font-bold flex items-center justify-center shrink-0">{{ $i }}</span>
                                <div class="flex-1 min-w-0">
                                    @if($grup)
                                        <p class="font-semibold text-slate-900 text-sm truncate">Grup {{ $grup->name }}</p>
                                        @if($grup->jadwal)
                                            <p class="text-xs text-slate-500">{{ $grup->jadwal->translatedFormat('d M Y, H:i') }}</p>
                                        @else
                                            <p class="text-xs text-amber-600 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Menunggu jadwal
                                            </p>
                                        @endif
                                    @else
                                        <p class="text-xs text-slate-400">Belum ditentukan</p>
                                    @endif
                                </div>
                                @if($grup?->jadwal)
                                    <a href="{{ route('dashboard.ept-registration.index') }}"
                                       class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Kartu
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- SECTION 3: Recent Activity / Status Widgets --}}
    @php
        $translation = $latestTranslation ?? null;
        $ept         = $latestEptSubmission ?? null;

        // Helper untuk menentukan Styling Full Card
        $getTheme = function($status) {
            $s = strtolower($status ?? '');
            
            // Default Theme (Abu-abu/Netral)
            $theme = [
                'card'    => 'bg-white border-slate-200 hover:border-slate-300',
                'text'    => 'text-slate-800',
                'subtext' => 'text-slate-500',
                'icon_bg' => 'bg-slate-100 text-slate-400',
                'badge'   => 'bg-slate-100 text-slate-600',
                'btn'     => 'bg-slate-800 text-white hover:bg-slate-700'
            ];

            // KUNING: Menunggu / Pending
            if ($s === 'menunggu' || $s === 'pending') {
                $theme = [
                    'card'    => 'bg-amber-50 border-amber-200 hover:border-amber-300',
                    'text'    => 'text-amber-900',
                    'subtext' => 'text-amber-700/70',
                    'icon_bg' => 'bg-white/60 text-amber-500',
                    'badge'   => 'bg-white text-amber-700 border border-amber-200 shadow-sm',
                    'btn'     => 'bg-amber-600 text-white hover:bg-amber-700'
                ];
            }
            // BIRU: Sedang Diproses (Opsional, jika ingin dibedakan)
            elseif ($s === 'diproses') {
                $theme = [
                    'card'    => 'bg-blue-50 border-blue-200 hover:border-blue-300',
                    'text'    => 'text-blue-900',
                    'subtext' => 'text-blue-700/70',
                    'icon_bg' => 'bg-white/60 text-blue-500',
                    'badge'   => 'bg-white text-blue-700 border border-blue-200 shadow-sm',
                    'btn'     => 'bg-blue-600 text-white hover:bg-blue-700'
                ];
            }
            // HIJAU CERAH (EMERALD): Disetujui, Approved, Selesai
            elseif (in_array($s, ['disetujui', 'approved', 'selesai'])) {
                $theme = [
                    'card'    => 'bg-emerald-50 border-emerald-200 hover:border-emerald-300', // Background hijau sangat muda
                    'text'    => 'text-emerald-900', // Teks hijau tua
                    'subtext' => 'text-emerald-700/70',
                    'icon_bg' => 'bg-white/60 text-emerald-600', // Icon hijau cerah
                    'badge'   => 'bg-white text-emerald-700 border border-emerald-200 shadow-sm',
                    'btn'     => 'bg-emerald-600 text-white hover:bg-emerald-700'
                ];
            }
            // MERAH: Ditolak / Rejected
            elseif (str_contains($s, 'ditolak') || $s === 'rejected') {
                $theme = [
                    'card'    => 'bg-rose-50 border-rose-200 hover:border-rose-300',
                    'text'    => 'text-rose-900',
                    'subtext' => 'text-rose-700/70',
                    'icon_bg' => 'bg-white/60 text-rose-500',
                    'badge'   => 'bg-white text-rose-700 border border-rose-200 shadow-sm',
                    'btn'     => 'bg-rose-600 text-white hover:bg-rose-700'
                ];
            }

            return (object) $theme;
        };

        $statusTrans = $translation?->status;
        $statusEpt = $ept?->status;

        $labelEpt = match($statusEpt) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => $statusEpt
        };

        $themeTrans = $getTheme($translation ? $statusTrans : null);
        $themeEpt   = $getTheme($ept ? $statusEpt : null);

    @endphp

    <h3 class="text-lg font-bold text-slate-800 mt-8 mb-5 px-1 flex items-center gap-2">
        <span class="w-1 h-6 bg-um-blue rounded-full"></span> Status Layanan Terakhir
    </h3>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Widget 1: Penerjemahan --}}
        <div class="group relative rounded-2xl border p-6 transition-all duration-300 flex flex-col h-full overflow-hidden {{ $themeTrans->card }}">
            
            {{-- Watermark Icon --}}
            <div class="absolute -right-6 -bottom-6 text-9xl opacity-[0.07] pointer-events-none select-none transition-transform group-hover:scale-110">
                <i class="fa-solid fa-language"></i>
            </div>

            {{-- Header --}}
            <div class="relative z-10 flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl flex items-center justify-center shadow-sm {{ $themeTrans->icon_bg }}">
                        <i class="fa-solid fa-language text-lg"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold {{ $themeTrans->text }}">Penerjemahan Abstrak</h4>
                        <p class="text-xs {{ $themeTrans->subtext }}">Layanan Bahasa</p>
                    </div>
                </div>
                @if($translation)
                    <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider {{ $themeTrans->badge }}">
                        {{ $statusTrans }}
                    </span>
                @endif
            </div>

            {{-- Body --}}
            <div class="relative z-10 flex-1 flex flex-col">
                @if (!$translation)
                    <div class="flex-1 flex flex-col items-center justify-center py-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                            <i class="fa-solid fa-file-lines text-2xl text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-700 mb-1">Belum Ada Pengajuan</p>
                        <p class="text-xs text-slate-500 mb-4 max-w-xs">Layanan penerjemahan abstrak untuk keperluan jurnal ilmiah & publikasi.</p>
                        <a href="{{ route('dashboard.translation') }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-um-blue text-white text-xs font-bold shadow-sm hover:bg-um-dark-blue transition">
                            <i class="fa-solid fa-plus"></i> Ajukan Sekarang
                        </a>
                    </div>
                @else
                    <div class="mt-2">
                        <p class="text-xs uppercase tracking-wide opacity-70 mb-1 {{ $themeTrans->text }}">Tanggal Pengajuan</p>
                        <p class="text-base font-bold {{ $themeTrans->text }}">
                            {{ optional($translation->submission_date ?? $translation->created_at)->translatedFormat('l, d F Y') }}
                        </p>
                    </div>

                    @php
                        $hasFile = $translation->dokumen_terjemahan || $translation->final_file_path;
                        $downloadUrl = $translation->dokumen_terjemahan
                            ? Storage::url($translation->dokumen_terjemahan)
                            : ($translation->final_file_path ? Storage::url($translation->final_file_path) : null);
                    @endphp

                    <div class="mt-auto pt-6 flex flex-wrap gap-3">
                        @if ($statusTrans === 'Selesai' && $hasFile && $downloadUrl)
                            <a href="{{ $downloadUrl }}" target="_blank" class="shadow-sm px-4 py-2 rounded-lg text-xs font-semibold transition-transform active:scale-95 flex items-center gap-2 bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50">
                                <i class="fa-solid fa-download"></i> Unduh
                            </a>
                        @endif
                        <a href="{{ route('dashboard.translation') }}" class="shadow-sm px-4 py-2 rounded-lg text-xs font-semibold transition-transform active:scale-95 flex items-center gap-2 {{ $themeTrans->badge }} hover:brightness-95">
                            Detail <i class="fa-solid fa-arrow-right opacity-50"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Widget 2: Surat Rekomendasi EPT --}}
        <div class="group relative rounded-2xl border p-6 transition-all duration-300 flex flex-col h-full overflow-hidden {{ $themeEpt->card }}">
            
            {{-- Watermark Icon --}}
            <div class="absolute -right-6 -bottom-6 text-9xl opacity-[0.07] pointer-events-none select-none transition-transform group-hover:scale-110">
                <i class="fa-solid fa-file-signature"></i>
            </div>

            {{-- Header --}}
            <div class="relative z-10 flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl flex items-center justify-center shadow-sm {{ $themeEpt->icon_bg }}">
                        <i class="fa-solid fa-file-signature text-lg"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold {{ $themeEpt->text }}">Surat Rekomendasi</h4>
                        <p class="text-xs {{ $themeEpt->subtext }}">Administrasi EPT</p>
                    </div>
                </div>
                @if($ept)
                    <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider {{ $themeEpt->badge }}">
                        {{ $labelEpt }}
                    </span>
                @endif
            </div>

            {{-- Body --}}
            <div class="relative z-10 flex-1 flex flex-col">
                @if (!$ept)
                    <div class="flex-1 flex flex-col items-center justify-center py-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                            <i class="fa-solid fa-file-pen text-2xl text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-700 mb-1">Belum Ada Pengajuan</p>
                        <p class="text-xs text-slate-500 mb-4 max-w-xs">Ajukan surat rekomendasi EPT setelah mengikuti 3x tes.</p>
                        <a href="{{ route('dashboard.ept') }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-um-blue text-white text-xs font-bold shadow-sm hover:bg-um-dark-blue transition">
                            <i class="fa-solid fa-plus"></i> Ajukan Sekarang
                        </a>
                    </div>
                @else
                    <div class="mt-2">
                        <p class="text-xs uppercase tracking-wide opacity-70 mb-1 {{ $themeEpt->text }}">Waktu Pengajuan</p>
                        <p class="text-base font-bold {{ $themeEpt->text }}">
                            {{ optional($ept->created_at)->translatedFormat('l, d F Y') }}
                            <span class="text-xs font-normal opacity-80 ml-1">pukul {{ optional($ept->created_at)->format('H:i') }}</span>
                        </p>
                    </div>
                    
                    <div class="mt-auto pt-6 flex flex-wrap gap-3">
                        <a href="{{ route('dashboard.ept') }}" class="w-full justify-center shadow-sm px-4 py-2 rounded-lg text-xs font-semibold transition-transform active:scale-95 flex items-center gap-2 {{ $themeEpt->badge }} hover:brightness-95">
                            Lihat Riwayat <i class="fa-solid fa-arrow-right opacity-50"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- WELCOME MODAL (untuk user baru atau belum verifikasi WA) --}}
@php
    $showWelcomeModal = !$user->has_seen_welcome || empty($user->whatsapp);
    $otpEnabledForModal = \App\Models\SiteSetting::isOtpEnabled();
@endphp

@if($showWelcomeModal)
<div x-data="{ 
    open: true,
    step: '{{ empty($user->whatsapp) ? 'whatsapp' : 'welcome' }}',
    phone: '',
    otp: '',
    loading: false,
    error: '',
    countdown: 0,
    async sendOtp() {
        if (!this.phone || this.phone.length < 10) {
            this.error = 'Nomor WhatsApp tidak valid';
            return;
        }
        this.loading = true;
        this.error = '';
        try {
            const res = await fetch('{{ route('api.whatsapp.send-otp') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ whatsapp: this.phone })
            });
            const data = await res.json();
            if (data.success) {
                this.step = 'otp';
                this.countdown = 60;
                this.startCountdown();
            } else {
                this.error = data.message || 'Gagal mengirim OTP';
            }
        } catch (e) {
            this.error = 'Terjadi kesalahan';
        }
        this.loading = false;
    },
    async savePhoneOnly() {
        if (!this.phone || this.phone.length < 10) {
            this.error = 'Nomor WhatsApp tidak valid';
            return;
        }
        this.loading = true;
        this.error = '';
        try {
            const res = await fetch('{{ route('api.whatsapp.save-only') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ whatsapp: this.phone })
            });
            const data = await res.json();
            if (data.success) {
                this.step = 'success';
                setTimeout(() => this.dismissWelcome(), 1500);
            } else {
                this.error = data.message || 'Gagal menyimpan nomor';
            }
        } catch (e) {
            this.error = 'Terjadi kesalahan';
        }
        this.loading = false;
    },
    async verifyOtp() {
        if (!this.otp || this.otp.length !== 6) {
            this.error = 'Kode OTP harus 6 digit';
            return;
        }
        this.loading = true;
        this.error = '';
        try {
            const res = await fetch('{{ route('api.whatsapp.verify-otp') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ otp: this.otp })
            });
            const data = await res.json();
            if (data.success) {
                this.step = 'success';
                setTimeout(() => this.dismissWelcome(), 1500);
            } else {
                this.error = data.message || 'OTP tidak valid';
            }
        } catch (e) {
            this.error = 'Terjadi kesalahan';
        }
        this.loading = false;
    },
    startCountdown() {
        const interval = setInterval(() => {
            this.countdown--;
            if (this.countdown <= 0) clearInterval(interval);
        }, 1000);
    },
    async dismissWelcome() {
        await fetch('{{ route('api.dismiss-welcome') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        this.open = false;
    }
}" x-cloak>
    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50">
    </div>

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
            
            {{-- Close Button --}}
            <button @click="dismissWelcome()" 
                    class="absolute top-4 right-4 text-white/70 hover:text-white transition-colors z-10">
                <i class="fa-solid fa-times text-lg"></i>
            </button>

            {{-- Header --}}
            <div class="bg-um-blue px-6 py-6 text-center relative">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-white rounded-full"></div>
                    <div class="absolute -left-4 -bottom-4 w-16 h-16 bg-white rounded-full"></div>
                </div>
                <div class="relative">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white/20 mb-3">
                        <i class="fa-solid fa-graduation-cap text-3xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">Selamat Datang!</h3>
                    <p class="text-blue-100 text-sm mt-1">Lembaga Bahasa UM Metro</p>
                </div>
            </div>

            {{-- Step: Welcome --}}
            <template x-if="step === 'welcome'">
                <div>
                    <div class="px-6 py-5">
                        <p class="text-sm text-slate-600 mb-4">Berikut langkah untuk memulai:</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                                <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-sm font-bold">✓</div>
                                <span class="text-sm text-emerald-700 font-medium">Akun berhasil dibuat</span>
                            </div>
                            <div class="flex items-center gap-3 p-3 rounded-xl {{ $biodataLengkap ? 'bg-emerald-50 border-emerald-100' : 'bg-amber-50 border-amber-100' }} border">
                                <div class="w-8 h-8 rounded-full {{ $biodataLengkap ? 'bg-emerald-500 text-white' : 'bg-amber-400 text-white' }} flex items-center justify-center text-sm font-bold">
                                    {{ $biodataLengkap ? '✓' : '2' }}
                                </div>
                                <span class="text-sm {{ $biodataLengkap ? 'text-emerald-700' : 'text-amber-700' }} font-medium">
                                    {{ $biodataLengkap ? 'Biodata lengkap' : 'Lengkapi biodata Anda' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="w-8 h-8 rounded-full bg-slate-300 text-white flex items-center justify-center text-sm font-bold">3</div>
                                <span class="text-sm text-slate-600 font-medium">Gunakan layanan (EPT, Terjemahan)</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 pb-6">
                        <button @click="dismissWelcome()" 
                                class="w-full py-3 px-4 rounded-xl bg-um-blue text-white text-sm font-bold hover:bg-um-dark-blue transition">
                            Mulai Sekarang
                        </button>
                    </div>
                </div>
            </template>

            {{-- Step: Input WhatsApp --}}
            <template x-if="step === 'whatsapp'">
                <div>
                    <div class="px-6 py-5">
                        <p class="text-sm text-slate-600 mb-4">Masukkan nomor WhatsApp untuk menerima notifikasi:</p>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fa-brands fa-whatsapp text-green-500"></i>
                            </div>
                            <input type="tel" 
                                   x-model="phone"
                                   inputmode="numeric"
                                   class="pl-10 block w-full py-3 rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-green-500 focus:ring-green-500 text-base"
                                   placeholder="08xxxxxxxxxx">
                        </div>
                        <p x-show="error" x-text="error" class="mt-2 text-xs text-rose-500"></p>
                    </div>
                    <div class="px-6 pb-6 flex gap-3">
                        <button @click="dismissWelcome()" 
                                class="flex-1 py-3 px-4 rounded-xl bg-slate-100 text-slate-600 text-sm font-medium hover:bg-slate-200 transition">
                            Nanti Saja
                        </button>
                        @if($otpEnabledForModal)
                            {{-- OTP Enabled: Kirim OTP --}}
                            <button @click="sendOtp()"
                                    :disabled="loading || !phone"
                                    :class="{'opacity-50 cursor-not-allowed': loading || !phone}"
                                    class="flex-1 py-3 px-4 rounded-xl bg-green-500 text-white text-sm font-bold hover:bg-green-600 transition flex items-center justify-center gap-2">
                                <i x-show="!loading" class="fa-solid fa-paper-plane"></i>
                                <i x-show="loading" class="fa-solid fa-spinner fa-spin"></i>
                                Kirim OTP
                            </button>
                        @else
                            {{-- OTP Disabled: Simpan langsung --}}
                            <button @click="savePhoneOnly()"
                                    :disabled="loading || !phone"
                                    :class="{'opacity-50 cursor-not-allowed': loading || !phone}"
                                    class="flex-1 py-3 px-4 rounded-xl bg-green-500 text-white text-sm font-bold hover:bg-green-600 transition flex items-center justify-center gap-2">
                                <i x-show="!loading" class="fa-solid fa-check"></i>
                                <i x-show="loading" class="fa-solid fa-spinner fa-spin"></i>
                                Simpan Nomor
                            </button>
                        @endif
                    </div>
                </div>
            </template>

            {{-- Step: OTP Verification --}}
            <template x-if="step === 'otp'">
                <div>
                    <div class="px-6 py-5">
                        <div class="p-3 rounded-xl bg-green-50 border border-green-100 text-sm text-green-700 mb-4">
                            <i class="fa-solid fa-check-circle"></i>
                            OTP dikirim ke <strong x-text="phone"></strong>
                        </div>
                        <input type="text" 
                               x-model="otp"
                               maxlength="6"
                               inputmode="numeric"
                               class="w-full text-center text-2xl tracking-[0.4em] font-mono py-3 rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-green-500 focus:ring-green-500"
                               placeholder="● ● ● ● ● ●">
                        <p x-show="error" x-text="error" class="mt-2 text-xs text-rose-500 text-center"></p>
                        <div class="mt-3 flex justify-between text-xs">
                            <button @click="step = 'whatsapp'" class="text-slate-500 hover:text-slate-700">
                                <i class="fa-solid fa-arrow-left"></i> Ganti Nomor
                            </button>
                            <button @click="sendOtp()" 
                                    :disabled="countdown > 0"
                                    :class="countdown > 0 ? 'text-slate-400' : 'text-green-600 hover:text-green-700'">
                                <span x-show="countdown > 0">Kirim ulang (<span x-text="countdown"></span>s)</span>
                                <span x-show="countdown <= 0">Kirim ulang OTP</span>
                            </button>
                        </div>
                    </div>
                    <div class="px-6 pb-6">
                        <button @click="verifyOtp()"
                                :disabled="loading || otp.length !== 6"
                                :class="{'opacity-50 cursor-not-allowed': loading || otp.length !== 6}"
                                class="w-full py-3 px-4 rounded-xl bg-green-500 text-white text-sm font-bold hover:bg-green-600 transition flex items-center justify-center gap-2">
                            <i x-show="!loading" class="fa-solid fa-check"></i>
                            <i x-show="loading" class="fa-solid fa-spinner fa-spin"></i>
                            Verifikasi
                        </button>
                    </div>
                </div>
            </template>

            {{-- Step: Success --}}
            <template x-if="step === 'success'">
                <div class="px-6 py-10 text-center">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-check text-3xl text-emerald-500"></i>
                    </div>
                    <h4 class="text-lg font-bold text-slate-800 mb-1">WhatsApp Terverifikasi!</h4>
                    <p class="text-sm text-slate-500">Selamat menggunakan layanan kami</p>
                </div>
            </template>
        </div>
    </div>
</div>
@endif

@endsection
