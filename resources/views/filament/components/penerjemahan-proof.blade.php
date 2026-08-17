<div class="space-y-4">
    {{-- Detail Pemohon --}}
    <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-3">Detail Pemohon</p>
        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div>
                <dt class="text-xs text-slate-400">Nama</dt>
                <dd class="mt-0.5 font-semibold text-slate-800">{{ $record->users?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400">Email</dt>
                <dd class="mt-0.5 font-medium text-slate-700">{{ $record->users?->email ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400">NPM / NIM</dt>
                <dd class="mt-0.5 font-medium text-slate-700">{{ $record->users?->srn ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400">Program Studi</dt>
                <dd class="mt-0.5 font-medium text-slate-700">{{ $record->users?->prody?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400">No. WhatsApp</dt>
                <dd class="mt-0.5 font-medium text-slate-700">{{ $record->users?->whatsapp ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400">Tanggal Pengajuan</dt>
                <dd class="mt-0.5 font-medium text-slate-700">
                    {{ optional($record->submission_date ?? $record->created_at)->translatedFormat('d M Y, H:i') }}
                </dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400">Jumlah Kata</dt>
                <dd class="mt-0.5 font-medium text-slate-700">{{ number_format($record->source_word_count ?? 0) }} kata</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-400">Status</dt>
                <dd class="mt-0.5 font-semibold text-slate-800">{{ $record->status ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Bukti Pembayaran --}}
    @if(filled($record->bukti_pembayaran))
        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-3">Bukti Pembayaran</p>
            <a href="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}" target="_blank" class="block">
                <img src="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}"
                     alt="Bukti Pembayaran"
                     class="mx-auto max-h-80 w-auto max-w-full rounded-xl border border-slate-200 shadow-sm object-contain">
            </a>
            <p class="mt-2 text-center text-xs text-slate-400">
                Klik gambar untuk membuka di tab baru
            </p>
        </div>
    @else
        <p class="py-6 text-center text-sm text-slate-400">Tidak ada bukti pembayaran.</p>
    @endif
</div>
