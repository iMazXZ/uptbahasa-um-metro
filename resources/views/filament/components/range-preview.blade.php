{{-- resources/views/filament/components/range-preview.blade.php --}}
<div class="space-y-4">
    @if ($error)
        <div class="rounded-lg bg-red-50 p-4 text-red-700 dark:bg-red-900/30 dark:text-red-300">
            <div class="flex items-center gap-2">
                <x-heroicon-o-x-circle class="h-5 w-5" />
                <span>{{ $error }}</span>
            </div>
        </div>
    @else
        {{-- Found Students --}}
        @if (count($students) > 0)
            <div>
                <div class="mb-2 flex items-center gap-2 text-sm font-medium text-green-700 dark:text-green-400">
                    <x-heroicon-o-check-circle class="h-5 w-5" />
                    <span>Ditemukan: {{ count($students) }} mahasiswa</span>
                </div>
                <div class="max-h-60 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-gray-100 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-400">No</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-400">NPM</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-400">Nama</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($students as $index => $student)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-3 py-2 text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-3 py-2 font-mono text-gray-900 dark:text-white">{{ $student['srn'] }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $student['name'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-lg bg-yellow-50 p-4 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-exclamation-circle class="h-5 w-5" />
                    <span>Tidak ada mahasiswa yang ditemukan dalam range ini.</span>
                </div>
            </div>
        @endif

        {{-- Skipped NPMs --}}
        @if (count($skipped) > 0)
            <div>
                <div class="mb-2 flex items-center gap-2 text-sm font-medium" style="color: #dc2626;">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                    <span>Tidak ditemukan: {{ count($skipped) }} NPM</span>
                </div>
                <div class="rounded-lg p-3" style="background-color: #fef2f2;">
                    <div class="flex flex-wrap gap-2">
                        @foreach (array_slice($skipped, 0, 20) as $npm)
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: #fee2e2; color: #991b1b;">
                                {{ $npm }}
                            </span>
                        @endforeach
                        @if (count($skipped) > 20)
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" style="background-color: #f3f4f6; color: #4b5563;">
                                +{{ count($skipped) - 20 }} lainnya
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
