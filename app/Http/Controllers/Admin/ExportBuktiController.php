<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penerjemahan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ExportBuktiController extends Controller
{
    private const MAX_ITEMS = 8;
    private const MAX_ROWS_PER_PAGE = 10;
    private const TOKEN_TTL_SECONDS = 21600; // 6 hours
    private const TEMP_CROP_TTL_SECONDS = 21600; // 6 hours
    private const TEMP_CROP_BASE_DIR = 'exports/penerjemahan-bukti-crops';

    public function preview(Request $request)
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']),
            403
        );

        $ids = $this->normalizeIdList($request->input('ids', []));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih.');
        }

        if (count($ids) > self::MAX_ITEMS) {
            return redirect()->back()->with(
                'error',
                'Maksimal ' . self::MAX_ITEMS . ' gambar per export agar server tetap ringan.'
            );
        }

        $records = Penerjemahan::query()
            ->with(['users:id,name,srn'])
            ->whereIn('id', $ids)
            ->get()
            ->filter(fn ($r) => filled($r->bukti_pembayaran) && Storage::disk('public')->exists($r->bukti_pembayaran))
            ->values();

        if ($records->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada bukti pembayaran yang tersedia.');
        }

        $this->cleanupStaleTempCrops((int) auth()->id());

        $selectionToken = $this->createSelectionToken(
            $records->pluck('id')->map(fn ($id) => (int) $id)->all(),
            (int) auth()->id()
        );

        $records = $records->map(function ($record) use ($selectionToken) {
            $record->preview_bukti_url = $this->resolvePreviewImageUrl($record, $selectionToken);
            return $record;
        });

        return view('admin.export-bukti-preview', [
            'records' => $records,
            'selectionToken' => $selectionToken,
            'maxItems' => self::MAX_ITEMS,
        ]);
    }

    public function generate(Request $request)
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']),
            403
        );
        
        // Increase memory limit for large exports
        ini_set('memory_limit', '512M');
        set_time_limit(120);

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
        $rowsPerPage = (int) $request->input('rows_per_page', 3);

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
                'error' => "Terlalu banyak gambar (" . count($uniqueItemIds) . '). Maksimum ' . self::MAX_ITEMS . ' gambar per export.',
            ], 422);
        }

        $unallowedIds = array_values(array_diff($uniqueItemIds, $allowedIds));
        if (!empty($unallowedIds)) {
            return response()->json(['error' => 'Ada data yang tidak diizinkan untuk diexport.'], 403);
        }

        $recordsById = Penerjemahan::query()
            ->with(['users:id,name,srn'])
            ->whereIn('id', $uniqueItemIds)
            ->get()
            ->keyBy('id');

        // Process rows - fetch records and pre-process images
        $processedRows = [];
        $manager = new ImageManager(new Driver());

        foreach ($normalizedRows as $row) {
            $columns = (int) ($row['columns'] ?? 2);
            $itemIds = $row['items'] ?? [];

            // Fetch records and process images
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

                // Process and compress image
                $imageData = $this->processImage(
                    $manager,
                    $resolvedImagePath,
                    $columns
                );

                if (!$imageData) {
                    continue;
                }

                $processedRecords[] = [
                    'name' => $record->users?->name ?? '-',
                    'srn' => $record->users?->srn ?? '-',
                    'imageData' => $imageData,
                ];
            }
            
            if (!empty($processedRecords)) {
                $processedRows[] = [
                    'columns' => $columns,
                    'items' => $processedRecords,
                ];
            }

            // Clear memory after each row
            gc_collect_cycles();
        }

        if (empty($processedRows)) {
            return response()->json(['error' => 'Tidak ada data valid'], 400);
        }

        // Split rows into pages
        $pages = array_chunk($processedRows, $rowsPerPage);

        $pdf = Pdf::loadView('exports.penerjemahan-bukti-rows-pdf', [
            'pages' => $pages,
        ])
        ->setPaper('legal', 'portrait')
        ->setOption('isRemoteEnabled', true);

        $filename = 'Bukti_Pembayaran_Penerjemahan_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Process and compress image for PDF embedding - balanced optimization
     */
    private function processImage(ImageManager $manager, string $filePath, int $columns): ?string
    {
        try {
            // Balanced quality for low-resource hosting.
            $maxWidth = match($columns) {
                1 => 840,
                2 => 620,
                3 => 460,
                default => 620,
            };
            $maxHeight = match($columns) {
                1 => 760,
                2 => 580,
                3 => 430,
                default => 580,
            };

            // Read image
            $image = $manager->read($filePath);

            // Resize to fit within bounds (maintain aspect ratio)
            $image->scaleDown($maxWidth, $maxHeight);

            // Encode as JPEG (compressed enough for shared hosting usage)
            $encoded = $image->toJpeg(88);

            // Convert to base64
            $base64 = base64_encode($encoded->toString());

            // Free memory immediately
            unset($image, $encoded);

            return 'data:image/jpeg;base64,' . $base64;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Save cropped image from preview page
     */
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
            return response()->json(['success' => false, 'message' => 'Data tidak diizinkan untuk sesi export ini.'], 403);
        }

        $record = Penerjemahan::query()->select(['id', 'bukti_pembayaran'])->find($recordId);

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        if (!filled($record->bukti_pembayaran) || !Storage::disk('public')->exists($record->bukti_pembayaran)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada bukti pembayaran']);
        }

        try {
            // Save crop as temporary file (non-destructive: original is untouched).
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
                'selection_token' => 'Sesi export tidak valid. Silakan buka ulang dari halaman daftar.',
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
                'selection_token' => 'Sesi export sudah kedaluwarsa. Silakan pilih data ulang.',
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

    private function resolveImagePath(Penerjemahan $record, string $selectionToken): ?string
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

    private function resolvePreviewImageUrl(Penerjemahan $record, string $selectionToken): ?string
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
                // Ignore cleanup errors to avoid blocking export flow.
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
}
