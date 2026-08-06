<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EptRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use ZipArchive;

class EptRegistrationExportBuktiController extends Controller
{
    private const MAX_ITEMS = 20;
    private const MAX_ITEMS_PER_PDF = 8;
    private const MAX_ROWS_PER_PAGE = 10;
    private const DEFAULT_ROWS_PER_PAGE = 4;
    private const TOKEN_TTL_SECONDS = 21600;
    private const TEMP_CROP_TTL_SECONDS = 21600;
    private const TEMP_CROP_BASE_DIR = 'exports/ept-registration-bukti-crops';

    public function preview(Request $request)
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']),
            403
        );

        $ids = $this->normalizeIdList($request->input('ids', []));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada data pendaftaran EPT yang dipilih.');
        }

        if (count($ids) > self::MAX_ITEMS) {
            return redirect()->back()->with(
                'error',
                'Maksimal ' . self::MAX_ITEMS . ' bukti per export agar preview tetap ringan.'
            );
        }

        $this->cleanupStaleTempCrops((int) auth()->id());

        $records = EptRegistration::query()
            ->with(['user:id,name,srn'])
            ->whereIn('id', $ids)
            ->get()
            ->filter(fn ($record) => filled($record->bukti_pembayaran) && Storage::disk('public')->exists($record->bukti_pembayaran))
            ->values();

        if ($records->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada bukti pembayaran yang tersedia dari data terpilih.');
        }

        $selectionToken = $this->createSelectionToken(
            $records->pluck('id')->map(fn ($id) => (int) $id)->all(),
            (int) auth()->id(),
        );

        $records = $records->map(function (EptRegistration $record) use ($selectionToken) {
            $record->preview_bukti_url = $this->resolvePreviewImageUrl($record, $selectionToken);
            $record->display_name = $record->user?->name ?? '-';
            $record->display_srn = $record->user?->srn ?? '-';
            $record->display_status_label = $record->student_status_label;
            return $record;
        });

        return view('admin.export-bukti-preview', [
            'records' => $records,
            'selectionToken' => $selectionToken,
            'maxItems' => self::MAX_ITEMS,
            'pageTitle' => 'Layout Designer - Export Bukti EPT Terpilih',
            'backUrl' => $this->resolveBackUrl(),
            'backLabel' => 'Kembali ke Pendaftaran EPT',
            'generateRoute' => route('admin.ept-registration-export-bukti.generate'),
            'cropSaveRoute' => route('admin.ept-registration-export-bukti.crop-save'),
            'downloadButtonText' => 'Download ZIP (PDF Batch)',
            'processingText' => 'Membuat ZIP PDF...',
            'defaultRowsPerPage' => self::DEFAULT_ROWS_PER_PAGE,
        ]);
    }

    public function generate(Request $request)
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']),
            403
        );

        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $request->validate([
            'rows' => 'required|string',
            'rows_per_page' => 'nullable|integer|min:1|max:' . self::MAX_ROWS_PER_PAGE,
            'selection_token' => 'required|string',
        ]);

        $selectionToken = $request->input('selection_token');
        try {
            $allowedIds = $this->extractAllowedIds($selectionToken);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => $e->errors()['selection_token'][0] ?? 'Sesi export tidak valid.',
            ], 422);
        }

        $rowsData = json_decode($request->input('rows'), true);
        $rowsPerPage = (int) $request->input('rows_per_page', self::DEFAULT_ROWS_PER_PAGE);

        if (!is_array($rowsData) || empty($rowsData)) {
            return response()->json(['error' => 'Data baris tidak valid.'], 422);
        }

        $normalizedRows = $this->normalizeRows($rowsData);
        if (empty($normalizedRows)) {
            return response()->json(['error' => 'Tidak ada baris valid untuk diproses.'], 422);
        }

        $uniqueItemIds = collect($normalizedRows)
            ->flatMap(fn ($row) => $row['items'])
            ->unique()
            ->values()
            ->all();

        if (count($uniqueItemIds) > self::MAX_ITEMS) {
            return response()->json([
                'error' => 'Terlalu banyak gambar (' . count($uniqueItemIds) . '). Maksimum ' . self::MAX_ITEMS . ' gambar per export.',
            ], 422);
        }

        $unallowedIds = array_values(array_diff($uniqueItemIds, $allowedIds));
        if (!empty($unallowedIds)) {
            return response()->json(['error' => 'Ada data yang tidak diizinkan untuk diexport.'], 403);
        }

        $recordsById = EptRegistration::query()
            ->with(['user:id,name,srn'])
            ->whereIn('id', $uniqueItemIds)
            ->get()
            ->keyBy('id');

        if ($recordsById->isEmpty()) {
            return response()->json(['error' => 'Data pendaftaran EPT tidak ditemukan.'], 404);
        }

        $zipTempPath = tempnam(sys_get_temp_dir(), 'ept_reg_bukti_zip_');
        if ($zipTempPath === false) {
            return response()->json(['error' => 'Gagal menyiapkan file ZIP sementara.'], 500);
        }

        $zip = new ZipArchive();
        $zipOpenStatus = $zip->open($zipTempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($zipOpenStatus !== true) {
            @unlink($zipTempPath);
            return response()->json(['error' => 'Gagal membuka file ZIP sementara.'], 500);
        }

        $manager = new ImageManager(new Driver());
        $rowBatches = $this->splitRowsIntoBatches($normalizedRows, self::MAX_ITEMS_PER_PDF);
        $batchIndex = 0;

        foreach ($rowBatches as $batchRows) {
            $processedRows = [];

            foreach ($batchRows as $row) {
                $columns = (int) ($row['columns'] ?? 2);
                $itemIds = $row['items'] ?? [];
                $processedRecords = [];

                foreach ($itemIds as $id) {
                    $record = $recordsById->get($id);
                    if (!$record) {
                        continue;
                    }

                    $resolvedImagePath = $this->resolveImagePath($record, $selectionToken);
                    if (!$resolvedImagePath) {
                        continue;
                    }

                    $imageData = $this->processImage($manager, $resolvedImagePath, $columns);
                    if (!$imageData) {
                        continue;
                    }

                    $processedRecords[] = [
                        'name' => $record->user?->name ?? '-',
                        'srn' => $record->user?->srn ?? '-',
                        'status_label' => $record->student_status_label,
                        'imageData' => $imageData,
                    ];
                }

                if (!empty($processedRecords)) {
                    $processedRows[] = [
                        'columns' => $columns,
                        'items' => $processedRecords,
                    ];
                }

                gc_collect_cycles();
            }

            if (empty($processedRows)) {
                continue;
            }

            $pages = array_chunk($processedRows, $rowsPerPage);
            $pdfBinary = Pdf::loadView('exports.penerjemahan-bukti-rows-pdf', [
                'pages' => $pages,
            ])
                ->setPaper('legal', 'portrait')
                ->setOption('isRemoteEnabled', true)
                ->output();

            $batchIndex++;
            $pdfFilename = sprintf(
                'Bukti_Pembayaran_EPT_TERPILIH_%s_Bagian_%02d.pdf',
                now()->format('Ymd'),
                $batchIndex
            );
            $zip->addFromString($pdfFilename, $pdfBinary);
        }

        $zip->close();

        if ($batchIndex === 0) {
            @unlink($zipTempPath);
            return response()->json(['error' => 'Tidak ada data valid untuk diexport.'], 400);
        }

        return response()->download(
            $zipTempPath,
            'Bukti_Pembayaran_EPT_TERPILIH_' . now()->format('Ymd_His') . '.zip',
            ['Content-Type' => 'application/zip'],
        )->deleteFileAfterSend(true);
    }

    public function cropSave(Request $request)
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']),
            403
        );

        $request->validate([
            'id' => 'required|integer',
            'image' => 'required|file|mimes:jpeg,jpg,png,webp',
            'selection_token' => 'required|string',
        ]);

        $selectionToken = $request->input('selection_token');
        try {
            $allowedIds = $this->extractAllowedIds($selectionToken);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()['selection_token'][0] ?? 'Sesi export tidak valid.',
            ], 422);
        }

        $recordId = (int) $request->input('id');
        if (!in_array($recordId, $allowedIds, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak diizinkan untuk sesi export ini.',
            ], 403);
        }

        $record = EptRegistration::query()
            ->select(['id', 'bukti_pembayaran'])
            ->whereKey($recordId)
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        if (!filled($record->bukti_pembayaran) || !Storage::disk('public')->exists($record->bukti_pembayaran)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada bukti pembayaran']);
        }

        try {
            $uploadedFile = $request->file('image');
            $manager = new ImageManager(new Driver());
            $image = $manager->read($uploadedFile->getPathname());
            $image->scaleDown(1800, 1800);
            $encoded = $image->toWebp(85);

            $tempRelativePath = $this->getTempCropRelativePath($selectionToken, $recordId);
            Storage::disk('public')->put($tempRelativePath, $encoded->toString());

            unset($image, $encoded);

            return response()->json([
                'success' => true,
                'message' => 'Gambar berhasil disimpan',
                'url' => Storage::disk('public')->url($tempRelativePath),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function processImage(ImageManager $manager, string $filePath, int $columns): ?string
    {
        try {
            $maxWidth = match ($columns) {
                1 => 840,
                2 => 620,
                3 => 460,
                default => 620,
            };
            $maxHeight = match ($columns) {
                1 => 760,
                2 => 580,
                3 => 430,
                default => 580,
            };

            $image = $manager->read($filePath);
            $image->scaleDown($maxWidth, $maxHeight);
            $encoded = $image->toJpeg(88);
            $base64 = base64_encode($encoded->toString());
            unset($image, $encoded);

            return 'data:image/jpeg;base64,' . $base64;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizeIdList(mixed $ids): array
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (!is_array($ids)) {
            return [];
        }

        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeRows(array $rowsData): array
    {
        $normalized = [];

        foreach ($rowsData as $row) {
            if (!is_array($row)) {
                continue;
            }

            $columns = (int) ($row['columns'] ?? 2);
            if ($columns < 1 || $columns > 3) {
                continue;
            }

            $items = $this->normalizeIdList($row['items'] ?? []);
            if (empty($items)) {
                continue;
            }

            $normalized[] = [
                'columns' => $columns,
                'items' => $items,
            ];
        }

        return $normalized;
    }

    private function splitRowsIntoBatches(array $rows, int $maxItemsPerBatch): array
    {
        $batches = [];
        $currentRows = [];
        $currentItemsCount = 0;

        foreach ($rows as $row) {
            $items = $row['items'] ?? [];
            if (empty($items)) {
                continue;
            }

            $chunks = array_chunk($items, $maxItemsPerBatch);
            foreach ($chunks as $chunkItems) {
                $chunkCount = count($chunkItems);
                if ($chunkCount === 0) {
                    continue;
                }

                if ($currentItemsCount > 0 && ($currentItemsCount + $chunkCount) > $maxItemsPerBatch) {
                    $batches[] = $currentRows;
                    $currentRows = [];
                    $currentItemsCount = 0;
                }

                $currentRows[] = [
                    'columns' => (int) ($row['columns'] ?? 2),
                    'items' => $chunkItems,
                ];
                $currentItemsCount += $chunkCount;
            }
        }

        if (!empty($currentRows)) {
            $batches[] = $currentRows;
        }

        return $batches;
    }

    private function createSelectionToken(array $ids, int $userId): string
    {
        return Crypt::encryptString(json_encode([
            'uid' => $userId,
            'ids' => $this->normalizeIdList($ids),
            'iat' => now()->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @throws ValidationException
     */
    private function extractAllowedIds(string $selectionToken): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($selectionToken), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'selection_token' => 'Sesi export tidak valid. Silakan buka ulang dari halaman pendaftaran EPT.',
            ]);
        }

        $tokenUserId = (int) ($payload['uid'] ?? 0);
        $issuedAt = (int) ($payload['iat'] ?? 0);
        $allowedIds = $this->normalizeIdList($payload['ids'] ?? []);

        if ($tokenUserId !== (int) auth()->id()) {
            throw ValidationException::withMessages([
                'selection_token' => 'Sesi export bukan milik user saat ini.',
            ]);
        }

        if ($issuedAt <= 0 || (now()->timestamp - $issuedAt) > self::TOKEN_TTL_SECONDS) {
            throw ValidationException::withMessages([
                'selection_token' => 'Sesi export sudah kedaluwarsa. Silakan buka ulang dari halaman pendaftaran EPT.',
            ]);
        }

        if (empty($allowedIds) || count($allowedIds) > self::MAX_ITEMS) {
            throw ValidationException::withMessages([
                'selection_token' => 'Daftar data export tidak valid.',
            ]);
        }

        return $allowedIds;
    }

    private function getTempCropRelativePath(string $selectionToken, int $recordId): string
    {
        $userId = (int) auth()->id();
        $sessionHash = substr(hash('sha256', $selectionToken), 0, 24);

        return self::TEMP_CROP_BASE_DIR . "/u{$userId}/{$sessionHash}/{$recordId}.webp";
    }

    private function resolveImagePath(EptRegistration $record, string $selectionToken): ?string
    {
        $disk = Storage::disk('public');
        $tempCropPath = $this->getTempCropRelativePath($selectionToken, (int) $record->id);

        if ($disk->exists($tempCropPath)) {
            return $disk->path($tempCropPath);
        }

        if (!filled($record->bukti_pembayaran) || !$disk->exists($record->bukti_pembayaran)) {
            return null;
        }

        return $disk->path($record->bukti_pembayaran);
    }

    private function resolvePreviewImageUrl(EptRegistration $record, string $selectionToken): ?string
    {
        $disk = Storage::disk('public');
        $tempCropPath = $this->getTempCropRelativePath($selectionToken, (int) $record->id);

        if ($disk->exists($tempCropPath)) {
            return $disk->url($tempCropPath);
        }

        if (!filled($record->bukti_pembayaran) || !$disk->exists($record->bukti_pembayaran)) {
            return null;
        }

        return $disk->url($record->bukti_pembayaran);
    }

    private function cleanupStaleTempCrops(int $userId): void
    {
        $disk = Storage::disk('public');
        $userRoot = self::TEMP_CROP_BASE_DIR . "/u{$userId}";

        try {
            $files = $disk->allFiles($userRoot);
            $directories = $disk->allDirectories($userRoot);
        } catch (\Throwable $e) {
            return;
        }

        foreach ($files as $file) {
            try {
                if ((time() - $disk->lastModified($file)) > self::TEMP_CROP_TTL_SECONDS) {
                    $disk->delete($file);
                }
            } catch (\Throwable $e) {
            }
        }

        $directories = collect($directories)
            ->sortByDesc(fn ($dir) => substr_count($dir, '/'))
            ->values()
            ->all();

        foreach ($directories as $dir) {
            if (empty($disk->files($dir)) && empty($disk->directories($dir))) {
                $disk->deleteDirectory($dir);
            }
        }
    }

    private function resolveBackUrl(): string
    {
        try {
            return route('filament.admin.resources.ept-registrations.index');
        } catch (\Throwable $e) {
            return URL::to('/admin/ept-registrations');
        }
    }
}
