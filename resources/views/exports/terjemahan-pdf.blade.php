@php
    /** @var \App\Models\Penerjemahan $record */

    // Relasi utama
    $pemohon = $record->users;

    // Path absolut aman (fallback kalau controller belum kirim variabel)
    $logoPathFs  = realpath($logoPath  ?? public_path('images/logo-um.png'));
    $stampPathFs = realpath($stampPath ?? public_path('images/stempel.png'));
    $signPathFs  = realpath($signPath  ?? public_path('images/ttd_ketua.png'));

    // Konversi ke URL file:// supaya Dompdf pasti mau membaca
    $logoSrc  = ($logoPathFs  && is_file($logoPathFs))  ? ('file://' . $logoPathFs)  : null;
    $stampSrc = ($stampPathFs && is_file($stampPathFs)) ? ('file://' . $stampPathFs) : null;
    $signSrc  = ($signPathFs  && is_file($signPathFs))  ? ('file://' . $signPathFs)  : null;

    // Tanggal tanda tangan
    $ttdDate = $ttdDate
        ?? \Illuminate\Support\Carbon::parse($record->completion_date ?? $record->updated_at ?? now())
            ->locale('id')->translatedFormat('d F Y');

    // Verifikasi (kode & URL)
    $verifyCode = $record->verification_code ?: null;
    $verifyUrl  = $verifyUrl
        ?? ($record->verification_url ?: ($verifyCode ? route('verification.show', ['code' => $verifyCode], true) : null));

    // Penandatangan
    $chairName = 'Dedy Subandowo, M.A., Ph.D.';
    $chairNip  = '0215068603';

    // Sanitasi HTML terjemahan
    $rich = (string) ($record->translated_text ?? '');
    $rich = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $rich);
    $rich = preg_replace('/\s+on\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $rich);
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Penerjemahan — Lembaga Bahasa UM Metro</title>
    <style>
        :root{ 
            --primary: #2563eb; 
            --primary-dark: #1e40af; 
            --accent: #60a5fa;
            --soft: #f8fafc; 
            --ink: #0f172a; 
            --muted: #64748b;
            --shadow: rgba(30, 64, 175, 0.08);
            --shadow-md: rgba(30, 64, 175, 0.12);
        }

        @page { margin: 20mm 18mm; size: A4 portrait; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif; 
            font-size: 10px; 
            color: var(--ink); 
            line-height: 1.3; 
            background: var(--soft);
        }

        /* Header - Clean & Floating */
        .header { 
            background: #fff;
            margin-bottom: 16px; 
            padding: 16px 20px;
            box-shadow: 0 2px 12px var(--shadow);
            border-radius: 12px;
        }
        .h-tbl { width: 100%; border-collapse: collapse; }
        .h-tbl td { vertical-align: middle; }
        .logo-cell { width: 80px; }
        .logo { 
            width: 65px; 
            height: 65px; 
            object-fit: contain; 
            display: block;
            filter: drop-shadow(0 1px 3px var(--shadow));
        }
        .kop { text-align: center; padding-left: 12px; }
        .header-line2 { 
            font-size: 16px; 
            font-weight: 800; 
            color: var(--primary-dark); 
            text-transform: uppercase; 
            letter-spacing: 0.4px;
            line-height: 1.3;
            margin-top: 2px;
        }
        .addr { 
            font-size: 9px; 
            color: var(--muted); 
            margin-top: 6px;
            line-height: 1.3;
        }

        /* Info Card - Floating Style */
        .info-card { 
            background: #fff;
            margin-bottom: 16px;
            padding: 14px 18px;
            box-shadow: 0 2px 8px var(--shadow);
            border-radius: 10px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table tr td {
            padding: 6px 0;
            vertical-align: top;
        }
        .info-label {
            width: 140px;
            font-weight: 700;
            font-size: 9px;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .info-sep {
            width: 20px;
            text-align: center;
            font-weight: 700;
            color: var(--primary);
            font-size: 10px;
        }
        .info-value {
            font-size: 10px;
            color: var(--ink);
            word-wrap: break-word;
        }

        /* Section Label - Minimal */
        .section-label { 
            font-size: 11px; 
            font-weight: 700; 
            color: var(--primary-dark); 
            text-transform: uppercase; 
            letter-spacing: 0.8px;
            margin-bottom: 10px;
            padding-left: 4px;
            border-left: 3px solid var(--primary);
            padding-left: 10px;
        }

        /* Content Card - Main Translation */
        .content-card { 
            background: #fff;
            padding: 16px 20px;
            box-shadow: 0 2px 10px var(--shadow-md);
            border-radius: 10px;
            page-break-inside: auto;
            margin-bottom: 16px;
        }
        .rich * { max-width: 100%; }
        .rich p { 
            margin: 8px 0; 
            text-align: justify; 
            line-height: 1.2; 
        }

        /* Footer Cards */
        .footer-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-grid td {
            vertical-align: top;
            width: 50%;
        }
        .f-left { padding-right: 8px; }
        .f-right { padding-left: 8px; }

        /* Verification Card */
        .verify-card {
            background: #fff;
            padding: 14px 16px;
            box-shadow: 0 2px 8px var(--shadow);
            border-radius: 10px;
        }
        .card-title {
            font-size: 9.5px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .verify-text {
            font-size: 8.5px;
            color: var(--muted);
            line-height: 1.3;
            margin-bottom: 8px;
        }
        .verify-badge {
            display: inline-block;
            background: var(--soft);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 7px;
            font-weight: 700;
            color: var(--primary);
            margin-top: 2px;
            box-shadow: 0 1px 3px var(--shadow);
        }
        .verify-url {
            display: block;
            margin-top: 6px;
            font-size: 7.5px;
            color: var(--accent);
            word-break: break-all;
            text-decoration: none;
        }

        /* Signature Card */
        .sig-card {
            background: #fff;
            padding: 14px 16px;
            box-shadow: 0 2px 8px var(--shadow);
            border-radius: 10px;
        }
        .sig-location { 
            text-align: right; 
            font-size: 9px; 
            color: var(--ink);
            margin-bottom: 8px;
        }
        .sig-area {
            position: relative;
            height: 52px;
            margin: 8px 0;
        }
        .sig-stamp { 
            position: absolute; 
            top: 50%; 
            left: 65%; 
            transform: translate(-50%, -50%) rotate(-10deg); 
            width: 24mm; 
            height: auto; 
            z-index: 1;
            opacity: 0.95;
        }
        .sig-sign { 
            position: absolute; 
            top: 50%; 
            left: 78%; 
            transform: translate(-50%, -50%) rotate(-2deg); 
            z-index: 2; 
        }
        .sig-sign img { 
            display: block; 
            width: 28mm; 
            height: auto;
            filter: drop-shadow(0 1px 2px var(--shadow));
        }
        .sig-name { 
            text-align: right; 
            font-weight: 700; 
            font-size: 9.5px; 
            line-height: 1;
            color: var(--ink);
        }
        .sig-nip { 
            text-align: right; 
            font-size: 8.5px; 
            color: var(--muted); 
            margin-top: 2px;
        }

        /* Page-break helpers */
        .avoid-break { page-break-inside: avoid; }
    </style>
</head>
<body>

    {{-- HEADER CARD --}}
    <div class="header avoid-break">
        <table class="h-tbl">
            <tr>
                <td class="logo-cell">
                    @if($logoSrc)
                        <img class="logo" src="{{ $logoSrc }}" alt="Logo UM">
                    @endif
                </td>
                <td class="kop">
                    <div class="header-line2">Lembaga Bahasa Universitas Muhammadiyah Metro</div>
                    <div class="addr">Jl. Gatot Subroto No.100, Yosodadi, Kec. Metro Timur, Kota Metro, Lampung 34124</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- INFO CARD --}}
    <div class="info-card avoid-break">
        <table class="info-table">
            <tr>
                <td class="info-label">Nama / NPM</td>
                <td class="info-sep">:</td>
                <td class="info-value">{{ $pemohon?->name ?? '-' }} / {{ $pemohon?->srn ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">Program Studi</td>
                <td class="info-sep">:</td>
                <td class="info-value">{{ $pemohon?->prody?->name ?? '-' }}</td>
            </tr>
            @if($verifyCode)
            <tr>
                <td class="info-label">Kode Verifikasi</td>
                <td class="info-sep">:</td>
                <td class="info-value"><strong>{{ $verifyCode }}</strong></td>
            </tr>
            @endif
        </table>
    </div>

    {{-- TRANSLATION CONTENT --}}
    <div class="section-label">HASIL TERJEMAHAN</div>
    <div class="content-card">
        <div class="rich">{!! $rich !== '' ? $rich : '<p><em>Tidak ada konten terjemahan.</em></p>' !!}</div>
    </div>

    {{-- FOOTER: VERIFICATION & SIGNATURE --}}
    <table class="footer-grid">
        <tr>
            <td class="f-left">
                <div class="verify-card">
                    <div class="card-title">Link Verifikasi Dokumen</div>
                    @if($verifyUrl)
                        <a class="verify-badge" href="{{ $verifyUrl }}" target="_blank">{{ $verifyUrl }}</a>
                    @else
                        <div class="verify-text">Tidak ada tautan verifikasi tersedia untuk dokumen ini.</div>
                    @endif
                </div>
            </td>
            <td class="f-right">
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
            </td>
        </tr>
    </table>
</body>
</html>
