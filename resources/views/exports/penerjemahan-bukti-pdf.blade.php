<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bukti Pembayaran Penerjemahan</title>
    <style>
        @page {
            size: legal portrait;
            margin: 10mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
        }
        
        .page {
            page-break-after: always;
        }
        
        .page:last-child {
            page-break-after: avoid;
        }
        
        .proof-item {
            margin-bottom: 5mm;
            border: 1px solid #ccc;
            padding: 3mm;
        }
        
        .proof-label {
            font-size: 8pt;
            color: #333;
            margin-bottom: 2mm;
        }
        
        .proof-label strong {
            font-size: 9pt;
        }
        
        .proof-image {
            text-align: center;
        }
        
        .proof-image img {
            max-width: 100%;
            max-height: 100mm;
        }
        
        .no-image {
            padding: 10mm;
            text-align: center;
            color: #999;
            font-style: italic;
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    @foreach($pages as $pageIndex => $records)
        <div class="page">
            @foreach($records as $record)
                <div class="proof-item">
                    <div class="proof-label">
                        <strong>{{ $record->users?->name ?? '-' }}</strong> — {{ $record->users?->srn ?? '-' }}
                    </div>
                    <div class="proof-image">
                        @php
                            $imagePath = $record->bukti_pembayaran;
                            $hasImage = $imagePath && Storage::disk('public')->exists($imagePath);
                            $imageData = null;
                            
                            if ($hasImage) {
                                try {
                                    $fullPath = Storage::disk('public')->path($imagePath);
                                    $imageContent = file_get_contents($fullPath);
                                    $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';
                                    $imageData = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);
                                } catch (\Exception $e) {
                                    $hasImage = false;
                                }
                            }
                        @endphp
                        
                        @if($hasImage && $imageData)
                            <img src="{{ $imageData }}" alt="Bukti">
                        @else
                            <div class="no-image">Tidak tersedia</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
