<div>
    @if(filled($record->bukti_pembayaran))
        <a href="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}" target="_blank" class="block">
            <img src="{{ Storage::disk('public')->url($record->bukti_pembayaran) }}"
                 alt="Bukti Pembayaran"
                 class="w-full rounded-xl border border-slate-200 shadow-sm">
        </a>
        <p class="mt-2 text-center text-xs text-slate-400">
            Klik gambar untuk membuka di tab baru
        </p>
    @else
        <p class="py-8 text-center text-sm text-slate-400">Tidak ada bukti pembayaran.</p>
    @endif
</div>
