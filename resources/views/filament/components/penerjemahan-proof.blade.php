<div>
    {{-- Detail Pemohon --}}
    <div style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:16px;margin-bottom:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin:0 0 12px;">Detail Pemohon</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px;">
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">Nama</p>
                <p style="margin:2px 0 0;font-size:13px;font-weight:600;color:#1e293b;word-break:break-word;">{{ $record->users?->name ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">Email</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;word-break:break-word;">{{ $record->users?->email ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">NPM / NIM</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;">{{ $record->users?->srn ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">Program Studi</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;">{{ $record->users?->prody?->name ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">No. WhatsApp</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;">{{ $record->users?->whatsapp ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">Status</p>
                <p style="margin:2px 0 0;font-size:13px;font-weight:600;color:#1e293b;">{{ $record->status ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Detail Penerjemahan --}}
    <div style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:16px;margin-bottom:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin:0 0 12px;">Detail Penerjemahan</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px;">
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">Tanggal Pengajuan</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;">
                    {{ optional($record->submission_date ?? $record->created_at)->translatedFormat('d M Y, H:i') }}
                </p>
            </div>
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">Tanggal Selesai</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;">
                    {{ optional($record->completion_date)->translatedFormat('d M Y, H:i') ?? '-' }}
                </p>
            </div>
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">Jumlah Kata (Sumber)</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;">{{ number_format($record->source_word_count ?? 0) }} kata</p>
            </div>
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">Jumlah Kata (Terjemahan)</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;">{{ number_format($record->translated_word_count ?? 0) }} kata</p>
            </div>
            <div style="grid-column:1 / -1;">
                <p style="font-size:11px;color:#94a3b8;margin:0;">Penerjemah</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;">{{ $record->translator?->name ?? '-' }}</p>
            </div>
            @if($record->rejection_reason)
            <div style="grid-column:1 / -1;">
                <p style="font-size:11px;color:#dc2626;margin:0;">Alasan Ditolak</p>
                <p style="margin:2px 0 0;font-size:13px;color:#b91c1c;">{{ $record->rejection_reason }}</p>
            </div>
            @endif
            @if($record->verification_code)
            <div style="grid-column:1 / -1;">
                <p style="font-size:11px;color:#94a3b8;margin:0;">Kode Verifikasi</p>
                <p style="margin:2px 0 0;font-size:13px;font-family:monospace;color:#1e40af;">{{ $record->verification_code }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Teks Abstrak --}}
    @if(filled($record->source_text))
    <div style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:16px;margin-bottom:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin:0 0 8px;">Teks Abstrak (Sumber)</p>
        <div style="font-size:13px;color:#334155;line-height:1.6;max-height:180px;overflow-y:auto;white-space:pre-wrap;word-break:break-word;">
            {!! strip_tags($record->source_text) !!}
        </div>
    </div>
    @endif

    {{-- Hasil Terjemahan --}}
    @if(filled($record->translated_text))
    <div style="border-radius:12px;background:#f0fdf4;border:1px solid #bbf7d0;padding:16px;margin-bottom:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#16a34a;margin:0 0 8px;">Hasil Terjemahan</p>
        <div style="font-size:13px;color:#1e293b;line-height:1.6;max-height:240px;overflow-y:auto;white-space:pre-wrap;word-break:break-word;">
            {!! strip_tags($record->translated_text) !!}
        </div>
    </div>
    @endif

    {{-- Bukti Pembayaran --}}
    @if(filled($record->bukti_pembayaran))
    <div style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin:0 0 12px;">Bukti Pembayaran</p>
        <a href="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}" target="_blank" style="display:block;">
            <img src="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}"
                 alt="Bukti Pembayaran"
                 style="display:block;margin:0 auto;max-width:100%;max-height:280px;width:auto;height:auto;object-fit:contain;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
        </a>
        <p style="margin:8px 0 0;text-align:center;font-size:11px;color:#94a3b8;">
            Klik gambar untuk membuka di tab baru
        </p>
    </div>
    @else
    <p style="padding:24px 0;text-align:center;font-size:13px;color:#94a3b8;">Tidak ada bukti pembayaran.</p>
    @endif
</div>
