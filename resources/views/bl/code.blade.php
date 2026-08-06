{{-- resources/views/bl/code.blade.php --}}
@extends('layouts.front')
@section('title', 'Masukkan Connect Code')

@section('content')

@php
    $sessionTitle = trim($session->title ?? '');

    $durationMinutes = (int) ($session->duration_minutes ?? 0);
    if ($durationMinutes <= 0) {
        $durationMinutes = 10;
    }
@endphp

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-md">
    
    {{-- Header Card --}}
    <div class="bg-white rounded-t-2xl shadow-xl p-4 border-b-4 border-blue-600">
      <div class="text-center mb-6">
        {{-- Icon --}}
        <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
          <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
          </svg>
        </div>

        {{-- Title --}}
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
          Masukkan Connect Code
        </h1>

        {{-- Session Badge (diperbaiki, tapi layout tetap sama) --}}
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-full text-sm font-semibold shadow-md">
          <span class="text-xl sm:text-xl leading-snug">
            @if ($sessionTitle !== '')
              {{ $sessionTitle }}
            @endif
          </span>
        </div>

        <div class="inline-flex mt-3 items-center gap-2 px-4 py-1 bg-gradient-to-r from-green-600 to-green-900 text-white rounded-full text-sm shadow-sm">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"d="M12 8v4l2.5 2.5M12 22a10 10 0 110-20 10 10 0 010 20z"/>
          </svg>
          <span class="text-sm sm:text-sm">
            Waktu Pengerjaan: 
            <span class="font-bold">{{ $durationMinutes }} menit</span>
          </span>
        </div>
      </div>
    </div>

    {{-- Form Card --}}
    <div class="bg-white rounded-b-2xl shadow-xl p-8">
      
      {{-- Error Message --}}
      @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg animate-shake">
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div>
              <p class="text-sm font-medium text-red-800">{{ $errors->first() }}</p>
            </div>
          </div>
        </div>
      @endif

      {{-- Success Message --}}
      @if (session('status'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <div>
              <p class="text-sm font-medium text-green-800">{{ session('status') }}</p>
            </div>
          </div>
        </div>
      @endif

      {{-- Form --}}
      <form method="POST" action="{{ route('bl.code.verify', $session) }}" class="space-y-5">
        @csrf

        <div>
          <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">
            Connect Code
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
            </div>
            <input 
              type="text" 
              id="code"
              name="code" 
              maxlength="64" 
              required
              autofocus
              class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-gray-900 font-mono text-lg tracking-wider placeholder-gray-400 @error('code') border-red-500 @enderror"
              value="{{ old('code') }}"
              placeholder="Masukkan kode akses"
            />
          </div>
          @error('code')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
          @else
            <p class="mt-2 text-xs text-gray-500">
              Masukkan kode yang diberikan oleh tutor.
            </p>
          @enderror
        </div>

        <button 
          type="submit"
          class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r from-green-600 to-green-800 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-[1.02] active:scale-[0.98]"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
          Mulai Sekarang
        </button>
      </form>

      {{-- Back Link + Riwayat Skor --}}
      <div class="mt-6 text-center">
        <a 
          href="{{ route('bl.session.show', $session) }}" 
          class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 font-medium transition-colors group"
        >
          <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Kembali ke Detail Sesi
        </a>

        <p class="mt-5 text-xs text-gray-500">
          Jika sudah mengerjakan quiz, cek Riwayat Skor:
        </p>

        <a
          href="{{ route('bl.history') }}" {{-- SESUAIKAN jika nama route beda --}}
          class="mt-2 inline-flex items-center justify-center px-4 py-2 rounded-full border border-blue-600 text-xs font-semibold text-blue-600 hover:bg-blue-50 transition-colors"
        >
          Lihat Riwayat Skor
        </a>
      </div>
    </div>

    {{-- Security Notice --}}
    <div class="mt-6 text-center">
      <div class="inline-flex items-center gap-2 text-xs text-gray-500">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        Koneksi aman dengan enkripsi end-to-end
      </div>
    </div>

  </div>
</div>

{{-- Custom Styles --}}
<style>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.animate-shake {
  animation: shake 0.5s ease-in-out;
}
</style>
@endsection
