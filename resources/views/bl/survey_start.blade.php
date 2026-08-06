{{-- resources/views/bl/survey_start.blade.php --}}
@extends('layouts.front')
@section('title','Mulai Kuesioner Basic Listening')

@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css">
  <style>
    /* Hide global navbar & footer for this flow */
    body > nav,
    body > footer{
      display: none !important;
    }

    /* ==== Clean Background ==== */
    body {
      background: #f8fafc;
    }
    .page-wrap {
      max-width: 600px;
      margin: 0 auto;
      padding: 40px 16px 60px;
      min-height: 100vh;
    }

    /* ==== Progress Steps ==== */
    .steps-nav {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0;
      margin-bottom: 32px;
    }
    .step-dot {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 600;
      flex-shrink: 0;
    }
    .step-dot.active {
      background: #1e40af;
      color: white;
    }
    .step-dot.inactive {
      background: #e2e8f0;
      color: #94a3b8;
    }
    .step-line {
      width: 50px;
      height: 2px;
      background: #e2e8f0;
      flex-shrink: 0;
    }
    .step-line.done {
      background: #1e40af;
    }

    /* ==== Card ==== */
    .form-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .card-header {
      padding: 20px 24px;
      border-bottom: 1px solid #f1f5f9;
    }
    .card-body {
      padding: 24px;
    }

    /* ==== Form Elements ==== */
    .form-group {
      margin-bottom: 20px;
    }
    .form-label {
      display: block;
      font-weight: 600;
      font-size: 13px;
      color: #334155;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.025em;
    }
    .form-hint {
      font-size: 12px;
      color: #64748b;
      margin-top: 6px;
    }
    .required-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 10px;
      font-weight: 600;
      background: #fee2e2;
      color: #dc2626;
      padding: 2px 8px;
      border-radius: 4px;
      margin-left: 8px;
    }
    .max-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 10px;
      font-weight: 600;
      background: #dbeafe;
      color: #1d4ed8;
      padding: 2px 8px;
      border-radius: 4px;
      margin-left: 8px;
    }

    /* ==== Tom Select Clean ==== */
    .ts-wrapper.multi .ts-control {
      background: #ffffff;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      padding: 8px 12px;
      min-height: 44px;
      box-shadow: none;
    }
    .ts-wrapper.multi .ts-control:hover {
      border-color: #9ca3af;
    }
    .ts-wrapper.multi.focus .ts-control {
      border-color: #1e40af;
      box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.1);
    }
    .ts-wrapper .ts-control .item {
      background: #1e40af;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 4px 10px;
      font-weight: 500;
      font-size: 13px;
      margin: 2px 4px 2px 0;
    }
    .ts-wrapper .ts-control .item .remove {
      border-left: 1px solid rgba(255,255,255,0.3);
      padding-left: 6px;
      margin-left: 6px;
    }
    .ts-dropdown {
      border: 1px solid #d1d5db;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      margin-top: 4px;
    }
    .ts-dropdown .option {
      padding: 10px 12px;
      font-size: 14px;
    }
    .ts-dropdown .option.active {
      background: #eff6ff;
      color: #1e40af;
    }

    /* ==== Select ==== */
    .custom-select {
      width: 100%;
      background: #ffffff;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      padding: 10px 40px 10px 12px;
      font-size: 14px;
      color: #1e293b;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
      background-position: right 12px center;
      background-repeat: no-repeat;
      background-size: 16px;
      cursor: pointer;
    }
    .custom-select:hover {
      border-color: #9ca3af;
    }
    .custom-select:focus {
      outline: none;
      border-color: #1e40af;
      box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.1);
    }

    /* ==== Alert ==== */
    .alert {
      display: flex;
      gap: 12px;
      padding: 14px 16px;
      border-radius: 8px;
      font-size: 14px;
      margin-bottom: 20px;
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
    .alert-icon {
      flex-shrink: 0;
      width: 20px;
      height: 20px;
    }

    /* ==== Buttons ==== */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-weight: 600;
      font-size: 14px;
      padding: 12px 24px;
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
    .btn-secondary {
      background: white;
      color: #475569;
      border: 1px solid #d1d5db;
    }
    .btn-secondary:hover {
      background: #f8fafc;
      border-color: #9ca3af;
    }

    /* ==== Confirm Modal ==== */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15,23,42,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 50;
      padding: 16px;
    }
    .modal-card {
      background: white;
      border-radius: 12px;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    .modal-header {
      padding: 20px 24px 0;
      text-align: center;
    }
    .modal-body {
      padding: 16px 24px 24px;
    }
    .modal-footer {
      padding: 16px 24px;
      border-top: 1px solid #f1f5f9;
      display: flex;
      gap: 12px;
      justify-content: center;
    }

    /* ==== Error text ==== */
    .error-text {
      font-size: 12px;
      color: #dc2626;
      margin-top: 6px;
      display: flex;
      align-items: center;
      gap: 4px;
    }
  </style>
@endpush

@section('content')
  <div class="page-wrap">
    
    {{-- Progress Steps --}}
    <div class="steps-nav">
      <div class="step-dot active">1</div>
      <div class="step-line"></div>
      <div class="step-dot inactive">2</div>
      <div class="step-line"></div>
      <div class="step-dot inactive">3</div>
    </div>

    {{-- Page Title --}}
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-slate-900 mb-2">Pilih Pembimbing</h1>
      <p class="text-sm text-slate-500">Tentukan tutor dan supervisor Anda sebelum mengisi kuesioner.</p>
    </div>
    
    {{-- Flash Messages --}}
    @foreach (['success','warning','error'] as $f)
      @if (session($f))
        <div class="alert alert-{{ $f }}">
          <i class="fa-solid fa-{{ $f==='success'?'circle-check':($f==='warning'?'triangle-exclamation':'circle-xmark') }} alert-icon"></i>
          <span>{{ session($f) }}</span>
        </div>
      @endif
    @endforeach

    {{-- Validation Errors --}}
    @if($errors->any())
      <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation alert-icon"></i>
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

    @php
      $hasTutors = isset($tutors) && $tutors->count()>0;
      $hasSupervisors = isset($supervisors) && $supervisors->count()>0;
      $prefillTutorIds = (array) (($prefill['tutor_ids'] ?? []) ?: []);
      $prefillSupervisorId = (int) ($prefill['supervisor_id'] ?? 0);
    @endphp

    {{-- Warning if no data --}}
    @if(!$hasTutors || !$hasSupervisors)
      <div class="alert alert-warning">
        <i class="fa-solid fa-exclamation-triangle alert-icon"></i>
        <span>
          @if(!$hasTutors && !$hasSupervisors)
            Data tutor dan supervisor belum tersedia. Silakan hubungi administrator.
          @elseif(!$hasTutors)
            Data tutor belum tersedia. Silakan hubungi administrator.
          @else
            Data supervisor belum tersedia. Silakan hubungi administrator.
          @endif
        </span>
      </div>
    @endif

    {{-- Main Form Card --}}
    <div class="form-card">
      <div class="card-header">
        <h2 class="text-base font-semibold text-slate-800">Data Pembimbing</h2>
        <p class="text-sm text-slate-500 mt-1">Pilih tutor yang mengajar Anda dan supervisor yang ditunjuk.</p>
      </div>

      <div class="card-body">
        <form method="POST" action="{{ route('bl.survey.start.submit') }}" id="surveyForm">
          @csrf

          {{-- Tutor Selection --}}
          <div class="form-group">
            <label for="tutorSelect" class="form-label">
              Tutor
              <span class="max-badge">Maks 2</span>
            </label>
            
            @if($hasTutors)
              <select id="tutorSelect" name="tutor_ids[]" multiple placeholder="Cari nama tutor..." autocomplete="off" class="w-full">
                @foreach($tutors as $t)
                  @php $pre = in_array($t->id, old('tutor_ids', $prefillTutorIds), true); @endphp
                  <option value="{{ $t->id }}" @selected($pre)>{{ $t->name }}</option>
                @endforeach
              </select>
              <p class="form-hint">Pilih tutor Basic Listening yang mengajar di kelas Anda.</p>
            @else
              <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm text-slate-500">
                <i class="fa-solid fa-inbox mr-2"></i>
                Belum ada data tutor tersedia
              </div>
            @endif
            
            @error('tutor_ids')
              <p class="error-text">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ $message }}
              </p>
            @enderror
          </div>

          {{-- Supervisor Selection --}}
          <div class="form-group">
            <label for="supervisor_id" class="form-label">
              Supervisor
              <span class="required-badge">Wajib</span>
            </label>
            
            <select id="supervisor_id" name="supervisor_id" class="custom-select" required>
              <option value="" disabled @selected((int)old('supervisor_id', $prefillSupervisorId)===0)>
                Pilih supervisor...
              </option>
              @foreach($supervisors as $s)
                @php $sel = (int) old('supervisor_id', $prefillSupervisorId) === (int) $s->id; @endphp
                <option value="{{ $s->id }}" @selected($sel)>{{ $s->name }}</option>
              @endforeach
            </select>
            
            <p class="form-hint">Tanyakan nama supervisor ke tutor Anda jika tidak yakin.</p>
            
            @error('supervisor_id')
              <p class="error-text">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ $message }}
              </p>
            @enderror
          </div>

          {{-- Submit Button --}}
          <div class="pt-4 border-t border-slate-100">
            <button type="submit" class="btn btn-primary w-full">
              Lanjutkan
              <i class="fa-solid fa-arrow-right"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
    
    {{-- Confirm Dialog --}}
    <div class="modal-backdrop hidden" id="confirmModal">
      <div class="modal-card">
        <div class="modal-header">
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fa-solid fa-user-check text-blue-600 text-lg"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-900">Konfirmasi Pilihan</h3>
          <p class="text-sm text-slate-500 mt-1">Pastikan data pembimbing sudah benar.</p>
        </div>
        <div class="modal-body">
          <div class="bg-slate-50 rounded-lg p-4 space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Tutor</span>
              <span class="font-medium text-slate-900" id="confirmTutor">-</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Supervisor</span>
              <span class="font-medium text-slate-900" id="confirmSupervisor">-</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="confirmCancel">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali
          </button>
          <button type="button" class="btn btn-primary" id="confirmProceed">
            Ya, Lanjut
            <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>
      </div>
    </div>

  </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const confirmModal = document.getElementById('confirmModal');
      const confirmTutor = document.getElementById('confirmTutor');
      const confirmSupervisor = document.getElementById('confirmSupervisor');
      const confirmCancel = document.getElementById('confirmCancel');
      const confirmProceed = document.getElementById('confirmProceed');
      const form = document.getElementById('surveyForm');
      const supervisorSelect = document.getElementById('supervisor_id');
      let pendingSubmit = false;

      const el = document.getElementById('tutorSelect');
      let ts;
      if (el) {
        ts = new TomSelect(el, {
          maxItems: 2,
          plugins: ['remove_button'],
          create: false,
          persist: false,
          placeholder: 'Cari nama tutor...',
          render: {
            no_results: function(data, escape) {
              return '<div class="px-3 py-2 text-sm text-slate-500">Tidak ditemukan: "' + escape(data.input) + '"</div>';
            }
          }
        });
      }

      const openConfirm = () => {
        const tutorValues = ts ? ts.getValue() : [];
        const tutorNames = (Array.isArray(tutorValues) ? tutorValues : [tutorValues])
          .filter(Boolean)
          .map(v => ts?.options?.[v]?.text || `Tutor #${v}`);
        const supervisorName = supervisorSelect?.options[supervisorSelect.selectedIndex]?.text || '-';

        confirmTutor.textContent = tutorNames.length ? tutorNames.join(', ') : '-';
        confirmSupervisor.textContent = supervisorName;
        confirmModal?.classList.remove('hidden');
      };

      form?.addEventListener('submit', (e) => {
        if (pendingSubmit) return;
        const val = ts ? ts.getValue() : [];
        const count = Array.isArray(val) ? val.length : (val ? 1 : 0);
        if (count < 1) {
          e.preventDefault();
          alert('Pilih minimal 1 tutor sebelum melanjutkan.');
          ts?.focus();
          return;
        }
        e.preventDefault();
        openConfirm();
      });

      confirmCancel?.addEventListener('click', () => {
        confirmModal?.classList.add('hidden');
      });
      confirmModal?.addEventListener('click', (e) => {
        if (e.target === confirmModal) confirmModal.classList.add('hidden');
      });
      confirmProceed?.addEventListener('click', () => {
        pendingSubmit = true;
        confirmModal?.classList.add('hidden');
        form?.submit();
      });
    });
  </script>
@endpush
