@extends('layouts.front')

@section('title', 'Cek Verifikasi Dokumen')

@php
    $initialQuery = (string) ($lookupQuery ?? request('code', ''));
    $initialResults = collect($lookupResults ?? [])->values()->all();
    $initialLookupPerformed = (bool) ($lookupPerformed ?? false);
    $initialSummary = $lookupSummary ?? null;
@endphp

@section('content')
<div class="relative min-h-[80vh] flex items-center overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-indigo-900"></div>
        <div class="absolute inset-0" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px; opacity: 0.1;"></div>
        <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-blue-400 rounded-full blur-3xl opacity-20"></div>
        <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-indigo-400 rounded-full blur-3xl opacity-20"></div>
    </div>

    <div class="relative z-10 w-full max-w-6xl mx-auto px-4 py-8 lg:py-16">
        <div class="flex flex-col lg:flex-row items-center gap-6 lg:gap-20">
            <div class="flex-1 text-white text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-blue-100 text-xs font-medium mb-4 backdrop-blur-md">
                    <i class="fa-solid fa-shield-halved text-xs"></i>
                    Sistem Verifikasi Resmi
                </div>
                <h1 class="text-2xl md:text-4xl lg:text-5xl font-black tracking-tight mb-2 lg:mb-4">
                    Cek <span class="text-blue-300">Keaslian</span> Dokumen
                </h1>
                <p class="text-blue-100 text-sm lg:text-lg leading-relaxed mb-4 lg:mb-8 max-w-xl">
                    Verifikasi keaslian sertifikat, surat rekomendasi, hasil terjemahan, sekaligus pencarian arsip nilai Basic Listening, Interactive Class, dan Interactive Bahasa Arab.
                </p>

                <div class="flex flex-wrap gap-3 justify-center lg:justify-start">
                    <div class="flex items-center gap-1.5 text-xs text-blue-200">
                        <i class="fa-solid fa-check-circle text-emerald-400 text-[10px]"></i>
                        Sertifikat EPT
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-blue-200">
                        <i class="fa-solid fa-check-circle text-emerald-400 text-[10px]"></i>
                        Surat Rekomendasi
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-blue-200">
                        <i class="fa-solid fa-check-circle text-emerald-400 text-[10px]"></i>
                        Hasil Terjemahan
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-blue-200">
                        <i class="fa-solid fa-check-circle text-emerald-400 text-[10px]"></i>
                        Nilai Basic Listening
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-blue-200">
                        <i class="fa-solid fa-check-circle text-emerald-400 text-[10px]"></i>
                        Nilai Interactive Class
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-blue-200">
                        <i class="fa-solid fa-check-circle text-emerald-400 text-[10px]"></i>
                        Nilai Interactive Bahasa Arab
                    </div>
                </div>
            </div>

            <div class="w-full max-w-sm lg:max-w-md">
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl lg:rounded-3xl p-5 lg:p-8 border border-white/20 shadow-2xl">
                    <div class="w-12 h-12 lg:w-16 lg:h-16 mx-auto mb-4 lg:mb-6 bg-white/20 backdrop-blur rounded-xl lg:rounded-2xl flex items-center justify-center">
                        <i class="fa-solid fa-magnifying-glass text-xl lg:text-3xl text-white"></i>
                    </div>

                    <div class="text-center mb-4 lg:mb-6">
                        <h2 class="text-lg lg:text-xl font-bold text-white mb-1">Masukkan Kode, NPM, atau Nama</h2>
                        <p class="text-blue-200 text-xs lg:text-sm">Kode verifikasi akan diprioritaskan. Jika tidak cocok, sistem mencari nilai Basic Listening, Interactive Class, dan Interactive Bahasa Arab.</p>
                    </div>

                    @if (session('verification_error'))
                        <div class="mb-6 bg-red-500/20 border border-red-400/30 rounded-xl p-4 animate-shake">
                            <div class="flex items-center gap-3 text-red-200">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <p class="text-sm font-medium">{{ session('verification_error') }}</p>
                            </div>
                        </div>
                    @endif

                    <form id="lookup-form" method="GET" action="{{ route('verification.index') }}" novalidate>
                        <div class="mb-4">
                            <input
                                type="text"
                                id="lookup-query"
                                name="code"
                                maxlength="100"
                                required
                                autofocus
                                autocomplete="off"
                                spellcheck="false"
                                value="{{ $initialQuery }}"
                                class="w-full px-4 py-4 bg-white/10 border border-white/20 rounded-xl text-white text-sm lg:text-base placeholder-blue-200/70 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all"
                            />
                        </div>

                        <button
                            type="submit"
                            id="lookup-go"
                            class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-white text-blue-700 font-bold rounded-xl shadow-lg hover:bg-blue-50 transition-all transform hover:-translate-y-0.5 active:scale-[0.98]"
                        >
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Cari Sekarang
                        </button>
                    </form>

                    <p class="text-center text-blue-200/70 text-xs mt-4">
                        Tekan <kbd class="px-1.5 py-0.5 bg-white/10 rounded text-xs">Enter</kbd> untuk cari dokumen atau arsip nilai
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<section id="lookup-results-section" class="relative z-10 -mt-10 pb-16 {{ $initialLookupPerformed ? '' : 'hidden' }}">
    <div class="max-w-5xl mx-auto px-4">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 lg:px-8 py-6 border-b border-slate-200 bg-slate-50/70">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold mb-3">
                    <i class="fa-solid fa-table-list text-[10px]"></i>
                    Arsip Nilai
                </div>
                <h2 class="text-xl lg:text-2xl font-bold text-slate-900">Hasil Pencarian Nilai</h2>
                <p id="lookup-results-meta" class="mt-2 text-sm text-slate-500">
                    @if ($initialLookupPerformed)
                        {{ count($initialResults) }} hasil untuk &quot;{{ $initialQuery }}&quot;
                    @endif
                </p>
                <p class="mt-3 text-sm text-slate-500">
                    Jika query cocok dengan kode verifikasi dokumen, halaman akan langsung diarahkan ke hasil verifikasi.
                </p>
            </div>

            <div class="px-6 lg:px-8 py-6">
                <div id="lookup-feedback" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500 {{ $initialLookupPerformed && count($initialResults) === 0 ? '' : 'hidden' }}">
                    Data tidak ditemukan. Gunakan kode verifikasi untuk dokumen, atau NPM untuk hasil nilai yang paling akurat.
                </div>

                <div id="lookup-summary" class="mb-5 {{ $initialSummary ? '' : 'hidden' }}">
                    @if ($initialSummary)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">Peserta</p>
                                    <h3 class="mt-1 text-xl font-bold text-slate-900 break-words">{{ $initialSummary['name'] ?? '-' }}</h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
                                        <span><span class="font-medium text-slate-900">NPM</span> {{ $initialSummary['srn'] ?? '-' }}</span>
                                        <span><span class="font-medium text-slate-900">Program Studi</span> {{ $initialSummary['study_program'] ?? '-' }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (($initialSummary['result_labels'] ?? []) as $label)
                                        <span class="inline-flex items-center rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                                            {{ $label }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div id="lookup-results" class="grid grid-cols-1 lg:grid-cols-2 gap-4 {{ count($initialResults) > 0 ? '' : 'hidden' }}">
                    @foreach ($initialResults as $item)
                        <article class="h-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">{{ $item['source_year'] ?? 'ARSIP' }}</p>
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                            {{ $item['result_label'] ?? 'Nilai' }}
                                        </span>
                                        @if (!empty($item['semester']))
                                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">
                                                {{ $item['semester_label'] ?? ('Semester ' . $item['semester']) }}
                                            </span>
                                        @endif
                                    </div>
                                    @if (! $initialSummary)
                                        <h4 class="mt-2 text-lg font-semibold text-slate-900 leading-snug break-words">{{ $item['name'] ?? 'Tanpa nama' }}</h4>
                                    @endif
                                </div>
                                <div class="shrink-0 rounded-2xl bg-blue-50 px-3 py-2 text-center ring-1 ring-blue-100 min-w-[62px]">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-500">Nilai</div>
                                    <div class="mt-0.5 text-2xl font-bold leading-none text-blue-700">{{ $item['score'] ?? '-' }}</div>
                                </div>
                            </div>
                            @if ($initialSummary)
                                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Tahun</dt>
                                        <dd class="mt-1 font-semibold text-slate-900">{{ $item['source_year'] ?? '-' }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Grade</dt>
                                        <dd class="mt-1 font-semibold text-slate-900">{{ $item['grade'] ?? '-' }}</dd>
                                    </div>
                                </dl>
                            @else
                                <dl class="mt-4 grid grid-cols-[98px_1fr] gap-y-2 gap-x-3 text-sm">
                                    <dt class="text-slate-500">NPM</dt>
                                    <dd class="font-medium text-slate-900 break-words">{{ $item['srn'] ?? '-' }}</dd>
                                    <dt class="text-slate-500">Program Studi</dt>
                                    <dd class="font-medium text-slate-900 break-words">{{ $item['study_program'] ?? '-' }}</dd>
                                    <dt class="text-slate-500">Grade</dt>
                                    <dd class="font-medium text-slate-900 break-words">{{ $item['grade'] ?? '-' }}</dd>
                                </dl>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<style>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
}
.animate-shake { animation: shake 0.5s ease-in-out; }
</style>
@endsection

@push('scripts')
<script>
  function normalizeLookupQuery(raw) {
    return String(raw || '').replace(/\s+/g, ' ').trim();
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  const form = document.getElementById('lookup-form');
  const input = document.getElementById('lookup-query');
  const button = document.getElementById('lookup-go');
  const resultsSection = document.getElementById('lookup-results-section');
  const resultsGrid = document.getElementById('lookup-results');
  const resultsSummary = document.getElementById('lookup-summary');
  const resultsMeta = document.getElementById('lookup-results-meta');
  const feedback = document.getElementById('lookup-feedback');
  const lookupEndpoint = @json(route('verification.lookup'));
  const indexEndpoint = @json(route('verification.index'));

  function setLookupLoading(isLoading) {
    if (!button) {
      return;
    }

    button.disabled = isLoading;
    button.innerHTML = isLoading
      ? '<i class="fa-solid fa-spinner fa-spin"></i> Memproses'
      : '<i class="fa-solid fa-magnifying-glass"></i> Cari Sekarang';
  }

  function setFeedback(message) {
    if (!feedback) {
      return;
    }

    if (!message) {
      feedback.textContent = '';
      feedback.classList.add('hidden');
      return;
    }

    feedback.textContent = message;
    feedback.classList.remove('hidden');
  }

  function renderLookupSummary(summary) {
    if (!resultsSummary) {
      return;
    }

    if (!summary) {
      resultsSummary.innerHTML = '';
      resultsSummary.classList.add('hidden');
      return;
    }

    const labels = Array.isArray(summary.result_labels) ? summary.result_labels : [];

    resultsSummary.classList.remove('hidden');
    resultsSummary.innerHTML = `
      <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">Peserta</p>
            <h3 class="mt-1 text-xl font-bold text-slate-900 break-words">${escapeHtml(summary.name ?? '-')}</h3>
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
              <span><span class="font-medium text-slate-900">NPM</span> ${escapeHtml(summary.srn ?? '-')}</span>
              <span><span class="font-medium text-slate-900">Program Studi</span> ${escapeHtml(summary.study_program ?? '-')}</span>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            ${labels.map((label) => `<span class="inline-flex items-center rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">${escapeHtml(label)}</span>`).join('')}
          </div>
        </div>
      </div>
    `;
  }

  function renderLookupResults(payload) {
    if (!resultsSection || !resultsGrid || !resultsMeta) {
      return;
    }

    const items = Array.isArray(payload?.items) ? payload.items : [];
    const query = payload?.query ?? '';
    const summary = payload?.summary ?? null;

    resultsSection.classList.remove('hidden');
    resultsMeta.textContent = `${items.length} hasil untuk "${query}"`;

    if (!items.length) {
      renderLookupSummary(null);
      resultsGrid.innerHTML = '';
      resultsGrid.classList.add('hidden');
      setFeedback('Data tidak ditemukan. Gunakan kode verifikasi untuk dokumen, atau NPM untuk hasil paling akurat.');
      return;
    }

    setFeedback('');
    renderLookupSummary(summary);
    resultsGrid.classList.remove('hidden');
    resultsGrid.innerHTML = items.map((item) => `
      <article class="h-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">${escapeHtml(item.source_year ?? 'ARSIP')}</p>
              <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">${escapeHtml(item.result_label ?? 'Nilai')}</span>
              ${(item.semester ?? null) ? `<span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">${escapeHtml(item.semester_label ?? ('Semester ' + item.semester))}</span>` : ''}
            </div>
            ${summary ? '' : `<h4 class="mt-2 text-lg font-semibold text-slate-900 leading-snug break-words">${escapeHtml(item.name ?? 'Tanpa nama')}</h4>`}
          </div>
          <div class="shrink-0 rounded-2xl bg-blue-50 px-3 py-2 text-center ring-1 ring-blue-100 min-w-[62px]">
            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-500">Nilai</div>
            <div class="mt-0.5 text-2xl font-bold leading-none text-blue-700">${escapeHtml(item.score ?? '-')}</div>
          </div>
        </div>
        ${summary ? `
          <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div class="rounded-xl bg-slate-50 px-3 py-2">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Tahun</dt>
              <dd class="mt-1 font-semibold text-slate-900">${escapeHtml(item.source_year ?? '-')}</dd>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Grade</dt>
              <dd class="mt-1 font-semibold text-slate-900">${escapeHtml(item.grade ?? '-')}</dd>
            </div>
          </dl>
        ` : `
          <dl class="mt-4 grid grid-cols-[98px_1fr] gap-y-2 gap-x-3 text-sm">
            <dt class="text-slate-500">NPM</dt>
            <dd class="font-medium text-slate-900 break-words">${escapeHtml(item.srn ?? '-')}</dd>
            <dt class="text-slate-500">Program Studi</dt>
            <dd class="font-medium text-slate-900 break-words">${escapeHtml(item.study_program ?? '-')}</dd>
            <dt class="text-slate-500">Grade</dt>
            <dd class="font-medium text-slate-900 break-words">${escapeHtml(item.grade ?? '-')}</dd>
          </dl>
        `}
      </article>
    `).join('');
  }

  async function handleLookup(event) {
    if (event) {
      event.preventDefault();
    }

    const query = normalizeLookupQuery(input?.value);
    if (!query) {
      form?.classList.remove('animate-shake');
      void form?.offsetWidth;
      form?.classList.add('animate-shake');
      input?.focus();
      return;
    }

    if (input) {
      input.value = query;
    }

    setLookupLoading(true);
    setFeedback('');

    try {
      const response = await fetch(`${lookupEndpoint}?q=${encodeURIComponent(query)}`, {
        headers: {
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('Pencarian gagal diproses.');
      }

      const payload = await response.json();
      window.history.replaceState({}, '', `${indexEndpoint}?code=${encodeURIComponent(query)}`);

      if (payload?.mode === 'document' && payload?.redirect_url) {
        window.location.href = payload.redirect_url;
        return;
      }

      renderLookupResults(payload);
      resultsSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (error) {
      resultsSection?.classList.remove('hidden');
      resultsGrid?.classList.add('hidden');
      setFeedback(error?.message || 'Terjadi kesalahan saat mencari data.');
    } finally {
      setLookupLoading(false);
    }
  }

  form?.addEventListener('submit', handleLookup);
  button?.addEventListener('click', handleLookup);
  input?.addEventListener('blur', () => {
    input.value = normalizeLookupQuery(input.value);
  });
</script>
@endpush
