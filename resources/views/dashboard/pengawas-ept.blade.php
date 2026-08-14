{{-- resources/views/dashboard/pengawas-ept.blade.php --}}
@extends('layouts.dashboard')

@section('title', 'Dashboard Pengawas EPT')
@section('page-title', 'Pengawas EPT')

@section('content')
@php
    $hasTokens = ($tokens ?? collect())->isNotEmpty();
@endphp

<div class="space-y-6">

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 flex items-start gap-3">
            <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 flex items-start gap-3">
            <i class="fa-solid fa-circle-xmark text-rose-600 mt-0.5"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Info banner --}}
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
        <div class="flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl bg-um-blue text-white flex items-center justify-center shrink-0">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <h2 class="font-bold text-blue-900">Dashboard Pengawas EPT</h2>
                <p class="text-sm text-blue-800 mt-1">
                    Verifikasi identitas peserta sebelum tes dimulai, dan pantau peserta yang sedang mengerjakan tes secara live.
                </p>
            </div>
        </div>
    </div>

    @if(!$hasTokens)
        <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto">
                <i class="fa-solid fa-clipboard-list text-2xl text-slate-400"></i>
            </div>
            <h3 class="mt-4 font-bold text-slate-800">Belum Ada Tugas</h3>
            <p class="text-sm text-slate-500 mt-1">Anda belum ditugaskan ke sesi tes EPT Online mana pun. Silakan hubungi admin.</p>
        </div>
    @endif

    @foreach($tokens as $token)
        @php
            $registrations = $token->registrations ?? collect();

            $waitingVerify = $registrations->filter(fn ($r) => blank($r->proctor_verified_at))->count();
            $verified = $registrations->filter(fn ($r) => filled($r->proctor_verified_at));
            $tokenCode = $token->token_hint ?? '-';
        @endphp

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            {{-- Header token --}}
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-um-blue/10 text-um-blue flex items-center justify-center">
                        <i class="fa-solid fa-laptop-file"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800">
                            {{ $token->form?->code ?? 'Paket Tes' }} - {{ $token->form?->title ?? 'Tanpa judul' }}
                        </h3>
                        <p class="text-xs text-slate-500">
                            Token: <span class="font-mono font-semibold">{{ $tokenCode }}</span>
                            @if($token->group)
                                · Grup: {{ $token->group->name }}
                            @endif
                            @if($token->starts_at)
                                · {{ $token->starts_at->translatedFormat('d M Y H:i') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($waitingVerify > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-700 px-3 py-1 text-xs font-semibold">
                            <i class="fa-solid fa-hourglass-half"></i> {{ $waitingVerify }} menunggu verifikasi
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 px-3 py-1 text-xs font-semibold">
                        <i class="fa-solid fa-circle-check"></i> {{ $verified->count() }} terverifikasi
                    </span>
                </div>
            </div>

            {{-- Task list --}}
            @if($registrations->isEmpty())
                <div class="p-10 text-center text-sm text-slate-400">
                    Tidak ada peserta EPT Online di grup ini.
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th class="px-6 py-3">Peserta</th>
                            <th class="px-3 py-3">Identitas</th>
                            <th class="px-3 py-3">Status Verifikasi</th>
                            <th class="px-3 py-3">Status Tes</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($registrations as $registration)
                            @php
                                $u = $registration->user;
                                $isVerified = filled($registration->proctor_verified_at);

                                $attempt = \App\Models\EptOnlineAttempt::query()
                                    ->where('user_id', $u->id)
                                    ->latest('id')
                                    ->first();

                                $attemptStatus = $attempt
                                    ? ($attempt->isPaused() ? 'paused' : $attempt->status)
                                    : 'none';
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition">
                                {{-- Peserta --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-blue-50 text-um-blue flex items-center justify-center font-bold text-sm shrink-0">
                                            {{ strtoupper(substr($u->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-800 truncate">{{ $u->name ?? '-' }}</p>
                                            <p class="text-xs text-slate-500">{{ $u->srn ?? '-' }} · {{ $u->prody?->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Identitas (KTP/selfie) --}}
                                <td class="px-3 py-4">
                                    @if($registration->mode === \App\Models\EptRegistration::MODE_ONLINE)
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('dashboard.pengawas-ept.identity', ['registration' => $registration->id, 'type' => 'ktp']) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:border-um-blue hover:text-um-blue transition">
                                                <i class="fa-solid fa-id-card"></i> KTP
                                            </a>
                                            <a href="{{ route('dashboard.pengawas-ept.identity', ['registration' => $registration->id, 'type' => 'selfie']) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:border-um-blue hover:text-um-blue transition">
                                                <i class="fa-solid fa-user"></i> Selfie
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>

                                {{-- Status verifikasi --}}
                                <td class="px-3 py-4">
                                    @if($isVerified)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1 text-[11px] font-semibold">
                                            <i class="fa-solid fa-circle-check"></i> Terverifikasi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-700 px-2.5 py-1 text-[11px] font-semibold">
                                            <i class="fa-solid fa-hourglass-half"></i> Menunggu
                                        </span>
                                    @endif
                                </td>

                                {{-- Status tes --}}
                                <td class="px-3 py-4">
                                    @if($attemptStatus === 'in_progress')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 text-blue-700 px-2.5 py-1 text-[11px] font-semibold">
                                            <i class="fa-solid fa-play"></i> Berjalan
                                        </span>
                                    @elseif($attemptStatus === 'paused')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-700 px-2.5 py-1 text-[11px] font-semibold">
                                            <i class="fa-solid fa-pause"></i> Dijeda
                                        </span>
                                    @elseif($attemptStatus === 'submitted')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1 text-[11px] font-semibold">
                                            <i class="fa-solid fa-flag-checkered"></i> Selesai
                                        </span>
                                    @elseif($attemptStatus === 'disqualified')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 text-rose-700 px-2.5 py-1 text-[11px] font-semibold">
                                            <i class="fa-solid fa-ban"></i> Diskualifikasi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 text-slate-500 px-2.5 py-1 text-[11px] font-semibold">
                                            <i class="fa-solid fa-clock"></i> Belum mulai
                                        </span>
                                    @endif
                                </td>

                                {{-- Aksi --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        @if(!$isVerified)
                                            <form method="POST" action="{{ route('dashboard.pengawas-ept.verify', $registration->id) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1.5 rounded-lg bg-um-blue px-3 py-2 text-xs font-bold text-white hover:bg-um-dark-blue transition">
                                                    <i class="fa-solid fa-circle-check"></i> Sesuai · Lanjut
                                                </button>
                                            </form>
                                        @endif

                                        @if($attempt && in_array($attemptStatus, ['in_progress']))
                                            <form method="POST" action="{{ route('dashboard.pengawas-ept.attempt.pause', $attempt->id) }}"
                                                  onsubmit="return confirm('Jeda tes peserta ini? Waktu tes akan dihentikan sementara.')">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 hover:bg-amber-100 transition">
                                                    <i class="fa-solid fa-pause"></i> Jeda
                                                </button>
                                            </form>
                                        @endif

                                        @if($attempt && $attemptStatus === 'paused')
                                            <form method="POST" action="{{ route('dashboard.pengawas-ept.attempt.resume', $attempt->id) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition">
                                                    <i class="fa-solid fa-play"></i> Lanjutkan
                                                </button>
                                            </form>
                                        @endif

                                        @if($attempt && in_array($attemptStatus, ['in_progress', 'paused']))
                                            <form method="POST" action="{{ route('dashboard.pengawas-ept.attempt.disqualify', $attempt->id) }}"
                                                  onsubmit="return confirm('Diskualifikasi peserta ini? Tes tidak dapat dilanjutkan.')">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100 transition">
                                                    <i class="fa-solid fa-ban"></i> Batalkan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    @endforeach

</div>
@endsection
