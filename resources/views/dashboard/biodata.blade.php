@extends('layouts.dashboard')

@section('title', 'Biodata')
@section('page-title', 'Biodata Pengguna')

@section('content')
<style>
    @keyframes pulse-once {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .animate-pulse-once {
        animation: pulse-once 1s ease-in-out 3;
    }
</style>
@php
    /** @var \App\Models\User $user */
    use Illuminate\Support\Facades\Storage;

    $avatarUrl = $user->image
        ? Storage::url($user->image)
        : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=EBF4FF&color=1E40AF&bold=true';
    
    $initialYear = old('year', $user->year);
    $initialSrn = old('srn', $user->srn);
    $initialProdyId = old('prody_id', $user->prody_id);
    $initialProdyName = $initialProdyId ? $prodis->firstWhere('id', $initialProdyId)?->name : ($user->prody->name ?? '');
    $initialLegacyScoreRaw = old('nilaibasiclistening', $legacyAutoScore ?? $user->nilaibasiclistening);
    $initialLegacyScore = is_numeric($initialLegacyScoreRaw) && (float) $initialLegacyScoreRaw > 0
        ? (int) round((float) $initialLegacyScoreRaw)
        : '';
    $initialInteractiveAutoScores = $interactiveAutoScores ?? [];
    $initialInteractiveArabicAutoScores = $interactiveArabicAutoScores ?? [];
    $shouldOpenPasswordModal = $errors->has('current_password') || $errors->has('password');
    $initialYearString = filled($initialYear) ? (string) $initialYear : '';
    $initialSrnString = filled($initialSrn) ? (string) $initialSrn : '';
    $prodiIslamNames = ['Komunikasi dan Penyiaran Islam', 'Pendidikan Agama Islam', 'Pendidikan Islam Anak Usia Dini'];
    $initialYearInt = filled($initialYearString) ? (int) $initialYearString : null;
    $initialShowLegacyScore = \App\Support\LegacyBasicListeningScores::requiresLegacyScore($initialYearInt, $initialProdyName ?: null);
    $initialShowInteractiveClass = $initialYearInt !== null && $initialYearInt <= 2024 && $initialProdyName === 'Pendidikan Bahasa Inggris';
    $initialShowInteractiveArabic = $initialYearInt !== null && $initialYearInt <= 2024 && in_array($initialProdyName, $prodiIslamNames, true);
@endphp

<div
    x-data="{
        year: @js($initialYearString),
        srn: @js($initialSrnString),
        legacyScore: @js($initialLegacyScore),
        legacyScoreFound: {{ $initialLegacyScore !== '' ? 'true' : 'false' }},
        legacyScoreLoading: false,
        legacyScoreMessage: @js($initialLegacyScore !== '' ? '' : 'Nilai Basic Listening belum tersedia di arsip. Jika Anda sudah mengikuti dan lulus kelas, silakan konfirmasi ke kantor Lembaga Bahasa.'),
        isS2: {{ str_starts_with($initialProdyName ?? '', 'S2') ? 'true' : 'false' }},
        prodiName: @js($initialProdyName),
        changePasswordOpen: {{ $shouldOpenPasswordModal ? 'true' : 'false' }},
        lookupUrl: @js(route('dashboard.biodata.manual-basic-listening-score'))
    }"
    class="max-w-7xl mx-auto"
>
    {{-- TOP BANNER: WhatsApp OTP Verification (hanya muncul jika ada pending OTP) --}}
    @php
        $hasPendingOtp = $user->whatsapp_otp && $user->whatsapp_otp_expires_at && now()->isBefore($user->whatsapp_otp_expires_at);
        $remainingSeconds = $hasPendingOtp ? (int) max(0, now()->diffInSeconds($user->whatsapp_otp_expires_at, false)) : 0;
    @endphp
    
    @if($hasPendingOtp)
        <div class="mb-6 bg-green-500 rounded-2xl shadow-lg overflow-hidden"
             x-data="{
                 otp: '',
                 loading: false,
                 error: '',
                 countdown: {{ $remainingSeconds }},
                 verified: false,
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
                             this.verified = true;
                             setTimeout(() => window.location.reload(), 1000);
                         } else {
                             this.error = data.message || 'OTP tidak valid';
                         }
                     } catch (e) {
                         this.error = 'Terjadi kesalahan';
                     }
                     this.loading = false;
                 },
                 async resendOtp() {
                     this.loading = true;
                     this.error = '';
                     try {
                         const res = await fetch('{{ route('api.whatsapp.send-otp') }}', {
                             method: 'POST',
                             headers: {
                                 'Content-Type': 'application/json',
                                 'X-CSRF-TOKEN': '{{ csrf_token() }}'
                             },
                             body: JSON.stringify({ whatsapp: '{{ $user->whatsapp }}' })
                         });
                         const data = await res.json();
                         if (data.success) {
                             this.countdown = 60;
                             this.startCountdown();
                         } else {
                             this.error = data.message || 'Gagal mengirim ulang OTP';
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
                 init() {
                     if (this.countdown > 0) this.startCountdown();
                 }
             }">
            
            <template x-if="!verified">
                <div class="p-5 lg:p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                        {{-- Left: Icon & Text --}}
                        <div class="flex items-start gap-4 flex-1">
                            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                                <i class="fa-brands fa-whatsapp text-white text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white mb-1">Verifikasi Nomor WhatsApp</h3>
                                <p class="text-green-100 text-sm">
                                    Kode OTP sudah dikirim ke <strong class="text-white">{{ $user->whatsapp }}</strong>. 
                                    Masukkan kode untuk verifikasi.
                                </p>
                            </div>
                        </div>
                        
                        {{-- Right: OTP Input --}}
                        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                            <input type="text" 
                                   x-model="otp"
                                   maxlength="6"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   class="w-full sm:w-40 text-center text-xl tracking-[0.3em] font-mono py-3 px-4 rounded-xl border-0 bg-white/20 text-white placeholder-green-200 focus:bg-white/30 focus:ring-2 focus:ring-white"
                                   placeholder="● ● ● ● ● ●">
                            <button type="button"
                                    @click="verifyOtp()"
                                    :disabled="loading || otp.length !== 6"
                                    :class="{'opacity-50 cursor-not-allowed': loading || otp.length !== 6}"
                                    class="px-6 py-3 rounded-xl bg-white text-green-600 font-bold text-sm hover:bg-green-50 transition-colors flex items-center justify-center gap-2">
                                <i x-show="!loading" class="fa-solid fa-check"></i>
                                <i x-show="loading" class="fa-solid fa-spinner fa-spin"></i>
                                <span>Verifikasi</span>
                            </button>
                        </div>
                    </div>
                    
                    {{-- Error Message --}}
                    <p x-show="error" x-text="error" class="mt-3 text-sm text-white bg-red-500/50 rounded-lg px-3 py-2"></p>
                    
                    {{-- Resend Link --}}
                    <div class="mt-3 flex justify-end">
                        <button type="button" 
                                @click="resendOtp()" 
                                :disabled="countdown > 0 || loading"
                                :class="countdown > 0 ? 'text-green-200 cursor-not-allowed' : 'text-white hover:underline'"
                                class="text-sm">
                            <span x-show="countdown > 0">Kirim ulang dalam <strong x-text="countdown"></strong> detik</span>
                            <span x-show="countdown <= 0"><i class="fa-solid fa-redo mr-1"></i> Kirim ulang OTP</span>
                        </button>
                    </div>
                </div>
            </template>
            
            {{-- Success State --}}
            <template x-if="verified">
                <div class="p-5 lg:p-6 bg-emerald-500 flex items-center justify-center gap-3">
                    <i class="fa-solid fa-circle-check text-white text-2xl"></i>
                    <span class="text-white font-bold text-lg">WhatsApp berhasil diverifikasi!</span>
                </div>
            </template>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {{-- KOLOM KIRI: Kartu Profil (Lebar 4/12) --}}
        <div class="hidden lg:block lg:col-span-4 space-y-6">
            
            {{-- Profile Card --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden relative group">
                {{-- Header Background --}}
                <div class="h-32 bg-gradient-to-br from-slate-800 to-um-blue relative overflow-hidden">
                    <div class="absolute inset-0 opacity-20 pattern-grid-lg"></div> {{-- Optional Pattern --}}
                    <div class="absolute -bottom-4 -right-4 text-9xl text-white opacity-5 rotate-12">
                        <i class="fa-solid fa-user-circle"></i>
                    </div>
                </div>

                {{-- Avatar & Basic Info --}}
                <div class="px-6 pb-6 relative">
                    <div class="relative -mt-12 mb-4 flex justify-center">
                        <div class="p-1.5 bg-white rounded-full shadow-sm">
                            <img src="{{ $avatarUrl }}" 
                                 alt="Avatar" 
                                 class="h-28 w-28 rounded-full object-cover border border-slate-100 bg-slate-50">
                        </div>
                    </div>

                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-slate-900">{{ $user->name }}</h2>
                        <p class="text-sm text-slate-500">{{ $user->email }}</p>
                    </div>

                    {{-- Detailed Info List --}}
                    <div class="space-y-3 bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500 flex items-center gap-2">
                                <i class="fa-regular fa-id-card text-slate-400 w-4"></i> NPM
                            </span>
                            <span class="font-semibold text-slate-700">{{ $user->srn ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm border-t border-slate-200/60 pt-3">
                            <span class="text-slate-500 flex items-center gap-2">
                                <i class="fa-solid fa-graduation-cap text-slate-400 w-4"></i> Prodi
                            </span>
                            <span class="font-semibold text-slate-700 text-right truncate ml-4">
                                {{ $user->prody->name ?? '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm border-t border-slate-200/60 pt-3">
                            <span class="text-slate-500 flex items-center gap-2">
                                <i class="fa-regular fa-calendar text-slate-400 w-4"></i> Angkatan
                            </span>
                            <span class="font-semibold text-slate-700">{{ $user->year ?? '-' }}</span>
                        </div>
                    </div>

                    {{-- Security Action --}}
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Keamanan Akun</h4>
                        <button type="button"
                                @click="changePasswordOpen = true"
                                class="w-full flex items-center justify-between px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 text-sm font-medium hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 transition-all group/btn shadow-sm">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-lock text-slate-400 group-hover/btn:text-amber-500"></i> Ganti Password</span>
                            <i class="fa-solid fa-chevron-right text-xs text-slate-300 group-hover/btn:text-amber-400"></i>
                        </button>
                        
                        @if (session('password_success'))
                            <div class="mt-3 text-xs text-emerald-600 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-100 flex items-center gap-2 animate-pulse">
                                <i class="fa-solid fa-check-circle"></i> {{ session('password_success') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: Form Edit (Lebar 8/12) --}}
        <div class="lg:col-span-8">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-2xl">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Edit Informasi</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Perbarui data diri dan profil akademik Anda.</p>
                        <div class="mt-3 lg:hidden">
                            <button type="button"
                                    @click="changePasswordOpen = true"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700">
                                <i class="fa-solid fa-lock text-slate-400"></i>
                                Ganti Password
                            </button>
                        </div>
                    </div>
                    <div class="hidden sm:block">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-medium border border-blue-100">
                            <i class="fa-solid fa-circle-info"></i> Pastikan data valid
                        </span>
                    </div>
                </div>

                <form method="POST"
                      action="{{ route('bl.profile.complete.submit') }}"
                      enctype="multipart/form-data"
                      class="p-6 space-y-8">
                    @csrf
                    <input type="hidden" name="next" value="{{ route('dashboard') }}">

                    {{-- SECTION 1: Informasi Pribadi --}}
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-um-blue"></span> Data Pribadi
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- Nama Lengkap --}}
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 ml-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fa-regular fa-user text-slate-400 text-sm"></i>
                                    </div>
                                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                           class="pl-10 block w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-um-blue focus:ring-um-blue sm:text-sm transition-all duration-200"
                                           placeholder="Nama sesuai KTM">
                                </div>
                                @error('name') <p class="mt-1 text-xs text-rose-600 pl-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Email --}}
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 ml-1">Email Akun <span class="text-rose-500">*</span></label>
                                <div class="relative opacity-75">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fa-regular fa-envelope text-slate-400 text-sm"></i>
                                    </div>
                                    <input type="email" name="email" value="{{ old('email', $user->email) }}" readonly
                                           class="pl-10 block w-full rounded-xl border-slate-200 bg-slate-100 text-slate-500 sm:text-sm cursor-not-allowed"
                                           title="Email tidak dapat diubah">
                                </div>
                            </div>

                            {{-- WhatsApp dengan Verifikasi OTP --}}
                            @php
                                $hasPendingOtp = $user->whatsapp_otp && $user->whatsapp_otp_expires_at && now()->isBefore($user->whatsapp_otp_expires_at);
                                $initialStep = $user->whatsapp_verified_at ? 'verified' : ($hasPendingOtp ? 'otp' : 'input');
                                $remainingSeconds = $hasPendingOtp ? (int) max(0, now()->diffInSeconds($user->whatsapp_otp_expires_at, false)) : 0;
                            @endphp
                            <div class="md:col-span-2 {{ $hasPendingOtp ? 'ring-2 ring-green-400 ring-offset-2 rounded-2xl p-4 bg-green-50/50 animate-pulse-once' : '' }}" 
                                 x-data="{
                                    phone: '{{ old('whatsapp', $user->whatsapp) }}',
                                    otp: '',
                                    step: '{{ $initialStep }}',
                                    loading: false,
                                    error: '',
                                    countdown: {{ $remainingSeconds }},
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
                                                // Jika OTP dinonaktifkan, langsung ke verified
                                                if (data.skip_otp) {
                                                    this.step = 'verified';
                                                } else {
                                                    this.step = 'otp';
                                                    this.countdown = 60;
                                                    this.startCountdown();
                                                }
                                            } else {
                                                this.error = data.message || 'Gagal mengirim OTP';
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
                                                this.step = 'verified';
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
                                    changeNumber() {
                                        this.step = 'input';
                                        this.otp = '';
                                        this.error = '';
                                    }
                                 }">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 ml-1">
                                    Nomor WhatsApp <span class="text-slate-400">(Opsional)</span>
                                </label>
                                
                                {{-- Step 1: Input Nomor --}}
                                <div x-show="step === 'input'" class="space-y-3">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fa-brands fa-whatsapp text-green-500"></i>
                                        </div>
                                        <input type="tel" 
                                               x-model="phone"
                                               name="whatsapp" 
                                               inputmode="numeric"
                                               class="pl-10 block w-full py-3 rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-green-500 focus:ring-green-500 text-base transition-all duration-200"
                                               placeholder="Masukan Nomor WhatsApp">
                                    </div>
                                    @php
                                        $otpEnabled = \App\Models\SiteSetting::isOtpEnabled();
                                    @endphp
                                    <button type="button"
                                            @click="sendOtp()"
                                            :disabled="loading || !phone"
                                            :class="{'opacity-50 cursor-not-allowed': loading || !phone}"
                                            class="w-full py-3 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                                        <i x-show="!loading" class="fa-solid {{ $otpEnabled ? 'fa-paper-plane' : 'fa-check' }}"></i>
                                        <i x-show="loading" class="fa-solid fa-spinner fa-spin"></i>
                                        <span>{{ $otpEnabled ? 'Kirim Kode OTP' : 'Simpan Nomor WhatsApp' }}</span>
                                    </button>
                                    <p class="text-xs text-slate-500 text-center">
                                        <i class="fa-solid fa-circle-info text-slate-400"></i>
                                        {{ $otpEnabled ? 'Kode verifikasi akan dikirim ke WhatsApp Anda' : 'Nomor WhatsApp akan tersimpan dan terverifikasi otomatis' }}
                                    </p>
                                </div>

                                {{-- Step 2: Input OTP --}}
                                <div x-show="step === 'otp'" class="space-y-3">
                                    <div class="p-3 rounded-xl bg-green-50 border border-green-100 text-sm text-green-700">
                                        <i class="fa-solid fa-check-circle"></i>
                                        OTP dikirim ke <strong x-text="phone"></strong>
                                    </div>
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <input type="text" 
                                               x-model="otp"
                                               maxlength="6"
                                               inputmode="numeric"
                                               pattern="[0-9]*"
                                               class="w-full sm:flex-1 text-center text-xl tracking-[0.4em] font-mono py-3 rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-green-500 focus:ring-green-500"
                                               placeholder="● ● ● ● ● ●">
                                        <button type="button"
                                                @click="verifyOtp()"
                                                :disabled="loading || otp.length !== 6"
                                                :class="{'opacity-50 cursor-not-allowed': loading || otp.length !== 6}"
                                                class="w-full sm:w-auto px-6 py-3 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                                            <i x-show="!loading" class="fa-solid fa-check"></i>
                                            <i x-show="loading" class="fa-solid fa-spinner fa-spin"></i>
                                            <span>Verifikasi</span>
                                        </button>
                                    </div>
                                    <div class="flex justify-between items-center text-xs">
                                        <button type="button" @click="changeNumber()" class="text-slate-500 hover:text-slate-700">
                                            <i class="fa-solid fa-arrow-left"></i> Ganti Nomor
                                        </button>
                                        <button type="button" 
                                                @click="sendOtp()" 
                                                :disabled="countdown > 0"
                                                :class="countdown > 0 ? 'text-slate-400 cursor-not-allowed' : 'text-green-600 hover:text-green-700'">
                                            <span x-show="countdown > 0">Kirim ulang (<span x-text="countdown"></span>s)</span>
                                            <span x-show="countdown <= 0">Kirim ulang OTP</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Step 3: Verified --}}
                                <div x-show="step === 'verified'" class="space-y-2">
                                    <div class="p-4 rounded-xl bg-green-50 border border-green-200">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                                <i class="fa-brands fa-whatsapp text-green-600 text-xl"></i>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-base font-bold text-slate-800" x-text="phone"></p>
                                                <p class="text-sm text-green-600 font-medium flex items-center gap-1">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    Terverifikasi
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="whatsapp" x-bind:value="phone">
                                    <div class="flex items-center gap-3">
                                        <button type="button" @click="changeNumber()" class="text-xs text-slate-500 hover:text-slate-700 flex items-center gap-1">
                                            <i class="fa-solid fa-pen-to-square"></i> Ganti Nomor
                                        </button>
                                        <span class="text-slate-300">|</span>
                                        <button type="button" 
                                                @click="if(confirm('Yakin ingin menghapus nomor WhatsApp? Anda perlu memasukkan nomor baru.')) { 
                                                    fetch('{{ route('api.whatsapp.delete') }}', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/json',
                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                        }
                                                    }).then(res => res.json()).then(data => {
                                                        if(data.success) {
                                                            window.location.reload();
                                                        } else {
                                                            alert(data.message || 'Gagal menghapus nomor');
                                                        }
                                                    });
                                                }" 
                                                class="text-xs text-rose-500 hover:text-rose-700 flex items-center gap-1">
                                            <i class="fa-solid fa-trash"></i> Hapus Nomor
                                        </button>
                                    </div>
                                </div>

                                {{-- Error Message --}}
                                <p x-show="error" x-text="error" class="mt-2 text-xs text-rose-600 pl-1"></p>
                                @error('whatsapp') <p class="mt-1 text-xs text-rose-600 pl-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Upload Foto --}}
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 ml-1">Foto Profil</label>
                                <p class="mb-2 pl-1 text-xs text-slate-500">Opsional, tidak wajib diisi.</p>
                                <div class="flex items-center gap-4 p-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 hover:bg-slate-100 transition-colors">
                                    <div class="h-10 w-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-400">
                                        <i class="fa-solid fa-camera"></i>
                                    </div>
                                    <div class="flex-1">
                                        <input type="file" name="image" id="file-upload" class="hidden" onchange="document.getElementById('file-name').innerText = this.files[0].name">
                                        <label for="file-upload" class="cursor-pointer text-sm font-medium text-um-blue hover:text-um-dark-blue focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-um-blue">
                                            <span>Upload foto baru</span>
                                        </label>
                                        <p class="text-xs text-slate-500 mt-0.5" id="file-name">JPG, PNG hingga 2MB</p>
                                        @if($user->image)
                                            <button type="submit"
                                                    form="delete-photo-form"
                                                    class="mt-2 text-xs font-semibold text-rose-600 hover:text-rose-700 flex items-center gap-1">
                                                <i class="fa-solid fa-trash"></i> Hapus Foto Profil
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                @error('image') <p class="mt-1 text-xs text-rose-600 pl-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-100 border-dashed">

                    {{-- SECTION 2: Data Akademik --}}
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-um-blue"></span> Data Akademik
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- NPM --}}
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 ml-1">NPM / NIM <span class="text-rose-500">*</span></label>
                                <input type="text" id="biodata-srn" name="srn" x-ref="srnInput" value="{{ $initialSrnString }}" x-model="srn"
                                       class="block w-full py-3 px-4 rounded-xl border-2 border-slate-200 bg-white shadow-sm focus:border-um-blue focus:ring-um-blue text-base transition-all duration-200 placeholder:text-slate-400"
                                       placeholder="Masukkan NPM / NIM">
                                <p class="mt-1 pl-1 text-xs text-slate-500">Jika NPM memiliki akhiran seperti P, masukkan lengkap.</p>
                                <p id="biodata-srn-warning" style="display:none" class="mt-1 pl-1 text-xs text-amber-700">
                                    NPM minimal 8 digit.
                                </p>
                                @error('srn') <p class="mt-1 text-xs text-rose-600 pl-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Angkatan --}}
                            <div>
                                <label class="mb-1.5 ml-1 flex items-center justify-between gap-2 text-xs font-semibold text-slate-700">
                                    <span>Tahun Angkatan <span class="text-rose-500">*</span></span>
                                    <span class="text-[11px] font-medium text-slate-400">Auto dari NPM, bisa diubah</span>
                                </label>
                                <select id="biodata-year" name="year" x-ref="yearSelect" x-model="year"
                                        class="block w-full py-3 px-4 rounded-xl border-2 border-slate-200 bg-white shadow-sm focus:border-um-blue focus:ring-um-blue text-base transition-all duration-200">
                                    <option value="">Pilih Tahun</option>
                                    @foreach (collect(range(2017, (int)date('Y')+1))->reverse() as $y)
                                        <option value="{{ $y }}" @selected((int) old('year', $user->year) === $y)>{{ $y }}</option>
                                    @endforeach
                                </select>
                                @error('year') <p class="mt-1 text-xs text-rose-600 pl-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Prodi - Searchable Dropdown --}}
                            <div class="md:col-span-2" 
                                 x-data="{
                                    open: false,
                                    search: '',
                                    selected: {{ old('prody_id', $user->prody_id) ?: 'null' }},
                                    selectedName: '{{ old('prody_id', $user->prody_id) ? $prodis->firstWhere('id', old('prody_id', $user->prody_id))?->name : '' }}',
                                    prodis: {{ Js::from($prodis->map(fn($p) => ['id' => $p->id, 'name' => $p->name])) }},
                                    get filtered() {
                                        if (!this.search) return this.prodis;
                                        return this.prodis.filter(p => 
                                            p.name.toLowerCase().includes(this.search.toLowerCase())
                                        );
                                    },
                                    selectProdi(prodi) {
                                        this.selected = prodi.id;
                                        this.selectedName = prodi.name;
                                        this.search = '';
                                        this.open = false;
                                        window.dispatchEvent(new CustomEvent('biodata-prodi-changed', {
                                            detail: { id: prodi.id, prodiName: prodi.name }
                                        }));
                                    },
                                    clear() {
                                        this.selected = null;
                                        this.selectedName = '';
                                        this.search = '';
                                    }
                                 }"
                                 @click.outside="open = false">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 ml-1">Program Studi <span class="text-rose-500">*</span></label>
                                
                                {{-- Hidden input for form submission --}}
                                <input type="hidden" id="biodata-prody-id" name="prody_id" value="{{ $initialProdyId }}" :value="selected">
                                
                                {{-- Dropdown trigger --}}
                                <div class="relative">
                                    <button type="button" 
                                            @click="open = !open"
                                            class="w-full py-3 px-4 rounded-xl border-2 border-slate-200 bg-white shadow-sm text-left text-base transition-all duration-200 flex items-center justify-between"
                                            :class="open ? 'border-um-blue ring-2 ring-um-blue/20' : 'hover:border-slate-300'">
                                        <span :class="selectedName ? 'text-slate-900' : 'text-slate-400'" x-text="selectedName || 'Pilih Program Studi'"></span>
                                        <svg class="w-5 h-5 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    
                                    {{-- Dropdown panel --}}
                                    <div x-show="open" 
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         class="absolute z-50 mt-2 w-full bg-white rounded-xl border-2 border-slate-200 shadow-lg overflow-hidden">
                                        
                                        {{-- Search input --}}
                                        <div class="p-2 border-b border-slate-100">
                                            <input type="text" 
                                                   x-model="search"
                                                   x-ref="searchInput"
                                                   @keydown.escape="open = false"
                                                   class="w-full py-2 px-3 rounded-lg border border-slate-200 text-sm focus:ring-um-blue focus:border-um-blue"
                                                   placeholder="Cari program studi...">
                                        </div>
                                        
                                        {{-- Options list --}}
                                        <ul class="max-h-60 overflow-y-auto py-1">
                                            <template x-for="prodi in filtered" :key="prodi.id">
                                                <li>
                                                    <button type="button"
                                                            @click="selectProdi(prodi)"
                                                            class="w-full px-4 py-2.5 text-left text-sm hover:bg-um-blue/10 transition-colors flex items-center gap-2"
                                                            :class="selected === prodi.id && 'bg-um-blue/10 text-um-blue font-medium'">
                                                        <svg x-show="selected === prodi.id" class="w-4 h-4 text-um-blue" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <span x-text="prodi.name"></span>
                                                    </button>
                                                </li>
                                            </template>
                                            <li x-show="filtered.length === 0" class="px-4 py-3 text-sm text-slate-400 text-center">
                                                Tidak ditemukan
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                @error('prody_id') <p class="mt-1 text-xs text-rose-600 pl-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Nilai BL (Conditional: angkatan <= 2024 DAN bukan S2 DAN bukan Pendidikan Bahasa Inggris) --}}
                            <div id="legacy-score-section" style="{{ $initialShowLegacyScore ? '' : 'display:none' }}" class="md:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-600 shrink-0">
                                        <i class="fa-solid fa-wave-square text-sm"></i>
                                    </div>
                                    <div>
                                        <label class="text-sm sm:text-base font-semibold text-slate-900 block">Nilai Basic Listening <span class="text-rose-500">*</span></label>
                                        <p class="text-xs text-slate-500 mt-0.5">Nilai diambil otomatis dari arsip Basic Listening. Anda tidak perlu mengisi manual.</p>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3">
                                        <input type="number" id="legacy-score-input" name="nilaibasiclistening" min="1" max="100" placeholder="0"
                                               value="{{ $initialLegacyScore }}"
                                               readonly
                                               class="w-28 sm:w-32 py-2.5 px-3 text-center text-xl font-semibold rounded-xl border border-slate-300 bg-slate-50 text-slate-700"
                                               data-has-score="{{ $initialLegacyScore !== '' ? '1' : '0' }}">
                                        <span id="legacy-score-loading" style="display:none" class="text-xs font-medium text-slate-500">Memuat...</span>
                                    </div>
                                    <p id="legacy-score-message" style="{{ $initialLegacyScore !== '' ? 'display:none' : '' }}" class="text-xs text-amber-700">
                                        <i id="legacy-score-message-icon" class="fa-solid fa-circle-info text-amber-500"></i>
                                        <span>Nilai Basic Listening belum tersedia di arsip. Jika Anda sudah mengikuti dan lulus kelas, silakan konfirmasi ke kantor Lembaga Bahasa.</span>
                                    </p>
                                    @error('nilaibasiclistening')
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Interactive Class (6 semester) - KHUSUS Pendidikan Bahasa Inggris --}}
                            <div id="interactive-class-section" style="{{ $initialShowInteractiveClass ? '' : 'display:none' }}" class="md:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-violet-50 text-violet-600 shrink-0">
                                        <i class="fa-solid fa-comments text-sm"></i>
                                    </div>
                                    <div>
                                        <label class="text-sm sm:text-base font-semibold text-slate-900 block">Nilai Interactive Bahasa Inggris <span class="text-rose-500">*</span></label>
                                        <p class="text-xs text-slate-500 mt-0.5">Nilai per semester terisi otomatis dari arsip Interactive Class dan tidak bisa diubah manual.</p>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        @for ($i = 1; $i <= 6; $i++)
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1">Semester {{ $i }}</label>
                                            <input type="number" id="interactive-class-input-{{ $i }}" data-interactive-class-input data-semester="{{ $i }}" name="interactive_class_{{ $i }}" min="0" max="100" placeholder="0" readonly
                                                   value="{{ old('interactive_class_'.$i, $initialInteractiveAutoScores[$i] ?? $user->{'interactive_class_'.$i}) }}"
                                                   class="w-full py-2.5 px-3 text-center text-base font-semibold rounded-xl border border-slate-300 bg-slate-50 text-slate-700 cursor-not-allowed focus:border-slate-300 focus:ring-0">
                                        </div>
                                        @endfor
                                    </div>
                                    <div class="flex items-center gap-3 text-xs">
                                        <span id="interactive-class-loading" style="display:none" class="font-medium text-slate-500">Memuat...</span>
                                        <p id="interactive-class-message" class="text-slate-500">
                                            <i class="fa-solid fa-info-circle text-violet-400"></i>
                                            Nilai Interactive Class akan terisi otomatis jika arsipnya ditemukan.
                                        </p>
                                    </div>
                                    @for ($i = 1; $i <= 6; $i++)
                                        @error('interactive_class_'.$i) <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                                    @endfor
                                </div>
                            </div>

                            {{-- Interactive Bahasa Arab (2 field) - KHUSUS 3 Prodi Islam --}}
                            @php
                                $prodiIslam = ['Komunikasi dan Penyiaran Islam', 'Pendidikan Agama Islam', 'Pendidikan Islam Anak Usia Dini'];
                                $prodiIslamJs = Js::from($prodiIslam);
                            @endphp
                            <div id="interactive-arabic-section" style="{{ $initialShowInteractiveArabic ? '' : 'display:none' }}" class="md:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 shrink-0">
                                        <i class="fa-solid fa-book-quran text-sm"></i>
                                    </div>
                                    <div>
                                        <label class="text-sm sm:text-base font-semibold text-slate-900 block">Nilai Interactive Bahasa Arab <span class="text-rose-500">*</span></label>
                                        <p class="text-xs text-slate-500 mt-0.5">Nilai diambil otomatis dari arsip Interactive Bahasa Arab. Anda tidak perlu mengisi manual.</p>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1">Bahasa Arab 1</label>
                                            <input type="number" id="interactive-arabic-input-1" data-interactive-arabic-input data-semester="1" name="interactive_bahasa_arab_1" min="0" max="100" placeholder="0" readonly
                                                   value="{{ old('interactive_bahasa_arab_1', (($initialInteractiveArabicAutoScores[1] ?? null) ?: ((is_numeric($user->interactive_bahasa_arab_1 ?? null) && (float) $user->interactive_bahasa_arab_1 > 0) ? $user->interactive_bahasa_arab_1 : ''))) }}"
                                                   class="w-full py-2.5 px-3 text-center text-base font-semibold rounded-xl border border-slate-300 bg-slate-50 text-slate-700 cursor-not-allowed focus:border-slate-300 focus:ring-0">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1">Bahasa Arab 2</label>
                                            <input type="number" id="interactive-arabic-input-2" data-interactive-arabic-input data-semester="2" name="interactive_bahasa_arab_2" min="0" max="100" placeholder="0" readonly
                                                   value="{{ old('interactive_bahasa_arab_2', (($initialInteractiveArabicAutoScores[2] ?? null) ?: ((is_numeric($user->interactive_bahasa_arab_2 ?? null) && (float) $user->interactive_bahasa_arab_2 > 0) ? $user->interactive_bahasa_arab_2 : ''))) }}"
                                                   class="w-full py-2.5 px-3 text-center text-base font-semibold rounded-xl border border-slate-300 bg-slate-50 text-slate-700 cursor-not-allowed focus:border-slate-300 focus:ring-0">
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <span id="interactive-arabic-loading" style="display:none" class="text-xs font-medium text-slate-500">Memuat data arsip...</span>
                                        <div id="interactive-arabic-message" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                            <div class="flex items-start gap-2">
                                                <i class="fa-solid fa-circle-info mt-0.5 text-slate-400"></i>
                                                <span>Masukkan NPM lengkap untuk mengecek nilai Bahasa Arab 1 dan 2 secara otomatis.</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-500">
                                        Jika ada nilai yang belum muncul, silakan datang ke kantor Lembaga Bahasa sambil membawa bukti hasil Interactive Bahasa Arab.
                                    </p>
                                    @error('interactive_bahasa_arab_1') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                                    @error('interactive_bahasa_arab_2') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Form Footer --}}
                    <div class="pt-6 flex items-center justify-end gap-3">
                        <button type="button" 
                                @click="if(confirm('Yakin ingin mereset biodata? Data SRN, Prodi, Tahun Angkatan, dan Nilai Basic Listening akan dihapus.')) { 
                                    fetch('{{ route('api.biodata.reset') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        }
                                    }).then(res => res.json()).then(data => {
                                        if(data.success) {
                                            window.location.reload();
                                        } else {
                                            alert(data.message || 'Gagal mereset biodata');
                                        }
                                    });
                                }" 
                                class="px-5 py-2.5 rounded-xl text-sm font-medium text-rose-600 hover:bg-rose-50 transition-colors">
                            <i class="fa-solid fa-rotate-left mr-1"></i> Reset Biodata
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-um-blue hover:bg-um-dark-blue text-white text-sm font-semibold shadow-lg shadow-blue-900/20 transition-all hover:scale-[1.02] active:scale-95">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>

                @if($user->image)
                    {{-- Form terpisah untuk hapus foto (di luar form utama agar tidak bentrok method) --}}
                    <form id="delete-photo-form" action="{{ route('dashboard.biodata.photo.delete') }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Ganti Password (Modern Style) --}}
    <div x-show="changePasswordOpen" 
         class="fixed inset-0 z-[60] overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        
        <div x-show="changePasswordOpen"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div x-show="changePasswordOpen"
                 @click.away="changePasswordOpen = false"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                 class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all border border-slate-100">
                
                {{-- Modal Header --}}
                <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center gap-4">
                    <div class="h-10 w-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                        <i class="fa-solid fa-key text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Ganti Password</h3>
                        <p class="text-xs text-slate-500">Amankan akun Anda dengan password baru.</p>
                    </div>
                </div>
                
                {{-- Modal Body --}}
                <div class="px-6 py-6">
                    <form id="passwordForm" method="POST" action="{{ route('dashboard.password.update') }}" class="space-y-5">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password Lama</label>
                            <input type="password" name="current_password" 
                                   class="block w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-amber-500 focus:ring-amber-500 sm:text-sm transition-colors">
                            @error('current_password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password Baru</label>
                            <input type="password" name="password" 
                                   class="block w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-amber-500 focus:ring-amber-500 sm:text-sm transition-colors">
                            @error('password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" 
                                   class="block w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-amber-500 focus:ring-amber-500 sm:text-sm transition-colors">
                        </div>
                    </form>
                </div>

                {{-- Modal Footer --}}
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-slate-100">
                    <button type="submit" form="passwordForm" 
                            class="inline-flex justify-center rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 transition-colors">
                        Simpan
                    </button>
                    <button type="button" @click="changePasswordOpen = false" 
                            class="inline-flex justify-center rounded-xl bg-white px-5 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const srnInput = document.getElementById('biodata-srn');
    const yearSelect = document.getElementById('biodata-year');
    const prodyInput = document.getElementById('biodata-prody-id');
    const nameInput = document.querySelector('input[name="name"]');
    const profileForm = srnInput?.form || document.querySelector('form[action="{{ route('bl.profile.complete.submit') }}"]');
    const submitButton = profileForm?.querySelector('button[type="submit"]');
    const legacySection = document.getElementById('legacy-score-section');
    const legacyInput = document.getElementById('legacy-score-input');
    const legacyLoading = document.getElementById('legacy-score-loading');
    const legacyMessage = document.getElementById('legacy-score-message');
    const legacyMessageIcon = document.getElementById('legacy-score-message-icon');
    const interactiveClassSection = document.getElementById('interactive-class-section');
    const interactiveClassInputs = Array.from(document.querySelectorAll('[data-interactive-class-input]'));
    const interactiveClassLoading = document.getElementById('interactive-class-loading');
    const interactiveClassMessage = document.getElementById('interactive-class-message');
    const interactiveArabicSection = document.getElementById('interactive-arabic-section');
    const interactiveArabicInputs = Array.from(document.querySelectorAll('[data-interactive-arabic-input]'));
    const interactiveArabicInput1 = document.getElementById('interactive-arabic-input-1');
    const interactiveArabicInput2 = document.getElementById('interactive-arabic-input-2');
    const interactiveArabicLoading = document.getElementById('interactive-arabic-loading');
    const interactiveArabicMessage = document.getElementById('interactive-arabic-message');
    const srnWarning = document.getElementById('biodata-srn-warning');
    const lookupUrl = @json(route('dashboard.biodata.manual-basic-listening-score'));
    const prodiMap = @json($prodis->pluck('name', 'id'));
    const prodiIslam = @json($prodiIslamNames);
    const originalIdentity = {
        srn: @json(\App\Support\LegacyBasicListeningScores::normalizeSrn($user->srn)),
        year: @json((string) ($user->year ?? '')),
        prodyId: @json((string) ($user->prody_id ?? '')),
    };
    const legacyLookupMinLength = 8;
    let lookupTimer = null;
    let selectedProdyName = @json($initialProdyName ?? '');

    if (!srnInput || !yearSelect || !prodyInput) {
        return;
    }

    const availableYears = new Set(Array.from(yearSelect.options).map((option) => String(option.value || '').trim()).filter(Boolean));
    let isApplyingAutoYear = false;
    let yearManualOverride = String(yearSelect.value || '').trim() !== '';

    yearSelect.dataset.autofilled = '0';

    const setVisible = (element, visible) => {
        if (!element) return;
        element.style.display = visible ? '' : 'none';
    };

    const setLegacyState = ({ score = '', found = false, message = '', loading = false } = {}) => {
        if (legacyInput) {
            legacyInput.value = score ?? '';
            legacyInput.classList.remove('bg-emerald-50', 'border-emerald-300', 'text-emerald-700', 'bg-slate-50', 'text-slate-700', 'border-slate-300');
            legacyInput.classList.add(found ? 'bg-emerald-50' : 'bg-slate-50');
            legacyInput.classList.add(found ? 'border-emerald-300' : 'border-slate-300');
            legacyInput.classList.add(found ? 'text-emerald-700' : 'text-slate-700');
        }

        if (legacyLoading) {
            legacyLoading.style.display = loading ? '' : 'none';
        }

        if (legacyMessage) {
            const span = legacyMessage.querySelector('span');
            legacyMessage.style.display = loading || found ? 'none' : '';
            legacyMessage.classList.remove('text-amber-700', 'text-slate-500');
            legacyMessage.classList.add('text-amber-700');
            if (span) {
                span.textContent = message || 'Nilai Basic Listening belum tersedia di arsip. Jika Anda sudah mengikuti dan lulus kelas, silakan konfirmasi ke kantor Lembaga Bahasa.';
            }
        }

        if (legacyMessageIcon) {
            legacyMessageIcon.classList.remove('text-amber-500');
            legacyMessageIcon.classList.add('text-amber-500');
        }
    };

    const setInteractiveClassState = ({ scores = {}, found = false, message = '', loading = false, clearAutoFilled = false, replaceAll = false } = {}) => {
        interactiveClassInputs.forEach((input) => {
            const semester = parseInt(input.dataset.semester || '0', 10);
            const hasIncoming = Object.prototype.hasOwnProperty.call(scores, semester);
            const hasAutoFilled = input.dataset.autofilled === '1';

            if (hasIncoming) {
                input.value = scores[semester];
                input.dataset.autofilled = '1';
            } else if (replaceAll) {
                input.value = '';
                input.dataset.autofilled = '0';
            } else if (clearAutoFilled && hasAutoFilled) {
                input.value = '';
                input.dataset.autofilled = '0';
            }

            input.classList.remove('bg-violet-50', 'border-violet-300', 'text-violet-700', 'bg-white', 'border-slate-300');
            const activeAutoFill = (hasIncoming || input.dataset.autofilled === '1') && String(input.value || '').trim() !== '';
            input.classList.add(activeAutoFill ? 'bg-violet-50' : 'bg-white');
            input.classList.add(activeAutoFill ? 'border-violet-300' : 'border-slate-300');
            input.classList.add(activeAutoFill ? 'text-violet-700' : 'text-slate-900');
        });

        if (interactiveClassLoading) {
            interactiveClassLoading.style.display = loading ? '' : 'none';
        }

        if (interactiveClassMessage) {
            interactiveClassMessage.style.display = loading ? 'none' : '';
            interactiveClassMessage.classList.remove('text-slate-500', 'text-violet-700', 'text-amber-700');
            interactiveClassMessage.classList.add(found ? 'text-violet-700' : 'text-amber-700');
            interactiveClassMessage.innerHTML = `
                <i class="fa-solid fa-circle-info ${found ? 'text-violet-500' : 'text-amber-500'}"></i>
                <span>${message || 'Nilai Interactive Class akan terisi otomatis jika arsipnya ditemukan.'}</span>
            `;
        }
    };

    const setInteractiveArabicState = ({ scores = {}, found = false, message = '', loading = false, clearAutoFilled = false, replaceAll = false } = {}) => {
        interactiveArabicInputs.forEach((input) => {
            const semester = parseInt(input.dataset.semester || '0', 10);
            const hasIncoming = Object.prototype.hasOwnProperty.call(scores, semester);
            const hasAutoFilled = input.dataset.autofilled === '1';

            if (hasIncoming) {
                input.value = scores[semester];
                input.dataset.autofilled = '1';
            } else if (replaceAll) {
                input.value = '';
                input.dataset.autofilled = '0';
            } else if (clearAutoFilled && hasAutoFilled) {
                input.value = '';
                input.dataset.autofilled = '0';
            }

            input.classList.remove('bg-emerald-50', 'border-emerald-300', 'text-emerald-700', 'bg-slate-50', 'text-slate-700', 'border-slate-300');
            const activeAutoFill = (hasIncoming || input.dataset.autofilled === '1') && String(input.value || '').trim() !== '';
            input.classList.add(activeAutoFill ? 'bg-emerald-50' : 'bg-slate-50');
            input.classList.add(activeAutoFill ? 'border-emerald-300' : 'border-slate-300');
            input.classList.add(activeAutoFill ? 'text-emerald-700' : 'text-slate-700');
        });

        if (interactiveArabicLoading) {
            interactiveArabicLoading.style.display = loading ? '' : 'none';
        }

        if (interactiveArabicMessage) {
            const resolvedCount = interactiveArabicInputs.filter((input) => String(input.value || '').trim() !== '').length;
            const messageTone = resolvedCount === 2 ? 'success' : (resolvedCount > 0 ? 'partial' : 'empty');
            const defaultMessage = messageTone === 'success'
                ? 'Bahasa Arab 1 dan 2 ditemukan. Nilai sudah terisi otomatis.'
                : messageTone === 'partial'
                    ? `Sebagian nilai sudah ditemukan. Periksa kolom yang masih kosong.`
                    : 'Masukkan NPM lengkap untuk mengecek nilai Bahasa Arab 1 dan 2 secara otomatis.';

            interactiveArabicMessage.style.display = loading ? 'none' : '';
            interactiveArabicMessage.classList.remove(
                'border-slate-200',
                'bg-slate-50',
                'text-slate-600',
                'border-emerald-200',
                'bg-emerald-50',
                'text-emerald-700',
                'border-amber-200',
                'bg-amber-50',
                'text-amber-700',
            );

            if (messageTone === 'success') {
                interactiveArabicMessage.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
            } else if (messageTone === 'partial') {
                interactiveArabicMessage.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-700');
            } else {
                interactiveArabicMessage.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');
            }

            interactiveArabicMessage.innerHTML = `
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5 ${messageTone === 'success' ? 'text-emerald-500' : (messageTone === 'partial' ? 'text-amber-500' : 'text-slate-400')}"></i>
                    <span>${message || defaultMessage}</span>
                </div>
            `;
        }
    };

    const getSelectedProdiName = () => {
        const id = String(prodyInput.value || '').trim();
        if (id && Object.prototype.hasOwnProperty.call(prodiMap, id)) {
            return prodiMap[id];
        }

        return selectedProdyName || '';
    };

    const getFlags = () => {
        const year = parseInt(yearSelect.value || '0', 10);
        const prodiName = getSelectedProdiName();
        const normalizedProdiName = String(prodiName || '').trim().toLowerCase().replace(/\s+/g, ' ');
        const isS2 = prodiName.startsWith('S2');
        const isEnglish = prodiName === 'Pendidikan Bahasa Inggris';
        const isIslamic = prodiIslam.includes(prodiName);
        const isGeneralStudy = ['umum', 'program studi umum'].includes(normalizedProdiName);

        return {
            year,
            prodiName,
            needsLegacy: !!year && year <= 2024 && !isS2 && !isEnglish && !isGeneralStudy,
            needsInteractiveClass: !!year && year <= 2024 && isEnglish,
            needsInteractiveArabic: !!year && year <= 2024 && isIslamic,
        };
    };

    const shouldLookupWhileTyping = (rawSrn) => {
        const value = String(rawSrn || '').trim().toUpperCase();
        const normalized = value.replace(/[^A-Z0-9]/g, '');

        if (normalized.length < legacyLookupMinLength) {
            return false;
        }

        const hasLetter = /[A-Z]/.test(normalized);
        if (hasLetter) {
            return true;
        }

        return normalized.length > legacyLookupMinLength;
    };

    const inferEnrollmentYearFromSrn = (rawSrn) => {
        const normalized = String(rawSrn || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (normalized.length < 2) {
            return null;
        }

        const prefix = normalized.slice(0, 2);
        if (!/^\d{2}$/.test(prefix)) {
            return null;
        }

        const shortYear = parseInt(prefix, 10);
        const currentYear = new Date().getFullYear();
        const currentCenturyBase = Math.floor(currentYear / 100) * 100;
        const futureThreshold = (currentYear % 100) + 1;
        let inferredYear = currentCenturyBase + shortYear;

        if (shortYear > futureThreshold) {
            inferredYear -= 100;
        }

        const inferredYearString = String(inferredYear);

        return availableYears.has(inferredYearString) ? inferredYearString : null;
    };

    const normalizeSrnForLookup = (rawSrn) => String(rawSrn || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '');

    const shouldAllowExistingUserLegacyScore = () => {
        return normalizeSrnForLookup(srnInput.value) === String(originalIdentity.srn || '')
            && String(yearSelect.value || '').trim() === String(originalIdentity.year || '')
            && String(prodyInput.value || '').trim() === String(originalIdentity.prodyId || '');
    };

    const applyAutoYearFromSrn = () => {
        const inferredYear = inferEnrollmentYearFromSrn(srnInput.value);
        if (!inferredYear) {
            return false;
        }

        const currentYearValue = String(yearSelect.value || '').trim();
        const wasAutofilled = yearSelect.dataset.autofilled === '1';

        if (yearManualOverride && currentYearValue !== '' && !wasAutofilled) {
            return false;
        }

        if (currentYearValue === inferredYear && wasAutofilled) {
            return false;
        }

        isApplyingAutoYear = true;
        yearSelect.value = inferredYear;
        yearSelect.dataset.autofilled = '1';
        yearSelect.dispatchEvent(new Event('input', { bubbles: true }));
        isApplyingAutoYear = false;

        return true;
    };

    const validateSrnInput = ({ report = false } = {}) => {
        const rawValue = String(srnInput.value || '').trim();
        const digitCount = String(rawValue).replace(/\D+/g, '').length;
        const hasValue = rawValue.length > 0;
        const isValid = !hasValue || digitCount >= legacyLookupMinLength;

        srnInput.setCustomValidity(isValid ? '' : `NPM minimal ${legacyLookupMinLength} digit.`);

        if (srnWarning) {
            srnWarning.style.display = hasValue && !isValid ? '' : 'none';
        }

        if (report && !isValid) {
            srnInput.reportValidity();
        }

        return isValid;
    };

    const setInputValidity = (input, valid, message = '') => {
        if (!input) {
            return;
        }

        input.setCustomValidity(valid ? '' : message);
    };

    const validateAcademicFields = ({ report = false } = {}) => {
        const flags = getFlags();
        const invalidInputs = [];

        if (legacyInput) {
            const needsLegacy = flags.needsLegacy;
            const hasValue = String(legacyInput.value || '').trim() !== '';
            legacyInput.required = needsLegacy;
            setInputValidity(legacyInput, !needsLegacy || hasValue, 'Nilai Basic Listening wajib terisi.');
            if (needsLegacy && !hasValue) {
                invalidInputs.push(legacyInput);
            }
        }

        interactiveClassInputs.forEach((input) => {
            const semester = input.dataset.semester || '?';
            const hasValue = String(input.value || '').trim() !== '';
            input.required = flags.needsInteractiveClass;
            setInputValidity(
                input,
                !flags.needsInteractiveClass || hasValue,
                `Nilai Interactive Class Semester ${semester} wajib terisi otomatis.`,
            );
            if (flags.needsInteractiveClass && !hasValue) {
                invalidInputs.push(input);
            }
        });

        interactiveArabicInputs.forEach((input) => {
            const semester = input.dataset.semester || '?';
            if (!input) {
                return;
            }

            const hasValue = String(input.value || '').trim() !== '';
            input.required = flags.needsInteractiveArabic;
            setInputValidity(input, !flags.needsInteractiveArabic || hasValue, `Nilai Interactive Bahasa Arab ${semester} wajib terisi otomatis.`);
            if (flags.needsInteractiveArabic && !hasValue) {
                invalidInputs.push(input);
            }
        });

        if (submitButton) {
            const valid = invalidInputs.length === 0 && validateSrnInput();
            submitButton.disabled = !valid;
            submitButton.style.opacity = valid ? '' : '0.6';
            submitButton.style.cursor = valid ? '' : 'not-allowed';
            submitButton.title = valid ? '' : 'Lengkapi field wajib terlebih dahulu.';
        }

        if (report && invalidInputs.length > 0) {
            invalidInputs[0].reportValidity();
        }

        return invalidInputs.length === 0;
    };

    const applyAcademicSections = () => {
        const flags = getFlags();
        setVisible(legacySection, flags.needsLegacy);
        setVisible(interactiveClassSection, flags.needsInteractiveClass);
        setVisible(interactiveArabicSection, flags.needsInteractiveArabic);

        if (!flags.needsLegacy) {
            setLegacyState({
                score: '',
                found: false,
                message: 'Nilai Basic Listening belum tersedia di arsip. Jika Anda sudah mengikuti dan lulus kelas, silakan konfirmasi ke kantor Lembaga Bahasa.',
                loading: false,
            });
        }

        if (!flags.needsInteractiveClass) {
            setInteractiveClassState({
                scores: {},
                found: false,
                message: 'Nilai Interactive Class akan terisi otomatis jika arsipnya ditemukan.',
                loading: false,
                clearAutoFilled: true,
            });
        }

        if (!flags.needsInteractiveArabic) {
            setInteractiveArabicState({
                scores: {},
                found: false,
                message: 'Masukkan NPM lengkap untuk mengecek nilai Bahasa Arab 1 dan 2 secara otomatis.',
                loading: false,
                clearAutoFilled: true,
            });
        }

        return flags;
    };

    const lookupLegacyScore = async () => {
        const flags = applyAcademicSections();
        if (!flags.needsLegacy && !flags.needsInteractiveClass && !flags.needsInteractiveArabic) {
            return;
        }

        const srn = String(srnInput.value || '').trim();
        const normalizedSrn = srn.replace(/\D+/g, '');
        if (!srn || normalizedSrn.length < legacyLookupMinLength) {
            if (flags.needsLegacy) {
                setLegacyState({
                    score: '',
                    found: false,
                    message: 'Lengkapi NPM terlebih dahulu untuk mendeteksi nilai Basic Listening.',
                    loading: false,
                });
            }
            if (flags.needsInteractiveClass) {
                setInteractiveClassState({
                    scores: {},
                    found: false,
                    message: 'Lengkapi NPM terlebih dahulu untuk mendeteksi nilai Interactive Class.',
                    loading: false,
                    clearAutoFilled: true,
                });
            }
            if (flags.needsInteractiveArabic) {
                setInteractiveArabicState({
                    scores: {},
                    found: false,
                    message: 'Lengkapi NPM terlebih dahulu untuk mengecek nilai Bahasa Arab 1 dan 2.',
                    loading: false,
                    clearAutoFilled: true,
                });
            }
            validateAcademicFields();
            return;
        }

        if (flags.needsLegacy) {
            setLegacyState({
                score: legacyInput?.value || '',
                found: false,
                message: '',
                loading: true,
            });
        }

        if (flags.needsInteractiveClass) {
            const currentScores = interactiveClassInputs.reduce((carry, input) => {
                const semester = parseInt(input.dataset.semester || '0', 10);
                const value = String(input.value || '').trim();
                if (semester && value !== '') {
                    carry[semester] = value;
                }

                return carry;
            }, {});

            setInteractiveClassState({
                scores: currentScores,
                found: false,
                message: '',
                loading: true,
            });
        }

        if (flags.needsInteractiveArabic) {
            const currentScores = interactiveArabicInputs.reduce((carry, input) => {
                const semester = parseInt(input.dataset.semester || '0', 10);
                const value = String(input.value || '').trim();
                if (semester && value !== '') {
                    carry[semester] = value;
                }

                return carry;
            }, {});

            setInteractiveArabicState({
                scores: currentScores,
                found: false,
                message: '',
                loading: true,
            });
        }

        try {
            const params = new URLSearchParams({
                srn,
                name: nameInput?.value || '',
                year: yearSelect.value || '',
                prody_id: prodyInput.value || '',
                allow_existing_user_score: shouldAllowExistingUserLegacyScore() ? '1' : '0',
            });

            const response = await fetch(`${lookupUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('Lookup nilai gagal diproses.');
            }

            const result = await response.json();

            if (flags.needsLegacy && !result.applicable) {
                setLegacyState({
                    score: '',
                    found: false,
                    message: 'Nilai Basic Listening belum tersedia di arsip. Jika Anda sudah mengikuti dan lulus kelas, silakan konfirmasi ke kantor Lembaga Bahasa.',
                    loading: false,
                });
                return;
            }

            if (flags.needsLegacy) {
                if (result.found && result.score !== null) {
                    setLegacyState({
                        score: result.score,
                        found: true,
                        message: result.message || 'Nilai ditemukan dari data manual.',
                        loading: false,
                    });
                } else {
                    setLegacyState({
                        score: '',
                        found: false,
                        message: result.message || 'Nilai belum ditemukan. Hubungi admin agar data manual diimport.',
                        loading: false,
                    });
                }
            }

            if (flags.needsInteractiveClass) {
                setInteractiveClassState({
                    scores: result.interactive_class_scores || {},
                    found: !!result.interactive_class_found,
                    message: result.interactive_class_message || 'Nilai Interactive Class belum ditemukan. Silakan ke kantor Lembaga Bahasa.',
                    loading: false,
                    clearAutoFilled: !result.interactive_class_found,
                    replaceAll: true,
                });
            }

            if (flags.needsInteractiveArabic) {
                setInteractiveArabicState({
                    scores: result.interactive_arabic_scores || {},
                    found: !!result.interactive_arabic_found,
                    message: result.interactive_arabic_message || 'Nilai Bahasa Arab 1 dan 2 belum ditemukan di arsip. Jika Anda sudah mengikuti dan lulus kelas, silakan konfirmasi ke kantor Lembaga Bahasa.',
                    loading: false,
                    clearAutoFilled: !result.interactive_arabic_found,
                    replaceAll: true,
                });
            }

            validateAcademicFields();
        } catch (error) {
            if (flags.needsLegacy) {
                setLegacyState({
                    score: '',
                    found: false,
                    message: error?.message || 'Terjadi kesalahan saat mencari nilai.',
                    loading: false,
                });
            }

            if (flags.needsInteractiveClass) {
                setInteractiveClassState({
                    scores: {},
                    found: false,
                    message: error?.message || 'Terjadi kesalahan saat mencari nilai Interactive Class.',
                    loading: false,
                    clearAutoFilled: true,
                });
            }

            if (flags.needsInteractiveArabic) {
                setInteractiveArabicState({
                    scores: {},
                    found: false,
                    message: error?.message || 'Terjadi kesalahan saat mengecek nilai Bahasa Arab 1 dan 2.',
                    loading: false,
                    clearAutoFilled: true,
                });
            }

            validateAcademicFields();
        }
    };

    srnInput.addEventListener('input', () => {
        validateSrnInput();
        applyAutoYearFromSrn();
        window.clearTimeout(lookupTimer);
        if (!shouldLookupWhileTyping(srnInput.value)) {
            const flags = applyAcademicSections();
            if (flags.needsLegacy) {
                setLegacyState({
                    score: '',
                    found: false,
                    message: 'Lengkapi NPM terlebih dahulu untuk mendeteksi nilai Basic Listening.',
                    loading: false,
                });
            }
            if (flags.needsInteractiveClass) {
                setInteractiveClassState({
                    scores: {},
                    found: false,
                    message: 'Lengkapi NPM terlebih dahulu untuk mendeteksi nilai Interactive Class.',
                    loading: false,
                    clearAutoFilled: true,
                });
            }
            if (flags.needsInteractiveArabic) {
                setInteractiveArabicState({
                    scores: {},
                    found: false,
                    message: 'Lengkapi NPM terlebih dahulu untuk mengecek nilai Bahasa Arab 1 dan 2.',
                    loading: false,
                    clearAutoFilled: true,
                });
            }
            validateAcademicFields();
            return;
        }

        lookupTimer = window.setTimeout(() => {
            lookupLegacyScore();
        }, 350);
    });

    srnInput.addEventListener('blur', () => {
        applyAutoYearFromSrn();
        validateSrnInput({ report: String(srnInput.value || '').trim().length > 0 });
        lookupLegacyScore();
    });

    yearSelect.addEventListener('change', () => {
        if (!isApplyingAutoYear) {
            yearManualOverride = String(yearSelect.value || '').trim() !== '';
            yearSelect.dataset.autofilled = '0';
        }
        lookupLegacyScore();
        validateAcademicFields();
    });

    window.addEventListener('biodata-prodi-changed', (event) => {
        if (event.detail?.id && prodyInput) {
            prodyInput.value = event.detail.id;
        }
        if (event.detail?.prodiName) {
            selectedProdyName = event.detail.prodiName;
        }
        lookupLegacyScore();
        validateAcademicFields();
    });

    [interactiveArabicInput1, interactiveArabicInput2].forEach((input) => {
        input?.addEventListener('input', () => {
            validateAcademicFields();
        });
    });

    profileForm?.addEventListener('submit', (event) => {
        const srnValid = validateSrnInput({ report: true });
        const academicValid = validateAcademicFields({ report: srnValid });

        if (!srnValid || !academicValid) {
            event.preventDefault();
        }
    });

    applyAutoYearFromSrn();
    applyAcademicSections();
    validateSrnInput();
    validateAcademicFields();
    lookupLegacyScore();
});
</script>
@endsection
