@php
    /** @var \App\Models\EptSubmission $submission */
    $record  = $submission;
    $pemohon = $record->user;

    // Aset (pakai file:// agar Dompdf konsisten)
    $logoPath = is_file(public_path('images/logo-um.png'))   ? 'file://' . realpath(public_path('images/logo-um.png'))   : null;
    $signPath = is_file(public_path('images/ttd_ketua.png')) ? 'file://' . realpath(public_path('images/ttd_ketua.png')) : null;

    // Nomor & Tanggal Surat
    $noSurat = $nomorSurat ?? ($record->surat_nomor ?: ('001/II.3.AU/F/KET/LB_UMM/' . now()->year));
    $tglSurat = $tanggalSurat
        ?? optional($record->approved_at)->timezone(config('app.timezone','Asia/Jakarta'))?->translatedFormat('d F Y')
        ?? now()->translatedFormat('d F Y');

    // Verifikasi (opsional ditampilkan kecil di footer)
    $verifyCode = $record->verification_code ?: null;
    $verifyUrl  = $record->verification_url
        ?: ($verifyCode ? route('verification.show', ['code' => $verifyCode], true) : null);

    // Penandatangan
    $chairName = 'Dedy Subandowo, M.A., Ph.D.';
    $chairNip  = '0215068603';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Surat Keterangan — Lembaga Bahasa UM Metro</title>
<style>
  @page { size: A4 portrait; margin: 20mm 18mm 20mm 23mm; }
  *{ box-sizing: border-box; }
  body{ font-family: "DejaVu Sans", Arial, Helvetica, sans-serif; font-size: 14px; color: black; line-height: 1.2; }

  /* ===== Kop (header) ===== */
  .kop { text-align: center; margin-bottom: 8px; position: relative; }
  .kop .logo {
    position: absolute; left: 0; top: -6px; width: 65px; height: auto;
  }
  .kop .u-line { color:#3b82f6; letter-spacing: 4px; font-weight: 800; font-size: 16px; text-transform: uppercase; }
  .kop .l-line { color:#60a5fa; letter-spacing: 8px; font-weight: 800; font-size: 14px; text-transform: uppercase; margin-top: 2px;}
  .kop .addr   { color:#2563eb; font-size: 10px; margin-top: 4px; }
  .divider { height: 2px; background: #c7d2fe; margin: 8px 0 16px; }

  /* ===== Judul & nomor ===== */
  .title { text-align: center; font-size: 16px; font-weight: 800; text-transform: uppercase; text-decoration: underline; margin-bottom: 2px; }
  .nomor { text-align: center; margin-bottom: 16px; }
  .nomor .label { display:block; font-weight: 700; margin-bottom: 2px; }
  .nomor .value { display:block; }

  /* ===== Paragraf ===== */
  p { text-align: justify; margin: 10px 0; }

  /* ===== Tabel identitas ===== */
  .ident { margin: 35px 0 35px 30mm; }
  .ident-table { border-collapse: collapse; width: 100%; }
  .ident-table td { padding: 3px 0; vertical-align: top; }
  .lab { width: 200px; }
  .lab.spaced { letter-spacing: 3px; }
  .col { width: 16px; text-align: center; }
  .val { }

  /* ===== Daftar Nilai (tabel 3 kolom, lebih stabil di Dompdf) ===== */
  .scores { margin: 35px 0 35px 55mm; }
  .scores-tbl { border-collapse: collapse; width: auto; }
  .scores-tbl td { padding: 2px 0; vertical-align: top; }
  .scores-tbl .left { width: 24mm; }
  .scores-tbl .sep  { width: 6mm; text-align: center; }
  .scores-tbl .right{ min-width: 15mm; }

  /* ===== Tanda tangan kanan ===== */
  .ttd-wrap { margin-top: 18px; width: 100%;  margin: 30px 0 15px 100mm; }
  .ttd-right { width: 100%; text-align: left; }
  .ttd-right .city-date { margin-bottom: 1px; }
  .ttd-right .jabatan { margin-bottom: 1px; }
  .ttd-right .sign { height: 100px; }
  .ttd-right .name { font-weight: 800; font-size: 15px; color: black; text-decoration: underline; }
  .ttd-right .nip  { font-size: 14px; color: black; }

  /* ===== Footer kecil ===== */
  .footer { position: fixed; bottom: 14mm; font-size: 12px; color:#525253; }
  .footer .verify { margin-top: 4px; word-break: break-all; }
</style>
</head>
<body>

  {{-- KOP --}}
  <div class="kop">
    @if($logoPath)
      <img class="logo" src="{{ $logoPath }}" alt="Logo UM">
    @endif
    <div class="u-line">Universitas Muhammadiyah Metro</div>
    <div class="l-line">Lembaga Bahasa</div>
    <div class="addr">Jl. Gatot Subroto No.100, Yosodadi, Kec. Metro Tim., Kota Metro, Lampung 34124, Indonesia</div>
  </div>
  <div class="divider"></div>

  {{-- JUDUL --}}
  <div class="title">Surat Keterangan</div>
  <div class="nomor">
    <span class="label">Nomor:</span>
    <span class="value">{{ $noSurat }}</span>
  </div>

  {{-- PARAGRAF PEMBUKA --}}
  <p>Yang bertanda tangan dibawah ini Kepala UPT Bahasa Universitas Muhammadiyah Metro, dengan ini menerangkan dengan sesungguhnya bahwa:</p>

  {{-- IDENTITAS --}}
  <div class="ident">
    <table class="ident-table">
      <tr>
        <td class="lab spaced">Nama</td>
        <td class="col">:</td>
        <td class="val">{{ $pemohon?->name ?? '-' }}</td>
      </tr>
      <tr>
        <td class="lab">Nomor Pokok Mahasiswa</td>
        <td class="col">:</td>
        <td class="val">{{ $pemohon?->srn ?? '-' }}</td>
      </tr>
      <tr>
        <td class="lab">Program Studi</td>
        <td class="col">:</td>
        <td class="val">{{ $pemohon?->prody?->name ?? '-' }}</td>
      </tr>
    </table>
  </div>

  {{-- PARAGRAF NILAI --}}
  <p>Bahwa yang bersangkutan benar-benar telah mengikuti <em>English Proficiency Test (EPT)</em> Lembaga Bahasa dengan nilai sebagai berikut:</p>

  {{-- LIST NILAI (Tes I/II/III) --}}
  <div class="scores">
    <table class="scores-tbl">
      <tr>
        <td class="left">Tes I</td>
        <td class="sep">:</td>
        <td class="right">{{ $record->nilai_tes_1 ?: '-' }}</td>
      </tr>
      <tr>
        <td class="left">Tes II</td>
        <td class="sep">:</td>
        <td class="right">{{ $record->nilai_tes_2 ?: '-' }}</td>
      </tr>
      <tr>
        <td class="left">Tes III</td>
        <td class="sep">:</td>
        <td class="right">{{ $record->nilai_tes_3 ?: '-' }}</td>
      </tr>
    </table>
  </div>


  {{-- PENUTUP --}}
  <p>Demikian surat ini dibuat dan dipergunakan sebagaimana mestinya.</p>

  {{-- TTD KANAN --}}
  <div class="ttd-wrap">
    <div class="ttd-right">
      <div class="city-date">Metro, {{ $tglSurat }}</div>
      <div class="jabatan">Kepala UPT Bahasa</div>
      @if($signPath)
        <img class="sign" src="{{ $signPath }}" alt="Tanda Tangan">
      @endif
      <div class="name">{{ $chairName }}</div>
      <div class="nip">NIDN. {{ $chairNip }}</div>
    </div>
  </div>

  {{-- FOOTER OPSIONAL: verifikasi + tembusan --}}
  <div class="footer">
    <div><em>Tembusan wakil rektor 1</em></div>
    @if($verifyUrl)
      <div class="verify">Cek Dokumen: {{ $verifyUrl }}</div>
    @endif
  </div>

</body>
</html>
