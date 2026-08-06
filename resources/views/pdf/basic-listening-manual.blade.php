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

    // Variabel penandatangan
    $ttdDate  = $certificate->issued_at->format('d F Y');
    $chairName= $chairName ?? 'Dedy Subandowo, M.A., Ph.D.';
    $chairNip = $chairNip ?? '0215068603';

    // Grade Color (Start with logic)
    $grade = $certificate->grade ?? '';
    $gradeColor = match (true) {
        str_starts_with($grade, 'A') => '#27ae60',
        str_starts_with($grade, 'B') => '#2ecc71', // B is usually blue in Basic Listening but green in EPP? BL Original: B -> #3498db (Blue). Let's stick to BL colors.
        // BL Colors:
        // 'A+', 'A' => '#27ae60',
        // 'A-', 'B+' => '#2ecc71',
        // 'B' => '#3498db',
        // 'B-', 'C+' => '#5dade2',
        // 'C' => '#f39c12',
        // 'C-', 'D+', 'D' => '#e67e22',
        // default => '#e74c3c'
        default => '#2c3e50', // Fallback
    };
    
    // Re-map specifically to match BL colors if possible, otherwise use simple logic
    $grade = trim($grade);
    if (in_array($grade, ['A+', 'A'])) $gradeColor = '#27ae60';
    elseif (in_array($grade, ['A-', 'B+'])) $gradeColor = '#2ecc71';
    elseif ($grade === 'B') $gradeColor = '#3498db';
    elseif (in_array($grade, ['B-', 'C+'])) $gradeColor = '#5dade2';
    elseif ($grade === 'C') $gradeColor = '#f39c12';
    elseif (in_array($grade, ['C-', 'D+', 'D'])) $gradeColor = '#e67e22';
    else $gradeColor = '#e74c3c';

    // Final Score
    $finalNumeric = $certificate->average_score ?? 0;
    $finalLetter  = $certificate->grade ?? '-';
    
    // Score fields
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
        background: #fff;
    }

    :root{
        --ink:#2c3e50;
        --muted:#7f8c8d;
        --shadow: rgba(2,6,23,.12);
        --brand:#3498db;
    }

    /* ---------- KANVAS UTAMA ---------- */
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
        padding-bottom:2px;
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
    .scores-table th{ color:#6c757d; background:#f8f9fa; text-align:left; }
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

    /* Signature Card */
    .sig-card {
        background: #fff;
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

    /* Helper */
    .avoid-break{ page-break-inside: avoid; }
  </style>
</head>
<body>
  <div class="certificate-container">
    <div class="watermark">CERTIFICATE</div>
    <div class="grade-floating" style="color: {{ $gradeColor }}; opacity: 0.14;">
      {{ strtoupper(substr($finalLetter, 0, 1)) }}
    </div>

    <div class="content-wrapper">
      {{-- HEADER --}}
      <div class="header avoid-break">
        <div class="institution-info">
          @if($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="Logo Institusi">
          @endif
          <div class="institution-text">
            <div class="institution-name">UPT Bahasa</div>
            <div class="institution-department">Universitas Muhammadiyah Metro</div>
          </div>
        </div>

        <div class="verification-header">
          This document is authentic and electronically verified<br>
          <strong>Code:</strong> {{ $certificate->verification_code ?? '-' }}<br>
          <strong>URL:</strong> {{ url('/verification/' . ($certificate->verification_code ?? '')) }}
        </div>
      </div>

      {{-- KONTEN UTAMA --}}
      <div class="main-content avoid-break">
        <div class="certificate-title">
          <div class="title-main">Certificate of Completion</div>
            <div class="certificate-number" style="margin-bottom:4mm;">
            <strong>Number:</strong> {{ $certificate->certificate_number }}
            </div>
            <div class="title-sub" style="margin-top:8mm;">Awarded to</div>
        </div>

        <div class="participant-name-container">
            <div class="participant-name">{{ strtoupper($certificate->name) }}</div>
            <div class="participant-prody">{{ $certificate->study_program ?? '—' }}</div>
            <div class="participant-details">
            <strong>SRN:</strong> {{ $certificate->srn ?? '—' }}
            &nbsp;|&nbsp; <strong>Issue Date:</strong> {{ $ttdDate }}
            </div>

            <div class="title-description">
            For successfully completing the <strong>{{ $category?->name ?? 'Course' }}</strong> with a score of:
            </div>
        </div>

        <div class="scores-section avoid-break">
          <table class="scores-table">
            <thead>
              <tr>
                <th style="width: 50%;">Assessment Components</th>
                <th style="width: 25%; text-align:center;">Score</th>
              </tr>
            </thead>
            <tbody>
              {{-- Dynamic Score Loop --}}
              @foreach($scoreFields as $field)
              <tr>
                <td>{{ ucwords(str_replace('_', ' ', $field)) }}</td>
                <td class="num-center">{{ isset($scores[$field]) && is_numeric($scores[$field]) ? number_format($scores[$field], 2) : '—' }}</td>
              </tr>
              @endforeach

              {{-- Show Total/Avg if not basic listening specifically --}}
              {{-- But since this is a generic template, we show Final Score / Grade like BL --}}
              
              <tr class="grade-row">
                <td>FINAL SCORE</td>
                <td class="num-center">{{ number_format($finalNumeric, 2) }}</td>
              </tr>
              <tr class="grade-row">
                <td>GRADE</td>
                <td class="grade-value" style="text-align:center;">
                  {{ $finalLetter }}
                </td>
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
