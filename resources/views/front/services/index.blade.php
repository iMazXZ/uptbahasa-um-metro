{{-- resources/views/front/services/index.blade.php --}}
@extends('layouts.front')

@section('title', 'Layanan Kami - Lembaga Bahasa UM Metro')

@section('meta')
  <meta name="description" content="Informasi lengkap tentang layanan Lembaga Bahasa UM Metro: EPT, Penerjemahan Dokumen, Basic Listening, dan Pelatihan Bahasa.">
  <meta name="keywords" content="Layanan Lembaga Bahasa, EPT, Penerjemahan, Basic Listening, UM Metro">
@endsection

@section('content')

{{-- HERO SECTION --}}
<section class="relative overflow-hidden py-16 lg:py-24">
  {{-- Background --}}
  <div class="absolute inset-0">
    <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-indigo-900"></div>
    <div class="absolute inset-0" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px; opacity: 0.1;"></div>
    <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-blue-400 rounded-full blur-3xl opacity-20"></div>
    <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-indigo-400 rounded-full blur-3xl opacity-20"></div>
  </div>

  <div class="relative max-w-5xl mx-auto px-4 lg:px-8 text-center text-white">
    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur border border-white/20 rounded-full px-4 py-1.5 mb-6 text-sm font-medium">
      <i class="fas fa-concierge-bell text-blue-300"></i>
      Layanan Profesional
    </div>
    
    <h1 class="text-3xl md:text-4xl lg:text-5xl font-black mb-4">
      Layanan <span class="text-blue-300">Kami</span>
    </h1>
    <p class="text-lg text-blue-100 max-w-2xl mx-auto">
      Temukan informasi lengkap tentang berbagai layanan bahasa yang kami sediakan untuk mendukung kesuksesan Anda.
    </p>
  </div>
</section>

{{-- SERVICES SECTION --}}
<section class="py-12 lg:py-20 bg-gray-50">
  <div class="max-w-5xl mx-auto px-4 lg:px-8">
    
    @if($services->isEmpty())
      {{-- Empty State --}}
      <div class="text-center py-16">
        <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
          <i class="fas fa-folder-open text-3xl text-gray-400"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-700 mb-2">Belum Ada Informasi Layanan</h2>
        <p class="text-gray-500 mb-6">Informasi layanan akan segera tersedia.</p>
        <a href="{{ route('front.home') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-full font-semibold hover:bg-blue-700 transition">
          <i class="fas fa-home"></i>
          Kembali ke Beranda
        </a>
      </div>
    @else
      {{-- Services List --}}
      <div class="grid gap-4">
        @foreach($services as $service)
          <a href="{{ route('layanan.show', $service->slug) }}" 
             class="group bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md hover:border-blue-200 transition-all overflow-hidden">
            <div class="flex flex-col md:flex-row">
              {{-- Image --}}
              <div class="md:w-48 h-40 md:h-auto flex-shrink-0 overflow-hidden">
                <img src="{{ $service->cover_url }}" 
                     alt="{{ $service->title }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                     loading="lazy">
              </div>
              
              {{-- Content --}}
              <div class="flex-1 p-5 flex flex-col justify-center">
                <div class="flex items-start gap-3 mb-2">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded-full flex-shrink-0 mt-1">
                    <i class="fas fa-info-circle text-[10px]"></i>
                    Layanan
                  </span>
                  <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                    {{ $service->title }}
                  </h3>
                </div>
                
                <p class="text-gray-600 text-sm line-clamp-2 mb-3">
                  {{ $service->excerpt ?: Str::limit(strip_tags($service->body ?? ''), 150) }}
                </p>
                
                <div class="flex items-center justify-between">
                  <span class="text-xs text-gray-400">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    {{ $service->published_at?->translatedFormat('d M Y') }}
                  </span>
                  <span class="inline-flex items-center gap-1 text-blue-600 font-semibold text-sm group-hover:gap-2 transition-all">
                    Baca Selengkapnya
                    <i class="fas fa-arrow-right text-xs"></i>
                  </span>
                </div>
              </div>
            </div>
          </a>
        @endforeach
      </div>
    @endif
    
  </div>
</section>

{{-- CTA SECTION --}}
<section class="py-12 bg-white">
  <div class="max-w-3xl mx-auto px-4 lg:px-8 text-center">
    <h2 class="text-xl lg:text-2xl font-bold text-gray-900 mb-3">Ada Pertanyaan?</h2>
    <p class="text-gray-600 text-sm mb-6">Hubungi kami untuk informasi lebih lanjut tentang layanan yang tersedia.</p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
      <a href="https://wa.me/6287790740408" target="_blank" rel="noopener"
         class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-green-500 text-white rounded-full font-semibold hover:bg-green-600 transition shadow">
        <i class="fab fa-whatsapp"></i>
        Hubungi via WhatsApp
      </a>
      <a href="{{ route('front.home') }}#kontak"
         class="inline-flex items-center justify-center gap-2 px-5 py-2.5 border-2 border-gray-300 text-gray-700 rounded-full font-semibold hover:border-blue-500 hover:text-blue-600 transition">
        <i class="fas fa-map-marker-alt"></i>
        Lihat Kontak Lengkap
      </a>
    </div>
  </div>
</section>

@endsection
