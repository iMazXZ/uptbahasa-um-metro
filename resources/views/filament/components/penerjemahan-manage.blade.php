<div>
    <style>
        .pm-btn { display:block; width:100%; box-sizing:border-box; border:none; border-radius:8px; padding:11px 14px; font-size:13px; font-weight:600; cursor:pointer; text-align:center; }
        .pm-btn-blue { background:#2563eb; color:#fff; }
        .pm-btn-green { background:#16a34a; color:#fff; }
        .pm-btn-red { background:#dc2626; color:#fff; }
        .pm-btn-red-outline { background:#fff; color:#dc2626; border:1px solid #dc2626; }
        .pm-btn-gray { background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; }
        .pm-btn-sm { display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; border-radius:8px; padding:8px 14px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none; }
        .pm-btn-sm-green { display:inline-flex; align-items:center; gap:6px; background:#16a34a; color:#fff; border:none; border-radius:8px; padding:8px 14px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none; }
        .pm-label { display:block; font-size:12px; font-weight:600; color:#475569; margin:0 0 6px; }
        .pm-select, .pm-textarea { width:100%; box-sizing:border-box; border:1px solid #cbd5e1; border-radius:8px; padding:10px; font-size:13px; background:#fff; font-family:inherit; }
        .pm-select { margin-bottom:10px; appearance:none; -webkit-appearance:none; -moz-appearance:none; padding-right:32px; background-image:url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; }
        .pm-textarea { margin-bottom:10px; min-height:64px; resize:vertical; }
        .pm-stack { display:flex; flex-direction:column; gap:10px; }
        .pm-ic { margin-right:6px; }
    </style>

    {{-- Baris atas: Status + tombol aksi --}}
    @php
        $status = $record->status ?? '-';
        $statusStyle = match (true) {
            $status === 'Menunggu' => 'background:#fef3c7;color:#92400e;border-color:#fcd34d;',
            in_array($status, ['Diproses', 'Disetujui'], true) => 'background:#e0f2fe;color:#075985;border-color:#7dd3fc;',
            $status === 'Selesai' => 'background:#dcfce7;color:#166534;border-color:#86efac;',
            default => 'background:#fee2e2;color:#991b1b;border-color:#fca5a5;',
        };
        $statusLower = strtolower((string) $status);
        $okStatus = in_array($statusLower, ['selesai', 'disetujui', 'completed', 'approved'], true);
        $hasOutput = filled($record->translated_text) || filled($record->final_file_path);
        $canDownload = $okStatus && $hasOutput;
        $canCopyLink = filled($record->verification_code) && in_array($statusLower, ['selesai', 'disetujui', 'completed', 'approved'], true);
        $internalUser = auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga']);
        $staffUser = auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']);
    @endphp
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
        <div style="{{ $statusStyle }}border:1px solid;border-radius:10px;padding:8px 14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-size:13px;font-weight:700;">{{ $status }}</span>
            @if($record->rejection_reason)
                <span style="font-size:12px;opacity:0.9;">— {{ $record->rejection_reason }}</span>
            @endif
        </div>
        @if($internalUser && ($canDownload || $staffUser || $canCopyLink))
        <div style="display:flex;gap:8px;">
            @if($canCopyLink)
            <button type="button" onclick="navigator.clipboard.writeText('{{ route('verification.penerjemahan.pdf', $record->verification_code) }}').then(() => alert('Link disalin!'))" class="pm-btn-sm"><i class="fas fa-link pm-ic"></i>Salin Link Publik</button>
            @endif
            @if($canDownload)
            <a href="{{ route('penerjemahan.pdf', [$record, 'dl' => 1]) }}" target="_blank" class="pm-btn-sm pm-btn-sm-green"><i class="fas fa-download pm-ic"></i>Unduh PDF</a>
            @endif
            @if($staffUser)
            <a href="{{ route('filament.admin.resources.penerjemahan.edit', $record) }}" class="pm-btn-sm"><i class="fas fa-pen pm-ic"></i>Edit Data</a>
            @endif
        </div>
        @endif
    </div>

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
                <p style="font-size:11px;color:#94a3b8;margin:0;">Tanggal Pengajuan</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;">
                    {{ optional($record->submission_date ?? $record->created_at)->translatedFormat('d M Y, H:i') }}
                </p>
            </div>
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">Jumlah Kata</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;">{{ number_format($record->source_word_count ?? 0) }} kata</p>
            </div>
            @if($record->translator)
            <div>
                <p style="font-size:11px;color:#94a3b8;margin:0;">Penerjemah</p>
                <p style="margin:2px 0 0;font-size:13px;color:#334155;">{{ $record->translator?->name }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Bukti Pembayaran --}}
    @if(filled($record->bukti_pembayaran))
    <div style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:16px;margin-bottom:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin:0 0 12px;">Bukti Pembayaran</p>
        <a href="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}" target="_blank" style="display:block;">
            <img src="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}"
                 alt="Bukti Pembayaran"
                 style="display:block;margin:0 auto;max-width:100%;max-height:240px;width:auto;height:auto;object-fit:contain;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
        </a>
        <p style="margin:8px 0 0;text-align:center;font-size:11px;color:#94a3b8;">
            Klik gambar untuk membuka di tab baru
        </p>
    </div>
    @else
    <div style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:16px;margin-bottom:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin:0 0 12px;">Bukti Pembayaran</p>
        <p style="padding:20px 0;text-align:center;font-size:13px;color:#94a3b8;">Tidak ada bukti pembayaran.</p>
    </div>
    @endif

    {{-- Aksi --}}
    @php
        $user = auth()->user();
        $isStaff = $user?->hasAnyRole(['Admin', 'Staf Administrasi']);
        $isAdmin = $user?->hasRole('Admin');
        $statusLower = strtolower((string) $status);
        $translators = \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', 'Penerjemah'))->orderBy('name')->get(['id', 'name']);
        $canRejectPayment = ! in_array($record->status, ['Disetujui', 'Diproses', 'Selesai'], true);
        $canReject = $canRejectPayment;
    @endphp

    @if($isStaff)
    <div style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:16px;margin-bottom:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin:0 0 12px;">Tindakan</p>
        <div class="pm-stack">

            {{-- Setujui Pembayaran --}}
            @if($canRejectPayment)
                <button type="button" onclick="if(!confirm('Setujui pembayaran ini?'))return;(function(){var s=document.createElement('form');s.method='POST';s.action='/admin/penerjemahan/{{ $record->id }}/approve';var t=document.createElement('input');t.type='hidden';t.name='_token';t.value='{{ csrf_token() }}';s.appendChild(t);document.body.appendChild(s);s.submit();})();" class="pm-btn pm-btn-blue"><i class="fas fa-check pm-ic"></i>Setujui Pembayaran</button>
            @endif

            {{-- Tolak Pengajuan (expand saat tombol diklik) --}}
            @if($canReject)
                <div>
                    <button type="button" onclick="var f=this.nextElementSibling;var h=f.style.display==='none'||f.style.display==='';f.style.display=h?'block':'none';this.innerHTML=h?'<i class=&quot;fas fa-chevron-up pm-ic&quot;></i>Tutup':'<i class=&quot;fas fa-xmark pm-ic&quot;></i>Tolak';" class="pm-btn pm-btn-red-outline"><i class="fas fa-xmark pm-ic"></i>Tolak</button>
                    <form class="pm-form pm-reject-box" style="display:none;margin-top:10px;">
                        <label class="pm-label">Jenis Penolakan</label>
                        <select name="jenis" class="pm-select">
                            @if($canRejectPayment)
                                <option value="payment">Pembayaran Tidak Valid</option>
                            @endif
                            <option value="document" @if(!$canRejectPayment) selected @endif>Dokumen Tidak Valid</option>
                        </select>
                        <textarea name="rejection_reason" required class="pm-textarea" placeholder="Alasan penolakan..."></textarea>
                        <button type="button" onclick="(function(btn){var f=btn.closest('form');var jenis=f.querySelector('[name=jenis]').value;var alasan=f.querySelector('[name=rejection_reason]').value;if(!alasan.trim()){alert('Isi alasan penolakan dulu.');return;}if(!confirm(jenis==='payment'?'Tolak karena pembayaran tidak valid? Bukti pembayaran akan dihapus.':'Tolak karena dokumen tidak valid?'))return;var s=document.createElement('form');s.method='POST';s.action='/admin/penerjemahan/{{ $record->id }}/'+(jenis==='payment'?'reject-payment':'reject-document');var t=document.createElement('input');t.type='hidden';t.name='_token';t.value='{{ csrf_token() }}';s.appendChild(t);var r=document.createElement('input');r.type='hidden';r.name='rejection_reason';r.value=alasan;s.appendChild(r);document.body.appendChild(s);s.submit();})(this);" class="pm-btn pm-btn-red"><i class="fas fa-xmark pm-ic"></i>Konfirmasi Tolak</button>
                    </form>
                </div>
            @endif

            {{-- Pilih Penerjemah --}}
            @if($isAdmin && $record->status === 'Disetujui')
                <div>
                    <label class="pm-label">Pilih Penerjemah</label>
                    <select name="translator_id" class="pm-select">
                        <option value="">— Pilih —</option>
                        @foreach($translators as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" onclick="(function(btn){var sel=btn.previousElementSibling;var tid=sel.value;if(!tid){alert('Pilih penerjemah dulu.');return;}var s=document.createElement('form');s.method='POST';s.action='/admin/penerjemahan/{{ $record->id }}/assign-translator';var t=document.createElement('input');t.type='hidden';t.name='_token';t.value='{{ csrf_token() }}';s.appendChild(t);var i=document.createElement('input');i.type='hidden';i.name='translator_id';i.value=tid;s.appendChild(i);document.body.appendChild(s);s.submit();})(this);" class="pm-btn pm-btn-blue"><i class="fas fa-user-check pm-ic"></i>Assign Penerjemah</button>
                </div>
            @endif

            {{-- Set Selesai --}}
            @if($isAdmin && $record->status === 'Diproses')
                <button type="button" onclick="if(!confirm('Tandai penerjemahan ini selesai?'))return;(function(){var s=document.createElement('form');s.method='POST';s.action='/admin/penerjemahan/{{ $record->id }}/set-selesai';var t=document.createElement('input');t.type='hidden';t.name='_token';t.value='{{ csrf_token() }}';s.appendChild(t);document.body.appendChild(s);s.submit();})();" class="pm-btn pm-btn-green"><i class="fas fa-check-circle pm-ic"></i>Tandai Selesai</button>
            @endif
        </div>
    </div>
    @endif

    {{-- Teks Abstrak --}}
    @if(filled($record->source_text))
    <div style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:16px;margin-bottom:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;margin:0 0 8px;">Teks Abstrak (Sumber)</p>
        <div style="font-size:13px;color:#334155;line-height:1.6;max-height:150px;overflow-y:auto;white-space:pre-wrap;word-break:break-word;">
            {!! strip_tags($record->source_text) !!}
        </div>
    </div>
    @endif

    {{-- Hasil Terjemahan --}}
    @if(filled($record->translated_text))
    <div style="border-radius:12px;background:#f0fdf4;border:1px solid #bbf7d0;padding:16px;margin-bottom:16px;">
        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#16a34a;margin:0 0 8px;">Hasil Terjemahan</p>
        <div style="font-size:13px;color:#1e293b;line-height:1.6;max-height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-word;">
            {!! strip_tags($record->translated_text) !!}
        </div>
    </div>
    @endif

</div>
