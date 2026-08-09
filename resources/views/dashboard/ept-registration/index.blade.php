{{-- resources/views/dashboard/ept-registration/index.blade.php --}}
@extends('layouts.dashboard')


@section('title', 'Pendaftaran EPT')
@section('page-title', 'Pendaftaran EPT')

@section('content')
<div class="space-y-6" x-data="{ showModal: false, downloadUrl: '' }">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Pendaftaran Tes EPT</h1>
            <p class="mt-1 text-sm text-slate-500">
                Daftarkan diri Anda untuk mengikuti tes EPT dengan mengunggah bukti pembayaran.
            </p>
        </div>
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Dashboard</span>
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 flex items-start gap-3">
            <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5"></i>
            <div class="text-sm text-emerald-800">{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 p-4 flex items-start gap-3">
            <i class="fa-solid fa-circle-xmark text-red-600 mt-0.5"></i>
            <div class="text-sm text-red-800">{{ session('error') }}</div>
        </div>
    @endif

    {{-- KONDISI 1: Belum daftar atau Ditolak --}}
    @if(!$registration || ! $registration->blocksNewRegistration())
        
        @if($registration && $registration->status === 'rejected')
            <div class="bg-red-50 rounded-xl border-2 border-red-200 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-red-100 text-red-500 rounded-full flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-xmark text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-red-800">Pendaftaran Ditolak</h3>
                        <p class="text-sm text-red-700 mt-1">
                            <strong>Alasan:</strong> {{ $registration->rejection_reason ?? 'Tidak ada keterangan.' }}
                        </p>
                        <p class="text-sm text-red-600 mt-2">Silakan unggah ulang bukti pembayaran yang valid.</p>
                    </div>
                </div>
            </div>
        @endif

        @if($registration && $registration->status === 'approved' && ! $registration->blocksNewRegistration())
            <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-circle-check text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-emerald-800">Pendaftaran Terakhir Selesai</h3>
                        <p class="text-sm text-emerald-700 mt-1">
                            Pendaftaran terakhir Anda sudah selesai dijalankan pada siklus sebelumnya.
                        </p>
                        <p class="text-sm text-emerald-600 mt-2">
                            Diajukan pada {{ $registration->created_at->translatedFormat('d F Y, H:i') }}
                        </p>
                    </div>
                </div>
            </div>

            @include('dashboard.ept-registration.partials.schedule-cards', ['registration' => $registration])
        @endif

        @if($canCreateRegistration)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm" x-data="{ step: 1, accepted: false, mode: '' }">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice text-slate-400"></i>
                        Formulir Pendaftaran
                    </h2>
                </div>
                <div class="p-6 sm:p-8">

                    {{-- STEP 1: Syarat & Ketentuan --}}
                    <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                        <div class="mb-4 rounded-2xl border border-blue-200 bg-blue-50 p-5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white shrink-0">
                                    <i class="fa-solid fa-bullhorn"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-blue-900">INFORMASI PENTING PENDAFTARAN EPT</h3>
                                    <p class="text-sm text-blue-700 mt-0.5">Harap membaca dan memahami ketentuan berikut sebelum melanjutkan pendaftaran:</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 text-sm text-slate-700 leading-relaxed">
                            <div class="rounded-xl border border-slate-200 p-5">
                                <h4 class="font-bold text-slate-900 mb-2"><i class="fa-solid fa-calendar-check text-um-blue mr-1.5"></i>1. Pelaksanaan Tes</h4>
                                <ul class="space-y-1.5 list-disc ml-5">
                                    <li>Tes EPT dilaksanakan secara <strong>luring (offline).</strong></li>
                                    <li>Jadwal pelaksanaan EPT akan diumumkan setelah <strong>kuota minimal 20 peserta</strong> terpenuhi dan berdasarkan <strong>ketersediaan ruangan.</strong></li>
                                    <li>Jadwal akan diumumkan paling lambat <strong>H-2 sebelum pelaksanaan tes.</strong></li>
                                    <li>Peserta wajib memantau Dashboard akun masing-masing secara berkala. <strong>Informasi jadwal hanya akan diumumkan melalui website UPT Bahasa</strong> dan tidak disampaikan melalui WhatsApp maupun email.</li>
                                </ul>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-5">
                                <h4 class="font-bold text-slate-900 mb-2"><i class="fa-solid fa-arrows-rotate text-um-blue mr-1.5"></i>2. Ketentuan Reschedule</h4>
                                <ul class="space-y-1.5 list-disc ml-5">
                                    <li>Permohonan reschedule hanya dapat diajukan paling lambat <strong>H-1 sebelum jadwal tes</strong> yang telah ditetapkan.</li>
                                    <li>Reschedule hanya diberikan atas alasan yang dapat dipertanggungjawabkan, yaitu:
                                        <ul class="list-circle ml-5 mt-1 space-y-1">
                                            <li>Sakit, dibuktikan dengan surat keterangan dokter/surat sakit;</li>
                                            <li>Perjalanan dinas, dibuktikan dengan surat perjalanan dinas atau surat tugas;</li>
                                            <li>Sidang skripsi, Seminar Proposal (Sempro), atau kegiatan akademik wajib lainnya, dibuktikan dengan SK Sidang, SK Sempro, atau surat resmi dari program studi/fakultas.</li>
                                        </ul>
                                    </li>
                                    <li>Permohonan reschedule yang tidak disertai dokumen pendukung atau diajukan melewati batas waktu <strong>tidak dapat diproses.</strong></li>
                                </ul>
                            </div>

                            <div class="rounded-xl border border-slate-200 p-5">
                                <h4 class="font-bold text-slate-900 mb-2"><i class="fa-solid fa-user-xmark text-rose-500 mr-1.5"></i>3. Ketentuan Ketidakhadiran</h4>
                                <p class="space-y-1.5">
                                    Peserta yang tidak hadir tanpa pemberitahuan atau tidak mengajukan reschedule sesuai ketentuan akan dianggap mengundurkan diri (<strong>gugur</strong>). Biaya pendaftaran yang telah dibayarkan <strong>tidak dapat dikembalikan</strong> maupun dialihkan ke jadwal berikutnya.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm text-amber-800">
                                <i class="fa-solid fa-triangle-exclamation text-amber-500 mr-1"></i>
                                <strong>PENTING:</strong> Dengan melanjutkan pendaftaran, peserta dianggap telah membaca, memahami, dan menyetujui seluruh ketentuan di atas.
                            </p>
                        </div>

                        <label class="mt-5 flex items-start gap-3 rounded-xl border border-slate-200 p-4 cursor-pointer hover:bg-slate-50 transition">
                            <input type="checkbox" x-model="accepted" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-um-blue focus:ring-um-blue/30">
                            <span class="text-sm text-slate-700">Saya telah membaca dan menyetujui seluruh syarat dan ketentuan pendaftaran EPT di atas.</span>
                        </label>

                        <div class="mt-5">
                            <button type="button"
                                    @click="step = 2"
                                    :disabled="!accepted"
                                    :class="accepted ? 'bg-um-blue hover:bg-um-dark-blue shadow-lg shadow-blue-900/20' : 'bg-slate-300 cursor-not-allowed'"
                                    class="w-full inline-flex items-center justify-center gap-2 px-8 py-3 rounded-full text-white font-bold text-sm transition-all">
                                <i class="fa-solid fa-arrow-right"></i>
                                Lanjut ke Pendaftaran
                            </button>
                        </div>
                    </div>

                    {{-- STEP 2: Pilih Mode Pelaksanaan --}}
                    <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                        <h3 class="text-base font-bold text-slate-900 mb-1">Pilih Mode Pelaksanaan EPT</h3>
                        <p class="text-sm text-slate-500 mb-5">Tentukan bagaimana Anda akan mengikuti tes EPT.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div @click="mode = 'offline'"
                                 :class="mode === 'offline' ? 'border-um-blue bg-blue-50 ring-2 ring-um-blue/20' : 'border-slate-200 hover:border-slate-300'"
                                 class="cursor-pointer rounded-2xl border-2 bg-white p-6 transition-all">
                                <div class="flex items-start justify-between">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                        <i class="fa-solid fa-building-columns text-xl"></i>
                                    </div>
                                    <i class="fa-solid fa-circle-check mt-1 text-2xl" :class="mode === 'offline' ? 'text-um-blue' : 'text-slate-200'"></i>
                                </div>
                                <h4 class="mt-4 font-bold text-slate-900">EPT Offline</h4>
                                <p class="mt-1 text-xs text-slate-500 leading-relaxed">Tes dilaksanakan secara luring di lokasi kampus sesuai jadwal dan grup yang ditentukan.</p>
                            </div>

                            <div @click="mode = 'online'"
                                 :class="mode === 'online' ? 'border-um-blue bg-blue-50 ring-2 ring-um-blue/20' : 'border-slate-200 hover:border-slate-300'"
                                 class="cursor-pointer rounded-2xl border-2 bg-white p-6 transition-all">
                                <div class="flex items-start justify-between">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                        <i class="fa-solid fa-laptop text-xl"></i>
                                    </div>
                                    <i class="fa-solid fa-circle-check mt-1 text-2xl" :class="mode === 'online' ? 'text-um-blue' : 'text-slate-200'"></i>
                                </div>
                                <h4 class="mt-4 font-bold text-slate-900">EPT Online</h4>
                                <p class="mt-1 text-xs text-slate-500 leading-relaxed">Tes dilaksanakan secara daring melalui sistem EPT Online dengan akses kode dari admin.</p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col sm:flex-row gap-3">
                            <button type="button"
                                    @click="step = 1"
                                    class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full border border-slate-200 bg-white text-slate-600 font-bold text-sm hover:bg-slate-50 transition">
                                <i class="fa-solid fa-arrow-left"></i> Kembali
                            </button>
                            <button type="button"
                                    @click="step = 3"
                                    :disabled="!mode"
                                    :class="mode ? 'bg-um-blue hover:bg-um-dark-blue shadow-lg shadow-blue-900/20' : 'bg-slate-300 cursor-not-allowed'"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-8 py-3 rounded-full text-white font-bold text-sm transition-all">
                                Lanjut ke Formulir <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    {{-- STEP 3: Formulir --}}
                    <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 overflow-hidden">
                        <div class="flex items-center gap-4 px-5 py-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400">
                                <i class="fa-solid fa-user text-lg"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold uppercase text-slate-900">{{ $user->name }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    {{ $user->srn ?? '-' }} <span class="mx-1.5 text-slate-300">&bull;</span> {{ $user->prody->name ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @php
                        $studentStatusOptions = \App\Models\EptRegistration::studentStatusOptions();
                        $studentStatusDescriptions = [
                            'regular' => 'Mahasiswa S1/D3 Reguler',
                            'magister' => 'Mahasiswa S2',
                            'konversi' => 'Mahasiswa S1 RPL/Pindahan',
                            'general' => 'Umum (bukan mahasiswa UM Metro)',
                        ];
                        $selectedStudentStatus = old('student_status');
                    @endphp
                    <form id="ept-registration-form" action="{{ route('dashboard.ept-registration.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                        {{-- Label Bukti Pembayaran --}}
                        <label class="block text-sm font-bold text-slate-800 mb-3">
                            Bukti Pembayaran <span class="text-red-500">*</span>
                        </label>

                        {{-- Info Box Biru --}}
                        <div class="bg-blue-50 rounded-xl p-4 border border-blue-100 -mt-1">
                            <p class="text-sm text-blue-800 flex items-start gap-2">
                                <i class="fa-solid fa-info-circle text-blue-500 mt-0.5"></i>
                                <span>Buat Tagihan dan Bayar <strong>UANG TOEFL/EPT</strong> terlebih dahulu di <strong><a href="https://siakad.ummetro.ac.id/app/keuangan/buat-tagihan" target="_blank" class="underline hover:text-blue-600">SIAKAD</a></strong>, kemudian unggah bukti pembayaran di bawah ini.</span>
                            </p>
                        </div>

                        {{-- Warning Box Kuning --}}
                        <div class="bg-amber-50 rounded-xl p-4 border border-amber-200">
                            <p class="text-sm font-semibold text-amber-800 mb-2 flex items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                                Perhatian! Pastikan foto bukti pembayaran:
                            </p>
                            <ul class="text-sm text-amber-700 space-y-1 ml-6 list-disc">
                                <li>Pastikan foto jelas, <strong>tidak buram</strong> atau ada bayangan</li>
                                <li>NPM dan jumlah pembayaran harus <strong>terlihat dengan jelas</strong></li>
                                <li>Gunakan hasil scan (CamScanner/scanner dokumen) atau <strong>screenshot langsung dari aplikasi bank</strong></li>
                            </ul>
                            <p class="text-sm text-amber-700 mt-4 flex items-center gap-2">
                                Pendaftaran akan Ditolak jika Bukti Pembayaran yang dilampirkan Tidak Valid / Tidak Terlihat Jelas.
                            </p>
                        </div>

                        <div class="h-4 sm:h-5"></div>

                        {{-- Mode EPT: diambil dari pilihan step 2 --}}
                        <input type="hidden" name="mode" :value="mode">
                        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 flex items-center gap-3">
                            <i class="fa-solid fa-location-dot text-um-blue"></i>
                            <p class="text-sm text-blue-900">
                                Mode pelaksanaan: <strong x-text="mode === 'online' ? 'EPT Online (daring)' : 'EPT Offline (luring)'"></strong>
                            </p>
                        </div>

                        {{-- Status Peserta --}}
                        <div class="space-y-3">
                        <label class="block text-sm font-bold text-slate-800">
                            Status Peserta <span class="text-red-500">*</span>
                        </label>
                        <p class="text-sm text-slate-500">
                            Pilih status Anda terlebih dahulu sebelum mengunggah bukti pembayaran.
                        </p>
                        <div id="student-status-options-ept" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($studentStatusOptions as $value => $label)
                                <label class="student-status-option-ept relative block">
                                    <input
                                        type="radio"
                                        name="student_status"
                                        value="{{ $value }}"
                                        class="sr-only peer"
                                        {{ $selectedStudentStatus === $value ? 'checked' : '' }}
                                    >
                                    <span class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-4 transition-all peer-checked:border-um-blue peer-checked:bg-blue-50 hover:border-slate-300 hover:bg-slate-50">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-semibold text-slate-700 peer-checked:text-um-blue">{{ $label }}</span>
                                            <span class="mt-1 block text-xs text-slate-500">
                                                {{ $studentStatusDescriptions[$value] ?? '' }}
                                            </span>
                                        </span>
                                        <i class="fa-solid fa-circle-check mt-0.5 text-slate-300 peer-checked:text-um-blue"></i>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('student_status')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        </div>

                        {{-- Tombol Mengerti --}}
                        <div id="understand-button-wrapper-ept" class="flex justify-center {{ $selectedStudentStatus ? 'hidden' : '' }}">
                            <button type="button" id="btn-understand-ept"
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-um-blue text-white font-bold text-sm shadow-lg shadow-blue-900/20 hover:bg-um-dark-blue transition-all hover:scale-[1.02] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none disabled:hover:scale-100"
                                    {{ $selectedStudentStatus ? '' : 'disabled' }}>
                                <i class="fa-solid fa-circle-check"></i>
                                Mengerti dan Unggah Bukti
                            </button>
                        </div>

                        {{-- Upload Area (hidden initially) --}}
                        <div id="upload-wrapper-ept" class="{{ $selectedStudentStatus ? '' : 'hidden' }} space-y-6">
                        <div>
                            <div class="relative group">
                                <div id="payment-dropzone-ept"
                                    class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center
                                            hover:border-um-blue hover:bg-blue-50/50 transition-colors
                                            flex flex-col items-center justify-center gap-2 cursor-pointer">
                                    
                                    <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-1
                                                text-slate-400 group-hover:text-um-blue group-hover:bg-blue-100 transition-colors">
                                        <i class="fa-solid fa-upload"></i>
                                    </div>

                                    <p class="text-sm text-slate-600">
                                        Klik atau seret file ke sini
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        JPG, PNG, WebP (Maks. 8MB)
                                    </p>

                                    {{-- Preview --}}
                                    <div id="payment-preview-wrapper-ept" class="mt-3 hidden">
                                        <div class="flex items-center gap-3 justify-center">
                                            <img id="payment-preview-ept"
                                                src=""
                                                alt="Preview bukti pembayaran"
                                                class="h-12 w-12 rounded-lg object-cover border border-slate-200 shadow-sm">
                                            <div class="text-left">
                                                <p id="payment-filename-ept"
                                                class="text-xs font-semibold text-slate-700 truncate max-w-[180px]"></p>
                                                <p class="text-[11px] text-emerald-600">
                                                    <i class="fa-solid fa-check mr-1"></i>File siap diunggah
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <input
                                        id="bukti_pembayaran_input_ept"
                                        type="file"
                                        name="bukti_pembayaran"
                                        accept="image/*"
                                        required
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                    >
                                </div>
                            </div>
                            @error('bukti_pembayaran') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Submit Button --}}
                        <div class="pt-4 border-t border-slate-100">
                            <button id="btn-submit-ept" type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 px-8 py-3 rounded-full bg-um-blue hover:bg-um-dark-blue text-white font-bold text-sm shadow-lg shadow-blue-900/20 transition-all hover:scale-[1.02] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:scale-100">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span id="btn-submit-ept-label">Daftar EPT</span>
                            </button>

                            <div id="ept-submit-progress" class="mt-3 hidden" aria-live="polite">
                                <div class="mb-1 flex items-center justify-between text-[11px] text-slate-500">
                                    <span id="ept-submit-status">Sedang mengunggah bukti pembayaran...</span>
                                    <span id="ept-submit-percent">0%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                    <div id="ept-submit-bar" class="h-full rounded-full bg-um-blue transition-[width] duration-300 ease-linear" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        </div>
                        </div>
                    </form>

                    <div class="mt-4">
                        <button type="button"
                                @click="step = 2"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-slate-200 bg-white text-slate-600 text-xs font-bold hover:bg-slate-50 transition">
                            <i class="fa-solid fa-arrow-left"></i> Kembali pilih mode
                        </button>
                    </div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-slate-600 shrink-0">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">Pendaftaran Baru Tidak Tersedia</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $eligibilityReason ?? 'Pendaftaran EPT belum bisa dibuat saat ini.' }}
                        </p>
                        <p class="mt-2 text-xs text-slate-500">
                            Anda masih bisa melihat status pendaftaran terakhir di halaman ini.
                        </p>
                    </div>
                </div>
            </div>
        @endif

    {{-- KONDISI 2: Pending --}}
    @elseif($registration->status === 'pending')
        <div class="bg-white rounded-2xl border border-slate-200 shadow-lg overflow-hidden">
            {{-- Header with gradient --}}
            <div class="bg-gradient-to-r from-amber-500 to-orange-500 p-6 text-center">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-clock text-white text-3xl"></i>
                </div>
                <h2 class="text-xl font-bold text-white">Menunggu Verifikasi</h2>
                <p class="text-amber-100 text-sm mt-1">Pendaftaran Anda sedang diproses</p>
            </div>

            {{-- Info Cards --}}
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    {{-- Tanggal Daftar --}}
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-calendar text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 font-medium">Tanggal Daftar</p>
                                <p class="text-sm font-bold text-slate-800">{{ $registration->created_at->translatedFormat('d M Y') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-hourglass-half text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 font-medium">Status</p>
                                <p class="text-sm font-bold text-amber-600">Menunggu</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Info Box --}}
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <p class="text-sm text-blue-800 flex items-start gap-2">
                        <i class="fa-solid fa-info-circle text-blue-500 mt-0.5"></i>
                        <span>Status pendaftaran akan ditampilkan di halaman ini setelah diverifikasi oleh admin.</span>
                    </p>
                </div>
            </div>
        </div>

    {{-- KONDISI 3: Approved --}}
    @elseif($registration->status === 'approved')
        {{-- Success Banner --}}
        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 rounded-2xl p-6 text-white shadow-lg text-center">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-circle-check text-3xl"></i>
            </div>
            <h2 class="text-xl font-bold">Pendaftaran Disetujui</h2>
            <p class="text-emerald-100 text-sm mt-1">Berikut adalah jadwal tes EPT yang telah ditetapkan untuk Anda</p>
        </div>

        @include('dashboard.ept-registration.partials.schedule-cards', ['registration' => $registration])
    @endif
</div>

<script>
    function initEptRegistrationPage() {
        // === Button "Mengerti dan Unggah Bukti" ===
        const btnUnderstand = document.getElementById('btn-understand-ept');
        const buttonWrapper = document.getElementById('understand-button-wrapper-ept');
        const uploadWrapper = document.getElementById('upload-wrapper-ept');
        const statusOptions = document.querySelectorAll('input[name="student_status"]');

        function toggleUnderstandButton() {
            if (!btnUnderstand) return;
            const hasSelectedStatus = Array.from(statusOptions).some((option) => option.checked);
            btnUnderstand.disabled = !hasSelectedStatus;
        }

        if (btnUnderstand && buttonWrapper && uploadWrapper) {
            btnUnderstand.addEventListener('click', function () {
                buttonWrapper.classList.add('hidden');
                uploadWrapper.classList.remove('hidden');

                requestAnimationFrame(() => {
                    uploadWrapper.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                });
            });
        }

        statusOptions.forEach((option) => {
            option.addEventListener('change', toggleUnderstandButton);
        });

        toggleUnderstandButton();

        // === Preview Bukti Pembayaran ===
        const dropzone   = document.getElementById('payment-dropzone-ept');
        const input      = document.getElementById('bukti_pembayaran_input_ept');
        const previewBox = document.getElementById('payment-preview-wrapper-ept');
        const previewImg = document.getElementById('payment-preview-ept');
        const fileNameEl = document.getElementById('payment-filename-ept');

        if (dropzone && input && previewBox && previewImg && fileNameEl) {
            function handleFile(file) {
                if (!file) return;
                fileNameEl.textContent = file.name;

                if (!file.type.startsWith('image/')) {
                    previewImg.src = '';
                    previewBox.classList.remove('hidden');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewBox.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }

            input.addEventListener('change', function (e) {
                const file = e.target.files && e.target.files[0];
                handleFile(file);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('border-um-blue', 'bg-blue-50/50');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('border-um-blue', 'bg-blue-50/50');
                });
            });

            dropzone.addEventListener('drop', function (e) {
                const dt = e.dataTransfer;
                if (!dt || !dt.files || !dt.files[0]) return;
                input.files = dt.files;
                handleFile(dt.files[0]);
            });
        }

        // === Submit Guard + Progress Indicator ===
        const registrationForm = document.getElementById('ept-registration-form');
        const submitButton = document.getElementById('btn-submit-ept');
        const submitLabel = document.getElementById('btn-submit-ept-label');
        const submitProgress = document.getElementById('ept-submit-progress');
        const submitBar = document.getElementById('ept-submit-bar');
        const submitPercent = document.getElementById('ept-submit-percent');
        const submitStatus = document.getElementById('ept-submit-status');

        if (registrationForm && submitButton && submitLabel) {
            let isSubmitting = false;
            let progressInterval = null;
            let progressValue = 0;

            const setProgress = (value) => {
                const normalized = Math.max(0, Math.min(100, Math.round(value)));
                progressValue = normalized;

                if (submitBar) {
                    submitBar.style.width = `${normalized}%`;
                }

                if (submitPercent) {
                    submitPercent.textContent = `${normalized}%`;
                }
            };

            const startPseudoProgress = () => {
                setProgress(8);
                progressInterval = setInterval(() => {
                    if (progressValue >= 92) {
                        return;
                    }

                    const bump = Math.floor(Math.random() * 7) + 3;
                    setProgress(Math.min(92, progressValue + bump));
                }, 350);
            };

            const stopPseudoProgress = () => {
                if (progressInterval) {
                    clearInterval(progressInterval);
                    progressInterval = null;
                }
            };

            registrationForm.addEventListener('submit', function (event) {
                if (isSubmitting) {
                    event.preventDefault();
                    return;
                }

                if (!registrationForm.checkValidity()) {
                    return;
                }

                isSubmitting = true;
                submitButton.disabled = true;
                submitButton.setAttribute('aria-busy', 'true');
                submitLabel.textContent = 'Sedang Mengunggah...';

                if (submitProgress) {
                    submitProgress.classList.remove('hidden');
                }

                if (submitStatus) {
                    submitStatus.textContent = 'Sedang mengunggah bukti pembayaran...';
                }

                startPseudoProgress();

                setTimeout(() => {
                    if (!isSubmitting) {
                        return;
                    }

                    if (submitStatus) {
                        submitStatus.textContent = 'Unggahan sedang diproses, mohon tunggu...';
                    }
                }, 8000);
            });

            window.addEventListener('pageshow', function () {
                isSubmitting = false;
                stopPseudoProgress();
                setProgress(0);
            });
        }
    }

    // Jalankan saat load awal maupun navigasi Turbo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEptRegistrationPage);
    } else {
        initEptRegistrationPage();
    }
    window.__pageInit = initEptRegistrationPage;
</script>
@endsection
