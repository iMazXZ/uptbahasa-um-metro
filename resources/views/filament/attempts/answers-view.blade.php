@php
  /** @var \App\Models\BasicListeningAttempt $record */
  $record    = $getRecord();
  $questions = $record?->quiz?->questions ?? collect();
  $answers   = $record?->answers ?? collect();
@endphp

@if ($questions->isEmpty())
  <div class="text-sm text-gray-500 dark:text-gray-400">Belum ada soal untuk attempt ini.</div>
@else
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left border-b border-gray-200 dark:border-gray-700">
          <th class="py-2 pr-3 font-semibold w-12">#</th>
          <th class="py-2 pr-3 font-semibold w-20">Tipe</th>
          <th class="py-2 pr-3 font-semibold">Soal / Paragraf</th>
          <th class="py-2 pr-3 font-semibold w-28 text-center">Benar?</th>
        </tr>
      </thead>
      <tbody class="align-top">
        @foreach ($questions as $i => $q)
          @php
            $type = $q->type ?? 'unknown';
            $i1   = $i + 1;
          @endphp

          {{-- ===================== FIB PARAGRAPH ===================== --}}
          @if ($type === 'fib_paragraph')
            @php
              // Jawaban mentah & kunci
              $fibAnswersRaw = $answers->where('question_id', $q->id)->sortBy('blank_index');
              $keys = is_array($q->fib_answer_key ?? null) ? $q->fib_answer_key : [];

              // Normalisasi indeks: kunci 1..N vs jawaban 0..N-1
              $keysStartsAt1 = isset($keys[1]) && ! isset($keys[0]);
              $offset = $keysStartsAt1 ? 1 : 0;

              // Petakan jawaban ke indeks "tampil"
              $ansByIdx = [];
              foreach ($fibAnswersRaw as $fa) {
                  $displayIdx = (int) $fa->blank_index + $offset;
                  $ansByIdx[$displayIdx] = $fa;
              }

              // Indeks final yang akan ditampilkan
              $indices = collect(array_unique(array_merge(array_keys($keys), array_keys($ansByIdx))))->sort()->values();

              $allCorrect = $fibAnswersRaw->isNotEmpty() && $fibAnswersRaw->every(fn($a) => (bool)$a->is_correct);
              $blankCount = $indices->count();
            @endphp

            {{-- Baris utama --}}
            <tr class="border-b border-gray-100 dark:border-gray-800">
              <td class="py-2 pr-3 font-mono">{{ sprintf('%02d', $i1) }}</td>
              <td class="py-2 pr-3">
                <x-filament::badge color="warning">FIB</x-filament::badge>
              </td>
              <td class="py-2 pr-3">
                <div class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap break-words">
                  {{ $q->paragraph_text ?? '—' }}
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  {{ $blankCount }} blank{{ $blankCount === 1 ? '' : 's' }}
                </div>
              </td>
              <td class="py-2 pr-3 text-center">
                @if ($fibAnswersRaw->isEmpty())
                  <x-filament::badge color="gray">—</x-filament::badge>
                @elseif ($allCorrect)
                  <x-filament::badge color="success">
                    <x-filament::icon icon="heroicon-o-check" class="h-4 w-4" />
                  </x-filament::badge>
                @else
                  <x-filament::badge color="danger">
                    <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                  </x-filament::badge>
                @endif
              </td>
            </tr>

            {{-- Sub-tabel detail per-blank --}}
            <tr class="border-b border-gray-100/70 dark:border-gray-800/70">
              <td></td>
              <td colspan="3" class="pb-3 pr-3">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                  <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/40">
                      <tr class="text-left">
                        <th class="py-2 pl-3 pr-2 font-semibold w-24">No</th>
                        <th class="py-2 px-2 font-semibold">Jawaban</th>
                        <th class="py-2 px-2 font-semibold">Kunci</th>
                        <th class="py-2 pl-2 pr-3 font-semibold w-24 text-center">✓</th>
                      </tr>
                    </thead>
                    <tbody>
                      @if ($indices->isEmpty())
                        <tr>
                          <td colspan="4" class="py-3 px-3 text-gray-500">Tidak ada jawaban / kunci.</td>
                        </tr>
                      @else
                        @foreach ($indices as $idx)
                          @php
                            /** @var \App\Models\BasicListeningAnswer|null $fa */
                            $fa      = $ansByIdx[$idx] ?? null;
                            $ansText = $fa?->answer;
                            $isOk    = $fa?->is_correct;

                            $keyRaw   = $keys[$idx] ?? null;
                            $keyShown = is_array($keyRaw) ? implode(' / ', $keyRaw) : ($keyRaw ?? '—');

                            $badgeColor = $isOk === null ? 'gray' : ($isOk ? 'success' : 'danger');
                          @endphp
                          <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="py-1.5 pl-3 pr-2 font-mono text-xs">({{ $idx }})</td>
                            <td class="py-1.5 px-2">
                              @if ($ansText === null || $ansText === '')
                                <span class="text-gray-400">—</span>
                              @else
                                <span class="font-mono break-words">“{{ $ansText }}”</span>
                              @endif
                            </td>
                            <td class="py-1.5 px-2">
                              <span class="font-mono break-words">{{ $keyShown }}</span>
                            </td>
                            <td class="py-1.5 pl-2 pr-3 text-center">
                              <x-filament::badge :color="$badgeColor">
                                @if ($isOk === null)
                                  —
                                @elseif ($isOk)
                                  <x-filament::icon icon="heroicon-o-check" class="h-4 w-4" />
                                @else
                                  <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                                @endif
                              </x-filament::badge>
                            </td>
                          </tr>
                        @endforeach
                      @endif
                    </tbody>
                  </table>
                </div>
              </td>
            </tr>

          {{-- ===================== MULTIPLE CHOICE ===================== --}}
          @else
            @php
              $ans    = $answers->firstWhere('question_id', $q->id);
              $chosen = $ans->answer ?? null;
              $isOk   = (bool) ($ans->is_correct ?? false);
              $correct= $q->correct ?? null;
              $opts   = [
                'A' => $q->option_a ?? '—',
                'B' => $q->option_b ?? '—',
                'C' => $q->option_c ?? '—',
                'D' => $q->option_d ?? '—',
              ];
            @endphp

            <tr class="border-b border-gray-100 dark:border-gray-800">
              <td class="py-2 pr-3 font-mono">{{ sprintf('%02d', $i1) }}</td>
              <td class="py-2 pr-3">
                <x-filament::badge color="success">MC</x-filament::badge>
              </td>
              <td class="py-2 pr-3">
                <div class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap break-words">
                  {{ $q->question ?? '—' }}
                </div>
                <ul class="mt-1 text-sm leading-6">
                  @foreach ($opts as $key => $val)
                    @php
                      $isChosen  = $chosen === $key;
                      $isCorrect = $correct === $key;
                    @endphp
                    <li class="flex items-start gap-2">
                      <span class="font-mono text-xs mt-1">{{ $key }}.</span>
                      <span class="break-words">
                        {{ $val }}
                        @if ($isCorrect)
                          <x-filament::badge color="success" class="ml-2">Kunci</x-filament::badge>
                        @endif
                        @if ($isChosen && ! $isCorrect)
                          <x-filament::badge color="danger" class="ml-2">Dipilih</x-filament::badge>
                        @elseif ($isChosen && $isCorrect)
                          <x-filament::badge color="success" class="ml-2">Dipilih</x-filament::badge>
                        @endif
                      </span>
                    </li>
                  @endforeach
                </ul>
              </td>
              <td class="py-2 pr-3 text-center">
                @if ($chosen === null)
                  <x-filament::badge color="gray">—</x-filament::badge>
                @elseif ($isOk)
                  <x-filament::badge color="success">
                    <x-filament::icon icon="heroicon-o-check" class="h-4 w-4" />
                  </x-filament::badge>
                @else
                  <x-filament::badge color="danger">
                    <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                  </x-filament::badge>
                @endif
              </td>
            </tr>
          @endif
        @endforeach
      </tbody>
    </table>
  </div>
@endif
