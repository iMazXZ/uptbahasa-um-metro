<?php

namespace App\Http\Controllers;

use App\Models\Penerjemahan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class PenerjemahanPdfController extends Controller
{
    /**
     * /penerjemahan/{record}/pdf  (protected)
     * Default: force download (attachment).
     * Untuk preview inline, tambahkan query ?inline=1
     */
    public function show(Request $request, Penerjemahan $record)
    {
        $this->ensureCanExport($record);

        // Pastikan ada verification_code & verification_url seperti perilaku lama
        $this->ensureVerification($record);

        $record->load(['users', 'translator']);

        $viewData = $this->buildViewData($record);

        // Render PDF di memori (tanpa simpan), opsi Dompdf dioptimalkan
        $pdf = Pdf::loadView('exports.terjemahan-pdf', $viewData)
            ->setPaper('A4')
            ->setOptions($this->dompdfOptions());

        $binary   = $pdf->output(); // <- penting agar bisa set Content-Length
        $filename = 'Surat_Terjemahan_' . Str::of($record->users?->name ?? 'Pemohon')->slug('_') . '.pdf';

        // Default: attachment (download). Jika ?inline=1 maka inline preview.
        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';

        return response($binary, 200, [
            'Content-Type'            => 'application/pdf',
            'Content-Disposition'     => $disposition . '; filename="' . $filename . '"',
            'Content-Length'          => (string) strlen($binary), // <- cegah throttling 100 KB/s
            'Cache-Control'           => 'private, max-age=0, must-revalidate',
            'Pragma'                  => 'public',
            'X-Content-Type-Options'  => 'nosniff',
            'X-Accel-Buffering'       => 'no',
        ]);
    }

    /**
     * /verification/{code}/penerjemahan.pdf  (public by code)
     * Default: force download (attachment).
     * Untuk preview inline, tambahkan query ?inline=1
     */
    public function byCode(Request $request, string $code)
    {
        $record = Penerjemahan::where('verification_code', $code)->firstOrFail();

        $this->ensureCanExport($record);
        // Pastikan verification_url terisi juga
        $this->ensureVerification($record);

        $record->load(['users', 'translator']);

        $viewData = $this->buildViewData($record);

        $pdf = Pdf::loadView('exports.terjemahan-pdf', $viewData)
            ->setPaper('A4')
            ->setOptions($this->dompdfOptions());

        $binary   = $pdf->output();
        $filename = 'Surat_Terjemahan_' . Str::of($record->users?->name ?? 'Pemohon')->slug('_') . '.pdf';

        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';

        return response($binary, 200, [
            'Content-Type'            => 'application/pdf',
            'Content-Disposition'     => $disposition . '; filename="' . $filename . '"',
            'Content-Length'          => (string) strlen($binary),
            'Cache-Control'           => 'private, max-age=0, must-revalidate',
            'Pragma'                  => 'public',
            'X-Content-Type-Options'  => 'nosniff',
            'X-Accel-Buffering'       => 'no',
        ]);
    }

    /* ============================================================
     | Helpers
     |============================================================ */

    /**
     * Opsi Dompdf optimal untuk shared hosting:
     * - isRemoteEnabled=false: pastikan semua aset (logo/stempel/ttd) adalah path lokal.
     * - isFontSubsettingEnabled=true: ukuran PDF lebih kecil.
     * - isHtml5ParserEnabled=true: parser HTML lebih andal.
     */
    private function dompdfOptions(): array
    {
        return [
            'isHtml5ParserEnabled'    => true,
            'isRemoteEnabled'         => false,
            'isFontSubsettingEnabled' => true,
            'chroot'                  => public_path(), // <- IZINKAN akses public/*
        ];
    }

    /**
     * Pastikan dokumen boleh diekspor (status & ada hasil).
     */
    private function ensureCanExport(Penerjemahan $record): void
    {
        $status = strtolower((string) $record->status);
        $allowed = ['selesai', 'disetujui', 'completed', 'approved'];

        abort_unless(in_array($status, $allowed, true), 403, 'Belum selesai.');
        abort_unless(
            filled($record->translated_text) || filled($record->final_file_path),
            404,
            'Belum ada hasil.'
        );
    }

    /**
     * Pastikan kolom verifikasi terisi (meniru perilaku lama PdfExportController).
     * - Jika model punya method ensureVerification(), pakai itu.
     * - Jika tidak, generate code & url dasar di sini.
     */
    private function ensureVerification(Penerjemahan $m): void
    {
        if (method_exists($m, 'ensureVerification')) {
            $m->ensureVerification();
            $m->refresh();
            return;
        }

        // Generate code jika kosong
        if (blank($m->verification_code)) {
            $m->verification_code = 'TERJ-' . $m->getKey() . '-' . Str::upper(Str::random(6));
            $m->save();
        }

        // Isi URL absolut jika kosong (menuju halaman verifikasi, bukan PDF)
        if (blank($m->verification_url) && filled($m->verification_code)) {
            $m->verification_url = route('verification.show', ['code' => $m->verification_code], true);
            $m->save();
        }

        $m->refresh();
    }

    private function buildViewData(Penerjemahan $record): array
    {
        $verifyCode = $record->verification_code ?: null;
        $verifyUrl  = $record->verification_url
            ?: ($verifyCode ? route('verification.show', ['code' => $verifyCode], true) : null);

        // Gambar lokal (bukan base64, bukan URL)
        $logo  = public_path('images/logo-um.png');
        $stamp = public_path('images/stempel.png');
        $sign  = public_path('images/ttd_ketua.png');

        return [
            'record'    => $record,
            'verifyUrl' => $verifyUrl,
            'logoPath'  => is_file($logo)  ? $logo  : null,
            'stampPath' => is_file($stamp) ? $stamp : null,
            'signPath'  => is_file($sign)  ? $sign  : null,
            'ttdDate'   => $this->formatTtdDate($record),
        ];
    }

    /**
     * Format tanggal tanda tangan (pakai completion_date, fallback updated_at/now).
     */
    private function formatTtdDate(Penerjemahan $record): string
    {
        $date = $record->completion_date ?? $record->updated_at ?? now();
        return Carbon::parse($date)->locale('id')->translatedFormat('d F Y');
    }
}
