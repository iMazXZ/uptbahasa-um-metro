@php
    $barColor = [
        'success' => '#10b981',
        'warning' => '#f59e0b',
        'danger'  => '#ef4444',
    ];
@endphp
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Hasil Kuesioner Compact</title>
  <style>
    /* 1. SETUP HALAMAN */
    @page {
      size: A4;
      margin: 10mm 15mm;
    }
    
    /* 2. RESET & FONT */
    body {
      font-family: sans-serif;
      font-size: 11px;
      color: #111;
      line-height: 1.3;
    }
    
    table { border-collapse: collapse; width: 100%; }
    
    /* UTILITY */
    .muted { color: #666; font-size: 10px; }
    .text-right { text-align: right; }
    
    /* HEADER */
    .header-table {
      margin-bottom: 15px;
      border-bottom: 2px solid #000;
      padding-bottom: 10px;
    }
    .header-table td { vertical-align: bottom; }
    
    h1 {
      font-size: 25px; 
      font-weight: 800;
      margin: 0;
      color: #000;
      letter-spacing: -0.5px;
    }
    .survey-title { 
      font-size: 12px; 
      font-weight: bold; 
      margin-top: 4px; 
      color: #333;
      text-transform: uppercase;
    }
    
    /* CONTENT */
    h2 {
      font-size: 12px;
      background-color: #eee;
      padding: 5px;
      margin: 0 0 10px 0;
      font-weight: bold;
      border-bottom: 1px solid #ccc;
    }

    /* ROW PERTANYAAN */
    .question-row {
      margin-bottom: 12px;
      border-bottom: 1px dashed #ddd;
      padding-bottom: 8px;
    }
    .question-row:last-child { border: none; }

    /* Layout Pertanyaan */
    .q-text { 
      font-size: 13px; 
      font-weight: bold; 
      margin-bottom: 4px; 
      display: block;
    }
    
    .q-meta {
      font-size: 12px;
      color: #555;
      margin-bottom: 5px;
      display: block;
    }

    .badge {
      display: inline-block;
      padding: 2px 6px;
      color: #fff;
      border-radius: 3px;
      font-size: 9px;
      font-weight: bold;
      float: right;
    }
    .bg-success { background: #10b981; }
    .bg-warning { background: #f59e0b; color: #000; }
    .bg-danger  { background: #ef4444; }

    /* CHART: SEDERHANA & STABIL */
    .chart-table {
      width: 100%;
      height: 60px;
      table-layout: fixed;
    }
    .chart-cell {
      vertical-align: bottom;
      text-align: center;
      padding: 0 2px;
      height: 60px;
    }
    
    .bar-wrapper {
      position: relative;
      width: 100%;
      height: 100%;
      display: flex;
      align-items: flex-end;
      justify-content: center;
    }

    .bar {
      width: 60%;
      background: #ccc;
      margin: 0 auto;
      min-height: 2px;
      display: inline-block;
      border-radius: 2px 2px 0 0;
    }
    
    .bar-color-1, .bar-color-2 { background: #ef4444; }
    .bar-color-3 { background: #f59e0b; }
    .bar-color-4 { background: #10b981; }
    .bar-color-5 { background: #047857; }

    .bar-val {
      display: block;
      font-size: 9px;
      font-weight: bold;
      margin-bottom: 2px;
      color: #333;
    }
    
    .axis-label {
      border-top: 1px solid #ccc;
      display: block;
      font-size: 9px;
      color: #777;
      padding-top: 2px;
    }

    /* SARAN */
    .suggestions { 
      margin-top: 15px; 
      border: 1px solid #eee; 
      padding: 10px; 
    }
    .sug-group {
      margin-bottom: 10px;
      border-bottom: 1px dotted #eee;
      padding-bottom: 8px;
    }
    .sug-group:last-child {
      margin-bottom: 0;
      border-bottom: none;
    }
    .sug-q { 
      font-weight: bold; 
      font-size: 10px; 
      color: #444; 
      margin-bottom: 4px;
      background: #f9f9f9;
      padding: 2px 4px;
    }
    .sug-list {
      margin: 0;
      padding-left: 20px;
    }
    .sug-item { 
      font-style: italic; 
      color: #333; 
      font-size: 10px; 
      margin-bottom: 3px;
    }

  </style>
</head>
<body>
  @foreach($segments as $segment)
    @php
      $meta = $segment['meta'];
      $rows = $segment['rows'];
      // Ensure suggestions is a collection for grouping
      $suggestions = collect($segment['suggestions']);
      $entityName = strtolower($meta['category'] ?? '') === 'supervisor'
          ? ($meta['supervisor'] ?? '-')
          : ($meta['tutor'] ?? '-');
    @endphp

    <!-- HEADER -->
    <table class="header-table">
      <tr>
        <td width="60%">
          <div class="muted">{{ $meta['category'] ?? 'Kuesioner' }}</div>
          <h1>{{ $entityName }}</h1>
          <div class="survey-title">{{ $meta['surveyTitle'] ?? 'Hasil Kuesioner' }}</div>
        </td>
        <td width="40%" class="text-right">
          <div style="font-size:10px; line-height:1.4;">
            <strong>{{ $meta['respondents'] }}</strong> Responden<br>
            Rata-rata: <strong>{{ $meta['avg'] }}</strong><br>
            <span class="muted">{{ $meta['generatedAt'] }}</span>
          </div>
        </td>
      </tr>
    </table>

    <!-- RINGKASAN -->
    <h2>Ringkasan Performa</h2>

    @forelse($rows as $row)
      @php
        $avg = (float) $row->avg_score;
        $tone = $avg >= 4.5 ? 'success' : ($avg >= 3.5 ? 'warning' : 'danger');
        $toneLabel = $avg >= 4.5 ? 'Sangat Baik' : ($avg >= 3.5 ? 'Baik' : 'Perlu Perbaikan');
        
        $counts = [
            1 => (int) ($row->c1 ?? 0),
            2 => (int) ($row->c2 ?? 0),
            3 => (int) ($row->c3 ?? 0),
            4 => (int) ($row->c4 ?? 0),
            5 => (int) ($row->c5 ?? 0),
        ];
        $maxCount = max($counts) ?: 1;
      @endphp

      <div class="question-row">
        <div style="margin-bottom: 4px;">
           <span class="badge bg-{{ $tone }}">{{ $toneLabel }}</span>
           <span class="q-text">{{ $loop->iteration }}. {{ $row->question_text }}</span>
           <span class="q-meta">Rata-rata: <strong>{{ number_format($avg, 2) }}</strong> dari {{ $row->responses_count }} responden</span>
        </div>

        <table class="chart-table">
          <tr>
            @foreach($counts as $score => $count)
              @php
                $heightPercent = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                $heightPx = ($heightPercent / 100) * 35;
                if($count > 0 && $heightPx < 4) $heightPx = 4; 
              @endphp
              <td class="chart-cell">
                  @if($count > 0)
                    <span class="bar-val">{{ $count }}</span>
                    <div class="bar bar-color-{{ $score }}" style="height: {{ $heightPx }}px;"></div>
                  @else
                    <div style="height: 35px;"></div>
                  @endif
                  <div class="axis-label">{{ $score }}</div>
              </td>
            @endforeach
          </tr>
        </table>
      </div>
    @empty
      <p class="muted" style="text-align:center; padding: 20px;">Belum ada data tersedia.</p>
    @endforelse

    <!-- SARAN -->
    @if($suggestions->isNotEmpty())
      <div class="suggestions">
        <h3 style="margin:0 0 5px 0; font-size:11px; text-transform:uppercase;">Top Saran</h3>
        
        @foreach($suggestions->groupBy('question') as $question => $answers)
          <div class="sug-group">
            <div class="sug-q">{{ $question }}</div>
            <ul class="sug-list">
              @foreach($answers as $s)
                <li class="sug-item">"{{ $s['text'] }}"</li>
              @endforeach
            </ul>
          </div>
        @endforeach
      </div>
    @endif

    @if(!$loop->last)
      <div style="page-break-after: always;"></div>
    @endif
  @endforeach
</body>
</html>
