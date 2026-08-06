{{-- resources/views/dashboard/translation/edit.blade.php --}}
@extends('layouts.dashboard')

@section('title', 'Perbaiki Permohonan')
@section('page-title', 'Perbaiki Permohonan')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Perbaiki Permohonan</h1>
            <p class="mt-1 text-sm text-slate-500">
                Perbarui data pengajuan yang ditolak atau tidak valid.
            </p>
        </div>
        <a href="{{ route('dashboard.translation') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
    </div>

    {{-- Error Alerts --}}
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
            <div class="flex items-center gap-2 mb-2 text-red-800 font-bold text-sm">
                <i class="fa-solid fa-circle-exclamation"></i>
                Mohon perbaiki kesalahan:
            </div>
            <ul class="list-disc list-inside text-xs text-red-700 space-y-1 ml-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Status Warning Banner --}}
    @php $status = $penerjemahan->status; @endphp
    @if(str_starts_with($status, 'Ditolak'))
        <div class="rounded-xl border border-red-200 bg-white p-5 shadow-sm border-l-4 border-l-red-500">
            <h3 class="text-sm font-bold text-red-700 flex items-center gap-2 mb-2">
                <i class="fa-solid fa-circle-xmark"></i>
                Alasan Penolakan
            </h3>
            <p class="text-sm text-slate-600 leading-relaxed">
                @if ($penerjemahan->rejection_reason)
                    {{ $penerjemahan->rejection_reason }}
                    <br>
                    <span class="text-xs text-slate-500">Silakan perbaiki data yang relevan, lalu simpan ulang.</span>
                @elseif ($status === 'Ditolak - Dokumen Tidak Valid')
                    Dokumen abstrak yang Anda ajukan dinilai <strong>tidak valid</strong>. Mohon periksa kembali konten abstrak Anda dan pastikan sesuai format, lalu simpan ulang.
                @elseif ($status === 'Ditolak - Pembayaran Tidak Valid')
                    Bukti pembayaran Anda dinilai <strong>tidak valid</strong> atau tidak terbaca. Mohon unggah ulang foto/scan bukti pembayaran yang jelas.
                @else
                    Permohonan ditolak dengan alasan: <span class="font-semibold">{{ $status }}</span>. Silakan perbaiki data yang relevan.
                @endif
            </p>
        </div>
    @endif

    <form action="{{ route('dashboard.translation.update', $penerjemahan) }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @csrf
        @method('PUT')

        {{-- Info Section --}}
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
             <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Formulir Perbaikan</h3>
                    <p class="text-xs text-slate-500">ID Pengajuan: #{{ $penerjemahan->id }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 sm:p-8 space-y-8">

            {{-- Upload Ulang Pembayaran --}}
            <div>
                <label class="block text-sm font-bold text-slate-800 mb-2">
                    Bukti Pembayaran @if(!$penerjemahan->bukti_pembayaran)<span class="text-red-500">* (Wajib Upload Ulang)</span>@endif
                </label>
                
                @if($penerjemahan->bukti_pembayaran)
                    @php
                        $currentUrl = \Illuminate\Support\Facades\Storage::url($penerjemahan->bukti_pembayaran);
                    @endphp
                    <div class="flex items-center gap-3 mb-3 p-3 bg-blue-50 border border-blue-100 rounded-lg max-w-md">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-blue-800 font-medium truncate">
                                Gambar Saat Ini Tersimpan
                            </p>

                            {{-- Preview gambar lama --}}
                            <div class="mt-1 flex items-center gap-3">
                                <img src="{{ $currentUrl }}"
                                    alt="Bukti pembayaran saat ini"
                                    class="h-12 w-12 rounded-lg object-cover border border-blue-100 shadow-sm">

                                <div class="flex flex-col">
                                    <a href="{{ $currentUrl }}" target="_blank"
                                    class="text-[11px] text-blue-600 underline hover:text-blue-800">
                                        Lihat gambar asli (buka tab baru)
                                    </a>
                                    <span class="text-[10px] text-blue-400">
                                        Gambar ini akan tetap digunakan jika Anda tidak mengunggah yang baru.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Warning: Bukti pembayaran sudah dihapus --}}
                    <div class="flex items-center gap-3 mb-3 p-3 bg-red-50 border border-red-200 rounded-lg max-w-md">
                        <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                        <div>
                            <p class="text-xs text-red-800 font-bold">Bukti Pembayaran Sebelumnya Dihapus</p>
                            <p class="text-[11px] text-red-600">Anda wajib mengunggah bukti pembayaran baru.</p>
                        </div>
                    </div>
                @endif

                <div class="relative group">
                    <div id="payment-dropzone-edit"
                        class="border-2 border-dashed {{ $penerjemahan->bukti_pembayaran ? 'border-slate-300' : 'border-red-300' }} rounded-xl p-6 text-center
                                hover:border-um-blue hover:bg-blue-50/50 transition-colors
                                flex flex-col items-center justify-center gap-2 cursor-pointer">
                        
                        <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-1
                                    text-slate-400 group-hover:text-um-blue group-hover:bg-blue-100 transition-colors">
                            <i class="fa-solid fa-upload"></i>
                        </div>

                        <p class="text-sm text-slate-600">
                            {{ $penerjemahan->bukti_pembayaran ? 'Klik untuk ganti file (Opsional)' : 'Klik untuk upload bukti pembayaran baru' }}
                        </p>
                        <p class="text-xs text-slate-400">
                            JPG, PNG (Maks. 8MB)
                        </p>

                        {{-- Preview file baru (opsional) --}}
                        <div id="payment-preview-wrapper-edit" class="mt-3 hidden">
                            <div class="flex items-center gap-3 justify-center">
                                <img id="payment-preview-edit"
                                    src=""
                                    alt="Preview bukti pembayaran baru"
                                    class="h-12 w-12 rounded-lg object-cover border border-slate-200 shadow-sm">
                                <div class="text-left">
                                    <p id="payment-filename-edit"
                                    class="text-xs font-semibold text-slate-700 truncate max-w-[180px]"></p>
                                    <p class="text-[11px] text-slate-400">
                                        File baru siap diunggah
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Input file menutupi area dropzone --}}
                        <input
                            id="bukti_pembayaran_input_edit"
                            type="file"
                            name="bukti_pembayaran"
                            accept="image/*"
                            {{ $penerjemahan->bukti_pembayaran ? '' : 'required' }}
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        >
                    </div>
                </div>

                @error('bukti_pembayaran')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Edit Abstrak --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-bold text-slate-800">
                        Teks Abstrak
                    </label>
                    <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded border border-slate-200">
                        <span id="word-count" class="font-bold text-um-blue">0</span> Kata
                    </span>
                </div>

                <div class="relative space-y-2">
                    {{-- Hidden input yang dikirim ke server --}}
                    <input
                        id="source_text"
                        type="hidden"
                        name="source_text"
                        value="{{ old('source_text', $penerjemahan->source_text ?? '') }}"
                    >

                    {{-- Trix editor untuk edit rich text --}}
                    <trix-editor
                        input="source_text"
                        class="trix-content block w-full rounded-xl border border-slate-300 text-sm leading-relaxed bg-white focus:border-um-blue focus:ring-um-blue"
                        placeholder="Edit teks abstrak di sini..."
                    ></trix-editor>
                </div>

                @error('source_text')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

        </div>

        {{-- Footer Actions --}}
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
             <a href="{{ route('dashboard.translation') }}"
               class="inline-flex items-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
                Batal
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-um-blue hover:bg-um-dark-blue text-white font-bold text-sm shadow-lg shadow-blue-900/20 transition-all hover:scale-[1.02]">
                <i class="fa-solid fa-rotate"></i>
                Perbarui Data
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropzone   = document.getElementById('payment-dropzone-edit');
        const input      = document.getElementById('bukti_pembayaran_input_edit');
        const previewBox = document.getElementById('payment-preview-wrapper-edit');
        const previewImg = document.getElementById('payment-preview-edit');
        const fileNameEl = document.getElementById('payment-filename-edit');

        if (dropzone && input && previewBox && previewImg && fileNameEl) {

            function handleFile(file) {
                if (!file) return;

                // Nama file
                fileNameEl.textContent = file.name;

                // Kalau bukan image, tetap tampilkan info nama saja
                if (!file.type.startsWith('image/')) {
                    previewImg.src = '';
                    previewBox.classList.remove('hidden');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewBox.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }

            input.addEventListener('change', function (e) {
                const file = e.target.files && e.target.files[0];
                handleFile(file);
            });

            // Highlight saat drag & drop
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('border-um-blue', 'bg-blue-50/50');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('border-um-blue', 'bg-blue-50/50');
                });
            });

            // Drop file ke area
            dropzone.addEventListener('drop', function (e) {
                const dt = e.dataTransfer;
                if (!dt || !dt.files || !dt.files[0]) return;

                input.files = dt.files;
                handleFile(dt.files[0]);
            });
        }
    });
</script>

@endsection
