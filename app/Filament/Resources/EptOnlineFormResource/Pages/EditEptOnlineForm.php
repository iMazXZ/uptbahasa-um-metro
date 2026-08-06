<?php

namespace App\Filament\Resources\EptOnlineFormResource\Pages;

use App\Exports\EptOnlineTemplateExport;
use App\Filament\Resources\EptOnlineFormResource;
use App\Models\EptOnlineForm;
use App\Support\EptOnlineWorkbookImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class EditEptOnlineForm extends EditRecord
{
    protected static string $resource = EptOnlineFormResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        if (($data['status'] ?? null) === EptOnlineForm::STATUS_PUBLISHED && empty($this->record->published_at)) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Template Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(new EptOnlineTemplateExport(), 'ept-online-template.xlsx')),
            Actions\Action::make('importWorkbook')
                ->label('Import Workbook')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('scope')
                        ->label('Mode Import')
                        ->options(EptOnlineWorkbookImportService::importScopeOptions())
                        ->default(EptOnlineWorkbookImportService::IMPORT_SCOPE_FULL)
                        ->required()
                        ->native(false)
                        ->helperText('Pilih Full untuk final 50/40/50, atau per section untuk draft/testing.'),
                    Forms\Components\FileUpload::make('file')
                        ->label('Workbook Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            '.xlsx',
                            '.xls',
                        ])
                        ->required()
                        ->disk('local')
                        ->directory('temp-imports/ept-online'),
                ])
                ->action(function (array $data): void {
                    $upload = $this->resolveUploadedWorkbook($data['file'] ?? null);
                    $scope = (string) ($data['scope'] ?? EptOnlineWorkbookImportService::IMPORT_SCOPE_FULL);

                    if ($upload['path'] === null) {
                        throw new RuntimeException('File workbook tidak ditemukan. Silakan upload ulang.');
                    }

                    try {
                        $summary = app(EptOnlineWorkbookImportService::class)->import(
                            form: $this->record->fresh(),
                            filePath: $upload['path'],
                            actorId: auth()->id(),
                            scope: $scope,
                        );

                        Notification::make()
                            ->title('Import workbook berhasil')
                            ->body($this->buildImportNotificationBody($summary))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Import workbook gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        if ($upload['cleanup_path'] !== null) {
                            Storage::disk('local')->delete($upload['cleanup_path']);
                        }
                    }

                    $this->record->refresh();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * @return array{path: string|null, cleanup_path: string|null}
     */
    private function resolveUploadedWorkbook(mixed $state): array
    {
        if ($state instanceof TemporaryUploadedFile) {
            return [
                'path' => $state->getRealPath(),
                'cleanup_path' => null,
            ];
        }

        if (is_string($state) && Storage::disk('local')->exists($state)) {
            return [
                'path' => Storage::disk('local')->path($state),
                'cleanup_path' => $state,
            ];
        }

        if (is_array($state)) {
            foreach ($state as $value) {
                $resolved = $this->resolveUploadedWorkbook($value);
                if ($resolved['path'] !== null) {
                    return $resolved;
                }
            }
        }

        return [
            'path' => null,
            'cleanup_path' => null,
        ];
    }

    private function buildImportNotificationBody(array $summary): string
    {
        $scope = (string) ($summary['scope'] ?? EptOnlineWorkbookImportService::IMPORT_SCOPE_FULL);
        $counts = $summary['counts'] ?? [];
        $durations = $summary['durations'] ?? [];

        $lines = [
            'Mode: ' . ($summary['scope_label'] ?? EptOnlineWorkbookImportService::importScopeLabel($scope)),
            'File: ' . ($summary['file_name'] ?? '-'),
        ];

        if ($scope === EptOnlineWorkbookImportService::IMPORT_SCOPE_FULL) {
            $lines[] = 'Listening: ' . (($counts['listening'] ?? 0) . ' soal / ' . ($durations['listening'] ?? 0) . ' menit');
            $lines[] = 'Structure: ' . (($counts['structure'] ?? 0) . ' soal / ' . ($durations['structure'] ?? 0) . ' menit');
            $lines[] = 'Reading: ' . (($counts['reading'] ?? 0) . ' soal / ' . ($durations['reading'] ?? 0) . ' menit');
            $lines[] = 'Passage reading: ' . ($summary['reading_passages'] ?? 0);
            $lines[] = 'Total soal: ' . ($summary['total_questions'] ?? 0);

            return implode(PHP_EOL, $lines);
        }

        $sectionLabel = match ($scope) {
            EptOnlineWorkbookImportService::IMPORT_SCOPE_LISTENING => 'Listening',
            EptOnlineWorkbookImportService::IMPORT_SCOPE_STRUCTURE => 'Structure',
            EptOnlineWorkbookImportService::IMPORT_SCOPE_READING => 'Reading',
            default => ucfirst($scope),
        };

        $lines[] = $sectionLabel . ': ' . (($counts[$scope] ?? 0) . ' soal / ' . ($durations[$scope] ?? 0) . ' menit');

        if ($scope === EptOnlineWorkbookImportService::IMPORT_SCOPE_READING) {
            $lines[] = 'Passage reading: ' . ($summary['reading_passages'] ?? 0);
        }

        return implode(PHP_EOL, $lines);
    }
}
