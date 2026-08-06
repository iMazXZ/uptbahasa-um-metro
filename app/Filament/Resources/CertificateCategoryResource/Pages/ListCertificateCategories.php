<?php

namespace App\Filament\Resources\CertificateCategoryResource\Pages;

use App\Filament\Resources\CertificateCategoryResource;
use App\Models\CertificateCategory;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListCertificateCategories extends ListRecords
{
    protected static string $resource = CertificateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Export Action
            Actions\Action::make('export')
                ->label('Export Settings')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $categories = CertificateCategory::all()->map(function ($cat) {
                        return [
                            'name' => $cat->name,
                            'slug' => $cat->slug,
                            'code_prefix' => $cat->code_prefix,
                            'number_format' => $cat->number_format,
                            'semesters' => $cat->semesters,
                            'score_fields' => $cat->score_fields,
                            'grade_rules' => $cat->grade_rules,
                            'pdf_template' => $cat->pdf_template,
                            'is_active' => $cat->is_active,
                        ];
                    })->toArray();

                    $json = json_encode(['categories' => $categories], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    $filename = 'certificate_categories_' . now()->format('Y-m-d_His') . '.json';

                    return response()->streamDownload(function () use ($json) {
                        echo $json;
                    }, $filename, [
                        'Content-Type' => 'application/json',
                    ]);
                }),

            // Import Action
            Actions\Action::make('import')
                ->label('Import Settings')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('File JSON')
                        ->acceptedFileTypes(['application/json'])
                        ->required()
                        ->disk('local')
                        ->directory('temp-imports'),
                    Forms\Components\Toggle::make('overwrite')
                        ->label('Overwrite existing (berdasarkan slug)')
                        ->default(false)
                        ->helperText('Jika aktif, kategori dengan slug sama akan di-update'),
                ])
                ->action(function (array $data) {
                    $filePath = $data['file'];
                    $overwrite = $data['overwrite'] ?? false;

                    try {
                        $content = Storage::disk('local')->get($filePath);
                        $parsed = json_decode($content, true);

                        if (!isset($parsed['categories']) || !is_array($parsed['categories'])) {
                            throw new \Exception('Format file tidak valid. Harus memiliki key "categories".');
                        }

                        $imported = 0;
                        $skipped = 0;

                        foreach ($parsed['categories'] as $catData) {
                            $slug = $catData['slug'] ?? null;
                            if (!$slug) continue;

                            $existing = CertificateCategory::where('slug', $slug)->first();

                            if ($existing && !$overwrite) {
                                $skipped++;
                                continue;
                            }

                            CertificateCategory::updateOrCreate(
                                ['slug' => $slug],
                                [
                                    'name' => $catData['name'] ?? $slug,
                                    'code_prefix' => $catData['code_prefix'] ?? null,
                                    'number_format' => $catData['number_format'] ?? '{seq}/{year}',
                                    'semesters' => $catData['semesters'] ?? null,
                                    'score_fields' => $catData['score_fields'] ?? null,
                                    'grade_rules' => $catData['grade_rules'] ?? null,
                                    'pdf_template' => $catData['pdf_template'] ?? null,
                                    'is_active' => $catData['is_active'] ?? true,
                                ]
                            );
                            $imported++;
                        }

                        // Cleanup temp file
                        Storage::disk('local')->delete($filePath);

                        Notification::make()
                            ->title('Import berhasil')
                            ->body("Imported: {$imported}, Skipped: {$skipped}")
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\CreateAction::make(),
        ];
    }
}

