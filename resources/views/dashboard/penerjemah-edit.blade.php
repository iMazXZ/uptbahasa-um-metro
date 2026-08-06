{{-- resources/views/dashboard/penerjemah-edit.blade.php --}}
@extends('layouts.penerjemah')

@section('title', 'Kerjakan Terjemahan')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Kerjakan Terjemahan</h1>
            <p class="text-lg text-slate-500 mt-1">
                Pemohon: <span class="font-semibold text-slate-700">{{ $tugas->users->name ?? 'Tidak diketahui' }}</span>
            </p>
        </div>
        <a href="{{ route('dashboard.penerjemah.tugas') }}" 
           class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-200 text-slate-700 font-bold text-base hover:bg-slate-300 transition-colors">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali
        </a>
    </div>

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-check text-emerald-600"></i>
            </div>
            <p class="text-lg text-emerald-800 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-exclamation text-red-600"></i>
            </div>
            <p class="text-lg text-red-800 font-medium">{{ session('error') }}</p>
        </div>
    @endif

    {{-- STATUS BADGE --}}
    @php
        $statusColor = match($tugas->status) {
            'Disetujui' => 'bg-amber-100 text-amber-700 border-amber-300',
            'Diproses' => 'bg-blue-100 text-blue-700 border-blue-300',
            'Selesai' => 'bg-emerald-100 text-emerald-700 border-emerald-300',
            default => 'bg-slate-100 text-slate-700 border-slate-300'
        };
    @endphp
    <div class="flex items-center gap-4">
        <span class="inline-flex items-center px-4 py-2 rounded-xl text-lg font-bold border-2 {{ $statusColor }}">
            <i class="fa-solid fa-circle text-xs mr-2"></i>
            Status: {{ $tugas->status }}
        </span>
        <span class="text-lg text-slate-500">
            Diajukan: {{ optional($tugas->submission_date)->translatedFormat('d F Y') }}
        </span>
    </div>

    {{-- ABSTRAK SUMBER (READ-ONLY) --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
            <h2 class="text-xl font-bold text-slate-900">
                <i class="fa-solid fa-file-lines text-slate-400 mr-2"></i>
                Abstrak Sumber (Bahasa Indonesia)
            </h2>
            <p class="text-base text-slate-500 mt-1">
                {{ $tugas->source_word_count ?? 0 }} kata
            </p>
        </div>
        <div class="p-6">
            <div class="prose prose-lg max-w-none text-slate-800 leading-relaxed">
                {!! $tugas->source_text !!}
            </div>
        </div>
    </div>

    {{-- FORM TERJEMAHAN --}}
    <form action="{{ route('dashboard.penerjemah.update', $tugas) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-indigo-100 bg-indigo-50">
                <h2 class="text-xl font-bold text-indigo-900">
                    <i class="fa-solid fa-pen text-indigo-500 mr-2"></i>
                    Hasil Terjemahan (Bahasa Inggris)
                </h2>
                <p class="text-base text-indigo-600 mt-1">Masukkan hasil terjemahan Anda di bawah ini</p>
            </div>
            <div class="p-6">
                @error('translated_text')
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                        <p class="text-lg text-red-800 font-medium">{{ $message }}</p>
                    </div>
                @enderror

                {{-- Hidden input untuk Trix --}}
                <input type="hidden" 
                       id="translated_text" 
                       name="translated_text" 
                       value="{{ old('translated_text', $tugas->translated_text) }}">

                {{-- Trix Editor dengan styling besar untuk senior --}}
                @if($tugas->status !== 'Selesai')
                    <style>
                        /* Override Trix untuk tampilan lebih besar */
                        #penerjemah-trix-editor trix-toolbar {
                            background: #f8fafc;
                            border: 2px solid #e2e8f0;
                            border-radius: 1rem;
                            padding: 0.75rem;
                            margin-bottom: 0.5rem;
                        }
                        #penerjemah-trix-editor trix-toolbar .trix-button {
                            padding: 0.5rem 0.75rem;
                            font-size: 1rem;
                        }
                        #penerjemah-trix-editor trix-toolbar .trix-button--icon {
                            width: 2.5rem;
                            height: 2.5rem;
                        }
                        #penerjemah-trix-editor trix-editor {
                            min-height: 350px;
                            font-size: 1.125rem;
                            line-height: 1.75rem;
                            padding: 1.5rem;
                            border: 2px solid #c7d2fe;
                            border-radius: 1rem;
                            background: white;
                        }
                        #penerjemah-trix-editor trix-editor:focus {
                            border-color: #6366f1;
                            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
                        }
                    </style>
                    <div id="penerjemah-trix-editor">
                        <trix-editor 
                            input="translated_text"
                            class="trix-content"
                            placeholder="Ketik hasil terjemahan di sini...">
                        </trix-editor>
                    </div>
                @else
                    {{-- Jika sudah selesai, tampilkan read-only --}}
                    <div class="prose prose-lg max-w-none text-slate-800 leading-relaxed bg-slate-50 rounded-xl p-6 border border-slate-200">
                        {!! $tugas->translated_text !!}
                    </div>
                @endif

                <p class="text-base text-slate-500 mt-4">
                    <i class="fa-solid fa-circle-info text-slate-400 mr-1"></i>
                    Tips: Gunakan toolbar di atas untuk memformat teks (bold, italic, list, dll).
                </p>
            </div>

            @if($tugas->status !== 'Selesai')
                <div class="px-6 py-4 border-t border-indigo-100 bg-indigo-50 flex flex-col md:flex-row gap-4">
                    {{-- Tombol Simpan Draft --}}
                    <button type="submit" 
                            class="flex-1 inline-flex items-center justify-center gap-3 px-6 py-4 rounded-xl bg-slate-700 text-white font-bold text-lg hover:bg-slate-800 transition-colors shadow-lg">
                        <i class="fa-solid fa-floppy-disk text-xl"></i>
                        Simpan Draft
                    </button>
                </div>
            @endif
        </div>
    </form>

    {{-- INFO SETELAH SIMPAN --}}
    @if($tugas->status !== 'Selesai' && !empty($tugas->translated_text))
        <div class="bg-blue-50 rounded-2xl border border-blue-200 p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-circle-info text-xl text-blue-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-blue-900">Draft Tersimpan</h3>
                    <p class="text-base text-blue-700 mt-1">
                        Terjemahan Anda akan diperiksa oleh admin. Setelah admin memverifikasi, status akan berubah menjadi "Selesai" dan pemohon akan menerima notifikasi.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- INFO JIKA SUDAH SELESAI --}}
    @if($tugas->status === 'Selesai')
        <div class="bg-emerald-50 rounded-2xl border-2 border-emerald-300 p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-check-circle text-3xl text-emerald-600"></i>
            </div>
            <h3 class="text-xl font-bold text-emerald-900">Terjemahan Sudah Selesai</h3>
            <p class="text-base text-emerald-700 mt-2">
                Selesai pada: {{ optional($tugas->completion_date)->translatedFormat('d F Y, H:i') }}
            </p>
        </div>
    @endif

</div>
@endsection
