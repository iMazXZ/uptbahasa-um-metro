{{-- resources/views/dashboard/translation/index.blade.php --}}
@extends('layouts.dashboard')

@section('title', 'Penerjemahan Abstrak')
@section('page-title', 'Layanan Penerjemahan')

@section('content')
<div class="space-y-6">

    {{-- Header & Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Penerjemahan Abstrak</h1>
            <p class="mt-1 text-sm text-slate-500">
                Daftar riwayat pengajuan penerjemahan dokumen abstrak Anda.
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
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-lg overflow-hidden animate-fade-in">
            {{-- Success Header with Animation --}}
            <div class="bg-gradient-to-r from-emerald-500 to-teal-500 p-6 text-center">
                <div class="relative inline-flex items-center justify-center">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg animate-bounce-in">
                        <i class="fa-solid fa-check text-emerald-500 text-3xl"></i>
                    </div>
                </div>
                <h2 class="text-white text-lg font-bold mt-4">Permohonan Berhasil Dikirim!</h2>
                <p class="text-emerald-100 text-sm mt-1">{{ session('success') }}</p>
            </div>

            {{-- Progress Steps --}}
            <div class="px-6 py-4 bg-emerald-50 border-b border-emerald-100">
                <div class="flex items-center justify-between max-w-md mx-auto">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-bold">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <span class="text-xs text-emerald-700 font-medium mt-1">Diajukan</span>
                    </div>
                    <div class="flex-1 h-1 bg-emerald-200 mx-2"></div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-bold animate-pulse">
                            2
                        </div>
                        <span class="text-xs text-emerald-700 font-medium mt-1">Disetujui</span>
                    </div>
                    <div class="flex-1 h-1 bg-slate-200 mx-2"></div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-400 flex items-center justify-center text-xs font-bold">
                            3
                        </div>
                        <span class="text-xs text-slate-400 font-medium mt-1">Diproses</span>
                    </div>
                    <div class="flex-1 h-1 bg-slate-200 mx-2"></div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-400 flex items-center justify-center text-xs font-bold">
                            4
                        </div>
                        <span class="text-xs text-slate-400 font-medium mt-1">Selesai</span>
                    </div>
                </div>
            </div>

            {{-- Info Cards --}}
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-center">
                    <i class="fa-solid fa-clock text-slate-400 text-xl mb-2"></i>
                    <p class="text-xs text-slate-500 mb-1">Estimasi Waktu</p>
                    <p class="text-sm font-bold text-slate-800">3-5 Hari Kerja</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-center">
                    <i class="fa-solid fa-bell text-slate-400 text-xl mb-2"></i>
                    <p class="text-xs text-slate-500 mb-1">Update Status</p>
                    <p class="text-sm font-bold text-slate-800">Pantau di Sini</p>
                </div>
            </div>
        </div>

        {{-- Animation Styles --}}
        <style>
            @keyframes fade-in {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes bounce-in {
                0% { transform: scale(0); }
                50% { transform: scale(1.2); }
                100% { transform: scale(1); }
            }
            .animate-fade-in { animation: fade-in 0.5s ease-out; }
            .animate-bounce-in { animation: bounce-in 0.5s ease-out; }
        </style>
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

    {{-- KONDISI 2: Basic Listening Belum - FULL PAGE --}}
    @elseif(! $completedBL)
        <div class="bg-white rounded-2xl border-2 border-blue-200 shadow-lg p-12 flex flex-col items-center text-center">
            <div class="w-20 h-20 bg-blue-100 text-blue-500 rounded-full flex items-center justify-center mb-6">
                <i class="fa-solid fa-headphones text-4xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-3">Wajib Basic Listening</h2>
            <p class="text-slate-500 max-w-lg mx-auto mb-8 leading-relaxed">
                Anda belum memenuhi syarat <strong>Basic Listening</strong>.
                <br>{{ $basicListeningRequirementMessage ?? 'Tombol pengajuan akan muncul otomatis setelah nilai Attendance & Final Test terdata.' }}
            </p>
            <a href="{{ route('bl.index') }}"
               class="inline-flex items-center gap-3 px-8 py-4 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-base shadow-lg shadow-blue-600/30 transition-all hover:scale-105">
                <i class="fa-solid fa-arrow-right text-lg"></i> 
                Daftar Basic Listening
            </a>
        </div>

    {{-- KONDISI 3: Biodata & BL Lengkap - Tampilkan Riwayat --}}
    @else
        {{-- Info Banner - hanya tampil saat ada records --}}
        @if(!$records->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 flex items-start gap-4">
                <div class="shrink-0 w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="fa-solid fa-list-check text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Ringkasan Permohonan</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Pantau status permohonan penerjemahan Anda di bawah ini.
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- List Riwayat --}}
        <div class="space-y-4">
            @if($records->isEmpty())
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    {{-- Header --}}
                    <div class="px-6 py-5 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-800">Mulai Langkah Pertama Anda</h3>
                        <p class="text-sm text-slate-500 mt-1">Ajukan penerjemahan dokumen abstrak dalam 3 langkah mudah</p>
                    </div>

                    {{-- Steps - Simplified --}}
                    <div class="px-6 py-6">
                        <div class="flex items-start max-w-sm mx-auto">
                            {{-- Step 1 --}}
                            <div class="flex-1 text-center">
                                <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold mx-auto">1</div>
                                <p class="text-xs text-slate-600 mt-2">Upload Bukti</p>
                            </div>
                            
                            {{-- Connector --}}
                            <div class="w-12 h-px bg-slate-300 mt-4"></div>
                            
                            {{-- Step 2 --}}
                            <div class="flex-1 text-center">
                                <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold mx-auto">2</div>
                                <p class="text-xs text-slate-600 mt-2">Isi Abstrak</p>
                            </div>
                            
                            {{-- Connector --}}
                            <div class="w-12 h-px bg-slate-300 mt-4"></div>
                            
                            {{-- Step 3 --}}
                            <div class="flex-1 text-center">
                                <div class="w-9 h-9 rounded-full bg-emerald-500 text-white flex items-center justify-center text-sm font-bold mx-auto">
                                    <i class="fa-solid fa-check text-xs"></i>
                                </div>
                                <p class="text-xs text-slate-600 mt-2">Selesai</p>
                            </div>
                        </div>
                    </div>

                    {{-- CTA --}}
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-center">
                        <a href="{{ route('dashboard.translation.create') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-um-blue text-white font-bold text-sm hover:bg-um-dark-blue transition">
                            <i class="fa-solid fa-plus"></i>
                            Ajukan Sekarang
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4">
                    @foreach($records as $row)
                        @php
                            $status = $row->status ?? '-';
                            $colorClass = match (true) {
                                $status === 'Menunggu' => 'bg-amber-100 text-amber-800 border-amber-200',
                                in_array($status, ['Diproses', 'Disetujui'], true) => 'bg-sky-100 text-sky-800 border-sky-200',
                                $status === 'Selesai' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                str_contains($status, 'Tidak Valid') || str_starts_with($status, 'Ditolak') => 'bg-red-100 text-red-800 border-red-200',
                                default => 'bg-slate-100 text-slate-800 border-slate-200',
                            };

                            // Progress step calculation: Menunggu → Disetujui → Diproses → Selesai
                            $progressStep = match (true) {
                                $status === 'Menunggu' => 1,
                                in_array($status, ['Disetujui'], true) => 2,
                                in_array($status, ['Diproses'], true) => 3,
                                $status === 'Selesai' => 4,
                                default => 0,
                            };

                            $canDownload = in_array(strtolower($status), ['selesai', 'disetujui', 'completed', 'approved'], true)
                                        && (filled($row->translated_text) || filled($row->final_file_path));
                            $downloadUrl = route('penerjemahan.pdf', [$row, 'dl' => 1]);
                        @endphp
                        {{-- Special Card for Selesai Status --}}
                        @if($status === 'Selesai')
                        <div class="rounded-xl border-2 border-emerald-200 shadow-lg overflow-hidden bg-gradient-to-br from-emerald-50 to-teal-50">
                            {{-- Header with Success Icon --}}
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-500 p-5 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-full bg-white flex items-center justify-center shadow-lg">
                                        <i class="fa-solid fa-circle-check text-emerald-500 text-2xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-emerald-100 font-medium uppercase tracking-wider">Selesai</p>
                                        <p class="text-sm font-bold text-white">
                                            {{ optional($row->submission_date ?? $row->created_at)->translatedFormat('d F Y, H:i') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Info --}}
                            <div class="p-5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm mb-4">
                                    <div class="bg-white rounded-lg p-3 border border-emerald-100">
                                        <span class="text-xs text-emerald-600 block mb-1"><i class="fa-solid fa-calendar-check mr-1"></i>Tanggal Selesai</span>
                                        <span class="font-medium text-slate-700">
                                            {{ $row->completion_date ? $row->completion_date->translatedFormat('d F Y') : '-' }}
                                        </span>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border border-emerald-100">
                                        <span class="text-xs text-emerald-600 block mb-1"><i class="fa-solid fa-file-lines mr-1"></i>Jenis</span>
                                        <span class="font-medium text-slate-700">Terjemahan Abstrak</span>
                                    </div>
                                </div>

                                @if($canDownload)
                                <a href="{{ $downloadUrl }}" target="_blank"
                                   class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-600/30 transition-all hover:scale-[1.02]">
                                    <i class="fa-solid fa-file-arrow-down"></i>
                                    Download Hasil Terjemahan PDF
                                </a>
                                @endif

                                {{-- Alert Info SIMYUD --}}
                                <div class="mt-4 flex items-start gap-3 p-3 rounded-xl bg-blue-50 border border-blue-100">
                                    <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                                    <p class="text-sm text-blue-700">
                                        Jika ingin <strong>Ceklis SIMYUD</strong>, silakan kumpulkan <strong>bukti pembayaran Asli (1 lembar)</strong> ke Kantor Lembaga Bahasa.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Special Card for Ditolak Status --}}
                        @elseif(str_starts_with($status, 'Ditolak') || str_contains($status, 'Tidak Valid'))
                        <div class="rounded-xl border-2 border-red-200 shadow-lg overflow-hidden bg-gradient-to-br from-red-50 to-orange-50">
                            {{-- Header with Error Icon --}}
                            <div class="bg-gradient-to-r from-red-500 to-rose-500 p-5 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-full bg-white flex items-center justify-center shadow-lg">
                                        <i class="fa-solid fa-circle-xmark text-red-500 text-2xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-red-100 font-medium uppercase tracking-wider">{{ $status }}</p>
                                        <p class="text-sm font-bold text-white">
                                            {{ optional($row->submission_date ?? $row->created_at)->translatedFormat('d F Y, H:i') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Info + Action --}}
                            <div class="p-5">
                                {{-- Rejection Message --}}
                                @if($row->rejection_reason ?? $row->notes ?? false)
                                <div class="bg-white rounded-lg p-4 border border-red-100 mb-4">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid fa-triangle-exclamation text-red-500 mt-0.5"></i>
                                        <div>
                                            <p class="text-xs text-red-600 font-bold mb-1">Alasan Penolakan:</p>
                                            <p class="text-sm text-slate-700">{{ $row->rejection_reason ?? $row->notes ?? 'Silakan hubungi admin untuk informasi lebih lanjut.' }}</p>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm mb-4">
                                    <div class="bg-white rounded-lg p-3 border border-red-100">
                                        <span class="text-xs text-red-600 block mb-1"><i class="fa-solid fa-calendar mr-1"></i>Tanggal Pengajuan</span>
                                        <span class="font-medium text-slate-700">
                                            {{ optional($row->submission_date ?? $row->created_at)->translatedFormat('d F Y') }}
                                        </span>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 border border-red-100">
                                        <span class="text-xs text-red-600 block mb-1"><i class="fa-solid fa-file-lines mr-1"></i>Jenis</span>
                                        <span class="font-medium text-slate-700">Terjemahan Abstrak</span>
                                    </div>
                                </div>

                                <a href="{{ route('dashboard.translation.edit', $row) }}"
                                   class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-amber-500 text-white text-sm font-bold hover:bg-amber-600 shadow-lg shadow-amber-500/30 transition-all hover:scale-[1.02]">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    Perbaiki dan Ajukan Ulang
                                </a>
                            </div>
                        </div>

                        {{-- Regular Card for Other Statuses --}}
                        @else
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:border-blue-300 transition-all group">
                            {{-- Header with Date and Status --}}
                            <div class="p-5 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shrink-0">
                                        <i class="fa-solid fa-language"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wider mb-0.5">Tanggal Pengajuan</p>
                                        <p class="text-sm font-bold text-slate-900">
                                            {{ optional($row->submission_date ?? $row->created_at)->translatedFormat('d F Y, H:i') }}
                                        </p>
                                    </div>
                                </div>
                                <span class="self-start px-3 py-1 rounded-full text-xs font-bold border {{ $colorClass }}">
                                    {{ $status }}
                                </span>
                            </div>

                            {{-- Mini Progress Indicator --}}
                            @if($progressStep > 0)
                            <div class="px-5 pb-4">
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 4; $i++)
                                        <div class="flex-1 h-1.5 rounded-full {{ $i <= $progressStep ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                    @endfor
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span class="text-[10px] text-slate-400">Diajukan</span>
                                    <span class="text-[10px] text-slate-400">Selesai</span>
                                </div>
                            </div>
                            @endif

                            {{-- Info Cards --}}
                            <div class="px-5 pb-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                    <span class="text-xs text-slate-400 block mb-1"><i class="fa-solid fa-clock mr-1"></i>Estimasi</span>
                                    <span class="font-medium text-slate-700">
                                        {{ $row->completion_date ? $row->completion_date->translatedFormat('d F Y') : '3-5 Hari Kerja' }}
                                    </span>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                    <span class="text-xs text-slate-400 block mb-1"><i class="fa-solid fa-file-lines mr-1"></i>Jenis</span>
                                    <span class="font-medium text-slate-700">Terjemahan Abstrak</span>
                                </div>
                            </div>
                            {{-- Footer Actions --}}
                            <div class="px-5 pb-5 flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                                @if(str_starts_with($status, 'Ditolak'))
                                    <a href="{{ route('dashboard.translation.edit', $row) }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-50 text-amber-700 text-xs font-bold hover:bg-amber-100 transition">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Perbaiki Data
                                    </a>
                                @endif

                                @if($canDownload)
                                    <a href="{{ $downloadUrl }}" target="_blank"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 shadow-sm transition">
                                        <i class="fa-solid fa-file-arrow-down"></i>
                                        Download Hasil PDF
                                    </a>
                                @endif

                                @if(!$canDownload && !str_starts_with($status, 'Ditolak'))
                                    <span class="text-xs text-slate-400 italic px-2">
                                        Menunggu proses...
                                    </span>
                                @endif
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
