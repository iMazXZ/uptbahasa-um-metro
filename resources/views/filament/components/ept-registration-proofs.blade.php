<div class="grid gap-4">
    {{-- Bukti Pembayaran (semua mode) --}}
    <div>
        <p class="mb-2 text-sm font-semibold text-slate-700">
            <i class="heroicon-o-arrow-down-tray heroicon h-4 w-4 inline-block text-gray-400 mr-1"></i>
            Bukti Pembayaran
        </p>
        @if(filled($record->bukti_pembayaran))
            <a href="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}" target="_blank">
                <img src="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}"
                     alt="Bukti Pembayaran"
                     class="w-full rounded-xl border border-slate-200 shadow-sm">
            </a>
        @else
            <p class="text-sm text-slate-400">-</p>
        @endif
    </div>

    {{-- KTP & Selfie (khusus EPT Online) --}}
    @if($record->mode === \App\Models\EptRegistration::MODE_ONLINE)
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="mb-2 text-sm font-semibold text-slate-700">Foto KTP</p>
                @if(filled($record->foto_ktp))
                    <a href="{{ route('admin.ept-registrations.identity-photo', ['registration' => $record->id, 'type' => 'ktp']) }}" target="_blank">
                        <img src="{{ route('admin.ept-registrations.identity-photo', ['registration' => $record->id, 'type' => 'ktp']) }}"
                             alt="Foto KTP"
                             class="w-full rounded-xl border border-slate-200 shadow-sm">
                    </a>
                @else
                    <p class="text-sm text-slate-400">-</p>
                @endif
            </div>
            <div>
                <p class="mb-2 text-sm font-semibold text-slate-700">Foto Selfie</p>
                @if(filled($record->foto_selfie))
                    <a href="{{ route('admin.ept-registrations.identity-photo', ['registration' => $record->id, 'type' => 'selfie']) }}" target="_blank">
                        <img src="{{ route('admin.ept-registrations.identity-photo', ['registration' => $record->id, 'type' => 'selfie']) }}"
                             alt="Foto Selfie"
                             class="w-full rounded-xl border border-slate-200 shadow-sm">
                    </a>
                @else
                    <p class="text-sm text-slate-400">-</p>
                @endif
            </div>
        </div>
    @endif
</div>
