{{-- resources/views/bl/complete_profile.blade.php --}}
@extends('layouts.front')
@section('title','Lengkapi Biodata')

@section('content')
<div class="min-h-screen bg-slate-50">
  {{-- Header --}}
  <div class="bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-900 text-white">
    <div class="max-w-2xl mx-auto px-4 py-10">
      <a href="{{ route('bl.index') }}" class="inline-flex items-center gap-2 text-sm text-white/70 hover:text-white mb-6">
        <i class="fa-solid fa-arrow-left"></i>
        Kembali
      </a>
      
      <h1 class="text-2xl font-bold mb-2">Lengkapi Biodata</h1>
      <p class="text-blue-200">Isi data berikut untuk melanjutkan ke quiz</p>
    </div>
  </div>

  {{-- Form --}}
  <div class="max-w-2xl mx-auto px-4 py-8">
    
    @if (session('warning'))
      <div class="mb-6 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
        <i class="fa-solid fa-exclamation-triangle mr-2"></i>
        {!! session('warning') !!}
      </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
      
      <form method="POST" action="{{ route('bl.profile.complete.submit') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="next" value="{{ $next }}">

        {{-- Program Studi --}}
        <div>
          <label for="prody_id" class="block text-sm font-medium text-slate-700 mb-1.5">
            Program Studi <span class="text-red-500">*</span>
          </label>
          <select name="prody_id" id="prody_id" required 
                  class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option value="">Pilih Program Studi</option>
            @foreach ($prodis as $p)
              <option value="{{ $p->id }}" @selected(old('prody_id', $user->prody_id)==$p->id)>
                {{ $p->name }}
              </option>
            @endforeach
          </select>
          @error('prody_id')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        {{-- NPM --}}
        <div>
          <label for="srn" class="block text-sm font-medium text-slate-700 mb-1.5">
            NPM <span class="text-red-500">*</span>
          </label>
          <input type="text" name="srn" id="srn"
                 value="{{ old('srn', $user->srn) }}" 
                 required
                 placeholder="Masukkan NPM"
                 class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-white text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          @error('srn')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        {{-- Tahun Angkatan --}}
        <div>
          <label for="year" class="block text-sm font-medium text-slate-700 mb-1.5">
            Tahun Angkatan <span class="text-red-500">*</span>
          </label>
          <input type="number" name="year" id="year"
                 value="{{ old('year', $user->year) }}" 
                 required 
                 min="2015" 
                 max="{{ now()->year }}"
                 placeholder="{{ now()->year }}"
                 class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-white text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          @error('year')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
          @enderror
        </div>

        {{-- Info --}}
        <div class="p-4 rounded-lg bg-blue-50 border border-blue-100 text-sm text-blue-800">
          <i class="fa-solid fa-info-circle mr-1.5"></i>
          Pastikan data sesuai dengan data resmi kampus.
        </div>

        {{-- Buttons --}}
        <div class="flex flex-col sm:flex-row gap-3 pt-4">
          <button type="submit" 
                  class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors">
            <span>Simpan & Lanjut</span>
            <i class="fa-solid fa-arrow-right"></i>
          </button>
          
          <a href="{{ route('dashboard.biodata') }}" 
             class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg border border-slate-300 text-slate-700 font-medium hover:bg-slate-50 transition-colors">
            Edit Lengkap
          </a>
        </div>
      </form>

    </div>
  </div>
</div>
@endsection