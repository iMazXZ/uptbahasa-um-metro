@extends('layouts.front')
@section('title', 'Listening Preparation')
@section('hide_navbar', '1')
@section('hide_footer', '1')
@section('translate_no', '1')

@include('ept-online.partials.mobile-device-guard')
@include('ept-online.partials.exam-guard')

@php
    $partList = $availableListeningParts ?? [];
@endphp

@push('styles')
<style>
    body { padding-top: 0 !important; background: #f8fafc; }
    .prep-shell {
        min-height: 100vh;
        background:
            radial-gradient(circle at top right, rgba(14,165,233,0.12), transparent 30%),
            radial-gradient(circle at left, rgba(16,185,129,0.08), transparent 25%),
            #f8fafc;
    }
    .prep-topbar {
        position: fixed; inset: 0 0 auto 0; z-index: 50; height: 76px;
        backdrop-filter: blur(14px);
        background: rgba(255,255,255,0.9);
        border-bottom: 1px solid #e2e8f0;
        box-shadow: 0 10px 30px rgba(15,23,42,.06);
    }
    .prep-content { width: 100%; padding: 120px 24px 48px; }
    .prep-stage { width: 100%; max-width: 980px; margin: 0 auto; }
    .prep-panel {
        width: 100%;
        border-radius: 32px;
        border: 1px solid #e2e8f0;
        background: rgba(255,255,255,0.94);
        box-shadow: 0 18px 40px rgba(15,23,42,.08);
        overflow: hidden;
    }
    .prep-hero {
        padding: 34px 32px 28px;
        border-bottom: 1px solid #e2e8f0;
        background:
            linear-gradient(135deg, rgba(255,255,255,0.98), rgba(240,249,255,0.92));
    }
    .prep-hero-stack {
        display: grid;
        gap: 18px;
    }
    .prep-hero-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        border: 1px solid #dbeafe;
        background: #ffffff;
        padding: .72rem 1rem;
        font-size: .74rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
        color: #475569;
        box-shadow: 0 10px 20px rgba(15,23,42,.06);
    }
    .prep-grid {
        display: grid;
        gap: 18px;
        padding: 28px 32px 32px;
    }
    .prep-summary,
    .prep-note {
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .prep-summary {
        padding: 22px 24px;
    }
    .prep-note {
        padding: 18px 20px;
        background: linear-gradient(135deg, #ffffff, #f8fafc);
    }
    .prep-row {
        padding: 14px 0;
        border-top: 1px solid #e2e8f0;
    }
    .prep-row:first-child {
        padding-top: 0;
        border-top: none;
    }
    .prep-action {
        border-radius: 28px;
        border: 1px solid #0f172a;
        background: linear-gradient(180deg, #111827, #0f172a);
        box-shadow: 0 18px 40px rgba(15,23,42,.16);
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .prep-action-icon {
        display: inline-flex;
        height: 52px;
        width: 52px;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: rgba(255,255,255,.08);
        color: #ffffff;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.08);
    }
    .prep-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: .45rem .8rem;
        border: 1px solid #dbeafe;
        background: #eff6ff;
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
        color: #475569;
    }
    .prep-start-btn {
        display: inline-flex;
        width: 100%;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: #ffffff;
        padding: 15px 18px;
        font-size: .95rem;
        font-weight: 800;
        color: #0f172a;
        transition: all .18s ease;
        box-shadow: 0 10px 24px rgba(15,23,42,.18);
    }
    .prep-start-btn:hover {
        transform: translateY(-1px);
        background: #f8fafc;
    }
    @media (min-width: 900px) {
        .prep-hero-stack {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: end;
        }
        .prep-grid {
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr);
            align-items: start;
        }
    }
    @media (min-width: 1280px) {
        .prep-content { padding-left: 32px; padding-right: 32px; }
    }
</style>
@endpush

@section('content')
<div
    class="prep-shell"
    data-exam-guard
    data-integrity-url="{{ route('ept-online.attempt.integrity', ['attempt' => $attempt->public_id]) }}"
    data-integrity-page="listening_intro"
    data-integrity-section="{{ $section->type }}"
>
    <div class="prep-topbar">
        <div class="flex h-full items-center justify-between gap-4 px-4 lg:px-8">
            <div class="min-w-0">
                <div class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">EPT Online</div>
                <div class="truncate text-sm font-bold text-slate-900 sm:text-base">{{ $attempt->form?->title ?? 'Online Test Package' }}</div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Listening Preparation</div>
            </div>
            <div class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">
                {{ $section->duration_minutes }} Minutes
            </div>
        </div>
    </div>

    <div class="prep-content">
        <section class="prep-stage">
            <div class="prep-panel">
                <div class="prep-hero">
                    <div class="prep-hero-stack">
                        <div class="text-center lg:text-left">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Listening Section</div>
                            <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-[2rem]">
                                {{ $section->title }}
                            </h1>
                            <p class="mt-3 text-sm leading-7 text-slate-600 sm:text-base">
                                Audio starts from the section opening and continues automatically through Parts A, B, and C.
                            </p>
                        </div>

                        <div class="flex justify-center lg:justify-end">
                            <div class="prep-hero-badge">{{ $section->duration_minutes }} minutes total</div>
                        </div>
                    </div>
                </div>

                <div class="prep-grid">
                    <div class="space-y-4">
                        <div class="prep-summary">
                            <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Summary</div>

                            <div class="mt-4">
                                <div class="prep-row">
                                    <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Section</div>
                                    <div class="mt-2 text-base font-bold text-slate-900">{{ $section->title }}</div>
                                </div>

                                <div class="prep-row">
                                    <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Duration</div>
                                    <div class="mt-2 text-base font-bold text-slate-900">{{ $section->duration_minutes }} minutes</div>
                                </div>

                                @if ($partList !== [])
                                    <div class="prep-row">
                                        <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Part</div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($partList as $part)
                                                <span class="prep-chip">Part {{ $part }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="prep-note">
                            <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Audio</div>
                            <div class="mt-2 text-sm leading-7 text-slate-600">
                                Audio will begin from the start of the section as soon as you press the start button.
                            </div>
                        </div>
                    </div>

                    <div class="prep-action">
                        <div class="prep-action-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 15v-3a8 8 0 0 1 16 0v3" />
                                <path d="M6 15h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H6a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2Z" />
                                <path d="M16 15h2a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2h-2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1Z" />
                            </svg>
                        </div>
                        <div class="text-xs font-black uppercase tracking-[0.16em] text-emerald-300">Ready to Begin</div>
                        <h2 class="text-2xl font-black tracking-tight text-white">Start listening now</h2>
                        <p class="text-sm leading-7 text-slate-300">
                            Make sure your headset and volume are ready. Once you begin, the timer will start immediately.
                        </p>

                        <form method="POST" action="{{ route('ept-online.attempt.start-section', ['attempt' => $attempt->public_id]) }}" data-allow-unload="1" class="pt-1">
                            @csrf
                            <button type="submit" data-allow-unload="1" class="prep-start-btn">
                                Start Listening
                            </button>
                        </form>

                        <div class="text-xs font-semibold leading-6 text-slate-400">
                            Once started, the listening audio will play from the beginning of the section.
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
