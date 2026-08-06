@php
    /**
     * Dompdf friendly: gambar lokal pakai file://
     */
    $toFileUrl = function (?string $absPath): ?string {
        if (!$absPath) return null;
        $real = realpath($absPath);
        return ($real && is_file($real)) ? ('file://' . $real) : null;
    };

    $logoSrc  = $toFileUrl($logoPath ?? null);
    $signSrc  = $toFileUrl($signPath ?? null);
    $stampSrc = $toFileUrl($stampPath ?? null);

    // Variabel dari controller
    $ttdDate   = $certificate->issued_at->format('d F Y');
    $chairName = $chairName ?? 'Dedy Subandowo, M.A., Ph.D.';
    $chairNip  = $chairNip ?? '0215068603';

    // Grade color
    $gradeColor = match (true) {
        str_starts_with($certificate->grade ?? '', 'A') => '#27ae60',
        str_starts_with($certificate->grade ?? '', 'B') => '#2ecc71',
        str_starts_with($certificate->grade ?? '', 'C') => '#f39c12',
        default => '#e74c3c',
    };

    // Score fields dari kategori
    $scoreFields = $category?->score_fields ?? [];
    $scores = $certificate->scores ?? [];
@endphp
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Sertifikat {{ $certificate->name }}</title>
  <style>
    /* ---------- HALAMAN A4 ---------- */
    @page { size: A4; margin: 0; }
    html, body { margin:0; padding:0; }
    body {
        font-family: DejaVu Sans, 'Helvetica', 'Arial', sans-serif;
        font-size: 10px;
        color: #2c3e50;
        line-height: 1.2;
        background: #ffffff;
    }

    :root{
        --ink:#2c3e50;
        --muted:#7f8c8d;
        --shadow: rgba(2,6,23,.12);
        --brand:#3498db;
    }

    .content-wrapper{ padding: 8mm 12mm; }

    /* ---------- WATERMARK ---------- */
    .watermark{
        position:absolute; top:56%; left:50%;
        transform: translate(-50%, -50%);
        font-size: 65px;
        font-weight: 900;
        color: rgba(52,152,219,0.2);
        z-index:1;
        white-space: nowrap;
        pointer-events: none;
        user-select: none;
    }

    /* ---------- GRADE BACKDROP ---------- */
    .grade-floating{
        position:absolute;
        left:-20mm; bottom:-30mm;
        font-size:380px;
        font-weight:900;
        line-height:1;
        letter-spacing:-5px;
        color: rgba(44,62,80,0.08);
        z-index:2;
        pointer-events:none;
        user-select:none;
    }

    /* ---------- HEADER ---------- */
    .header{
        position: relative;
        display: table;
        width: 100%;
        table-layout: fixed;
        padding-bottom: 2mm;
        border-bottom: 2px solid var(--brand);
        z-index:3;
    }
    .institution-info{ display: table-cell; width: 60%; vertical-align: top; }
    .logo{ width: 42px; height: 42px; margin-right: 2mm; float:left; display:block; }
    .institution-text{ overflow:hidden; }
    .institution-name{ font-size:21px; font-weight:500; letter-spacing: -0.2px; }
    .institution-department{ font-size:11px; color:var(--muted); }

    .verification-header{
        display: table-cell; width: 40%;
        vertical-align: bottom; text-align:right;
        font-size: 7px; color:var(--muted); line-height:1;
        word-break: break-word;
    }
    .verification-header strong{ color:var(--ink); }

    /* ---------- UTAMA ---------- */
    .main-content{ text-align:center; position:relative; z-index:3; }

    .certificate-title{ margin: 12mm 0 7mm 0; }
    .title-main{ font-size:35px; font-weight:800; color:var(--brand); letter-spacing:1.6px; margin-bottom:1mm; margin-top:5mm; }
    .title-sub{ font-size:12px; }

    .participant-name-container{ margin: 7mm 0; }
    .participant-name{ font-size:24px; font-weight:900; color:var(--ink);}
    .participant-prody{ font-size:12px; color:var(--muted); margin-bottom:3.5mm; }
    .participant-details{ font-size:10px; color:#6c757d; line-height:1.4; }
    .certificate-number{ font-size:12px; color:#6c757d; line-height:1; }
    .participant-details strong{ color:var(--ink); font-weight:700; }

    .title-description{ font-size:10px; color:#6c757d; margin-top:7mm; }

    .scores-section{ margin:10mm auto 0 auto; width:82%; }
    table{ border-collapse:collapse; }
    .scores-table{ width:100%; font-size:14px; }
    .scores-table th, .scores-table td{ padding:3px 5px; border-bottom:1px dashed #dee2e6; }
    .scores-table th{ color:#6c757d; text-align:left; }
    .scores-table td.num-center{ text-align:center; }
    .scores-table td.num-right{ text-align:right; }

    .grade-row td{
        font-weight:800; font-size:20px; padding-top:4px;
        border-top:1px solid var(--brand);
    }
    .grade-value{ font-size:20px; font-weight:900; color: {{ $gradeColor }}; }

    /* ---------- FOOTER: SIG-CARD ---------- */
    .footer{
        position:absolute;
        left: 12mm; right: 20mm;
        margin-top: 8mm;
        z-index:5;
    }

    .sig-card {
        padding: 14px 16px;
        border-radius: 10px;
        margin-left: auto;
    }
    .sig-location {
        text-align: right;
        font-size: 9px;
        color: var(--ink);
    }
    .sig-area {
        position: relative;
        height: 52px;
        margin: 8px 0;
    }
    .sig-stamp {
        position: absolute;
        top: 30%;
        left: 80%;
        transform: translate(-50%, -50%) rotate(-10deg);
        width: 30mm;
        height: auto;
        z-index: 1;
        opacity: 0.95;
    }
    .sig-sign {
        position: absolute;
        top: 40%;
        left: 90%;
        transform: translate(-50%, -50%) rotate(-2deg);
        z-index: 2;
    }
    .sig-sign img {
        display: block;
        width: 40mm;
        height: auto;
    }
    .sig-name {
        text-align: right;
        font-weight: 700;
        font-size: 9.5px;
        margin-top: -10px;
        line-height: 1;
        color: var(--ink);
    }
    .sig-nip {
        text-align: right;
        font-size: 8.5px;
        color: var(--muted);
        margin-top: 1px;
    }

    .avoid-break{ page-break-inside: avoid; }
  </style>
</head>
<body>
  <div class="certificate-container">
    <div class="watermark">CERTIFICATE</div>
    <div class="grade-floating" style="color: {{ $gradeColor }}; opacity: 0.14;">
      {{ strtoupper(substr($certificate->grade ?? 'A', 0, 1)) }}
    </div>

    <div class="content-wrapper">
      {{-- HEADER --}}
      <div class="header avoid-break">
        <div class="institution-info">
          @if($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="Logo Institusi">
          @endif
          <div class="institution-text">
            <div class="institution-name">Lembaga Bahasa</div>
            <div class="institution-department">Universitas Muhammadiyah Metro</div>
          </div>
        </div>

        <div class="verification-header">
          Dokumen ini sah dan telah diverifikasi secara elektronik<br>
          <strong>Code:</strong> {{ $certificate->verification_code }}<br>
          <strong>URL:</strong> {{ url('/verification/' . $certificate->verification_code) }}
        </div>
      </div>

      {{-- KONTEN UTAMA --}}
      <div class="main-content avoid-break">
        <div class="certificate-title">
          <div class="title-main">SERTIFIKAT</div>
            <div class="certificate-number" style="margin-bottom:4mm;">
            <strong>Nomor:</strong> {{ $certificate->certificate_number }}
            </div>
            <div class="title-sub" style="margin-top:8mm;">Diberikan kepada</div>
        </div>

        <div class="participant-name-container">
            <div class="participant-name">{{ strtoupper($certificate->name) }}</div>
            <div class="participant-prody">{{ $certificate->study_program ?? '—' }}</div>
            <div class="participant-details">
            <strong>NPM:</strong> {{ $certificate->srn ?? '—' }}
            &nbsp;|&nbsp; <strong>Tanggal Terbit:</strong> {{ $certificate->issued_at->format('d F Y') }}
            </div>

            <div class="title-description">
            Atas keberhasilan menyelesaikan <strong>{{ $category?->name ?? 'Interactive Class' }}{{ $certificate->semester ? " Semester {$certificate->semester}" : '' }}</strong> dengan hasil nilai:
            </div>
        </div>

        <div class="scores-section avoid-break">
          {{-- Scores Grid: 4 columns --}}
          <table class="scores-table" style="margin-bottom:3mm;">
            <thead>
              <tr>
                <th colspan="4" style="text-align:center; padding:5px;">Komponen Penilaian</th>
              </tr>
            </thead>
            <tbody>
              @php
                // Chunk scores into pairs for 4-column layout (2 pairs per row)
                $chunkedScores = array_chunk($scoreFields, 2);
              @endphp
              @foreach($chunkedScores as $pair)
              <tr>
                @foreach($pair as $field)
                <td style="width:30%; padding:4px 6px;">{{ ucfirst($field) }}</td>
                <td class="num-center" style="width:20%; padding:4px 6px; font-weight:600;">{{ $scores[$field] ?? '—' }}</td>
                @endforeach
                @if(count($pair) === 1)
                <td style="width:30%;"></td>
                <td style="width:20%;"></td>
                @endif
              </tr>
              @endforeach
            </tbody>
          </table>

          {{-- Summary: Total, Average, Grade --}}
          <table class="scores-table" style="margin-top:2mm;">
            <tbody>
              <tr style="border-top:2px solid #3498db;">
                <td style="width:50%; padding:5px 8px; font-weight:700;">Jumlah</td>
                <td class="num-center" style="width:50%; padding:5px 8px; font-weight:700; font-size:16px;">{{ $certificate->total_score ?? '—' }}</td>
              </tr>
              <tr>
                <td style="padding:5px 8px; font-weight:700;">Rata-rata</td>
                <td class="num-center" style="padding:5px 8px; font-weight:700; font-size:16px;">{{ number_format($certificate->average_score ?? 0, 2) }}</td>
              </tr>
              <tr style="background:linear-gradient(90deg, #f8f9fa 50%, #e8f4f8 50%);">
                <td style="padding:8px; font-weight:800; font-size:14px; color:#2c3e50;">PREDIKAT</td>
                <td class="grade-value" style="padding:8px; text-align:center; font-size:18px;">{{ $certificate->grade ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      {{-- FOOTER: SIG-CARD --}}
      <div class="footer avoid-break">
        <div class="sig-card">
          <div class="sig-location">Metro, {{ $ttdDate }}</div>
          <div class="sig-area">
            @if($stampSrc)
              <img class="sig-stamp" src="{{ $stampSrc }}" alt="Stempel">
            @endif
            @if($signSrc)
              <div class="sig-sign">
                <img src="{{ $signSrc }}" alt="Tanda Tangan">
              </div>
            @endif
          </div>
          <div class="sig-name">{{ $chairName }}</div>
          <div class="sig-nip">NIDN. {{ $chairNip }}</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
