<x-filament-panels::page>
    @if ($this->approvedSubmission)
        @php $reason = $this->latestSubmission->catatan_admin; @endphp
        <x-filament::section
            class="border-green-200 bg-green-50/60 dark:bg-green-900/20"
        >
            <x-slot name="heading">
                <span class="inline-flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-check-badge" class="h-5 w-5 text-green-600 dark:text-green-400" />
                    <span>Dokumen Siap Dicetak</span>
                </span>
            </x-slot>

            <div class="text-sm text-green-800 dark:text-green-200">
                Silakan <strong>Download Dokumen Surat Rekomendasi</strong> kemudian dicetak,
                lalu minta <strong>Cap Basah</strong> dan <strong>Legalisir</strong>
                ke Kantor Lembaga Bahasa di <strong>Kampus 3 Gedung FIKOM Lantai 2 UM Metro</strong> (bila diperlukan).

                @if($reason)
                    <br><br><span class="font-semibold">Alasan:</span><br> {{ $reason }}
                @endif
            </div>
        </x-filament::section>
    
    @elseif ($this->latestSubmission && $this->latestSubmission->status === 'rejected')
        @php $reason = $this->latestSubmission->catatan_admin; @endphp
        <x-filament::section
            class="border-rose-200 bg-rose-50/60 dark:bg-rose-900/20"
        >
            <x-slot name="heading">
                <span class="inline-flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-x-circle" class="h-5 w-5 text-rose-600 dark:text-rose-400"/>
                    <span>Pengajuan Ditolak</span>
                </span>
            </x-slot>
            <div class="text-sm text-rose-800 dark:text-rose-200">
                Mohon perbaiki data sesuai catatan staf di bawah, lalu ajukan kembali melalui formulir.
                @if($reason)
                    <br><br><span class="font-semibold">Alasan:</span><br> {{ $reason }}
                @endif
            </div>
        </x-filament::section>
    @endif
  <x-filament::section>
    <x-slot name="heading"><div class="text-center">Riwayat Pengajuan Surat Rekomendasi</div></x-slot>
    {{ $this->table }}
  </x-filament::section>

  @unless($this->hasSubmissions)
    <x-filament::section>
      <x-slot name="heading"><div class="text-center">Form Pengajuan Surat Rekomendasi</div></x-slot>
      {{ $this->form }}
      <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament::section>
  @endunless
</x-filament-panels::page>
