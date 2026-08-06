<x-filament-widgets::widget>
  <x-filament::section>
    @php
      $user = filament()->auth()->user();
    @endphp

    @role('tutor')
      <div
        class="mb-6 rounded-lg border border-green-300/60 bg-green-50 px-4 py-3 text-sm text-green-800
               dark:border-green-600/60 dark:bg-green-500/10 dark:text-green-400">
        <div class="flex items-start gap-2">
          <x-filament::icon icon="heroicon-o-academic-cap" class="h-5 w-5 mt-0.5"/>
          <p>
            Hai, <strong>{{ $user->name }}</strong> 👋 — kamu login sebagai
            <span class="font-semibold">Tutor</span>. Gunakan tautan cepat di bawah untuk
            mengelola mahasiswa binaan, connect code, dan memantau pengerjaan quiz.
          </p>
        </div>
      </div>
    @endrole

    {{-- =========================
         Role: Admin
       ========================= --}}
    @role('Admin')
      @php
        $pendingSurat    = \App\Models\EptSubmission::where('status', 'pending')->count();
        $pendingTerjemah = \App\Models\Penerjemahan::where('status', 'Menunggu')->count();
        $approvedTerjemah = \App\Models\Penerjemahan::where('status', 'Disetujui')->count();
        $terjemahColor = $pendingTerjemah > 0
            ? 'danger'
            : ($approvedTerjemah > 0 ? 'primary' : 'success');
        $terjemahBadge = $pendingTerjemah > 0
            ? $pendingTerjemah
            : ($approvedTerjemah > 0 ? $approvedTerjemah : null);

        // Pendaftaran EPT
        $pendingEpt = \App\Models\EptRegistration::where('status', 'pending')->count();
      @endphp

      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 mb-6">
        <x-filament::button
          tag="a"
          href="{{ route('filament.admin.resources.suratrekomendasi.index') }}"
          size="lg"
          :color="$pendingSurat > 0 ? 'danger' : 'success'"
          icon="heroicon-o-document-check"
          :badge="$pendingSurat ?: null"
          class="w-full justify-start"
        >
          Pengajuan Surat Rekomendasi
        </x-filament::button>

        <x-filament::button
          tag="a"
          href="{{ route('filament.admin.resources.penerjemahan.index') }}"
          size="lg"
          :color="$terjemahColor"
          icon="heroicon-o-language"
          :badge="$terjemahBadge"
          class="w-full justify-start"
        >
          Penerjemahan Abstrak
        </x-filament::button>

        <x-filament::button
          tag="a"
          href="{{ route('filament.admin.resources.ept-registrations.index') }}"
          size="lg"
          :color="$pendingEpt > 0 ? 'danger' : 'success'"
          icon="heroicon-o-academic-cap"
          :badge="$pendingEpt ?: null"
          class="w-full justify-start"
        >
          Pendaftaran EPT
        </x-filament::button>

        <x-filament::button
          tag="a"
          href="{{ route('filament.admin.resources.users.index') }}"
          size="lg"
          color="success"
          icon="heroicon-o-users"
          class="w-full justify-start"
        >
          Data User
        </x-filament::button>
      </div>
    @endrole

    {{-- =========================
         Role: tutor
       ========================= --}}
    @role('tutor')

      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 mb-2 mt-3">
        <x-filament::button tag="a" href="{{ route('filament.admin.pages.tutor-mahasiswa') }}" size="lg" color="success"
            class="w-full justify-start" icon="heroicon-o-user-group">
          Data Mahasiswa Diampu
        </x-filament::button>

        <x-filament::button tag="a" href="{{ route('filament.admin.resources.basic-listening-connect-codes.index') }}" size="lg" color="success"
            class="w-full justify-start" icon="heroicon-o-link">
          Connect Code
        </x-filament::button>

        <x-filament::button tag="a" href="{{ route('filament.admin.resources.basic-listening-attempts.index') }}" size="lg" color="success"
            class="w-full justify-start" icon="heroicon-o-clipboard-document-check">
          Data Quiz Dikerjakan
        </x-filament::button>

        <x-filament::button tag="a" href="{{ route('front.home') }}" size="lg" color="primary"
            class="w-full justify-start" icon="heroicon-o-home">
          Halaman Utama
        </x-filament::button>
      </div>
    @endrole

    {{-- Fallback bila tidak punya salah satu role di atas --}}
    @unlessrole('pendaftar|Admin|tutor')
      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
        <x-filament::button tag="a" href="{{ route('front.home') }}" size="lg" color="primary"
            class="w-full justify-start" icon="heroicon-o-home">
          Halaman Utama
        </x-filament::button>
      </div>
    @endunlessrole
  </x-filament::section>
</x-filament-widgets::widget>
