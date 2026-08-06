<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kartu Peserta EPT</title>
    <style>
        @page { size: A4; margin: 20mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1e293b; background: #fff; }
        .page { padding: 10px; }
        
        .card {
            background: white;
            border: 2px solid #1e40af;
            border-radius: 12px;
            padding: 22px 25px;
            margin-bottom: 22px;
            page-break-inside: avoid;
            position: relative;
        }
        
        .card-header {
            border-bottom: 2px solid #1e40af;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        
        .card-header h1 {
            font-size: 14pt;
            color: #1e40af;
            margin-bottom: 3px;
        }
        
        .card-header p {
            font-size: 8pt;
            color: #64748b;
        }
        
        .card-badge {
            position: absolute;
            top: 18px;
            right: 20px;
            background: #1e40af;
            color: white;
            font-size: 9pt;
            font-weight: bold;
            padding: 5px 14px;
            border-radius: 20px;
        }
        
        .card-body {
            display: table;
            width: 100%;
        }
        
        .card-left, .card-right {
            display: table-cell;
            vertical-align: top;
        }
        
        .card-left {
            width: 55%;
            padding-right: 20px;
        }
        
        .card-right {
            width: 45%;
            background: #dbeafe;
            padding: 15px;
            border-radius: 8px;
        }
        
        .info-row {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        
        .info-value {
            font-size: 10pt;
            font-weight: bold;
            color: #1e293b;
        }
        
        .schedule-grup {
            font-size: 12pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
        }
        
        .schedule-date {
            font-size: 10pt;
            color: #475569;
            margin-bottom: 3px;
        }
        
        .schedule-time {
            font-size: 12pt;
            font-weight: bold;
            color: #1e293b;
        }
        
        .schedule-location {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #93c5fd;
            font-size: 9pt;
            color: #475569;
        }
        
        .schedule-location strong {
            color: #1e40af;
        }
        
        .card-footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 7pt;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="card-badge">Tes ke-{{ $jadwalNum }}</div>
            
            <div class="card-header">
                <h1>KARTU PESERTA TES EPT</h1>
                <p>UPT Bahasa - Universitas Muhammadiyah Metro</p>
            </div>
            
            <div class="card-body">
                <div class="card-left">
                    <div class="info-row">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value">{{ $user->name }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">NPM / NIM</div>
                        <div class="info-value">{{ $user->srn }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Program Studi</div>
                        <div class="info-value">{{ $user->prody->name ?? '-' }}</div>
                    </div>
                </div>
                
                <div class="card-right">
                    <div class="schedule-grup">{{ $grup->name }}</div>
                    <div class="schedule-date">{{ $grup->jadwal->translatedFormat('l, d F Y') }}</div>
                    <div class="schedule-time">{{ $grup->jadwal->format('H:i') }} WIB</div>
                    <div class="schedule-location">
                        <strong>Lokasi:</strong> {{ $grup->lokasi }}
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                Dicetak pada {{ now()->translatedFormat('d F Y, H:i') }} WIB | © {{ date('Y') }} UPT Bahasa UM Metro
            </div>
        </div>
    </div>
</body>
</html>
