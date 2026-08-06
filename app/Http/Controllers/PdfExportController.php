<?php

namespace App\Http\Controllers;

use App\Models\Penerjemahan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfExportController extends Controller
{
    /**
     * DOWNLOAD PDF RESMI
     * - Tidak pernah menaikkan versi.
     * - Jika file belum ada di storage/public, generate sekali dengan versi saat ini (default 1).
     */
    public function penerjemahan(Penerjemahan $penerjemahan)
    {
        $user = auth()->user();

        // Hak akses:
        $isAdminOrStaff = $user && $user->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga']);
        $isOwnerDone    = $user && $user->hasRole('pendaftar')
            && (int)$penerjemahan->user_id === (int)$user->id
            && $penerjemahan->status === 'Selesai';

        abort_unless($isAdminOrStaff || $isOwnerDone, 403, 'Tidak diizinkan.');
        abort_if(blank($penerjemahan->translated_text), 404, 'Belum ada hasil terjemahan.');

        // Pastikan kolom verifikasi ada
        $this->ensureVerification($penerjemahan);

        // Generate jika belum ada file fisiknya
        if (
            blank($penerjemahan->pdf_path) ||
            ! Storage::disk('public')->exists($penerjemahan->pdf_path)
        ) {
            $this->generateAndPersist($penerjemahan, /* bumpVersion */ false);
        }

        // Nama file download ramah (tanpa vN)
        $downloadName = 'Terjemahan_' .
            Str::of($penerjemahan->users?->name ?? 'pemohon')->slug('_') . '_' .
            ($penerjemahan->users?->srn ?? '-') . '.pdf';

        return response()->file(
            Storage::disk('public')->path($penerjemahan->pdf_path),
            ['Content-Disposition' => 'attachment; filename="'.$downloadName.'"']
        );
    }

    /**
     * Generate + simpan PDF ke storage/public, isi path & hash.
     *
     * @param  Penerjemahan $m
     * @param  bool         $bumpVersion True = naikkan versi terlebih dulu; False = gunakan versi sekarang.
     */
    protected function generateAndPersist(Penerjemahan $m, bool $bumpVersion = true): void
    {
        // Pastikan verifikasi ada
        $this->ensureVerification($m);

        // Atur versi
        $currentVersion = max(1, (int) $m->version);
        if ($bumpVersion) {
            $currentVersion++;
            $m->version = $currentVersion;
            $m->save();
        }

        // Data untuk view
        $data = ['record' => $m->load(['users', 'translator'])];

        // Render PDF dengan opsi agar SVG (QR) muncul
        $pdf = Pdf::loadView('exports.terjemahan-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions($this->dompdfOptions());

        // Simpan ke storage/public
        $dir      = 'penerjemahan/pdfs';
        $filename = $this->officialPdfFilename($m->verification_code, $currentVersion); // {CODE}-v{N}.pdf
        $path     = $dir . '/' . $filename;

        Storage::disk('public')->put($path, $pdf->output());

        // Isi metadata
        $fullPath = Storage::disk('public')->path($path);
        $m->pdf_path   = $path;
        $m->pdf_sha256 = is_file($fullPath) ? hash_file('sha256', $fullPath) : null;
        $m->issued_at  = now();
        $m->save();
    }

    /**
     * Pastikan kolom verification_code & verification_url terisi.
     * - Jika model punya method ensureVerification(), akan dipanggil.
     * - Jika tidak, isi minimal di sini (fallback).
     */
    protected function ensureVerification(Penerjemahan $m): void
    {
        if (method_exists($m, 'ensureVerification')) {
            $m->ensureVerification();
            return;
        }

        if (blank($m->verification_code)) {
            // Kode verifikasi 10 char uppercase (stabil, mudah dibaca)
            $code = strtoupper(Str::random(10));
            $m->verification_code = $code;

            // URL verifikasi — gunakan route absolut jika ada rutenya
            // Contoh: route('verification.show', ['code' => $code], true)
            $m->verification_url  = url('/verification/' . $code);
            $m->save();
        } elseif (blank($m->verification_url)) {
            // Isi URL jika code sudah ada tapi url kosong
            $m->verification_url = url('/verification/' . $m->verification_code);
            $m->save();
        }
    }

    /**
     * Format nama file PDF resmi: {VERIFCODE}-v{version}.pdf
     */
    protected function officialPdfFilename(?string $verificationCode, int $version): string
    {
        $code = $verificationCode ?: 'UNVERIFIED';
        $ver  = max(1, (int) $version);
        return "{$code}-v{$ver}.pdf";
    }

    /**
     * Opsi Dompdf supaya inline SVG (QR) tampil mulus.
     */
    protected function dompdfOptions(): array
    {
        return [
            // Penting untuk parsing elemen modern seperti <svg>
            'isHtml5ParserEnabled'     => true,

            // Bolehkan resource eksternal (kalau kamu pakai URL absolut untuk logo, dsb.)
            'isRemoteEnabled'          => true,

            // Subsetting font agar ukuran file kecil dan glyph non-ASCII (Indonesia) aman
            'isFontSubsettingEnabled'  => true,

            // (Opsional) defaultFont bisa diset ke DejaVu Sans jika diperlukan
            // 'defaultFont'           => 'DejaVu Sans',
        ];
    }
}
