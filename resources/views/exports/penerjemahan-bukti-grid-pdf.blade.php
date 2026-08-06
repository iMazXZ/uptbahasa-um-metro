<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bukti Pembayaran Penerjemahan</title>
    <style>
        @page {
            size: legal portrait;
            margin: 8mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
        }
        
        .page {
            page-break-after: always;
        }
        
        .page:last-child {
            page-break-after: avoid;
        }
        
        .grid {
            width: 100%;
        }
        
        .grid-1 .row {
            display: block;
            width: 100%;
            margin-bottom: 3mm;
        }
        
        .grid-1 .cell {
            display: block;
            width: 100%;
            padding: 2mm;
        }
        
        .grid-2 .row {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        
        .grid-2 .cell {
            display: table-cell;
            width: 50%;
            padding: 3mm;
            vertical-align: top;
        }
        
        .grid-3 .row {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        
        .grid-3 .cell {
            display: table-cell;
            width: 33.33%;
            padding: 2mm;
            vertical-align: top;
        }
        
        .item {
            border: 1px solid #ddd;
            padding: 2mm;
            height: 100%;
        }
        
        .item-label {
            font-size: 7pt;
            color: #333;
            margin-bottom: 1mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .item-label strong {
            font-size: 8pt;
        }
        
        .item-image {
            text-align: center;
        }
        
        .grid-1 .item-image img {
            max-width: 100%;
            max-height: 75mm;
        }
        
        .grid-2 .item-image img {
            max-width: 100%;
            max-height: 85mm;
        }
        
        .grid-3 .item-image img {
            max-width: 100%;
            max-height: 70mm;
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
    @php
        $rowsPerPage = $columns === 2 ? 3 : 2;
        $itemsPerPage = $columns * $rowsPerPage;
    @endphp

    @foreach($pages as $pageRecords)
        <div class="page">
            <div class="grid grid-{{ $columns }}">
                @php
                    $chunked = $pageRecords->chunk($columns);
                @endphp
                
                @foreach($chunked as $row)
                    <div class="row">
                        @foreach($row as $record)
                            <div class="cell">
                                <div class="item">
                                    <div class="item-label">
                                        <strong>{{ Str::limit($record->users?->name ?? '-', 20) }}</strong> — {{ $record->users?->srn ?? '-' }}
                                    </div>
                                    <div class="item-image">
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
                                            <div class="no-image">-</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        
                        {{-- Fill empty cells if row is incomplete --}}
                        @for($i = $row->count(); $i < $columns; $i++)
                            <div class="cell"></div>
                        @endfor
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</body>
</html>
