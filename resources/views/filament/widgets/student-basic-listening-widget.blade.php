<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            {{-- HEADER --}}
            <div class="text-sm text-gray-700 leading-relaxed">
                Selamat, <strong class="text-gray-900">{{ $user->name }}.</strong>
                Kamu telah menyelesaikan kelas Basic Listening.
            </div>

            {{-- GRID STATS (3 kartu: Daily, Final, Total) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <x-filament::card class="h-full flex flex-col justify-center py-3">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Daily Score Total (Meeting 1–5)</div>
                    <div class="text-xl font-semibold text-gray-900">
                        {{ is_numeric($daily) ? number_format($daily, 2) : '—' }}
                    </div>
                </x-filament::card>

                <x-filament::card class="h-full flex flex-col justify-center py-3">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Final Test</div>
                    <div class="text-xl font-semibold text-gray-900">
                        {{ is_numeric($finalTest) ? number_format($finalTest, 0) : '—' }}
                    </div>
                </x-filament::card>

                <x-filament::card class="h-full flex flex-col justify-center py-3">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Total Score</div>
                    <div class="text-xl font-semibold text-gray-900">
                        @php $letter = $finalLetter ? strtoupper($finalLetter) : null; @endphp
                        {{ is_numeric($finalNumeric) ? number_format($finalNumeric, 2) : '—' }}
                        {!! $letter ? ' <span class="text-gray-500 text-base">(' . e($letter) . ')</span>' : '' !!}
                    </div>
                </x-filament::card>
            </div>

            {{-- SURVEY NOTICE --}}
            @if(($surveyRequired ?? false) && !($surveyDone ?? false))
                <div class="border border-amber-200 bg-amber-50 text-amber-800 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="space-y-0.5">
                            <div class="font-medium">Kuesioner Wajib</div>
                            <p class="text-sm">
                                Sebelum mengunduh sertifikat, silakan isi kuesioner akhir Basic Listening terlebih dahulu.
                            </p>
                        </div>
                        @if(!empty($surveyUrl))
                            <x-filament::button
                                tag="a"
                                href="{{ $surveyUrl }}"
                                color="warning"
                                icon="heroicon-o-pencil-square"
                            >
                                Isi Kuesioner
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ACTIONS --}}
            <div class="pt-2 border-t border-gray-100 flex flex-wrap items-center gap-3">
                @php $downloadEnabled = $canDownload ?? false; @endphp

                {{-- Preview --}}
                @if($downloadEnabled && !empty($previewUrl))
                    <x-filament::button
                        tag="a"
                        href="{{ $previewUrl }}"
                        color="gray"
                        size="sm"
                        icon="heroicon-o-eye"
                    >
                        Preview Sertifikat
                    </x-filament::button>
                @else
                    <x-filament::button color="gray" size="sm" icon="heroicon-o-eye" aria-disabled="true" disabled>
                        Preview Sertifikat
                    </x-filament::button>
                @endif

                {{-- Download --}}
                @if($downloadEnabled && !empty($downloadUrl))
                    <x-filament::button
                        tag="a"
                        href="{{ $downloadUrl }}"
                        color="success"
                        size="sm"
                        icon="heroicon-o-arrow-down-tray"
                    >
                        Download PDF Sertifikat
                    </x-filament::button>
                    <p class="text-xs text-gray-500">Sertifikat dapat diunduh.</p>
                @else
                    <x-filament::button color="gray" size="sm" icon="heroicon-o-lock-closed" aria-disabled="true" disabled>
                        Download PDF Sertifikat Tidak Tersedia
                    </x-filament::button>
                    <p class="text-xs text-gray-500">
                        @if(($surveyRequired ?? false) && !($surveyDone ?? false))
                            Silakan isi kuesioner terlebih dahulu.
                        @else
                            Hubungi tutor jika nilai Final Test belum diinput.
                        @endif
                    </p>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
