@extends('layouts.front')
@section('title', 'EPT Online')
@section('hide_navbar', '1')
@section('hide_footer', '1')
@section('translate_no', '1')

@include('ept-online.partials.mobile-device-guard')

@section('content')
@php
    /** @var \App\Models\EptOnlineAttempt $attempt */
    $hasActiveAttempts = $activeAttempts->isNotEmpty();
    $hasCompletedAttempts = $completedAttempts->isNotEmpty();
    $hasHistoryPanels = $hasActiveAttempts || $hasCompletedAttempts;
@endphp

<div class="min-h-screen bg-slate-100">
    <section class="mx-auto flex min-h-screen max-w-5xl flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-xl">
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.26em] text-slate-500">
                    Language Center - Muhammadiyah Metro University
                </p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                    English Proficiency Test Online
                </h1>
                <p class="mt-3 text-sm leading-7 text-slate-600 sm:text-base">
                    Insert your access code to start the test.
                </p>
            </div>

            <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="border-b border-slate-200 pb-4 text-center">
                    <h2 class="text-lg font-semibold text-slate-900">Insert Access Code</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        Make sure you have the correct code.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-center text-sm leading-6 text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm leading-6 text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('ept-online.access') }}" class="mx-auto mt-6 max-w-xl space-y-5 text-center">
                    @csrf
                    <div>
                        <input
                            id="code"
                            name="code"
                            type="text"
                            maxlength="128"
                            value="{{ old('code') }}"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-center text-base font-medium text-slate-900 outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-200"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Start Test
                    </button>
                </form>
            </div>
        </div>

        @if ($hasHistoryPanels)
            <div class="mx-auto mt-8 w-full max-w-3xl space-y-4">
                @if ($hasActiveAttempts)
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Active Attempt</h2>
                                <p class="mt-1 text-sm text-slate-600">Continue your unfinished test session.</p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($activeAttempts as $attempt)
                                <a href="{{ route('ept-online.attempt.show', ['attempt' => $attempt->public_id]) }}" class="block rounded-xl border border-slate-200 px-4 py-4 transition hover:border-slate-300 hover:bg-slate-50">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $attempt->form?->title ?? 'Online Test Package' }}</div>
                                            <div class="mt-1 text-xs uppercase tracking-[0.14em] text-slate-500">
                                                {{ strtoupper($attempt->current_section_type ?? '-') }} • {{ \App\Models\EptOnlineAttempt::statusOptions()[$attempt->status] ?? $attempt->status }}
                                            </div>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-700">Continue</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($hasCompletedAttempts)
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Recent Completed</h2>
                            <p class="mt-1 text-sm text-slate-600">Review your latest submitted attempts and final scores.</p>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($completedAttempts as $attempt)
                                @php
                                    $result = $attempt->result;
                                    $scoreVisibleAfterSubmit = (bool) ($attempt->form?->show_score_after_submit && filled($result?->total_scaled));
                                    $scorePublished = (bool) ($result?->is_published && filled($result?->total_scaled));
                                    $canShowScore = $scoreVisibleAfterSubmit || $scorePublished;
                                    $overallCefr = $result?->overallCefrLevel();
                                @endphp
                                <a href="{{ route('ept-online.attempt.finished', ['attempt' => $attempt->public_id]) }}" class="block rounded-2xl border border-slate-200 px-5 py-4 transition hover:border-slate-300 hover:bg-slate-50">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-slate-900">{{ $attempt->form?->title ?? 'Online Test Package' }}</div>
                                            <div class="mt-1 text-xs uppercase tracking-[0.14em] text-slate-500">
                                                Submitted {{ optional($attempt->submitted_at)?->format('d M Y H:i') ?? '-' }}
                                            </div>
                                            @if ($canShowScore)
                                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                                    @if ($overallCefr)
                                                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700">
                                                            {{ $overallCefr }}
                                                        </span>
                                                    @endif
                                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600">
                                                        Final Score Available
                                                    </span>
                                                </div>
                                            @else
                                                <div class="mt-3">
                                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-amber-700">
                                                        Pending Result
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex items-center justify-between gap-4 sm:justify-end">
                                            <div class="text-right">
                                                @if ($canShowScore)
                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Final Score</div>
                                                    <div class="mt-1 text-2xl font-black tracking-tight text-slate-950">
                                                        {{ $result->total_scaled }}
                                                    </div>
                                                @else
                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Status</div>
                                                    <div class="mt-1 text-sm font-semibold text-slate-700">Awaiting publication</div>
                                                @endif
                                            </div>
                                            <span class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                                                View
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </section>
</div>
@endsection
