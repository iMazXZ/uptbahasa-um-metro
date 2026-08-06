<x-filament-widgets::widget>
  <x-filament::section>
    @php
      $avg = $avgScoreWeek !== null ? number_format($avgScoreWeek, 1) : '—';
      $delta = ($attemptsThisWeek ?? 0) - ($attemptsLastWeek ?? 0);
      $deltaPrefix = $delta > 0 ? '+' : '';
      $deltaColor = $delta > 0
        ? 'text-primary-600 bg-primary-50 border-primary-100'
        : ($delta < 0 ? 'text-danger-600 bg-danger-50 border-danger-100' : 'text-gray-600 bg-gray-50 border-gray-200');
    @endphp

    <div class="flex items-center justify-between mb-4">
      <div>
        <p class="text-sm text-gray-500">Basic Listening</p>
        <h3 class="text-lg font-semibold text-gray-900">Ringkasan Mingguan</h3>
      </div>
      <div class="inline-flex items-center gap-1 rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold text-gray-700">
        <x-filament::icon icon="heroicon-o-chart-bar" class="h-4 w-4" />
        Monitoring
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
      <x-filament::card class="border border-gray-200 shadow-sm">
        <div class="flex items-center gap-3">
          <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-8 w-8 text-primary-600" />
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <p class="text-sm text-gray-600">Attempt minggu ini</p>
              <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-semibold {{ $deltaColor }}">
                {{ $deltaPrefix }}{{ $delta }} vs last week
              </span>
            </div>
            <p class="text-3xl font-semibold text-gray-900">{{ $attemptsThisWeek }}</p>
            <p class="text-xs text-gray-500">Termasuk re-submit dan paksa submit.</p>
          </div>
        </div>
      </x-filament::card>

      <x-filament::card class="border border-gray-200 shadow-sm">
        <div class="flex items-center gap-3">
          <x-filament::icon icon="heroicon-o-sparkles" class="h-8 w-8 text-primary-600" />
          <div class="flex-1">
            <p class="text-sm text-gray-600">Rata skor</p>
            <p class="text-3xl font-semibold text-gray-900">{{ $avg }}</p>
            <p class="text-xs text-gray-500">Hanya attempt yang sudah submit & punya skor.</p>
          </div>
        </div>
      </x-filament::card>
    </div>

    <div class="mt-6">
      <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
          <x-filament::icon icon="heroicon-o-bolt" class="h-4 w-4 text-primary-600" />
          Aktivitas per Prodi
        </h4>
        <a href="{{ route('filament.admin.resources.basic-listening-attempts.index') }}" class="text-xs font-semibold text-primary-700 hover:underline">
          Lihat semua
        </a>
      </div>
      @if ($prodyStats->isEmpty())
        <p class="text-sm text-gray-500">Belum ada attempt yang tersubmit.</p>
      @else
        <div class="grid gap-3 md:grid-cols-2">
          @foreach ($prodyStats as $item)
            <div class="rounded-lg border border-gray-100 bg-white shadow-sm p-3 flex items-start justify-between">
              <div class="space-y-1">
                <p class="text-sm font-semibold text-gray-900">{{ $item->prody_name ?? 'Prodi -' }}</p>
                @php
                  $delta = ($item->attempt_this_week ?? 0) - ($item->attempt_last_week ?? 0);
                  $deltaPrefix = $delta > 0 ? '+' : '';
                  $deltaColor = $delta > 0
                    ? 'text-primary-600 bg-primary-50 border-primary-100'
                    : ($delta < 0 ? 'text-danger-600 bg-danger-50 border-danger-100' : 'text-gray-600 bg-gray-50 border-gray-200');
                @endphp
                <div class="flex items-center gap-2 text-xs text-gray-500">
                  <span>Attempt minggu ini: {{ $item->attempt_this_week ?? 0 }}</span>
                  <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 font-semibold {{ $deltaColor }}">
                    {{ $deltaPrefix }}{{ $delta }} vs last week
                  </span>
                </div>
                <p class="text-xs text-gray-400">Total tersubmit: {{ $item->attempt_count }}</p>
              </div>
              <p class="text-xs text-gray-500">
                {{ optional($item->latest_submitted)?->diffForHumans() ?? '—' }}
              </p>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </x-filament::section>
</x-filament-widgets::widget>
