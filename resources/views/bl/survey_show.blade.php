{{-- resources/views/bl/survey_show.blade.php --}}
@extends('layouts.front')
@section('title', $survey->title ?: 'Kuesioner ' . ucfirst($survey->category))

@push('styles')
  <style>
  /* Hide global navbar & footer for survey flow */
  body > nav,
  body > footer{
    display: none !important;
  }

  /* ==== Page Layout ==== */
  body {
    background: #f8fafc;
  }
  .survey-header {
    background: #1e40af;
    color: white;
  }

  /* ==== Alert ==== */
  .alert {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 16px;
  }
  .alert-warning {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    color: #92400e;
  }
  .alert-error {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    color: #b91c1c;
  }
  .alert-success {
    background: #f0fdf4;
    border: 1px solid #86efac;
    color: #166534;
  }
  .alert-info {
    background: #eff6ff;
    border: 1px solid #93c5fd;
    color: #1e40af;
  }

  /* ==== Question Card ==== */
  .question-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    transition: border-color 0.2s;
  }
  .question-card:hover {
    border-color: #cbd5e1;
  }
  .question-card:focus-within {
    border-color: #1e40af;
    box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.1);
  }

  /* ==== Question Number ==== */
  .question-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #1e40af;
    color: white;
    font-weight: 600;
    font-size: 13px;
    border-radius: 6px;
    flex-shrink: 0;
  }

  /* ==== Likert Scale ==== */
  .likert-options {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
  }
  .likert-option {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 12px 8px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
    background: white;
  }
  .likert-option:hover {
    border-color: #93c5fd;
    background: #f8fafc;
  }
  .likert-option input[type="radio"] {
    appearance: none;
    width: 18px;
    height: 18px;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .likert-option input[type="radio"]:checked {
    border-color: #1e40af;
    background: #1e40af;
    box-shadow: inset 0 0 0 3px white;
  }
  .likert-option:has(input[type="radio"]:checked) {
    border-color: #1e40af;
    background: #eff6ff;
  }
  .likert-label {
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
  }
  .likert-option:has(input[type="radio"]:checked) .likert-label {
    color: #1e40af;
    font-weight: 600;
  }

  /* ==== Scale Labels ==== */
  .scale-labels {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 0 4px;
  }
  .scale-label {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 500;
  }

  /* ==== Textarea ==== */
  .custom-textarea {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 12px;
    font-size: 14px;
    color: #1e293b;
    transition: all 0.15s ease;
    resize: vertical;
    font-family: inherit;
    line-height: 1.6;
  }
  .custom-textarea:hover {
    border-color: #9ca3af;
  }
  .custom-textarea:focus {
    outline: none;
    border-color: #1e40af;
    box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.1);
  }

  /* ==== Buttons ==== */
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .btn-primary {
    background: #1e40af;
    color: white;
    border: none;
  }
  .btn-primary:hover {
    background: #1e3a8a;
  }
  .btn-ghost {
    background: transparent;
    color: #64748b;
    border: none;
    padding: 12px 16px;
  }
  .btn-ghost:hover {
    color: #475569;
    background: #f1f5f9;
  }

  /* ==== Error text ==== */
  .error-text {
    font-size: 12px;
    color: #dc2626;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 4px;
  }
  </style>
@endpush

@section('content')
  {{-- Header --}}
  <div class="survey-header">
    <div class="max-w-3xl mx-auto px-4 py-6">
      <div class="mb-3">
        <span class="inline-flex items-center gap-2 bg-white/15 backdrop-blur rounded-full px-3 py-1.5 text-xs font-medium">
          <i class="fa-solid fa-clipboard-list"></i>
          Kuesioner {{ ucfirst($survey->category) }}
        </span>
      </div>
      
      <h1 class="text-2xl font-bold mb-2">
        {{ $survey->title ?: 'Part 1: Kuesioner ' . ucfirst($survey->category) }}
      </h1>
      
      <div class="flex flex-wrap items-center gap-3 text-sm">
        <span class="text-blue-200">Kuesioner {{ ucfirst($survey->category) }}</span>
        
        @if($survey->category === 'tutor' && $response->tutor_id)
          <span class="inline-flex items-center gap-2 bg-white/15 backdrop-blur rounded-full px-3 py-1.5 text-xs font-medium">
            <i class="fa-solid fa-chalkboard-user"></i>
            {{ optional(\App\Models\User::find($response->tutor_id))->name }}
          </span>
        @endif
      </div>
    </div>
  </div>

  <div class="max-w-3xl mx-auto px-4 py-6">
    
    {{-- Flash Messages --}}
    @foreach (['success','info','warning','error'] as $f)
      @if (session($f))
        <div class="alert alert-{{ $f }}">
          <i class="fa-solid fa-{{ $f==='success'?'circle-check':($f==='info'?'info-circle':($f==='warning'?'triangle-exclamation':'circle-xmark')) }}"></i>
          <span>{{ session($f) }}</span>
        </div>
      @endif
    @endforeach

    {{-- Validation Errors --}}
    @if($errors->any())
      <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div>
          <strong class="block mb-1">Mohon periksa kembali:</strong>
          <ul class="list-disc list-inside text-sm space-y-0.5">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    @endif

    {{-- Survey Form --}}
    <form method="POST" action="{{ route('bl.survey.submit', $survey) }}">
      @csrf

      {{-- Hidden Fields --}}
      @if($survey->target === 'session')
        <input type="hidden" name="session_id" value="{{ request('session_id', $survey->session_id) }}">
      @endif

      @if($survey->category === 'tutor' && $response->tutor_id)
        <input type="hidden" name="tutor_id" value="{{ (int) $response->tutor_id }}">
      @endif

      {{-- Questions List --}}
      @foreach($survey->questions as $idx => $q)
        @php
          $qText = $q->question ?? $q->text ?? $q->prompt ?? $q->title ?? '— (Pertanyaan belum diisi) —';
          $type = $q->type ?? 'text';
          $required = (bool) ($q->is_required ?? false);
          $key = "q.{$q->id}";
          $ans = $answers[$q->id] ?? null;
        @endphp

        <div class="question-card">
          <div class="flex items-start gap-3 mb-4">
            <div class="question-number">{{ $idx + 1 }}</div>
            <label class="flex-1 text-sm font-semibold text-slate-800 leading-relaxed pt-0.5">
              {{ $qText }}
              @if($required)
                <span class="text-red-500 ml-0.5">*</span>
              @endif
            </label>
          </div>

          @if($type === 'likert')
            {{-- Likert Scale --}}
            <div class="scale-labels">
              <span class="scale-label">Sangat Tidak Setuju</span>
              <span class="scale-label">Sangat Setuju</span>
            </div>
            <div class="likert-options">
              @for($i=1; $i<=5; $i++)
                <label class="likert-option">
                  <input
                    type="radio"
                    name="q[{{ $q->id }}]"
                    value="{{ $i }}"
                    @checked( (int) old($key, (int) ($ans?->likert_value)) === $i )
                    {{ $required ? 'required' : '' }}
                  >
                  <span class="likert-label">{{ $i }}</span>
                </label>
              @endfor
            </div>
          @else
            {{-- Text Answer --}}
            <textarea
              name="q[{{ $q->id }}]"
              rows="4"
              class="custom-textarea"
              placeholder="Tulis jawaban Anda di sini..."
              {{ $required ? 'required' : '' }}
            >{{ old($key, $ans?->text_value) }}</textarea>
          @endif

          @error($key)
            <p class="error-text">
              <i class="fa-solid fa-circle-exclamation"></i>
              {{ $message }}
            </p>
          @enderror
        </div>
      @endforeach

      {{-- Action Buttons --}}
      <div class="flex items-center justify-between pt-4 border-t border-slate-200 mt-4">
        <a href="{{ route('bl.survey.reset-choice') }}" class="btn btn-ghost">
          <i class="fa-solid fa-rotate-left"></i>
          Reset
        </a>
        
        <button type="submit" class="btn btn-primary">
          Kirim Jawaban
          <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>
    </form>

    {{-- Info Footer --}}
    <div class="mt-6 text-center text-xs text-slate-400">
      <i class="fa-solid fa-info-circle mr-1"></i>
      Pastikan semua jawaban telah terisi dengan benar sebelum mengirim
    </div>

  </div>
@endsection
