<x-filament-widgets::widget>
  <x-filament::section>
    @php
      $terPending   = $terjemahan['pending_count'] ?? 0;
      $terApproved  = $terjemahan['approved_count'] ?? 0;
      $terProcess   = $terjemahan['process_count'] ?? 0;
      $surPending   = $surat['pending_count'] ?? 0;

      $terNumClass   = $terPending > 0 ? 'text-danger-600' : 'text-success-600';
      $terBadgeClass = $terPending > 0 ? 'bg-danger-50 text-danger-700' : 'bg-primary-50 text-primary-700';
      $terLabel      = $terPending > 0 ? 'Perlu review' : 'Terkendali';

      $surNumClass   = $surPending > 0 ? 'text-danger-600' : 'text-success-600';
      $surBadgeClass = $surPending > 0 ? 'bg-danger-50 text-danger-700' : 'bg-primary-50 text-primary-700';
      $surLabel      = $surPending > 0 ? 'Urgent' : 'Tidak ada antrean';
    @endphp

    <div class="flex items-center justify-between mb-4">
      <div>
        <p class="text-sm text-gray-500">Dashboard Admin</p>
      </div>
      <div class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">
        <x-filament::icon icon="heroicon-o-shield-check" class="h-4 w-4" />
        Admin
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Penerjemahan</p>
            <p class="text-2xl font-semibold {{ $terNumClass }}">{{ $terPending }} menunggu</p>
            <p class="text-xs text-gray-500">Disetujui: {{ $terApproved }} · Diproses: {{ $terProcess }}</p>
          </div>
          <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $terBadgeClass }}">{{ $terLabel }}</div>
        </div>

        <div class="mt-4 space-y-3">
          @forelse ($terjemahan['latest'] as $item)
            <div class="flex items-start justify-between rounded-lg border border-gray-100 bg-gray-50/70 p-3">
              <div class="space-y-1">
                <p class="text-sm font-semibold text-gray-900">
                  {{ $item->users?->name ?? '—' }}
                </p>
                <p class="text-xs text-gray-500">Status: {{ $item->status }}</p>
              </div>
              <p class="text-xs text-gray-500">{{ optional($item->created_at)?->diffForHumans() }}</p>
            </div>
          @empty
            <p class="text-sm text-gray-500">Tidak ada antrean.</p>
          @endforelse
        </div>
      </div>

      <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Surat Rekomendasi</p>
            <p class="text-2xl font-semibold {{ $surNumClass }}">{{ $surPending }} menunggu</p>
            <p class="text-xs text-gray-500">{{ $surPending > 0 ? 'Prioritas review' : 'Tidak ada antrean' }}</p>
          </div>
          <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $surBadgeClass }}">{{ $surLabel }}</div>
        </div>

        <div class="mt-4 space-y-3">
          @forelse ($surat['latest'] as $item)
            <div class="flex items-start justify-between rounded-lg border border-gray-100 bg-gray-50/70 p-3">
              <div class="space-y-1">
                <p class="text-sm font-semibold text-gray-900">
                  {{ $item->user?->name ?? '—' }}
                </p>
                <p class="text-xs text-gray-500">Status: {{ ucfirst($item->status) }}</p>
              </div>
              <p class="text-xs text-gray-500">{{ optional($item->created_at)?->diffForHumans() }}</p>
            </div>
          @empty
            <p class="text-sm text-gray-500">Tidak ada antrean.</p>
          @endforelse
        </div>
      </div>
    </div>
  </x-filament::section>
</x-filament-widgets::widget>
