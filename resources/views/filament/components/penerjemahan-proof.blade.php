<div>
    {{-- Detail Pemohon --}}
    <div style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:16px;margin-bottom:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin-bottom:12px;">Detail Pemohon</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px;">
            <div>
                <dt style="font-size:11px;color:#94a3b8;margin:0;">Nama</dt>
                <dd style="margin:2px 0 0;font-size:13px;font-weight:600;color:#1e293b;word-break:break-word;">{{ $record->users?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt style="font-size:11px;color:#94a3b8;margin:0;">Email</dt>
                <dd style="margin:2px 0 0;font-size:13px;color:#334155;word-break:break-word;">{{ $record->users?->email ?? '-' }}</dd>
            </div>
            <div>
                <dt style="font-size:11px;color:#94a3b8;margin:0;">NPM / NIM</dt>
                <dd style="margin:2px 0 0;font-size:13px;color:#334155;">{{ $record->users?->srn ?? '-' }}</dd>
            </div>
            <div>
                <dt style="font-size:11px;color:#94a3b8;margin:0;">Program Studi</dt>
                <dd style="margin:2px 0 0;font-size:13px;color:#334155;">{{ $record->users?->prody?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt style="font-size:11px;color:#94a3b8;margin:0;">No. WhatsApp</dt>
                <dd style="margin:2px 0 0;font-size:13px;color:#334155;">{{ $record->users?->whatsapp ?? '-' }}</dd>
            </div>
            <div>
                <dt style="font-size:11px;color:#94a3b8;margin:0;">Tanggal Pengajuan</dt>
                <dd style="margin:2px 0 0;font-size:13px;color:#334155;">
                    {{ optional($record->submission_date ?? $record->created_at)->translatedFormat('d M Y, H:i') }}
                </dd>
            </div>
            <div>
                <dt style="font-size:11px;color:#94a3b8;margin:0;">Jumlah Kata</dt>
                <dd style="margin:2px 0 0;font-size:13px;color:#334155;">{{ number_format($record->source_word_count ?? 0) }} kata</dd>
            </div>
            <div>
                <dt style="font-size:11px;color:#94a3b8;margin:0;">Status</dt>
                <dd style="margin:2px 0 0;font-size:13px;font-weight:600;color:#1e293b;">{{ $record->status ?? '-' }}</dd>
            </div>
        </div>
    </div>

    {{-- Bukti Pembayaran --}}
    @if(filled($record->bukti_pembayaran))
        <div style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:16px;">
            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin-bottom:12px;">Bukti Pembayaran</p>
            <a href="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}" target="_blank" style="display:block;">
                <img src="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}"
                     alt="Bukti Pembayaran"
                     style="display:block;margin:0 auto;max-width:100%;max-height:320px;width:auto;height:auto;object-fit:contain;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
            </a>
            <p style="margin:8px 0 0;text-align:center;font-size:11px;color:#94a3b8;">
                Klik gambar untuk membuka di tab baru
            </p>
        </div>
    @else
        <p style="padding:24px 0;text-align:center;font-size:13px;color:#94a3b8;">Tidak ada bukti pembayaran.</p>
    @endif
</div>
