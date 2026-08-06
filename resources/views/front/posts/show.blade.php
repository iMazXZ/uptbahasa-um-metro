{{-- resources/views/front/posts/show.blade.php --}}
@extends('layouts.front')
@section('title', ($post->title ?? 'Detail') . ' - Lembaga Bahasa')

@section('meta')
  @php
      $metaDescription = $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($body ?? ''), 160);
      $metaCanonical = ($post->type ?? null) === 'career'
          ? route('front.career.show', $post->slug)
          : route('front.post.show', $post->slug);
      $metaRobots = in_array(($post->type ?? null), ['news', 'career'], true) ? 'index,follow' : 'noindex,follow';
      $metaImage = $post->cover_url;
  @endphp
  <meta name="description" content="{{ $metaDescription }}">
  <link rel="canonical" href="{{ $metaCanonical }}">
  <meta name="robots" content="{{ $metaRobots }}">
  <meta property="og:type" content="article">
  <meta property="og:title" content="{{ $post->title }}">
  <meta property="og:description" content="{{ $metaDescription }}">
  <meta property="og:url" content="{{ $metaCanonical }}">
  <meta property="og:image" content="{{ $metaImage }}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $post->title }}">
  <meta name="twitter:description" content="{{ $metaDescription }}">
  <meta name="twitter:image" content="{{ $metaImage }}">
@endsection

@php
    use Illuminate\Support\Facades\Route;

    // Mode lebar untuk konten khusus (jadwal/nilai)
    $isWide = in_array($post->type ?? null, ['schedule','scores'], true);

    // Tentukan fallback route untuk tombol kembali
    $candidates = [
        'front.posts.index',
        'front.post.index',
        'front.news.index',
        'front.news',
        'front.index',
        'home',
    ];
    $fallbackIndex = null;
    foreach ($candidates as $name) {
        if (Route::has($name)) { $fallbackIndex = route($name); break; }
    }
    $fallbackIndex ??= (url('/berita') ?: url('/'));

    $prevUrl  = url()->previous();
    $backHref = ($prevUrl && $prevUrl !== url()->current()) ? $prevUrl : $fallbackIndex;

    $typeLabel = \App\Models\Post::TYPES[$post->type] ?? ucfirst((string) $post->type);
    $postType = (string) ($post->type ?? 'news');
    $newsCategorySlug = null;
    $newsCategoryLabel = null;
    $newsCategoryUrl = null;
    if ($postType === 'news') {
        $newsCategorySlug = \App\Models\Post::normalizeNewsCategory($post->news_category ?? null);
        $newsCategoryLabel = \App\Models\Post::newsCategoryLabel($newsCategorySlug);

        if (\App\Models\Post::isValidNewsCategory($newsCategorySlug)) {
            $newsCategoryUrl = route('front.news.category', ['newsCategory' => $newsCategorySlug]);
        }
    }
    $sectionRootLabel = $postType === 'career' ? 'Karier' : 'Berita';
    $sectionRootUrl = $postType === 'career' ? route('front.career') : route('front.news');

    $authorName = trim((string) ($post->author?->name ?? 'Redaksi'));
    $authorInitial = mb_strtoupper(mb_substr($authorName, 0, 1));
    $editorName = 'Admin';
    $editorInitial = 'ED';
    $careerDeadline = null;
    if ($postType === 'career' && ! empty($post->career_deadline)) {
        $careerDeadline = $post->career_deadline instanceof \Carbon\CarbonInterface
            ? $post->career_deadline
            : \Carbon\Carbon::parse($post->career_deadline);
    }
    $careerApplyUrl = $postType === 'career' ? trim((string) ($post->career_apply_url ?? '')) : '';
    $careerIsOpen = $postType === 'career'
        ? ((bool) ($post->career_is_open ?? true) && ($careerDeadline === null || $careerDeadline->greaterThanOrEqualTo(now())))
        : false;
    $careerStatusLabel = $careerIsOpen ? 'Lowongan Dibuka' : 'Lowongan Ditutup';
    $careerStatusClass = $careerIsOpen
        ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
        : 'bg-rose-50 border-rose-200 text-rose-700';
    $shareUrl = rawurlencode($metaCanonical);
    $shareText = rawurlencode((string) ($post->title ?? ''));
    $shareLinks = [
        'facebook' => "https://www.facebook.com/sharer/sharer.php?u={$shareUrl}",
        'x' => "https://twitter.com/intent/tweet?url={$shareUrl}&text={$shareText}",
        'whatsapp' => "https://wa.me/?text={$shareText}%20{$shareUrl}",
        'telegram' => "https://t.me/share/url?url={$shareUrl}&text={$shareText}",
    ];
    $inlineReadMore = null;
    if (($post->type ?? null) === 'news') {
        $inlineReadMore = $related->first(
            fn ($item) => (int) ($item->id ?? 0) !== (int) ($post->id ?? 0)
        );
    }
    $postDetailUrl = function ($item): string {
        if (($item->type ?? null) === 'career') {
            return route('front.career.show', $item->slug);
        }

        return route('front.post.show', $item->slug);
    };

    /**
     * Fallback sanitizer untuk $body
     */
    $sanitizeHtml = function (?string $html) {
        $html = (string) $html;
        $html = \Illuminate\Support\Str::of($html)
            ->replaceMatches('/<script\b[^>]*>.*?<\/script>/is', '')
            ->replaceMatches('/\son\w+\s*=\s*"[^"]*"/i', '')
            ->replaceMatches("/\son\w+\s*=\s*'[^']*'/i", '')
            ->replaceMatches('/\son\w+\s*=\s*[^\s>]+/i', '')
            ->toString();
        return $html;
    };
    $renderBody = function (?string $html) use ($sanitizeHtml) {
        if (function_exists('clean')) { try { return clean($html, 'post'); } catch (\Throwable $e) {} }
        return $sanitizeHtml($html);
    };

    // ===== Helper format tampilan views (1.2K / 3.4M) =====
    $formatViews = function ($n) {
        $n = (int) ($n ?? 0);
        if ($n >= 1000000) return number_format($n / 1000000, 1) . 'M';
        if ($n >= 1000)    return number_format($n / 1000, 1) . 'K';
        return number_format($n);
    };
    $scheduleGroupCode = null;
    $isSyncedSchedule = ($post->type ?? null) === 'schedule' && filled($post->ept_group_id);
    if (($post->type ?? null) === 'schedule' && filled($post->eptGroup?->name)) {
        preg_match('/(\d+)/', (string) $post->eptGroup->name, $matches);
        $scheduleGroupCode = $matches[1] ?? $post->eptGroup->name;
    }
    $scheduleDateLabel = $isSyncedSchedule && $post->event_date
        ? \Carbon\Carbon::parse($post->event_date)->translatedFormat('l, d F Y')
        : null;
    $scheduleTimeLabel = $isSyncedSchedule && $post->event_time
        ? \Carbon\Carbon::parse($post->event_time)->format('H:i')
        : null;
    $statusShort = function (?string $status): string {
        return match ($status) {
            \App\Models\EptRegistration::STUDENT_STATUS_REGULAR => 'R',
            \App\Models\EptRegistration::STUDENT_STATUS_KONVERSI => 'C',
            \App\Models\EptRegistration::STUDENT_STATUS_MAGISTER => 'M',
            \App\Models\EptRegistration::STUDENT_STATUS_GENERAL => 'G',
            default => '-',
        };
    };
@endphp

@push('styles')
<style>
  /* ================== TABEL UTAMA ================== */
  .tbl-wrap{
    overflow:auto;border:1px solid #e5e7eb;border-radius:12px;background:#fff;
    box-shadow:none;margin:.2rem 0;
    position:relative;
  }
  .prose .tbl-wrap table{width:100%;border-collapse:separate;border-spacing:0;font-size:.8rem}
  .prose .tbl-wrap thead th{
    position:sticky;top:0;z-index:10;background:#2563eb;
    background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);color:#fff;font-weight:600;
    padding:6px 8px;text-align:left;white-space:nowrap;border-bottom:2px solid #1d4ed8
  }
  .prose .tbl-wrap tbody td{
    padding:6px 8px;vertical-align:top;color:#374151;border-bottom:1px solid #f3f4f6;word-break:break-word
  }
  .prose .tbl-wrap tbody tr:nth-child(even) td{background:#f9fafb}
  .prose .tbl-wrap tbody tr:hover td{background:#eff6ff}
  .prose .tbl-wrap thead th:first-child,.prose .tbl-wrap tbody td:first-child{text-align:center;width:56px}
  .prose .tbl-wrap thead th:last-child,.prose .tbl-wrap tbody td:last-child{text-align:center;white-space:nowrap}

  /* Scroll hint indicator untuk mobile */
  @media (max-width:640px){
    .tbl-wrap.can-scroll::before{
      content:'← Geser untuk melihat semua kolom →';
      position:sticky;
      left:0;
      display:block;
      text-align:center;
      padding:8px;
      background:linear-gradient(135deg,#dbeafe 0%,#e0e7ff 100%);
      color:#2563eb;
      font-size:11px;
      font-weight:600;
      border-bottom:2px solid #93c5fd;
      z-index:8;
      letter-spacing:0.3px;
      animation:pulse 2s infinite;
    }

    @keyframes pulse{
      0%,100%{opacity:1}
      50%{opacity:0.7}
    }

    .tbl-wrap.scrolled::before{
      display:none;
    }
  }

  /* ====== MODE COMPACT MOBILE (≤640px) ====== */
  @media (max-width:640px){
    .tbl-wrap{border-radius:8px;box-shadow:none;margin:.1rem 0}
    .prose .tbl-wrap table{font-size:clamp(10px,3.1vw,12px);line-height:1.2;border-spacing:0}
    .prose .tbl-wrap thead th,.prose .tbl-wrap tbody td{padding:4px 6px}
    .prose .tbl-wrap thead th:first-child,.prose .tbl-wrap tbody td:first-child{width:36px;text-align:center;font-variant-numeric:tabular-nums}
    .prose .tbl-wrap thead th{top:0;z-index:12}
    .prose .tbl-wrap tbody tr:nth-child(even) td{background:#fafafa}
    .prose .tbl-wrap tbody tr:hover td{background:#f3f6ff}
  }

  /* Opsi super-compact untuk pembungkus bertag .compact-table */
  @media (max-width:640px){
    .compact-table .tbl-wrap table{font-size:10.5px;line-height:1.15}
    .compact-table .tbl-wrap th,.compact-table .tbl-wrap td{padding:2px 4px}
    .compact-table .tbl-wrap thead th:first-child,.compact-table .tbl-wrap tbody td:first-child{width:30px}
  }

  /* ================== DAFTAR ISI ================== */
  #post-toc summary::-webkit-details-marker { display: none; }
  #post-toc-icon { transition: transform .2s ease; }
  #post-toc[open] #post-toc-icon { transform: rotate(180deg); }
  #post-body h1, #post-body h2, #post-body h3, #post-body h4 { scroll-margin-top: 90px; }
  #post-toc-list li { list-style: none; }
  #post-toc-list .toc-link {
    display: flex;
    align-items: center;
    gap: .5rem;
    color: #374151;
    text-decoration: none;
    border-radius: .375rem;
    padding: .125rem .25rem;
  }
  #post-toc-list .toc-link:hover {
    color: #1d4ed8;
    background: rgba(59, 130, 246, .08);
  }
  #post-toc-list .toc-mark {
    width: 1rem;
    text-align: center;
    color: #6b7280;
    font-size: .75rem;
    line-height: 1;
    flex: 0 0 auto;
  }
  #post-toc-list .toc-text {
    line-height: 1.35;
  }

  /* ================== NORMALISASI JARAK LIST ================== */
  /* Tiptap sering menghasilkan <li><p>...</p></li>, margin <p> membuat jarak terlalu jauh. */
  #post-body :is(ul, ol) {
    margin-top: .65rem;
    margin-bottom: .9rem;
    padding-left: 1.2rem;
  }
  #post-body :is(ul, ol) > li {
    margin-top: .25rem;
    margin-bottom: .25rem;
  }
  #post-body :is(ul, ol) > li + li {
    margin-top: .35rem;
  }
  #post-body :is(ul, ol) > li > p {
    margin: 0;
    line-height: 1.65;
  }
  #post-body li :is(ul, ol) {
    margin-top: .35rem;
    margin-bottom: .35rem;
  }

  /* ================== NEWS DETAIL ================== */
  .news-title {
    color: #102a6b;
    letter-spacing: -0.01em;
  }
  .news-meta {
    color: #6b7280;
  }
  .news-avatar {
    width: 2.1rem;
    height: 2.1rem;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    font-weight: 700;
    color: #fff;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px #e5e7eb;
  }
  .news-avatar + .news-avatar { margin-left: -.45rem; }
  .news-byline {
    display: flex;
    align-items: center;
    gap: .55rem;
    flex-wrap: wrap;
  }
  .news-byline-text {
    font-size: .95rem;
    color: #4b5563;
  }
  .news-byline-text .name-author {
    color: #1f2937;
    font-weight: 600;
  }
  .news-byline-text .name-editor {
    color: #dc2626;
    font-weight: 600;
  }
  .news-share-row {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-top: .7rem;
  }
  .news-share-btn {
    width: 2.05rem;
    height: 2.05rem;
    border-radius: 9999px;
    border: none;
    background: #9ca3af;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    transition: all .2s ease;
  }
  .news-share-btn:hover {
    filter: brightness(0.92);
  }
  .news-share-btn--fb { background: #1877f2; }
  .news-share-btn--x { background: #111827; }
  .news-share-btn--wa { background: #22c55e; }
  .news-share-btn--tg { background: #229ed9; }
  .news-share-btn--copy { background: #9ca3af; }
  .news-prose > p:first-of-type {
    font-size: 1.05rem;
    line-height: 1.75;
  }
  .news-readmore {
    border-left: 4px solid #dc2626;
    padding-left: .85rem;
    margin: 1.5rem 0;
  }
  .news-readmore-label {
    display: block;
    font-size: .95rem;
    line-height: 1;
    font-weight: 800;
    letter-spacing: .02em;
    text-transform: uppercase;
    color: #1e3a8a;
    margin-bottom: .4rem;
  }
  .news-readmore-link {
    display: inline-block;
    color: #1f2937;
    font-size: clamp(1.3rem, 1.6vw, 2rem);
    line-height: 1.25;
    font-weight: 700;
    text-decoration: none;
  }
  .news-readmore-link:hover {
    text-decoration: underline;
    text-underline-offset: 2px;
  }
  @media (min-width: 1024px) {
    .news-prose > p:first-of-type {
      font-size: 1.125rem;
    }
  }

  .schedule-shell {
    border: 1px solid #dbe5f0;
    border-radius: 24px;
    background: #ffffff;
    overflow: hidden;
  }
  .schedule-banner {
    padding: 1rem 1.1rem;
    border-bottom: 1px solid #e5eef8;
    background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 100%);
  }
  .schedule-banner-title {
    font-size: .82rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #c2410c;
    margin-bottom: .35rem;
  }
  .schedule-banner-body {
    color: #374151;
    line-height: 1.75;
    font-size: .97rem;
  }
  .schedule-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0;
    padding: 0;
  }
  .schedule-section {
    padding: 1rem 1.1rem;
    border-bottom: 1px solid #edf2f7;
  }
  .schedule-section--info {
    background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
  }
  .schedule-section-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #111827;
    margin-bottom: .85rem;
  }
  .schedule-meta {
    display: grid;
    gap: .75rem;
  }
  .schedule-meta-item {
    padding: .1rem 0;
  }
  .schedule-meta-label {
    display: block;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #2563eb;
    margin-bottom: .25rem;
  }
  .schedule-meta-value {
    color: #111827;
    font-weight: 700;
    line-height: 1.45;
  }
  .schedule-rules {
    margin: 0;
    padding-left: 0;
    list-style: none;
    color: #374151;
  }
  .schedule-rules > li {
    position: relative;
    margin: .45rem 0;
    padding-left: 1rem;
    line-height: 1.7;
  }
  .schedule-rules > li::before {
    content: '•';
    position: absolute;
    left: 0;
    top: 0;
    color: #2563eb;
    font-weight: 800;
  }
  .schedule-footer-note {
    padding: 1rem 1.1rem;
    color: #475569;
    line-height: 1.7;
    border-bottom: 1px solid #edf2f7;
  }
  .schedule-participants {
    background: #fff;
    overflow: hidden;
  }
  .schedule-participants .tbl-wrap {
    border-left: 0;
    border-right: 0;
    border-bottom: 0;
    border-radius: 0;
    margin: 0;
  }
  .schedule-participants-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: 1rem 1.1rem .8rem;
    border-bottom: 1px solid #edf2f7;
    background: #fff;
  }
  .schedule-participants-title {
    font-size: 1.02rem;
    font-weight: 800;
    color: #111827;
  }
  .schedule-participants-subtitle {
    margin-top: .2rem;
    font-size: .88rem;
    color: #64748b;
  }
  .schedule-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.2rem;
    height: 2rem;
    padding: 0 .7rem;
    border-radius: 999px;
    background: #dbeafe;
    color: #1d4ed8;
    font-size: .82rem;
    font-weight: 800;
  }
  .schedule-scroll-note {
    padding: 0 1.1rem .85rem;
    font-size: .85rem;
    color: #64748b;
    font-style: italic;
  }
  .participants-table {
    width: 100%;
    min-width: 980px;
    border-collapse: separate;
    border-spacing: 0;
    font-size: .92rem;
    color: #1f2937;
  }
  .participants-table thead th {
    position: sticky;
    top: 0;
    z-index: 6;
    background: #f8fafc;
    color: #475569;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: .9rem .9rem;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    text-align: left;
  }
  .participants-table tbody td {
    padding: .95rem .9rem;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
    background: #fff;
  }
  .participants-table tbody tr:nth-child(even) td {
    background: #fcfdff;
  }
  .participants-table tbody tr:hover td {
    background: #f8fbff;
  }
  .participants-col-no {
    width: 4.2rem;
    text-align: center;
  }
  .participants-col-name {
    min-width: 15rem;
  }
  .participants-col-status {
    width: 7rem;
    text-align: center;
  }
  .participants-col-year {
    width: 6.2rem;
  }
  .participants-col-srn {
    width: 9rem;
  }
  .participants-col-group {
    width: 6rem;
    text-align: center;
  }
  .participants-no {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 800;
    font-size: .82rem;
  }
  .participants-name {
    font-weight: 700;
    color: #0f172a;
    line-height: 1.45;
    white-space: nowrap;
  }
  .participants-subtle {
    display: block;
    margin-top: .2rem;
    font-size: .8rem;
    color: #64748b;
  }
  .participants-mono {
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    color: #334155;
    font-size: .88rem;
  }
  .participants-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.1rem;
    height: 2rem;
    padding: 0 .7rem;
    border-radius: 999px;
    font-size: .82rem;
    font-weight: 800;
    line-height: 1;
    border: 1px solid transparent;
  }
  .participants-chip--status {
    background: #ecfeff;
    color: #0f766e;
    border-color: #a5f3fc;
  }
  .participants-chip--group {
    background: #eef2ff;
    color: #4338ca;
    border-color: #c7d2fe;
  }
  .participants-prodi {
    color: #334155;
    line-height: 1.5;
    min-width: 14rem;
    white-space: nowrap;
  }
  @media (max-width: 640px) {
    .schedule-participants-head {
      padding: .9rem .95rem .65rem;
      align-items: flex-start;
    }
    .schedule-participants-title {
      font-size: .96rem;
      line-height: 1.3;
    }
    .schedule-participants-subtitle {
      margin-top: .15rem;
      font-size: .8rem;
    }
    .schedule-chip {
      min-width: 1.9rem;
      height: 1.9rem;
      padding: 0 .55rem;
      font-size: .75rem;
    }
    .schedule-scroll-note {
      padding: 0 .95rem .65rem;
      font-size: .78rem;
    }
    .participants-table {
      min-width: 700px;
      font-size: .78rem;
    }
    .participants-table thead th {
      padding: .55rem .5rem;
      font-size: .62rem;
      letter-spacing: .04em;
    }
    .participants-table tbody td {
      padding: .58rem .5rem;
    }
    .participants-col-no {
      width: 2.7rem;
    }
    .participants-col-name {
      min-width: 10.5rem;
    }
    .participants-col-status {
      width: 4.2rem;
    }
    .participants-col-year {
      width: 4.6rem;
    }
    .participants-col-srn {
      width: 6.5rem;
    }
    .participants-no {
      width: 1.45rem;
      height: 1.45rem;
      font-size: .68rem;
    }
    .participants-name {
      font-size: .72rem;
      line-height: 1.2;
    }
    .participants-mono {
      font-size: .72rem;
    }
    .participants-chip {
      min-width: 1.55rem;
      height: 1.55rem;
      padding: 0 .35rem;
      font-size: .68rem;
    }
    .participants-prodi {
      min-width: 9rem;
      font-size: .72rem;
      line-height: 1.2;
    }
  }
  @media (min-width: 1024px) {
    .schedule-grid {
      grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr);
      gap: 0;
    }
    .schedule-banner {
      padding: 1.2rem 1.25rem;
    }
    .schedule-section,
    .schedule-footer-note,
    .schedule-participants-head,
    .schedule-scroll-note {
      padding-left: 1.25rem;
      padding-right: 1.25rem;
    }
  }
</style>
@endpush

@section('content')
<!-- Tombol kembali hanya untuk mode jadwal / nilai -->
@if($isWide)
  <div class="border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4 lg:px-6 py-3">
      <a href="{{ $backHref }}"
         class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 font-medium transition-colors group"
         aria-label="Kembali ke halaman sebelumnya">
        <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        <span>Kembali</span>
      </a>
    </div>
  </div>
@endif

<section class="max-w-6xl mx-auto px-4 lg:px-6 py-7 lg:py-9 {{ $isWide ? 'compact-table' : '' }}">
  
  @if($isWide)
    {{-- ========== HEADER ARTIKEL (JADWAL/NILAI - TETAP) ========== --}}
    <header class="mb-6 text-center">
      <h1 class="text-xl lg:text-2xl font-bold leading-tight text-gray-900 mb-3">
        {{ $post->title }}
      </h1>

      <div class="flex flex-wrap justify-center items-center gap-3 text-sm text-gray-600">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <span>{{ optional($post->published_at)->translatedFormat('d M Y, H:i') }}</span>
        </div>

        @if($post->author?->name)
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>{{ $post->author->name }}</span>
          </div>
        @endif

        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true" focusable="false">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12z"/>
            <circle cx="12" cy="12" r="3" stroke-width="2" stroke="currentColor" fill="none"/>
          </svg>
          <span>{{ $formatViews($post->views ?? 0) }} views</span>
        </div>
      </div>
    </header>
  @else
    {{-- ========== HEADER ARTIKEL (NEWS) ========== --}}
    <nav class="mb-3 text-sm text-gray-500">
      <a href="{{ url('/') }}" class="hover:underline">Home</a>
      <span class="mx-1">›</span>
      <a href="{{ $sectionRootUrl }}" class="hover:underline">{{ $sectionRootLabel }}</a>
      @if($newsCategoryLabel)
        <span class="mx-1">›</span>
        @if($newsCategoryUrl)
          <a href="{{ $newsCategoryUrl }}" class="font-semibold text-red-600 hover:underline">{{ $newsCategoryLabel }}</a>
        @else
          <span class="font-semibold text-red-600">{{ $newsCategoryLabel }}</span>
        @endif
      @endif
    </nav>

    <header class="mb-5 text-left">
      <h1 class="news-title text-3xl lg:text-5xl font-bold leading-tight mb-3">
        {{ $post->title }}
      </h1>

      <div class="news-meta text-sm mb-3">
        {{ optional($post->published_at)->translatedFormat('l, d F Y | H:i') }} WIB
      </div>

      <div class="mb-4">
        @if($postType === 'career')
          <div class="rounded-xl border p-4 {{ $careerStatusClass }}">
            <div class="flex flex-wrap items-center gap-2 mb-3">
              <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $careerStatusClass }}">
                {{ strtoupper($careerStatusLabel) }}
              </span>
              @if($careerDeadline)
                <span class="text-sm font-medium">
                  Deadline: {{ $careerDeadline->translatedFormat('d M Y, H:i') }} WIB
                </span>
              @endif
            </div>
            <p class="text-sm opacity-90">
              Informasi pendaftaran tersedia di bagian bawah artikel ini.
            </p>
          </div>
        @else
          <div class="news-byline">
            <div class="flex items-center">
              <span class="news-avatar bg-blue-900">{{ $authorInitial }}</span>
              <span class="news-avatar bg-red-600">{{ $editorInitial }}</span>
            </div>
            <div class="news-byline-text">
              Penulis: <span class="name-author">{{ $authorName }}</span>
              <span class="text-gray-400">|</span>
              Editor: <span class="name-editor">{{ $editorName }}</span>
            </div>
          </div>

          <div class="news-share-row">
            <a href="{{ $shareLinks['facebook'] }}" target="_blank" rel="noopener noreferrer nofollow" class="news-share-btn news-share-btn--fb" aria-label="Bagikan ke Facebook">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 8H16V5h-2.5C11.57 5 10 6.57 10 8.5V11H8v3h2v5h3v-5h2.5l.5-3H13v-2.5c0-.28.22-.5.5-.5z"/></svg>
            </a>
            <a href="{{ $shareLinks['x'] }}" target="_blank" rel="noopener noreferrer nofollow" class="news-share-btn news-share-btn--x" aria-label="Bagikan ke X">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 3H21l-6.5 7.4L22 21h-5.9l-4.6-6-5.2 6H4.2l6.9-7.9L2 3h6l4.1 5.4L18.9 3z"/></svg>
            </a>
            <a href="{{ $shareLinks['whatsapp'] }}" target="_blank" rel="noopener noreferrer nofollow" class="news-share-btn news-share-btn--wa" aria-label="Bagikan ke WhatsApp">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 12a8 8 0 0 1-11.7 7l-4.3 1.1 1.2-4.2A8 8 0 1 1 20 12zm-4.7 2.3c-.2-.1-1.3-.6-1.5-.6s-.3-.1-.5.1c-.1.2-.6.6-.7.8-.1.1-.3.2-.5 0s-.9-.3-1.8-1.1c-.7-.6-1.1-1.4-1.2-1.6-.1-.2 0-.3.1-.4l.3-.3c.1-.1.1-.2.2-.3.1-.1.1-.2.2-.4s0-.3 0-.4-.5-1.3-.7-1.8c-.2-.4-.4-.4-.5-.4h-.4c-.1 0-.4.1-.6.3s-.8.8-.8 1.9.8 2.2.9 2.3c.1.1 1.6 2.5 3.9 3.5.5.2.9.3 1.2.4.5.2 1 .1 1.4.1.4-.1 1.3-.5 1.5-.9.2-.5.2-.9.2-1 0-.1-.2-.1-.4-.2z"/></svg>
            </a>
            <a href="{{ $shareLinks['telegram'] }}" target="_blank" rel="noopener noreferrer nofollow" class="news-share-btn news-share-btn--tg" aria-label="Bagikan ke Telegram">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.7 15.5l-.4 4c.6 0 .9-.3 1.2-.6l2.9-2.8 6-4.4c.3-.2-.1-.3-.4-.2l-7.4 2.3-3.2-1c-.7-.2-.7-.7.1-1l12.6-4.9c.6-.2 1 .1.8.9l-2.1 10c-.2.7-.6.9-1.2.6l-3.4-2.5-1.6 1.6c-.2.2-.4.4-.8.4z"/></svg>
            </a>
            <button type="button" id="copy-share-link" class="news-share-btn news-share-btn--copy" aria-label="Salin tautan">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 6h9v12h-9V6zm-5 4h3v10h8v3H5V10zm2-7h12v2H7V3z"/></svg>
            </button>
          </div>
        @endif
      </div>
    </header>
  @endif

  @if($isWide)
    {{-- ========== MODE SATU KOLOM (FULL WIDTH) ========== --}}
    <div id="post-toc-wrap" class="hidden mb-6">
      <details id="post-toc" class="border border-gray-200 rounded-md bg-gray-50">
        <summary class="list-none cursor-pointer px-4 py-3 flex items-center justify-between">
          <span class="font-semibold text-gray-900">Daftar Isi</span>
          <span id="post-toc-icon" class="text-gray-500">▾</span>
        </summary>
        <nav class="border-t border-gray-200 px-4 py-3">
          <ol id="post-toc-list" class="space-y-2 text-sm text-gray-700"></ol>
        </nav>
      </details>
    </div>

    @if($isSyncedSchedule)
      <div class="schedule-shell">
        <div class="schedule-banner">
          <div class="schedule-banner-title">Pengumuman Jadwal EPT</div>
          <div class="schedule-banner-body">
            Peserta ujian agar memperhatikan jadwal ujian. Jika setelah pengumuman ini ditetapkan, YBS tidak hadir maka YBS dianggap gugur dan harus daftar ulang lagi.
          </div>
        </div>

        <div class="schedule-grid">
          <section class="schedule-section">
            <h2 class="schedule-section-title">Perhatian Sebelum Ujian</h2>
            <p class="text-slate-700 leading-7 mb-3">Saat ujian EPT atau memasuki Lab. Bahasa, peserta wajib:</p>
            <ul class="schedule-rules mt-0">
              <li>Menonaktifkan HP atau alat elektronik lainnya.</li>
              <li>Menunjukkan kartu peserta ujian (print) dan Kartu Tanda Penduduk.</li>
              <li>Memakai pakaian yang sopan.</li>
            </ul>
          </section>

          <section class="schedule-section schedule-section--info">
            <h2 class="schedule-section-title">Jadwal Ujian</h2>
            <div class="schedule-meta">
              <div class="schedule-meta-item">
                <span class="schedule-meta-label">Hari, Tanggal</span>
                <div class="schedule-meta-value">{{ $scheduleDateLabel ?? '-' }}</div>
              </div>
              <div class="schedule-meta-item">
                <span class="schedule-meta-label">Pukul</span>
                <div class="schedule-meta-value">{{ $scheduleTimeLabel ? $scheduleTimeLabel . ' s/d selesai' : '-' }}</div>
              </div>
              <div class="schedule-meta-item">
                <span class="schedule-meta-label">Ruang</span>
                <div class="schedule-meta-value">{{ $post->event_location ?? '-' }}</div>
              </div>
            </div>
          </section>
        </div>

        <div class="schedule-footer-note">
          Hal yang kurang jelas dapat ditanyakan langsung pada bagian Pendaftaran EPT di Lembaga Bahasa UM Metro.
        </div>

        @if($post->eptGroup && $scheduleParticipants->count())
          <section class="schedule-participants compact-table">
            <div class="schedule-participants-head">
              <div>
                <div class="schedule-participants-title">Daftar Peserta Grup {{ $scheduleGroupCode ?? $post->eptGroup->name }}</div>
                <div class="schedule-participants-subtitle">{{ $scheduleParticipants->count() }} peserta terdaftar pada jadwal ini.</div>
              </div>
              <span class="schedule-chip">{{ $scheduleParticipants->count() }}</span>
            </div>
            <div class="tbl-wrap can-scroll">
              <table class="participants-table">
                <thead>
                  <tr>
                    <th class="participants-col-no">No</th>
                    <th class="participants-col-name">Nama Peserta</th>
                    <th class="participants-col-status">Status</th>
                    <th class="participants-col-year">Year</th>
                    <th class="participants-col-srn">SRN</th>
                    <th>Program Studi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($scheduleParticipants as $participant)
                    <tr>
                      <td class="participants-col-no">
                        <span class="participants-no">{{ $loop->iteration }}</span>
                      </td>
                      <td class="participants-col-name">
                        <div class="participants-name">{{ $participant->user?->name ?? '-' }}</div>
                      </td>
                      <td class="participants-col-status">
                        <span class="participants-chip participants-chip--status">{{ $statusShort($participant->student_status) }}</span>
                      </td>
                      <td class="participants-col-year">
                        <span class="participants-mono">{{ $participant->user?->year ?? '-' }}</span>
                      </td>
                      <td class="participants-col-srn">
                        <span class="participants-mono">{{ $participant->user?->srn ?? '-' }}</span>
                      </td>
                      <td>
                        <div class="participants-prodi">{{ $participant->user?->prody?->name ?? '-' }}</div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </section>
        @endif
      </div>
    @else
      <article class="bg-white rounded-lg border border-gray-200 p-4 lg:p-6">
        <div id="post-body"
             class="prose prose-sm lg:prose-lg max-w-none
                    prose-headings:font-bold prose-headings:text-gray-900 prose-headings:mt-2 prose-headings:mb-2
                    prose-h1:text-2xl prose-h2:text-xl prose-h3:text-lg prose-h4:text-base prose-h5:text-base prose-h6:text-sm
                    prose-p:text-gray-700 prose-p:leading-relaxed
                    prose-a:text-blue-600 prose-a:font-medium hover:prose-a:text-blue-700 hover:prose-a:underline
                    prose-img:rounded-md prose-img:shadow-none prose-img:my-6
                    prose-figure:my-2 prose-figcaption:text-center prose-figcaption:text-sm prose-figcaption:text-gray-500 prose-figcaption:mt-2
                    prose-ul:my-2 prose-ol:my-2
                    prose-li:text-gray-700 prose-li:my-2
                    prose-strong:text-gray-900 prose-strong:font-semibold
                    prose-blockquote:border-l-4 prose-blockquote:border-blue-500 prose-blockquote:bg-blue-50 prose-blockquote:py-2 prose-blockquote:px-6 prose-blockquote:rounded-r-lg">
          {!! $renderBody($body) !!}
        </div>

        @if(($post->type ?? null) === 'schedule' && $post->eptGroup && $scheduleParticipants->count())
          <div class="mt-8 compact-table">
            <div class="tbl-wrap can-scroll">
              <table>
                <thead>
                  <tr>
                    <th>NO</th>
                    <th>NAMA</th>
                    <th>R/C/M/G</th>
                    <th>YEAR</th>
                    <th>SRN</th>
                    <th>PRODI</th>
                    <th>GROUP</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($scheduleParticipants as $participant)
                    <tr>
                      <td>{{ $loop->iteration }}</td>
                      <td>{{ $participant->user?->name ?? '-' }}</td>
                      <td>{{ $statusShort($participant->student_status) }}</td>
                      <td>{{ $participant->user?->year ?? '-' }}</td>
                      <td>{{ $participant->user?->srn ?? '-' }}</td>
                      <td>{{ $participant->user?->prody?->name ?? '-' }}</td>
                      <td>{{ $scheduleGroupCode ?? '-' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif
      </article>
    @endif

    @if($related->count())
      <div class="mt-16">
        <x-post.card-grid 
          title="Informasi Lainnya" 
          :items="$related" 
          :moreRoute="$post->type === 'schedule' ? route('front.schedule') : route('front.scores')" 
          :type="$post->type"
          emptyText="Tidak ada informasi lainnya."
        />
      </div>
    @endif

  @else
    {{-- ========== MODE DUA KOLOM (NEWS) ========== --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-7 lg:gap-10">
      <article class="lg:col-span-8">
        <div class="bg-white">
          @if($post->cover_url)
            <figure class="mb-4">
              <img src="{{ $post->cover_url }}"
                   alt="{{ $post->title }}"
                   class="w-full aspect-[16/9] object-cover border border-gray-200"
                   loading="lazy" decoding="async"
                   width="1280" height="720">
              @if(filled($post->excerpt))
                <figcaption class="text-sm text-gray-500 mt-2">{{ $post->excerpt }}</figcaption>
              @endif
            </figure>
          @endif

          <div id="post-toc-wrap" class="hidden mb-6">
            <details id="post-toc" class="border border-gray-200 rounded-md bg-gray-50">
              <summary class="list-none cursor-pointer px-4 py-3 flex items-center justify-between">
                <span class="font-semibold text-gray-900">Daftar Isi</span>
                <span id="post-toc-icon" class="text-gray-500">▾</span>
              </summary>
              <nav class="border-t border-gray-200 px-4 py-3">
                <ol id="post-toc-list" class="space-y-2 text-sm text-gray-700"></ol>
              </nav>
            </details>
          </div>

          <div id="post-body"
               class="news-prose prose prose-base lg:prose-lg max-w-none
                      prose-headings:font-semibold prose-headings:text-gray-900 prose-headings:mt-6 prose-headings:mb-3
                      prose-h1:text-2xl prose-h2:text-xl prose-h3:text-lg prose-h4:text-base prose-h5:text-base prose-h6:text-base
                      prose-p:text-gray-800 prose-p:leading-relaxed
                      prose-a:text-blue-700 prose-a:font-medium hover:prose-a:text-blue-800 hover:prose-a:underline
                      prose-img:rounded-sm prose-img:shadow-none prose-img:my-6
                      prose-figure:my-6 prose-figcaption:text-sm prose-figcaption:text-gray-500 prose-figcaption:mt-2
                      prose-ul:my-4 prose-ol:my-4
                      prose-li:text-gray-700 prose-li:my-2
                      prose-strong:text-gray-900 prose-strong:font-semibold
                      prose-blockquote:border-l-4 prose-blockquote:border-gray-300 prose-blockquote:bg-gray-50 prose-blockquote:py-2 prose-blockquote:px-5 prose-blockquote:rounded-r">
            {!! $renderBody($body) !!}
          </div>

          @if($postType === 'career' && $careerApplyUrl !== '')
            <div class="mt-8 rounded-xl border p-4 {{ $careerStatusClass }}">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                  <p class="font-semibold">
                    {{ $careerIsOpen ? 'Pendaftaran masih dibuka.' : 'Pendaftaran sudah ditutup.' }}
                  </p>
                  @if($careerDeadline)
                    <p class="text-sm opacity-90 mt-1">
                      Deadline: {{ $careerDeadline->translatedFormat('d M Y, H:i') }} WIB
                    </p>
                  @endif
                </div>

                @if($careerIsOpen)
                  <a href="{{ $careerApplyUrl }}"
                     target="_blank"
                     rel="noopener noreferrer nofollow"
                     class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-6 py-2.5 text-white text-sm font-semibold hover:bg-emerald-700 transition-colors">
                    Daftar Sekarang
                  </a>
                @endif
              </div>
            </div>
          @endif

          @if($inlineReadMore)
            <div id="inline-readmore-data"
                 class="hidden"
                 data-url="{{ $postDetailUrl($inlineReadMore) }}"
                 data-title="{{ $inlineReadMore->title }}"
                 aria-hidden="true"></div>
          @endif
        </div>
      </article>

      <aside class="hidden lg:block lg:col-span-4">
        @if($related->count())
          <div class="sticky top-24">
            <div class="flex items-center gap-3 mb-4">
              <h2 class="text-2xl font-bold text-gray-900 uppercase tracking-wide whitespace-nowrap">Artikel Terpopuler</h2>
              <span class="flex-1 border-t border-dashed border-gray-300"></span>
            </div>

            <ol class="border-y border-dashed border-gray-300">
              @foreach($related->take(5) as $r)
                <li class="border-b border-dashed border-gray-300 last:border-b-0 py-4">
                  <a href="{{ $postDetailUrl($r) }}" class="grid grid-cols-[2.25rem,1fr] gap-3 group">
                    <span class="text-4xl font-extrabold leading-none text-blue-900">{{ $loop->iteration }}</span>
                    <span class="text-lg font-semibold leading-snug text-gray-900 group-hover:text-blue-700 transition-colors">
                      {{ $r->title }}
                    </span>
                  </a>
                </li>
              @endforeach
            </ol>
          </div>
        @endif
      </aside>
    </div>
  @endif
</section>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('post-body');
    if (!root) return;

    const copyShareButton = document.getElementById('copy-share-link');
    if (copyShareButton) {
      copyShareButton.addEventListener('click', async function () {
        const url = window.location.href;

        try {
          await navigator.clipboard.writeText(url);
          this.style.backgroundColor = '#e5e7eb';
          setTimeout(() => { this.style.backgroundColor = ''; }, 900);
        } catch (e) {
          window.prompt('Salin tautan ini:', url);
        }
      });
    }

    // ===== Bangun Daftar Isi otomatis dari heading konten =====
    const tocWrap = document.getElementById('post-toc-wrap');
    const tocList = document.getElementById('post-toc-list');

    if (tocWrap && tocList) {
      const headings = Array.from(root.querySelectorAll('h1, h2, h3, h4'))
        .filter(h => (h.textContent || '').trim().length > 0);
      const minHeadingLevel = headings.length
        ? Math.min(...headings.map(h => parseInt(h.tagName.replace('H', ''), 10)))
        : 2;

      const slugify = (text) => String(text || '')
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');

      if (headings.length >= 2) {
        headings.forEach((heading, index) => {
          const text = (heading.textContent || '').trim();
          if (!text) return;

          if (!heading.id) {
            const base = slugify(text) || `bagian-${index + 1}`;
            let id = base;
            let counter = 2;
            while (document.getElementById(id)) {
              id = `${base}-${counter++}`;
            }
            heading.id = id;
          }

          const li = document.createElement('li');
          li.className = 'leading-snug';
          const headingLevel = parseInt(heading.tagName.replace('H', ''), 10);
          const indentLevel = Math.max(0, headingLevel - minHeadingLevel);
          if (indentLevel > 0) {
            li.style.paddingLeft = `${indentLevel}rem`;
          }

          const link = document.createElement('a');
          link.href = `#${heading.id}`;
          link.className = 'toc-link';

          const mark = document.createElement('span');
          mark.className = 'toc-mark';
          mark.textContent = indentLevel === 0 ? '•' : '◦';

          const textNode = document.createElement('span');
          textNode.className = 'toc-text';
          textNode.textContent = text;

          link.appendChild(mark);
          link.appendChild(textNode);
          li.appendChild(link);
          tocList.appendChild(li);
        });

        if (tocList.children.length > 0) {
          tocWrap.classList.remove('hidden');
        }
      }
    }

    // Sisipkan blok "BACA JUGA" setelah paragraf ke-2 untuk artikel berita
    const inlineReadMoreData = document.getElementById('inline-readmore-data');
    if (inlineReadMoreData && !root.querySelector('.news-readmore')) {
      const readMoreUrl = inlineReadMoreData.getAttribute('data-url') || '';
      const readMoreTitle = (inlineReadMoreData.getAttribute('data-title') || '').trim();

      if (readMoreUrl && readMoreTitle) {
        const children = Array.from(root.children).filter((el) => {
          return el.tagName !== 'SCRIPT' && el.tagName !== 'STYLE';
        });
        const paragraphCandidates = children.filter((el) => {
          return el.tagName === 'P' && (el.textContent || '').trim().length >= 80;
        });

        const anchor = paragraphCandidates[1] || paragraphCandidates[0] || children[0] || null;
        if (anchor) {
          const aside = document.createElement('aside');
          aside.className = 'news-readmore';
          const label = document.createElement('span');
          label.className = 'news-readmore-label';
          label.textContent = 'Baca Juga';

          const link = document.createElement('a');
          link.className = 'news-readmore-link';
          link.href = readMoreUrl;
          link.textContent = readMoreTitle;

          aside.appendChild(label);
          aside.appendChild(link);
          anchor.insertAdjacentElement('afterend', aside);
        }
      }
    }

    root.querySelectorAll('table').forEach((tbl) => {
      tbl.removeAttribute('style');

      tbl.querySelectorAll('th,td,thead,tbody,tr').forEach(el => {
        el.removeAttribute('style');
        el.removeAttribute('height');
      });

      if (!tbl.parentElement || !tbl.parentElement.classList.contains('tbl-wrap')) {
        const wrap = document.createElement('div');
        wrap.className = 'tbl-wrap';
        
        // Aktifkan mode compact otomatis di layar kecil
        if (window.matchMedia('(max-width: 640px)').matches) {
          wrap.classList.add('compact-table');
        }
        
        tbl.parentNode.insertBefore(wrap, tbl);
        wrap.appendChild(tbl);

        // Deteksi jika tabel bisa di-scroll (untuk mobile)
        const checkScroll = () => {
          if (window.innerWidth <= 640) {
            if (wrap.scrollWidth > wrap.clientWidth) {
              wrap.classList.add('can-scroll');
            } else {
              wrap.classList.remove('can-scroll');
            }
          } else {
            wrap.classList.remove('can-scroll');
          }
        };

        checkScroll();
        window.addEventListener('resize', checkScroll);

        // Hapus hint setelah user scroll
        wrap.addEventListener('scroll', function() {
          if (this.scrollLeft > 10) {
            this.classList.add('scrolled');
            this.classList.remove('can-scroll');
          }
        });
      }
    });

    // Amankan tautan eksternal
    root.querySelectorAll('a[href]').forEach(a => {
      try {
        const href = a.getAttribute('href');
        const u = new URL(href, window.location.origin);
        if (u.origin !== window.location.origin) {
          a.target = '_blank';
          const rel = (a.rel || '').split(/\s+/);
          if (!rel.includes('noopener')) rel.push('noopener');
          if (!rel.includes('noreferrer')) rel.push('noreferrer');
          if (!rel.includes('nofollow')) rel.push('nofollow');
          a.rel = rel.join(' ').trim();
        }
      } catch (e) { /* abaikan URL tidak valid */ }
    });
  });
</script>
@endpush
