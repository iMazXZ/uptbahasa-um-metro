<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Http\UploadedFile;

class ImageTransformer
{
    protected static function makeFilename(string $basename): string
    {
        // slug + potong panjang agar aman (Windows path limit)
        $slug = \Illuminate\Support\Str::slug(pathinfo($basename, PATHINFO_FILENAME), '_');
        $slug = mb_substr($slug, 0, 80);
        $date = now()->format('Ymd_His');
        $rand = substr(bin2hex(random_bytes(3)), 0, 6);
        return "{$slug}_{$date}_{$rand}.webp";
    }

    public static function toWebpFromUploaded(
        TemporaryUploadedFile|UploadedFile $uploaded,
        string $targetDisk = 'public',
        string $targetDir = 'uploads/images',
        int $quality = 82,
        ?int $maxWidth = 2000,
        ?int $maxHeight = null,
        ?string $basename = null
    ): array {
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());

        $real = $uploaded->getRealPath();
        if (!is_file($real)) {
            throw new \RuntimeException("Upload temp file missing: {$real}");
        }

        $image = $manager->read($real);

        if ($maxWidth || $maxHeight) {
            $image = $image->scaleDown($maxWidth, $maxHeight);
        }

        // gunakan nama custom kalau diberikan, kalau tidak pakai nama asli file upload
        $filename = self::makeFilename($basename ?: $uploaded->getClientOriginalName());
        $relPath  = trim($targetDir, '/') . '/' . $filename;

        $binary = $image->toWebp($quality);
        \Storage::disk($targetDisk)->put($relPath, $binary);

        return [
            'path'   => $relPath,
            'width'  => $image->width(),
            'height' => $image->height(),
            'bytes'  => \Storage::disk($targetDisk)->size($relPath),
        ];
    }

    /**
     * Versi dari path lokal (opsional, kalau kamu butuh).
     */
    public static function toWebp(
        string $inputPath,
        string $targetDisk = 'public',
        string $targetDir  = 'uploads/images',
        int $quality = 82,
        ?int $maxWidth = 2000,
        ?int $maxHeight = null
    ): array {
        $manager = new ImageManager(new Driver());

        if (!is_file($inputPath)) {
            throw new \RuntimeException("Input file not found: {$inputPath}");
        }

        $image = $manager->read($inputPath);

        if ($maxWidth || $maxHeight) {
            $image = $image->scaleDown($maxWidth, $maxHeight);
        }

        $filename = Str::uuid()->toString() . '.webp';
        $relPath  = trim($targetDir, '/') . '/' . $filename;

        $binary = $image->toWebp($quality);
        Storage::disk($targetDisk)->put($relPath, $binary);

        return [
            'path'   => $relPath,
            'width'  => $image->width(),
            'height' => $image->height(),
            'bytes'  => Storage::disk($targetDisk)->size($relPath),
        ];
    }
}
