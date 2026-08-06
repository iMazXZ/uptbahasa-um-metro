<?php

namespace App\Filament\Resources\BasicListeningLegacyScoreResource\Pages;

use App\Filament\Resources\BasicListeningLegacyScoreResource;
use App\Imports\BasicListeningLegacyScoreImport;
use App\Models\BasicListeningLegacyScore;
use App\Models\User;
use App\Support\LegacyBasicListeningScores;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListBasicListeningLegacyScores extends ListRecords
{
    protected static string $resource = BasicListeningLegacyScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Template CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $content = implode(PHP_EOL, [
                        'NPM;NAMA;PRODI;TAHUN;NILAI',
                        '19210001;NAMA MAHASISWA;Ilmu Komunikasi;2024;78',
                        '19210002;NAMA MAHASISWA 2;Akuntansi;2024;82',
                    ]) . PHP_EOL;

                    return response()->streamDownload(function () use ($content): void {
                        echo $content;
                    }, 'nilai-manual-basic-listening-template.csv', [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),
            Actions\Action::make('previewCsv')
                ->label('Preview CSV')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->slideOver()
                ->modalHeading('Preview CSV Nilai Manual')
                ->modalSubmitActionLabel('Tutup Preview')
                ->steps([
                    Forms\Components\Wizard\Step::make('Data CSV')
                        ->schema([
                            Forms\Components\FileUpload::make('file')
                                ->label('File CSV')
                                ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', '.csv'])
                                ->required()
                                ->disk('local')
                                ->directory('temp-imports')
                                ->afterStateUpdated(function (Forms\Components\FileUpload $component, Set $set): void {
                                    $component->saveUploadedFiles();

                                    $state = $component->getState();
                                    $resolvedPath = $this->resolveUploadedFilePath($state);

                                    $set('resolved_file_path', $resolvedPath);
                                    $set('preview_payload', null);
                                }),
                            Forms\Components\TextInput::make('source_year')
                                ->label('Tahun Data (opsional)')
                                ->numeric()
                                ->minValue(2010)
                                ->maxValue((int) now()->year + 1)
                                ->helperText('Jika CSV tidak punya kolom tahun, nilai ini dipakai sebagai fallback.'),
                            Forms\Components\Hidden::make('resolved_file_path')
                                ->dehydrated(),
                            Forms\Components\Hidden::make('preview_payload')
                                ->dehydrated(),
                        ])
                        ->afterValidation(function (Get $get, Set $set): void {
                            $resolvedPath = $this->resolveUploadedFilePath($get('file'));
                            if ($resolvedPath === null) {
                                throw ValidationException::withMessages([
                                    'data.file' => 'File tidak ditemukan. Silakan upload ulang.',
                                ]);
                            }

                            $sourceYear = $get('source_year');
                            try {
                                $preview = $this->buildPreviewSummary(
                                    filePath: $resolvedPath,
                                    defaultYear: is_numeric($sourceYear)
                                        ? (int) $sourceYear
                                        : null,
                                );
                            } finally {
                                if (is_file($resolvedPath)) {
                                    @unlink($resolvedPath);
                                }
                            }

                            $set('resolved_file_path', null);
                            $set('preview_payload', json_encode($preview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        }),
                    Forms\Components\Wizard\Step::make('Preview')
                        ->schema([
                            Forms\Components\Placeholder::make('preview')
                                ->label('Ringkasan Import')
                                ->content(function (Get $get): HtmlString {
                                    $payload = json_decode((string) ($get('preview_payload') ?? ''), true);

                                    return new HtmlString(view('filament.basic-listening-legacy-score.preview', [
                                        'preview' => is_array($payload) ? $payload : null,
                                    ])->render());
                                })
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data): void {
                    $filePath = isset($data['resolved_file_path']) && is_string($data['resolved_file_path'])
                        ? trim($data['resolved_file_path'])
                        : $this->resolveUploadedFilePath($data['file'] ?? null);

                    if ($filePath !== null && is_file($filePath)) {
                        @unlink($filePath);
                    }
                }),
            Actions\Action::make('importCsv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->slideOver()
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('File CSV')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', '.csv'])
                        ->required()
                        ->disk('local')
                        ->directory('temp-imports'),
                    Forms\Components\TextInput::make('source_year')
                        ->label('Tahun Data (opsional)')
                        ->numeric()
                        ->minValue(2010)
                        ->maxValue((int) now()->year + 1)
                        ->helperText('Jika CSV tidak punya kolom tahun, nilai ini dipakai sebagai fallback.'),
                ])
                ->action(function (array $data): void {
                    $filePath = $this->resolveUploadedFilePath($data['file'] ?? null);
                    if ($filePath === null) {
                        Notification::make()
                            ->title('File tidak ditemukan')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $defaultYear = isset($data['source_year']) && is_numeric($data['source_year']) ? (int) $data['source_year'] : null;
                        $importStartedAt = now();
                        $importer = new BasicListeningLegacyScoreImport();
                        $summary = $importer->import(
                            filePath: $filePath,
                            defaultYear: $defaultYear,
                        );
                        $importFinishedAt = now();
                        $reportPath = $this->storeImportReport(
                            fileName: basename($filePath),
                            defaultYear: $defaultYear,
                            startedAt: $importStartedAt->toDateTimeString(),
                            finishedAt: $importFinishedAt->toDateTimeString(),
                            reportSummary: $summary,
                        );
                        $this->storeLastImportMetadata(
                            fileName: basename($filePath),
                            defaultYear: $defaultYear,
                            startedAt: $importStartedAt->toDateTimeString(),
                            finishedAt: $importFinishedAt->toDateTimeString(),
                            reportPath: $reportPath,
                            reportSummary: $summary,
                            undoPayload: $importer->undoPayload(),
                        );

                        @unlink($filePath);

                        $lines = [
                            'Import nilai manual selesai.',
                            "Total baris: {$summary['total_rows']}",
                            "Baris valid: {$summary['valid_rows']}",
                            "Baris baru: {$summary['imported_rows']}",
                            "Baris update: {$summary['updated_rows']}",
                            "Baris konflik/dilewati: {$summary['skipped_rows']}",
                            "User tersinkron: {$summary['synced_users']}",
                            "Jika salah data: gunakan tombol 'Undo Import Terakhir'.",
                        ];

                        foreach (array_slice($summary['reason_counts'] ?? [], 0, 5, true) as $reason => $count) {
                            $lines[] = "- {$reason}: {$count}";
                        }

                        Notification::make()
                            ->title('Import berhasil')
                            ->body(implode(PHP_EOL, $lines))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Import gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('importHistory')
                ->label('Riwayat Import')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->slideOver()
                ->modalHeading('Riwayat Import Nilai Manual')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(fn (): \Illuminate\Contracts\View\View => view('filament.basic-listening-legacy-score.import-history', [
                    'reports' => $this->getRecentImportReports(),
                ])),
            Actions\Action::make('undoLastImport')
                ->label('Undo Import Terakhir')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->hasLastImportMetadata())
                ->modalHeading('Batalkan Import Terakhir?')
                ->modalDescription(fn (): string => $this->buildUndoImportDescription())
                ->modalSubmitActionLabel('Undo')
                ->action(function (): void {
                    $this->undoLastImport();
                }),
            Actions\CreateAction::make(),
        ];
    }

    private function buildPreviewSummary(string $filePath, ?int $defaultYear): array
    {
        $preview = (new BasicListeningLegacyScoreImport())->preview(
            filePath: $filePath,
            defaultYear: $defaultYear,
        );

        return [
            'file_name' => basename($filePath),
            'default_year' => $defaultYear,
            'summary' => $preview,
        ];
    }

    private function storeImportReport(
        string $fileName,
        ?int $defaultYear,
        string $startedAt,
        string $finishedAt,
        array $reportSummary,
    ): string {
        $path = $this->getImportReportPath();

        $payload = [
            'saved_at' => now()->toDateTimeString(),
            'actor_id' => auth()->id(),
            'actor_name' => auth()->user()?->name,
            'file_name' => $fileName,
            'default_year' => $defaultYear,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'report_summary' => $reportSummary,
        ];

        Storage::disk('local')->put(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}'
        );

        return $path;
    }

    private function storeLastImportMetadata(
        string $fileName,
        ?int $defaultYear,
        string $startedAt,
        string $finishedAt,
        string $reportPath,
        array $reportSummary,
        array $undoPayload,
    ): void {
        try {
            $payload = [
                'saved_at' => now()->toDateTimeString(),
                'file_name' => $fileName,
                'default_year' => $defaultYear,
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'report_path' => $reportPath,
                'report_summary' => [
                    'total_rows' => (int) ($reportSummary['total_rows'] ?? 0),
                    'valid_rows' => (int) ($reportSummary['valid_rows'] ?? 0),
                    'imported_rows' => (int) ($reportSummary['imported_rows'] ?? 0),
                    'updated_rows' => (int) ($reportSummary['updated_rows'] ?? 0),
                    'skipped_rows' => (int) ($reportSummary['skipped_rows'] ?? 0),
                    'conflict_rows' => (int) ($reportSummary['conflict_rows'] ?? 0),
                    'synced_users' => (int) ($reportSummary['synced_users'] ?? 0),
                ],
                'undo_payload' => [
                    'created_record_ids' => array_values(array_unique(array_map('intval', Arr::wrap($undoPayload['created_record_ids'] ?? [])))),
                    'updated_record_snapshots' => array_values(array_filter(
                        Arr::wrap($undoPayload['updated_record_snapshots'] ?? []),
                        fn (mixed $snapshot): bool => is_array($snapshot) && isset($snapshot['id'])
                    )),
                    'synced_user_snapshots' => array_values(array_filter(
                        Arr::wrap($undoPayload['synced_user_snapshots'] ?? []),
                        fn (mixed $snapshot): bool => is_array($snapshot) && isset($snapshot['id'])
                    )),
                ],
            ];

            Storage::disk('local')->put(
                $this->getLastImportMetadataPath(),
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}'
            );
        } catch (\Throwable) {
            // Ignore metadata failure, import has already succeeded.
        }
    }

    private function getImportReportsDirectory(): string
    {
        return 'import-reports/basic-listening-legacy';
    }

    private function getImportReportPath(): string
    {
        return $this->getImportReportsDirectory() . '/import-' . now()->format('Ymd_His') . '-' . Str::lower(Str::random(6)) . '.json';
    }

    private function getLastImportMetadataPath(): string
    {
        return 'import-reports/basic-listening-legacy-last-import.json';
    }

    /** @return array<int, array<string, mixed>> */
    private function getRecentImportReports(int $limit = 10): array
    {
        $directory = $this->getImportReportsDirectory();
        if (! Storage::disk('local')->exists($directory)) {
            return [];
        }

        return collect(Storage::disk('local')->files($directory))
            ->filter(fn (string $path): bool => str_ends_with($path, '.json'))
            ->map(function (string $path): ?array {
                $decoded = json_decode((string) Storage::disk('local')->get($path), true);
                if (! is_array($decoded)) {
                    return null;
                }

                $decoded['path'] = $path;

                return $decoded;
            })
            ->filter(fn (?array $report): bool => is_array($report))
            ->sortByDesc(fn (array $report): string => (string) ($report['saved_at'] ?? $report['started_at'] ?? ''))
            ->take($limit)
            ->values()
            ->all();
    }

    private function getLastImportMetadata(): ?array
    {
        $path = $this->getLastImportMetadataPath();
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $decoded = json_decode((string) Storage::disk('local')->get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function hasLastImportMetadata(): bool
    {
        return $this->getLastImportMetadata() !== null;
    }

    private function buildUndoImportDescription(): string
    {
        $metadata = $this->getLastImportMetadata();
        if ($metadata === null) {
            return 'Belum ada metadata import yang bisa di-undo.';
        }

        $undoPayload = is_array($metadata['undo_payload'] ?? null) ? $metadata['undo_payload'] : [];
        $createdIds = array_values(array_filter(
            array_map('intval', Arr::wrap($undoPayload['created_record_ids'] ?? [])),
            fn (int $id): bool => $id > 0
        ));
        $createdLookup = array_flip($createdIds);

        $updatedSnapshots = array_values(array_filter(
            Arr::wrap($undoPayload['updated_record_snapshots'] ?? []),
            fn (mixed $snapshot): bool => is_array($snapshot)
                && isset($snapshot['id'])
                && ! isset($createdLookup[(int) $snapshot['id']])
        ));
        $userSnapshots = array_values(array_filter(
            Arr::wrap($undoPayload['synced_user_snapshots'] ?? []),
            fn (mixed $snapshot): bool => is_array($snapshot) && isset($snapshot['id'])
        ));

        $summary = is_array($metadata['report_summary'] ?? null) ? $metadata['report_summary'] : [];
        $fileName = (string) ($metadata['file_name'] ?? '-');
        $defaultYear = $metadata['default_year'] ?? null;

        return implode(PHP_EOL, [
            "File: {$fileName}",
            $defaultYear ? "Fallback tahun: {$defaultYear}" : 'Fallback tahun: mengikuti CSV / nama file',
            'Baris valid import: ' . max((int) ($summary['valid_rows'] ?? 0), count($createdIds) + count($updatedSnapshots)),
            'Record baru yang akan dihapus: ' . max((int) ($summary['imported_rows'] ?? 0), count($createdIds)),
            'Record lama yang akan dipulihkan: ' . max((int) ($summary['updated_rows'] ?? 0), count($updatedSnapshots)),
            'User yang nilainya akan dipulihkan: ' . max((int) ($summary['synced_users'] ?? 0), count($userSnapshots)),
            'Hanya import terakhir yang akan dibatalkan.',
        ]);
    }

    private function undoLastImport(): void
    {
        $metadata = $this->getLastImportMetadata();

        if ($metadata === null) {
            Notification::make()
                ->title('Tidak ada import untuk di-undo')
                ->warning()
                ->send();

            return;
        }

        $undoPayload = is_array($metadata['undo_payload'] ?? null) ? $metadata['undo_payload'] : [];
        $createdIds = array_values(array_unique(array_filter(
            array_map('intval', Arr::wrap($undoPayload['created_record_ids'] ?? [])),
            fn (int $id): bool => $id > 0
        )));
        $createdLookup = array_flip($createdIds);

        $updatedSnapshots = collect(Arr::wrap($undoPayload['updated_record_snapshots'] ?? []))
            ->filter(fn (mixed $snapshot): bool => is_array($snapshot) && isset($snapshot['id']))
            ->mapWithKeys(fn (array $snapshot): array => [(int) $snapshot['id'] => $snapshot])
            ->reject(fn (array $snapshot, int $id): bool => isset($createdLookup[$id]));

        $userSnapshots = collect(Arr::wrap($undoPayload['synced_user_snapshots'] ?? []))
            ->filter(fn (mixed $snapshot): bool => is_array($snapshot) && isset($snapshot['id']))
            ->mapWithKeys(fn (array $snapshot): array => [(int) $snapshot['id'] => $snapshot]);

        if ($createdIds === [] && $updatedSnapshots->isEmpty() && $userSnapshots->isEmpty()) {
            Storage::disk('local')->delete($this->getLastImportMetadataPath());

            Notification::make()
                ->title('Metadata import kosong')
                ->body('Tidak ada data import terakhir yang tersimpan untuk di-undo.')
                ->warning()
                ->send();

            return;
        }

        $deleted = 0;
        $restoredRecords = 0;
        $restoredUsers = 0;

        DB::transaction(function () use ($createdIds, $updatedSnapshots, $userSnapshots, &$deleted, &$restoredRecords, &$restoredUsers): void {
            if ($createdIds !== []) {
                $deleted = BasicListeningLegacyScore::query()
                    ->whereIn('id', $createdIds)
                    ->delete();
            }

            foreach ($updatedSnapshots as $snapshot) {
                $recordId = (int) $snapshot['id'];
                $record = BasicListeningLegacyScore::query()->find($recordId) ?? new BasicListeningLegacyScore();

                if (! $record->exists) {
                    $record->id = $recordId;
                }

                $record->forceFill([
                    'srn' => $snapshot['srn'] ?? null,
                    'srn_normalized' => $snapshot['srn_normalized'] ?? null,
                    'name' => $snapshot['name'] ?? null,
                    'name_normalized' => $snapshot['name_normalized'] ?? null,
                    'study_program' => $snapshot['study_program'] ?? null,
                    'source_year' => $snapshot['source_year'] ?? null,
                    'score' => $snapshot['score'] ?? null,
                    'grade' => $snapshot['grade'] ?? null,
                    'source_file' => $snapshot['source_file'] ?? null,
                    'meta' => $snapshot['meta'] ?? null,
                ]);
                $record->save();
                $restoredRecords++;
            }

            if ($userSnapshots->isNotEmpty()) {
                $users = User::query()
                    ->whereIn('id', $userSnapshots->keys()->all())
                    ->get(['id', 'year', 'nilaibasiclistening']);

                foreach ($users as $user) {
                    $snapshot = $userSnapshots->get($user->id);
                    if (! is_array($snapshot)) {
                        continue;
                    }

                    $score = is_numeric($snapshot['nilaibasiclistening'] ?? null)
                        ? (float) $snapshot['nilaibasiclistening']
                        : null;

                    LegacyBasicListeningScores::applyScoreToUser(
                        user: $user,
                        score: $score,
                        gradeYear: isset($snapshot['year']) && is_numeric($snapshot['year'])
                            ? (int) $snapshot['year']
                            : null,
                    );
                    $restoredUsers++;
                }
            }
        });

        Storage::disk('local')->delete($this->getLastImportMetadataPath());

        Notification::make()
            ->title('Undo import selesai')
            ->body(implode(PHP_EOL, [
                "Record baru terhapus: {$deleted}",
                "Record lama dipulihkan: {$restoredRecords}",
                "Nilai user dipulihkan: {$restoredUsers}",
            ]))
            ->success()
            ->send();
    }

    private function resolveUploadedFilePath(mixed $state): ?string
    {
        if ($state instanceof TemporaryUploadedFile) {
            $stored = $state->storeAs('temp-imports', $state->getClientOriginalName(), 'local');

            return Storage::disk('local')->path($stored);
        }

        if (is_array($state)) {
            $state = Arr::first(array_values($state));
        }

        if (is_string($state) && trim($state) !== '') {
            $path = Storage::disk('local')->path(ltrim($state, '/'));

            return is_file($path) ? $path : null;
        }

        return null;
    }
}
