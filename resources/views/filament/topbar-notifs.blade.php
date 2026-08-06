@php
    $rekom = \App\Models\EptSubmission::where('status','pending')->count();
    $terjemah = \App\Models\Penerjemahan::where('status','Menunggu')->count();
@endphp

<div class="flex items-center gap-2">
    {{-- Surat Rekomendasi --}}
    <a href="{{ \App\Filament\Resources\EptSubmissionResource::getUrl() }}"
       class="relative inline-flex items-center p-2 rounded-lg hover:bg-gray-100">
        <x-filament::icon icon="heroicon-o-clipboard-document-check" class="w-6 h-6" />
        @if($rekom > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center
                         min-w-5 h-5 px-1 text-[11px] font-medium rounded-full
                         bg-warning-600 text-white">
                {{ $rekom }}
            </span>
        @endif
    </a>

    {{-- Penerjemahan --}}
    <a href="{{ \App\Filament\Resources\PenerjemahanResource::getUrl() }}"
       class="relative inline-flex items-center p-2 rounded-lg hover:bg-gray-100">
        <x-filament::icon icon="heroicon-o-language" class="w-6 h-6" />
        @if($terjemah > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center
                         min-w-5 h-5 px-1 text-[11px] font-medium rounded-full
                         bg-danger-600 text-white">
                {{ $terjemah }}
            </span>
        @endif
    </a>
</div>
