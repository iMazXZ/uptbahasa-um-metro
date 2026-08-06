{{-- resources/views/dashboard/submit-ept-score.blade.php --}}
@extends('layouts.dashboard')

@section('title', 'Pengajuan Surat Rekomendasi')
@section('page-title', 'Surat Rekomendasi EPT')

@section('content')
<div class="space-y-6">

    {{-- Header & Actions --}}
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Pengajuan Surat Rekomendasi</h1>
            <p class="mt-1 text-sm text-slate-500 max-w-2xl">
                Ajukan surat rekomendasi EPT setelah memenuhi persyaratan Basic Listening dan melampirkan 3 bukti nilai tes.
            </p>
        </div>

        <div class="w-full sm:w-auto">
            <a href="{{ route('dashboard') }}"
               class="w-full sm:w-auto inline-flex items-center gap-2 px-4 py-2 rounded-full border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Dashboard</span>
            </a>
        </div>
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

    {{-- KONDISI 1: Biodata Belum Lengkap - FULL PAGE --}}
    @if(! $biodataComplete)
        <div class="bg-white rounded-2xl border-2 border-amber-200 shadow-lg p-12 flex flex-col items-center text-center">
            <div class="w-20 h-20 bg-amber-100 text-amber-500 rounded-full flex items-center justify-center mb-6">
                <i class="fa-solid fa-triangle-exclamation text-4xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-3">Biodata Belum Lengkap</h2>
            <p class="text-slate-500 max-w-lg mx-auto mb-8 leading-relaxed">
                Sistem mendeteksi data diri Anda belum lengkap (<strong>Prodi</strong>, <strong>NPM</strong>, <strong>Tahun Angkatan</strong>).
                <br>Khusus angkatan <strong>≤ 2024</strong> wajib memiliki data nilai Basic Listening manual yang sudah terdaftar.
            </p>
            <a href="{{ route('dashboard.biodata') }}"
               class="inline-flex items-center gap-3 px-8 py-4 rounded-full bg-amber-500 hover:bg-amber-600 text-white font-bold text-base shadow-lg shadow-amber-500/30 transition-all hover:scale-105">
                <i class="fa-solid fa-user-pen text-lg"></i> 
                Lengkapi Biodata Sekarang
            </a>
        </div>

    {{-- KONDISI 2: Angkatan Baru Belum BL - FULL PAGE --}}
    @elseif($year >= 2025 && ! $completedBL)
        <div class="bg-white rounded-2xl border-2 border-blue-200 shadow-lg p-12 flex flex-col items-center text-center">
            <div class="w-20 h-20 bg-blue-100 text-blue-500 rounded-full flex items-center justify-center mb-6">
                <i class="fa-solid fa-headphones text-4xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-3">Wajib Basic Listening</h2>
            <p class="text-slate-500 max-w-lg mx-auto mb-8 leading-relaxed">
                Mahasiswa angkatan <strong>2025 ke atas</strong> wajib mengikuti program 
                <strong>Basic Listening</strong> terlebih dahulu sebelum mengajukan surat rekomendasi.
            </p>
            <a href="{{ route('bl.index') }}"
               class="inline-flex items-center gap-3 px-8 py-4 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-base shadow-lg shadow-blue-600/30 transition-all hover:scale-105">
                <i class="fa-solid fa-arrow-right text-lg"></i> 
                Daftar Basic Listening
            </a>
        </div>

    {{-- KONDISI 3 & 4: Biodata Lengkap - Tampilkan Sections --}}
    @else
        {{-- SECTION 1: Riwayat Pengajuan --}}
        <div class="space-y-4">
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2 px-1">
                <i class="fa-solid fa-clock-rotate-left text-slate-400"></i>
                Riwayat Pengajuan
            </h2>

            @if($submissions->isEmpty())
                <div class="bg-white rounded-xl border border-slate-200 border-dashed p-8 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-inbox text-slate-300 text-xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-900">Belum ada riwayat</p>
                    <p class="text-xs text-slate-500 mt-1">
                        Riwayat pengajuan Anda akan muncul di sini.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4">
                    @foreach($submissions as $row)
                        @php
                            $statusConfig = match ($row->status) {
                                'pending'  => ['label' => 'Menunggu', 'class' => 'bg-amber-100 text-amber-800 border-amber-200'],
                                'approved' => ['label' => 'Disetujui', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200'],
                                'rejected' => ['label' => 'Ditolak', 'class' => 'bg-red-100 text-red-800 border-red-200'],
                                default    => ['label' => $row->status, 'class' => 'bg-slate-100 text-slate-800 border-slate-200'],
                            };
                        @endphp

                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:border-blue-300 transition-colors">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">Tanggal Pengajuan</p>
                                    <div class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                        <i class="fa-regular fa-calendar"></i>
                                        {{ $row->created_at->translatedFormat('d M Y, H:i') }}
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $statusConfig['class'] }}">
                                    {{ $statusConfig['label'] }}
                                </span>
                            </div>

                            <div class="bg-slate-50 rounded-lg border border-slate-100 p-3 mb-4">
                                <p class="text-[10px] text-slate-400 text-center uppercase font-bold tracking-wider mb-2">Rincian Skor</p>
                                <div class="grid grid-cols-3 gap-2 text-center divide-x divide-slate-200">
                                    <div>
                                        <div class="text-[10px] text-slate-500 mb-0.5">Tes 1</div>
                                        <div class="text-sm font-mono font-bold text-slate-800">{{ $row->nilai_tes_1 }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-500 mb-0.5">Tes 2</div>
                                        <div class="text-sm font-mono font-bold text-slate-800">{{ $row->nilai_tes_2 }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-500 mb-0.5">Tes 3</div>
                                        <div class="text-sm font-mono font-bold text-slate-800">{{ $row->nilai_tes_3 }}</div>
                                    </div>
                                </div>
                            </div>

                            @if($row->catatan_admin)
                                <div class="mb-4 text-xs text-slate-600 bg-yellow-50 border border-yellow-100 p-3 rounded-lg">
                                    <span class="font-bold text-yellow-700 block mb-1"><i class="fa-regular fa-comment-dots"></i> Catatan Admin:</span> 
                                    {{ $row->catatan_admin }}
                                </div>
                            @endif

                            @if($row->status === 'approved')
                                @php
                                    $pdfUrlRow = filled($row->verification_code)
                                        ? route('verification.ept.pdf', ['code' => $row->verification_code, 'dl' => 1])
                                        : route('ept-submissions.pdf', [$row, 'dl' => 1]);

                                    $verifyUrlRow = $row->verification_url
                                        ?: (filled($row->verification_code)
                                            ? route('verification.show', ['code' => $row->verification_code], true)
                                            : null);
                                @endphp

                                <div class="flex flex-col sm:flex-row gap-2 mt-2 pt-3 border-t border-slate-100">
                                    @if($verifyUrlRow)
                                        <a href="{{ $verifyUrlRow }}" target="_blank"
                                           class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 hover:text-um-blue transition">
                                            <i class="fa-solid fa-certificate"></i> Cek Link Verifikasi
                                        </a>
                                    @endif

                                    <a href="{{ $pdfUrlRow }}" target="_blank"
                                       class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-bold hover:bg-emerald-100 transition">
                                        <i class="fa-solid fa-download"></i> Download PDF
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- SECTION 2: Form Pengajuan --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-slate-400"></i>
                    Formulir Pengajuan Baru
                </h2>
            </div>

            <div class="p-6 sm:p-8">
                @if($hasSubmissions)
                    <div class="flex flex-col items-center text-center py-8">
                        <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-4">
                            <i class="fa-solid fa-file-circle-check text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Pengajuan Sedang Diproses</h3>
                        <p class="text-slate-500 max-w-md mx-auto mt-2 text-sm">
                            Anda sudah memiliki pengajuan yang berstatus <strong>Menunggu</strong> atau <strong>Disetujui</strong>.
                            Silakan tunggu proses selesai atau hubungi admin jika ada kesalahan.
                        </p>
                    </div>
                @else
                    <form id="ept-recommendation-form" action="{{ route('dashboard.ept.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        @foreach(range(1, 3) as $i)
                        <div class="relative rounded-xl border border-slate-200 p-5 hover:border-blue-300 hover:bg-blue-50/30 transition-colors">
                            <div class="absolute -top-3 -left-3 w-8 h-8 rounded-full bg-um-blue text-white flex items-center justify-center font-bold text-sm shadow-sm border-2 border-white">
                                {{ $i }}
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                                <div class="md:col-span-12 mb-1">
                                    <h3 class="text-sm font-bold text-slate-800">Tes EPT Ke-{{ $i }}</h3>
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Nilai Yang Diperoleh <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="nilai_tes_{{ $i }}" value="{{ old('nilai_tes_'.$i) }}"
                                           class="w-full py-3 px-4 rounded-xl border-2 border-slate-200 bg-white shadow-sm focus:border-um-blue focus:ring-um-blue text-base"
                                           min="0" max="677" placeholder="Contoh: 450" required>
                                    @error('nilai_tes_'.$i) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-4">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Tanggal Tes <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="tanggal_tes_{{ $i }}" value="{{ old('tanggal_tes_'.$i) }}"
                                           class="w-full py-3 px-4 rounded-xl border-2 border-slate-200 bg-white shadow-sm focus:border-um-blue focus:ring-um-blue text-base" required>
                                    @error('tanggal_tes_'.$i) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-5">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                        Bukti Screenshot <span class="text-red-500">*</span>
                                    </label>
                                    <input type="file" name="foto_path_{{ $i }}" accept="image/*"
                                           class="block w-full text-sm text-slate-600
                                                  file:mr-4 file:py-2.5 file:px-4
                                                  file:rounded-lg file:border-0
                                                  file:text-sm file:font-semibold
                                                  file:bg-um-blue file:text-white
                                                  hover:file:bg-um-dark-blue cursor-pointer border-2 border-slate-200 rounded-xl py-2 px-3 bg-white shadow-sm">
                                    <p class="mt-1 text-[10px] text-slate-400">Format JPG/PNG, Max 8MB.</p>
                                    @error('foto_path_'.$i) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                        @endforeach

                        <div class="pt-4 border-t border-slate-100 space-y-3">
                            <div class="flex justify-end">
                                <button id="btn-submit-ept-recommendation" type="submit"
                                        class="inline-flex items-center gap-2 px-8 py-3 rounded-full bg-um-blue hover:bg-um-dark-blue text-white font-bold text-sm shadow-lg shadow-blue-900/20 transition-all hover:scale-[1.02] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:scale-100">
                                <i class="fa-solid fa-paper-plane"></i>
                                    <span id="btn-submit-ept-recommendation-label">Kirim Pengajuan</span>
                                </button>
                            </div>

                            <div id="ept-recommendation-submit-progress" class="hidden" aria-live="polite">
                                <div class="mb-1 flex items-center justify-between text-[11px] text-slate-500">
                                    <span id="ept-recommendation-submit-status">Sedang mengunggah bukti screenshot...</span>
                                    <span id="ept-recommendation-submit-percent">0%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                    <div id="ept-recommendation-submit-bar" class="h-full rounded-full bg-um-blue transition-[width] duration-300 ease-linear" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const recommendationForm = document.getElementById('ept-recommendation-form');
        const submitButton = document.getElementById('btn-submit-ept-recommendation');
        const submitLabel = document.getElementById('btn-submit-ept-recommendation-label');
        const submitProgress = document.getElementById('ept-recommendation-submit-progress');
        const submitBar = document.getElementById('ept-recommendation-submit-bar');
        const submitPercent = document.getElementById('ept-recommendation-submit-percent');
        const submitStatus = document.getElementById('ept-recommendation-submit-status');

        if (!recommendationForm || !submitButton || !submitLabel) {
            return;
        }

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

        recommendationForm.addEventListener('submit', function (event) {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            if (!recommendationForm.checkValidity()) {
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
                submitStatus.textContent = 'Sedang mengunggah bukti screenshot...';
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
    });
</script>
@endsection
