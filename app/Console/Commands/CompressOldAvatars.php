<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ImageTransformer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompressOldAvatars extends Command
{
    protected $signature = 'avatars:compress 
        {--dry : Simulasi saja, tidak menulis file}
        {--limit=0 : Batasi jumlah user yang diproses}
        {--user= : Hanya untuk user id tertentu, koma-separated (mis: 12,45)}
        {--quality=82 : Kualitas WebP}
        {--max=600 : Lebar & tinggi maksimum (px)}';

    protected $description = 'Kompresi avatar JPG/PNG lama ke WebP (max 600px), update path & hapus file lama.';

    public function handle(): int
    {
        $diskPublic = Storage::disk('public');

        $q = User::query()->whereNotNull('image')->where('image', '!=', '');

        if ($ids = $this->option('user')) {
            $list = collect(explode(',', $ids))->map(fn ($v) => (int) trim($v))->filter();
            $q->whereIn('id', $list);
        }

        $limit = (int) $this->option('limit');
        $users = $limit > 0 ? $q->limit($limit)->get() : $q->get();

        $dry     = (bool) $this->option('dry');
        $quality = (int) $this->option('quality');
        $max     = (int) $this->option('max');

        $ok = 0; $skip = 0; $miss = 0; $err = 0;

        foreach ($users as $u) {
            $oldRel = (string) $u->image;
            if ($oldRel === '') { $skip++; continue; }

            // Cari file lama di beberapa kemungkinan lokasi
            $candidates = [
                // path relatif di disk public (paling umum)
                ['type' => 'public', 'rel' => $oldRel, 'abs' => storage_path('app/public/' . ltrim($oldRel, '/'))],
                // kadang tersimpan di storage/app (bukan public)
                ['type' => 'storage', 'rel' => $oldRel, 'abs' => storage_path('app/' . ltrim($oldRel, '/'))],
                // kadang langsung di public
                ['type' => 'public_dir', 'rel' => $oldRel, 'abs' => public_path(ltrim($oldRel, '/'))],
            ];

            $src = null;
            foreach ($candidates as $c) {
                if (is_file($c['abs'])) { $src = $c; break; }
            }

            if (!$src) {
                $this->warn("MISS  | user#{$u->id} file missing in known locations: {$oldRel}");
                $miss++;
                continue;
            }

            $ext = strtolower(pathinfo($src['abs'], PATHINFO_EXTENSION));
            if ($ext === 'webp') {
                $this->line("SKIP  | user#{$u->id} already webp: {$oldRel}");
                $skip++;
                continue;
            }
            if (! in_array($ext, ['jpg','jpeg','png'], true)) {
                $this->line("SKIP  | user#{$u->id} unsupported ext ({$ext}): {$oldRel}");
                $skip++;
                continue;
            }

            // Nama baru konsisten per user (informasi hanya untuk log; toWebp tidak menerima basename)
            $suggest = 'avatar_' . Str::of((string) $u->id)->padLeft(6, '0') . '.webp';
            $this->info("PROC  | user#{$u->id} {$oldRel} -> (webp in public/profile_pictures/*) [suggest {$suggest}]");

            if ($dry) { $ok++; continue; }

            try {
                // Penting: toWebp() TIDAK ada parameter 'basename', jadi biarkan helper menentukan nama.
                $out = ImageTransformer::toWebp(
                    inputPath: $src['abs'],
                    targetDisk: 'public',
                    targetDir:  'profile_pictures',
                    quality:    $quality,
                    maxWidth:   $max,
                    maxHeight:  $max,
                );
                // $out diharapkan mengandung ['path' => 'profile_pictures/xxx.webp']

                if (!is_array($out) || empty($out['path'])) {
                    throw new \RuntimeException('ImageTransformer::toWebp() tidak mengembalikan path.');
                }

                $newRel = $out['path'];

                // Update DB
                $u->image = $newRel;
                $u->save();

                // Hapus file lama kalau ada di disk public
                if ($src['type'] === 'public' && $diskPublic->exists($oldRel) && $oldRel !== $newRel) {
                    $diskPublic->delete($oldRel);
                } else {
                    // Hapus file lama di lokasi absolut non-disk
                    if (is_file($src['abs']) && realpath(storage_path('app/public/'.$newRel)) !== realpath($src['abs'])) {
                        @unlink($src['abs']);
                    }
                }

                $ok++;
            } catch (\Throwable $e) {
                $this->error("FAIL  | user#{$u->id} {$oldRel} : {$e->getMessage()}");
                $err++;
            }
        }

        $this->line("== Selesai: ok={$ok}, skip={$skip}, miss={$miss}, err={$err} (dry=" . ($dry?'yes':'no') . ")");

        return $err > 0 ? self::FAILURE : self::SUCCESS;
    }
}
