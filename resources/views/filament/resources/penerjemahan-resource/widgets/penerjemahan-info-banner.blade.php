@php
    /** @var \App\Models\Penerjemahan|null $latest */
    $latest   = $this->latestSubmission;   // dari widget class
    /** @var \App\Models\Penerjemahan|null $finished */
    $finished = $this->finishedSubmission; // dari widget class
    $status   = $latest?->status;
@endphp

{{-- =========================================
     SELESAI
========================================= --}}
@if ($finished)
    <x-filament::section class="border-green-200 bg-green-50/60 dark:bg-green-900/20 mb-2">
        <x-slot name="heading">
            <span class="inline-flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-check-badge" class="h-5 w-5 text-green-600 dark:text-green-400" />
                <span>Terjemahan Selesai</span>
            </span>
        </x-slot>

        <div class="text-sm text-green-800 dark:text-green-200">
            Dokumen terjemahan Anda sudah selesai. Silakan <strong>unduh PDF resmi</strong> di bawah ini.
            <br><br>
            Untuk Ceklis SIMYUD, silakan kumpulkan Kwitansi Pembayaran asli ke Kantor Lembaga Bahasa.
            <br><br>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-filament::button
                    tag="a"
                    href="{{ route('penerjemahan.pdf', [$finished, 'dl' => 1]) }}"
                    target="_blank" rel="noopener"
                    color="success"
                    icon="heroicon-m-arrow-down-tray"
                >
                    Unduh PDF
                </x-filament::button>

                @php
                    $verifyUrl = $finished?->verification_url
                        ?: (filled($finished?->verification_code)
                            ? route('verification.show', ['code' => $finished->verification_code], true)
                            : null);
                @endphp

                @if ($verifyUrl)
                    <x-filament::button
                        tag="a"
                        href="{{ $verifyUrl }}"
                        target="_blank" rel="noopener"
                        color="gray"
                        icon="heroicon-m-link"
                    >
                        Lihat Verifikasi
                    </x-filament::button>
                @endif
            </div>
        </div>
    </x-filament::section>

{{-- =========================================
     DITOLAK (pembayaran/dokumen)
========================================= --}}
@elseif ($latest && str_contains($status, 'Ditolak'))
    <x-filament::section class="border-rose-200 bg-rose-50/70 dark:bg-rose-900/20 mb-2">
        <x-slot name="heading">
            <span class="inline-flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-x-circle" class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                <span>Pengajuan Ditolak</span>
            </span>
        </x-slot>

        <div class="text-sm text-rose-900 dark:text-rose-200">
            Status: <strong>{{ $status }}</strong>.
            Silakan perbaiki sesuai arahan admin, lalu ajukan kembali melalui formulir.
            @if($latest?->rejection_reason)
                <br><br>
                <span class="font-semibold">Alasan:</span> {{ $latest->rejection_reason }}
            @endif
        </div>
    </x-filament::section>

{{-- =========================================
     MENUNGGU (verifikasi awal)
========================================= --}}
@elseif ($latest && $status === 'Menunggu')
    <x-filament::section class="border-yellow-200 bg-yellow-50/70 dark:bg-amber-900/20 mb-2">
        <x-slot name="heading">
            <span class="inline-flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-clock" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                <span>Menunggu Peninjauan</span>
            </span>
        </x-slot>

        <div class="text-sm text-amber-900 dark:text-amber-200">
            Pengajuan Anda menunggu verifikasi. Admin akan mengecek bukti pembayaran & dokumen.
        </div>
    </x-filament::section>

{{-- =========================================
     DISETUJUI / DIPROSES
========================================= --}}
@elseif ($latest && in_array($status, ['Disetujui', 'Diproses'], true))
    <x-filament::section class="border-sky-200 bg-sky-50/70 dark:bg-sky-900/20 mb-2">
        <x-slot name="heading">
            <span class="inline-flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-information-circle" class="h-5 w-5 text-sky-600 dark:text-sky-400" />
                <span>{{ $status === 'Disetujui' ? 'Pembayaran Disetujui' : 'Sedang Diproses' }}</span>
            </span>
        </x-slot>

        <div class="text-sm text-sky-900 dark:text-sky-200">
            {{ $status === 'Disetujui'
                ? 'Pengajuan Anda disetujui dan akan segera dikerjakan oleh penerjemah.'
                : 'Penerjemah sedang mengerjakan dokumen Anda.' }}
        </div>
    </x-filament::section>

{{-- =========================================
     BELUM ADA PENGAJUAN
========================================= --}}
@else
    <x-filament::section class="border-slate-200 bg-white/70 dark:bg-slate-900/20 mb-2">
        <x-slot name="heading">
            <span class="inline-flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-sparkles" class="h-5 w-5 text-slate-600 dark:text-slate-300" />
                <span>Belum Ada Pengajuan</span>
            </span>
        </x-slot>

        <div class="text-sm text-slate-700 dark:text-slate-300">
            Silakan ajukan dokumen abstrak Anda melalui tombol <em>Permintaan Baru</em> di kanan atas.
        </div>
    </x-filament::section>
@endif
