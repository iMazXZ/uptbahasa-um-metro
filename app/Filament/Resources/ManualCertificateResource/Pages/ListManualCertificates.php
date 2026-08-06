<?php

namespace App\Filament\Resources\ManualCertificateResource\Pages;

use App\Filament\Resources\ManualCertificateResource;
use App\Imports\ManualCertificateImport;
use App\Models\CertificateCategory;
use App\Models\ManualCertificate;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Components\Tab;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ListManualCertificates extends ListRecords
{
    protected static string $resource = ManualCertificateResource::class;

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Semua')
                ->badge(ManualCertificate::query()->count()),
        ];

        $categories = CertificateCategory::query()
            ->withCount('certificates')
            ->orderBy('name')
            ->get()
            ->filter(fn (CertificateCategory $category): bool => $category->is_active || $category->certificates_count > 0);

        foreach ($categories as $category) {
            $tabs['category_' . $category->id] = Tab::make($category->name)
                ->badge($category->certificates_count)
                ->query(fn (Builder $query): Builder => $query->where('category_id', $category->id));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Template CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $content = implode(PHP_EOL, [
                        'NO INDUK;GROUP;NO ABSN;SRN;NAME;PRODI;LIST;SPEAK;READ;WRIT;PHON;VOC;STRU;TTL;AVE;HRF;BLN;TAHUN;SEM;PRED;',
                        '1;1;1;25340022;NAMA MAHASISWA;Pendidikan Bahasa Inggris;77;78;77;80;68;77;70;525;75;B+;12;2025;1;GOOD;',
                        '2;1;2;25340021;NAMA MAHASISWA 2;Pendidikan Agama Islam;73;75;76;80;70;76;69;518;74;B+;12;2025;1;GOOD;',
                    ]) . PHP_EOL;

                    return response()->streamDownload(function () use ($content): void {
                        echo $content;
                    }, 'manual-certificate-template.csv', [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),

            Actions\Action::make('previewCsv')
                ->label('Preview CSV')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->slideOver()
                ->modalHeading('Preview CSV Sertifikat Manual')
                ->modalSubmitActionLabel('Tampilkan Preview')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('File CSV')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', '.csv'])
                        ->required()
                        ->disk('local')
                        ->directory('temp-imports'),
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

                    if (!file_exists($filePath)) {
                        Notification::make()
                            ->title('File tidak ditemukan')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $previewImport = new ManualCertificateImport(
                            categoryId: 0,
                            semester: null,
                            issuedAt: now()->toDateString(),
                            studyProgram: null,
                        );

                        $preview = $previewImport->preview($filePath);
                        @unlink($filePath);

                        $summaryLines = [
                            "Total baris data: {$preview['total_rows']}",
                            "Siap di-import: {$preview['valid_rows']}",
                            "Akan dilewati: {$preview['skipped_rows']}",
                        ];

                        foreach (array_slice($preview['reason_counts'], 0, 4, true) as $reason => $count) {
                            $summaryLines[] = "- {$reason}: {$count}";
                        }

                        Notification::make()
                            ->title('Preview selesai')
                            ->body(implode(PHP_EOL, $summaryLines))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Preview gagal')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('importCsv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->slideOver()
                ->modalHeading('Import CSV Sertifikat Manual')
                ->modalSubmitActionLabel('Import')
                ->steps([
                    Forms\Components\Wizard\Step::make('Data CSV')
                        ->schema([
                            Forms\Components\FileUpload::make('file')
                                ->label('File CSV')
                                ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', '.csv'])
                                ->required()
                                ->disk('local')
                                ->directory('temp-imports')
                                ->dehydratedWhenHidden()
                                ->afterStateUpdated(function (Forms\Components\FileUpload $component, Set $set): void {
                                    // Persist upload immediately so next wizard step reads a stable local path.
                                    $component->saveUploadedFiles();

                                    $state = $component->getState();
                                    $firstValue = is_array($state)
                                        ? Arr::first(array_values($state))
                                        : $state;

                                    if (! is_string($firstValue) || trim($firstValue) === '') {
                                        $set('resolved_file_path', null);
                                        $set('import_summary_text', null);

                                        return;
                                    }

                                    $absolutePath = Storage::disk('local')->path(ltrim($firstValue, '/'));
                                    $set('resolved_file_path', is_file($absolutePath) ? $absolutePath : null);
                                    $set('import_summary_text', null);
                                })
                                ->helperText('Format didukung: (1) BL final-only: NO INDUK, GROUP, NO ABSEN, SRN, NAME, SCORE, ALPHABETICAL, PRODI, BLN, TAHUN. (2) Format lama LIST/SPEAK/READ/WRIT/PHON/VOC/STRU.'),

                            Forms\Components\Select::make('category_id')
                                ->label('Kategori Sertifikat')
                                ->options(CertificateCategory::where('is_active', true)->pluck('name', 'id'))
                                ->dehydratedWhenHidden()
                                ->required(),

                            Forms\Components\DatePicker::make('issued_at')
                                ->label('Tanggal Terbit')
                                ->dehydratedWhenHidden()
                                ->required()
                                ->default(now()),

                            Forms\Components\TextInput::make('study_program')
                                ->label('Program Studi (Fallback)')
                                ->placeholder('Kosongkan jika tidak diperlukan')
                                ->dehydratedWhenHidden()
                                ->helperText('Digunakan hanya jika kolom PRODI pada CSV kosong.'),

                            Forms\Components\Hidden::make('resolved_file_path')
                                ->dehydrated(),

                            Forms\Components\Hidden::make('import_summary_text')
                                ->dehydrated(),
                        ])
                        ->afterValidation(function (Get $get, Set $set): void {
                            $fileState = $get('file');
                            $resolvedPath = $this->resolveUploadedFilePath($fileState);
                            if ($resolvedPath === null) {
                                $resolvedPath = $this->persistUploadedFileToTempImports($fileState);
                            }

                            $set('resolved_file_path', $resolvedPath);

                            $summary = $this->buildImportConfirmationSummary([
                                'file' => $resolvedPath ?? $fileState,
                                'category_id' => $get('category_id'),
                                'issued_at' => $get('issued_at'),
                                'study_program' => $get('study_program'),
                            ]);

                            $set('import_summary_text', $summary);
                        }),
                    Forms\Components\Wizard\Step::make('Konfirmasi')
                        ->schema([
                            Forms\Components\Placeholder::make('import_summary')
                                ->label('Data yang Akan Diimport')
                                ->content(function (Get $get): HtmlString {
                                    $summary = (string) ($get('import_summary_text') ?? '');
                                    if (blank($summary)) {
                                        $summary = $this->buildImportConfirmationSummary([
                                            'file' => $get('resolved_file_path') ?: $get('file'),
                                            'category_id' => $get('category_id'),
                                            'issued_at' => $get('issued_at'),
                                            'study_program' => $get('study_program'),
                                        ]);
                                    }

                                    return new HtmlString(nl2br(e($summary)));
                                })
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data): void {
                    $resolvedFilePath = isset($data['resolved_file_path']) && is_string($data['resolved_file_path'])
                        ? trim($data['resolved_file_path'])
                        : null;

                    $filePath = filled($resolvedFilePath)
                        ? $resolvedFilePath
                        : $this->resolveUploadedFilePath($data['file'] ?? null);
                    if ($filePath === null) {
                        $filePath = $this->persistUploadedFileToTempImports($data['file'] ?? null);
                    }
                    if ($filePath === null) {
                        Notification::make()
                            ->title('File tidak ditemukan')
                            ->danger()
                            ->send();

                        return;
                    }
                    
                    if (!file_exists($filePath)) {
                        Notification::make()
                            ->title('File tidak ditemukan')
                            ->danger()
                            ->send();
                        return;
                    }

                    $importStartedAt = now();

                    try {
                        $previewImport = new ManualCertificateImport(
                            categoryId: (int) $data['category_id'],
                            semester: null,
                            issuedAt: $data['issued_at'],
                            studyProgram: $data['study_program'] ?? null
                        );
                        $preview = $previewImport->preview($filePath);

                        if (($preview['valid_rows'] ?? 0) < 1) {
                            Notification::make()
                                ->title('Import dibatalkan')
                                ->body('Tidak ada baris valid untuk diimport. Periksa file CSV terlebih dahulu.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $import = new ManualCertificateImport(
                            categoryId: $data['category_id'],
                            semester: null,
                            issuedAt: $data['issued_at'],
                            studyProgram: $data['study_program'] ?? null
                        );

                        Excel::import($import, $filePath);
                        $report = $import->getReportSummary();
                        $importFinishedAt = now();

                        $reportPath = 'import-reports/manual-certificate-import-' . now()->format('Ymd_His') . '.txt';
                        Storage::disk('local')->put($reportPath, $import->toTextReport());
                        $this->storeLastImportMetadata(
                            categoryId: (int) $data['category_id'],
                            issuedAt: (string) $data['issued_at'],
                            startedAt: $importStartedAt->toDateTimeString(),
                            finishedAt: $importFinishedAt->toDateTimeString(),
                            reportPath: $reportPath,
                            reportSummary: $report,
                        );

                        // Clean up temp file
                        @unlink($filePath);

                        Notification::make()
                            ->title('Import berhasil!')
                            ->body(implode(PHP_EOL, [
                                "Berhasil: {$report['imported_rows']}",
                                "Dilewati: {$report['skipped_rows']}",
                                "Diproses: {$report['processed_rows']}",
                                "Laporan: storage/app/private/{$reportPath}",
                                "Jika salah data: gunakan tombol 'Undo Import Terakhir'.",
                            ]))
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import gagal')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

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

    protected function configureCreateAction(CreateAction | Tables\Actions\CreateAction $action): void
    {
        parent::configureCreateAction($action);

        $action
            ->slideOver()
            ->url(null)
            ->modalHeading('Buat Sertifikat Manual')
            ->modalSubmitActionLabel('Simpan Sertifikat')
            ->modalWidth('7xl');
    }

    private function storeLastImportMetadata(
        int $categoryId,
        string $issuedAt,
        string $startedAt,
        string $finishedAt,
        string $reportPath,
        array $reportSummary
    ): void {
        try {
            $started = \Illuminate\Support\Carbon::parse($startedAt)->subSeconds(2);
            $finished = \Illuminate\Support\Carbon::parse($finishedAt)->addSeconds(2);

            $recordIds = ManualCertificate::query()
                ->where('category_id', $categoryId)
                ->whereDate('issued_at', $issuedAt)
                ->whereBetween('created_at', [$started, $finished])
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $payload = [
                'saved_at' => now()->toDateTimeString(),
                'category_id' => $categoryId,
                'issued_at' => $issuedAt,
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'report_path' => $reportPath,
                'record_ids' => $recordIds,
                'report_summary' => [
                    'processed_rows' => (int) ($reportSummary['processed_rows'] ?? 0),
                    'imported_rows' => (int) ($reportSummary['imported_rows'] ?? 0),
                    'skipped_rows' => (int) ($reportSummary['skipped_rows'] ?? 0),
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

    private function getLastImportMetadataPath(): string
    {
        return 'import-reports/manual-certificate-last-import.json';
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

        $recordIds = array_values(array_filter(
            array_map('intval', Arr::wrap($metadata['record_ids'] ?? [])),
            fn (int $id): bool => $id > 0
        ));

        $summary = $metadata['report_summary'] ?? [];
        $importedRows = (int) ($summary['imported_rows'] ?? count($recordIds));
        $issuedAt = (string) ($metadata['issued_at'] ?? '-');
        $categoryId = (string) ($metadata['category_id'] ?? '-');

        return implode(PHP_EOL, [
            "Kategori ID: {$categoryId}",
            "Tanggal terbit: {$issuedAt}",
            "Baris berhasil import: {$importedRows}",
            'Data yang dihapus hanya data import terakhir.',
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

        $recordIds = array_values(array_unique(array_filter(
            array_map('intval', Arr::wrap($metadata['record_ids'] ?? [])),
            fn (int $id): bool => $id > 0
        )));

        if ($recordIds === []) {
            Storage::disk('local')->delete($this->getLastImportMetadataPath());

            Notification::make()
                ->title('Metadata import kosong')
                ->body('Tidak ada ID data yang tersimpan untuk dihapus.')
                ->warning()
                ->send();

            return;
        }

        $deleted = ManualCertificate::query()
            ->whereIn('id', $recordIds)
            ->delete();

        Storage::disk('local')->delete($this->getLastImportMetadataPath());

        Notification::make()
            ->title('Undo import selesai')
            ->body("Data terhapus: {$deleted}")
            ->success()
            ->send();
    }

    private function buildImportConfirmationSummary(array $data): string
    {
        $filePath = $this->resolveUploadedFilePath($data['file'] ?? null);
        if ($filePath === null) {
            return 'File tidak ditemukan. Silakan upload ulang.';
        }

        try {
            $previewImport = new ManualCertificateImport(
                categoryId: (int) ($data['category_id'] ?? 0),
                semester: null,
                issuedAt: (string) ($data['issued_at'] ?? now()->toDateString()),
                studyProgram: filled($data['study_program'] ?? null) ? (string) $data['study_program'] : null
            );

            $preview = $previewImport->preview($filePath);
            $lines = [
                'Data yang akan diimport:',
                "Total baris: {$preview['total_rows']}",
                "Siap di-import: {$preview['valid_rows']}",
                "Akan dilewati: {$preview['skipped_rows']}",
            ];

            foreach (array_slice($preview['reason_counts'] ?? [], 0, 4, true) as $reason => $count) {
                $lines[] = "- {$reason}: {$count}";
            }

            $lines[] = '';
            $lines[] = 'Lanjutkan import?';

            return implode(PHP_EOL, $lines);
        } catch (\Throwable $e) {
            return 'Preview gagal: ' . $e->getMessage();
        }
    }

    private function resolveUploadedFilePath(mixed $uploadedState): ?string
    {
        $uploadedState = $this->normalizeLivewireUploadState($uploadedState);

        if (is_object($uploadedState)) {
            $objectPath = $this->resolveObjectUploadPath($uploadedState);

            if ($objectPath !== null) {
                return $objectPath;
            }
        }

        $candidates = $this->extractUploadedCandidates($uploadedState);

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            foreach ($this->expandUploadCandidate($candidate) as $resolvedCandidate) {
                if ($this->looksLikeMetadataValue($resolvedCandidate)) {
                    continue;
                }

                if (str_starts_with($resolvedCandidate, '/')) {
                    if (file_exists($resolvedCandidate)) {
                        return $resolvedCandidate;
                    }

                    continue;
                }

                $normalized = ltrim($resolvedCandidate, '/');
                $diskPath = Storage::disk('local')->path($normalized);

                $tempImportsPos = strpos($normalized, 'temp-imports/');
                $tempImportsRelative = $tempImportsPos !== false
                    ? substr($normalized, $tempImportsPos)
                    : null;

                $pathsToCheck = [
                    $diskPath,
                    storage_path('app/private/' . $normalized),
                    storage_path('app/' . $normalized),
                    storage_path($normalized),
                ];

                if ($tempImportsRelative !== null) {
                    $pathsToCheck[] = storage_path('app/private/' . $tempImportsRelative);
                    $pathsToCheck[] = storage_path('app/' . $tempImportsRelative);
                }

                foreach ($pathsToCheck as $path) {
                    if (file_exists($path)) {
                        return $path;
                    }
                }
            }
        }

        foreach ($this->fallbackUploadDirectories() as $directoryPath) {
            $fallback = $this->findRecentUploadInDirectory($directoryPath);

            if ($fallback !== null) {
                return $fallback;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function extractUploadedCandidates(mixed $uploadedState): array
    {
        $uploadedState = $this->normalizeLivewireUploadState($uploadedState);

        if (is_string($uploadedState)) {
            $value = trim($uploadedState);
            return $value !== '' ? [$value] : [];
        }

        if (is_object($uploadedState)) {
            $candidates = [];

            $objectPath = $this->resolveObjectUploadPath($uploadedState);
            if ($objectPath !== null) {
                $candidates[] = $objectPath;
            }

            if (method_exists($uploadedState, 'getFilename')) {
                $filename = trim((string) $uploadedState->getFilename());
                if ($filename !== '') {
                    $candidates[] = $filename;
                }
            }

            if (method_exists($uploadedState, 'getClientOriginalName')) {
                $originalName = trim((string) $uploadedState->getClientOriginalName());
                if ($originalName !== '') {
                    $candidates[] = $originalName;
                }
            }

            return array_values(array_unique($candidates));
        }

        if (! is_array($uploadedState)) {
            return [];
        }

        $results = [];
        foreach ($uploadedState as $key => $value) {
            if (is_string($key)) {
                if (str_contains($key, '/')) {
                    $results[] = $key;
                } elseif (! $this->looksLikeMetadataValue($key)) {
                    $results[] = $key;
                }
            }

            if (is_object($value)) {
                $objectPath = $this->resolveObjectUploadPath($value);
                if ($objectPath !== null) {
                    $results[] = $objectPath;
                }

                if (method_exists($value, 'getFilename')) {
                    $filename = trim((string) $value->getFilename());
                    if ($filename !== '') {
                        $results[] = $filename;
                    }
                }

                if (method_exists($value, 'getClientOriginalName')) {
                    $originalName = trim((string) $value->getClientOriginalName());
                    if ($originalName !== '') {
                        $results[] = $originalName;
                    }
                }

                continue;
            }

            if (is_string($value)) {
                $results[] = $value;
                continue;
            }

            if (is_array($value)) {
                $results = array_merge($results, $this->extractUploadedCandidates($value));
            }
        }

        return array_values(array_unique(array_filter($results, fn ($v) => trim((string) $v) !== '')));
    }

    private function looksLikeMetadataValue(string $value): bool
    {
        return in_array($value, [
            'csv',
            'text/csv',
            'application/vnd.ms-excel',
            'livewire-file:',
            'livewire-files:',
        ], true);
    }

    /**
     * @return array<int, string>
     */
    private function expandUploadCandidate(string $candidate): array
    {
        $candidate = trim($candidate);

        if ($candidate === '') {
            return [];
        }

        if (str_starts_with($candidate, 'livewire-file:')) {
            $token = trim(substr($candidate, strlen('livewire-file:')));

            if ($token === '') {
                return [];
            }

            return array_values(array_unique([
                $token,
                'livewire-tmp/' . ltrim($token, '/'),
            ]));
        }

        if (str_starts_with($candidate, 'livewire-files:')) {
            $payload = trim(substr($candidate, strlen('livewire-files:')));

            if ($payload === '') {
                return [];
            }

            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $expanded = [];

                foreach ($decoded as $value) {
                    if (! is_string($value)) {
                        continue;
                    }

                    $value = trim($value);
                    if ($value === '') {
                        continue;
                    }

                    $expanded[] = $value;
                    $expanded[] = 'livewire-tmp/' . ltrim($value, '/');
                }

                if (! empty($expanded)) {
                    return array_values(array_unique($expanded));
                }
            }

            return array_values(array_unique([
                $payload,
                'livewire-tmp/' . ltrim($payload, '/'),
            ]));
        }

        $expanded = [$candidate];

        // Some serialized upload states only contain the raw Livewire hash token.
        if (! str_contains($candidate, '/')
            && ! str_contains($candidate, ':')
            && ! str_contains($candidate, '.')
            && strlen($candidate) >= 10) {
            $expanded[] = 'livewire-tmp/' . $candidate;
            $expanded[] = 'temp-imports/' . $candidate;
        }

        return array_values(array_unique($expanded));
    }

    /**
     * @return array<int, string>
     */
    private function fallbackUploadDirectories(): array
    {
        $directories = [
            Storage::disk('local')->path('temp-imports'),
            Storage::disk('local')->path('livewire-tmp'),
            storage_path('app/private/temp-imports'),
            storage_path('app/private/livewire-tmp'),
            storage_path('app/temp-imports'),
            storage_path('app/livewire-tmp'),
            storage_path('framework/livewire-tmp'),
        ];

        return array_values(array_unique(array_filter($directories, fn (string $path): bool => is_dir($path))));
    }

    private function findRecentUploadInDirectory(string $directoryPath): ?string
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directoryPath, \FilesystemIterator::SKIP_DOTS)
            );
        } catch (\Throwable) {
            return null;
        }

        $threshold = now()->subMinutes(30)->getTimestamp();
        $recentFiles = [];

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo instanceof \SplFileInfo || ! $fileInfo->isFile()) {
                continue;
            }

            $mtime = $fileInfo->getMTime();
            if ($mtime < $threshold) {
                continue;
            }

            $path = $fileInfo->getPathname();
            $basename = strtolower($fileInfo->getBasename());

            // Skip metadata sidecar files.
            if (str_ends_with($basename, '.json') || str_contains($basename, 'meta')) {
                continue;
            }

            $recentFiles[] = [
                'path' => $path,
                'mtime' => $mtime,
                'is_csv_like' => str_ends_with($basename, '.csv') || str_contains($basename, '.csv'),
            ];
        }

        if ($recentFiles === []) {
            return null;
        }

        usort($recentFiles, static function (array $a, array $b): int {
            if ($a['is_csv_like'] !== $b['is_csv_like']) {
                return $a['is_csv_like'] ? -1 : 1;
            }

            return $b['mtime'] <=> $a['mtime'];
        });

        return $recentFiles[0]['path'] ?? null;
    }

    private function persistUploadedFileToTempImports(mixed $uploadedState): ?string
    {
        $uploadedState = $this->normalizeLivewireUploadState($uploadedState);

        if ($uploadedState instanceof TemporaryUploadedFile) {
            return $this->storeTemporaryUploadedFile($uploadedState);
        }

        if (is_array($uploadedState)) {
            foreach ($uploadedState as $value) {
                $storedPath = $this->persistUploadedFileToTempImports($value);

                if ($storedPath !== null) {
                    return $storedPath;
                }
            }
        }

        return null;
    }

    private function storeTemporaryUploadedFile(TemporaryUploadedFile $uploadedFile): ?string
    {
        try {
            if (! $uploadedFile->exists()) {
                return null;
            }

            $extension = trim((string) $uploadedFile->getClientOriginalExtension());
            $filename = (string) Str::ulid() . ($extension !== '' ? ".{$extension}" : '');

            $storedRelativePath = $uploadedFile->storeAs('temp-imports', $filename, 'local');

            if (! is_string($storedRelativePath) || trim($storedRelativePath) === '') {
                return null;
            }

            $absolutePath = Storage::disk('local')->path($storedRelativePath);

            return is_file($absolutePath) ? $absolutePath : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveObjectUploadPath(object $uploadedState): ?string
    {
        $possiblePaths = [];

        foreach (['getRealPath', 'getPathname'] as $method) {
            if (method_exists($uploadedState, $method)) {
                $value = $uploadedState->{$method}();

                if (is_string($value) && trim($value) !== '') {
                    $possiblePaths[] = $value;
                }
            }
        }

        foreach ($possiblePaths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function normalizeLivewireUploadState(mixed $uploadedState): mixed
    {
        try {
            if (TemporaryUploadedFile::canUnserialize($uploadedState)) {
                return TemporaryUploadedFile::unserializeFromLivewireRequest($uploadedState);
            }
        } catch (\Throwable) {
            // Ignore and fallback to default resolver.
        }

        return $uploadedState;
    }
}
