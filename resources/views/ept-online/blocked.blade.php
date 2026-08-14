@extends('layouts.front', ['hideNavbar' => true, 'hideFooter' => true])

@section('title', 'Tes Dijeda - UPT Bahasa UM Metro')
@section('translate_no', '1')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-um-blue via-um-dark-blue to-um-navy flex items-center justify-center p-4" style="margin-top:-4rem;">
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-5">
            <i class="fa-solid fa-pause text-2xl text-amber-600"></i>
        </div>
        <h1 class="text-xl font-black text-slate-900">Tes Dijeda oleh Pengawas</h1>
        <p class="text-sm text-slate-500 mt-2 leading-relaxed">
            {{ $blockedMessage }}
        </p>
        <div class="mt-6 p-4 rounded-2xl bg-slate-50 border border-slate-100">
            <p class="text-xs text-slate-400 mb-1">Halaman akan diperbarui otomatis</p>
            <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden">
                <div class="h-full rounded-full bg-amber-400 animate-pulse" style="width: 60%"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-refresh setiap 10 detik — saat pengawas lanjutkan, halaman reload otomatis
    setInterval(() => {
        window.location.reload();
    }, 10000);
</script>
@endsection
