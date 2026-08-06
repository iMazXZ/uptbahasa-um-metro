{{-- resources/views/front/services/show.blade.php --}}
@extends('layouts.front')

@section('title', ($service->title ?? 'Layanan') . ' - Lembaga Bahasa UM Metro')

@section('meta')
  <meta name="description" content="{{ $service->excerpt ?: Str::limit(strip_tags($service->body), 160) }}">
  <meta property="og:title" content="{{ $service->title }}">
  <meta property="og:description" content="{{ $service->excerpt ?: Str::limit(strip_tags($service->body), 160) }}">
  <meta property="og:image" content="{{ $service->cover_url }}">
@endsection

@php
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
@endphp

@push('styles')
<style>
  /* Clean simple table like reference */
  .simple-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    margin: 0.5rem 0;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #e0e0e0;
  }
  .simple-table th {
    background: #0d9488 !important;
    color: #fff !important;
    font-weight: 600 !important;
    padding: 10px 8px !important;
    text-align: left !important;
    font-size: 12px !important;
  }
  .simple-table td {
    padding: 10px 8px;
    border-bottom: 1px solid #eee;
    color: #333;
  }
  .simple-table tr:last-child td {
    border-bottom: none;
  }
  /* Column widths for KATEGORI, BIAYA, TES */
  .simple-table th:first-child,
  .simple-table td:first-child { width: 45%; }
  .simple-table th:nth-child(2),
  .simple-table td:nth-child(2) { width: 35%; }
  .simple-table th:nth-child(3),
  .simple-table td:nth-child(3) { width: 20%; text-align: center; }
  
  .prose ul li::marker, .prose ol li::marker { color: #0d9488; }
  
  /* Compact prose */
  .prose-compact p { margin:0.4rem 0; }
  .prose-compact h1,.prose-compact h2,.prose-compact h3 { margin:0.6rem 0 0.3rem; font-size:0.9rem; }
  .prose-compact ul,.prose-compact ol { margin:0.3rem 0; padding-left:1.2rem; }
  .prose-compact li { margin:0.1rem 0; }
</style>
@endpush

@section('content')

{{-- Compact Header --}}
<div class="bg-gradient-to-r from-teal-600 to-emerald-600 py-6">
  <div class="max-w-4xl mx-auto px-4 text-center">
    {{-- Title --}}
    <h1 class="text-xl md:text-2xl font-bold text-white leading-tight">
      {{ $service->title }}
    </h1>
    
    {{-- Last edited --}}
    <p class="mt-2 text-sm text-teal-100">
      Terakhir diedit pada {{ $service->updated_at?->translatedFormat('d M Y') }}
    </p>
  </div>
</div>

{{-- Content --}}
<section class="py-6 bg-gray-50">
  <div class="max-w-4xl mx-auto px-4">
    
    {{-- Cover (smaller) --}}
    @if($service->cover_path)
      <div class="mb-4 rounded-lg overflow-hidden shadow-md">
        <img src="{{ $service->cover_url }}" 
             alt="{{ $service->title }}"
             class="w-full h-48 md:h-56 object-cover"
             loading="lazy">
      </div>
    @endif
    
    {{-- Article Body --}}
    <article class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 md:p-6">
      <div id="service-body"
           class="prose prose-sm max-w-none prose-compact
                  prose-headings:font-bold prose-headings:text-gray-900
                  prose-p:text-gray-700 prose-p:leading-relaxed prose-p:text-sm
                  prose-a:text-teal-600 hover:prose-a:underline
                  prose-img:rounded-lg prose-img:my-3
                  prose-ul:my-2 prose-ol:my-2
                  prose-li:text-gray-700 prose-li:text-sm
                  prose-strong:text-gray-900
                  prose-blockquote:border-l-3 prose-blockquote:border-teal-500 prose-blockquote:bg-teal-50 prose-blockquote:py-1 prose-blockquote:px-4 prose-blockquote:rounded-r prose-blockquote:not-italic prose-blockquote:text-sm">
        {!! $renderBody($body) !!}
      </div>
    </article>
    
    {{-- Action Buttons (compact) --}}
    <div class="mt-4 flex flex-wrap gap-3 justify-center">
      <a href="https://wa.me/6287790740408?text={{ urlencode('Halo, saya ingin bertanya tentang: ' . $service->title) }}" 
         target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-full text-sm font-semibold hover:bg-green-600 transition shadow">
        <i class="fab fa-whatsapp"></i>
        Tanya via WA
      </a>
      <a href="{{ route('layanan.index') }}"
         class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-600 rounded-full text-sm font-semibold hover:border-teal-500 hover:text-teal-600 transition">
        <i class="fas fa-arrow-left"></i>
        Layanan Lain
      </a>
    </div>
    
  </div>
</section>

{{-- Related (compact) --}}
@if($related->count())
<section class="py-6 bg-white border-t">
  <div class="max-w-4xl mx-auto px-4">
    <h2 class="text-lg font-bold text-gray-900 mb-4">
      <i class="fas fa-th-large text-teal-600 mr-2"></i>Layanan Lainnya
    </h2>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      @foreach($related as $r)
        <a href="{{ route('layanan.show', $r->slug) }}" 
           class="group bg-gray-50 rounded-lg overflow-hidden border border-gray-100 hover:border-teal-200 hover:shadow-md transition">
          <div class="h-20 overflow-hidden">
            <img src="{{ $r->cover_url }}" alt="{{ $r->title }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform" loading="lazy">
          </div>
          <div class="p-2">
            <h3 class="font-semibold text-gray-900 group-hover:text-teal-600 text-xs line-clamp-2">
              {{ $r->title }}
            </h3>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('service-body');
    if (!root) return;

    root.querySelectorAll('table').forEach((tbl) => {
      // Remove ALL attributes from table and children
      tbl.removeAttribute('style');
      tbl.removeAttribute('width');
      tbl.removeAttribute('border');
      tbl.removeAttribute('cellpadding');
      tbl.removeAttribute('cellspacing');
      
      tbl.querySelectorAll('*').forEach(el => {
        el.removeAttribute('style');
        el.removeAttribute('width');
        el.removeAttribute('height');
        el.removeAttribute('bgcolor');
        el.removeAttribute('color');
      });
      
      tbl.querySelectorAll('colgroup').forEach(cg => cg.remove());
      
      // Add simple-table class for styling
      tbl.classList.add('simple-table');
      
      // Force white text on headers AND ALL nested elements
      tbl.querySelectorAll('th').forEach(th => {
        th.style.cssText = 'color:#fff !important;background:#0d9488 !important;';
        // Also force on any nested p, span, strong, etc
        th.querySelectorAll('*').forEach(child => {
          child.style.cssText = 'color:#fff !important;background:transparent !important;';
        });
      });
    });

    root.querySelectorAll('a[href]').forEach(a => {
      try {
        const u = new URL(a.getAttribute('href'), window.location.origin);
        if (u.origin !== window.location.origin) {
          a.target = '_blank';
          a.rel = 'noopener noreferrer';
        }
      } catch (e) {}
    });
  });
</script>
@endpush
