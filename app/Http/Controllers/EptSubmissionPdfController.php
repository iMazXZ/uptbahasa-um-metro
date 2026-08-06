<?php

namespace App\Http\Controllers;

use App\Models\EptSubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class EptSubmissionPdfController extends Controller
{
    public function show(EptSubmission $submission)
    {
        abort_unless($submission->status === 'approved', 403, 'Belum disetujui');

        $submission->load(['user.prody']);

        $nomorSurat   = $submission->surat_nomor
            ?? ('001/II.3.AU/F/KET/LB_UMM/' . now()->year);

        $tanggalSurat = $submission->approved_at?->timezone(config('app.timezone', 'Asia/Jakarta'))
            ?->translatedFormat('d F Y');

        $viewData = [
            'submission'   => $submission,
            'nomorSurat'   => $nomorSurat,
            'tanggalSurat' => $tanggalSurat,
        ];

        $pdf = Pdf::loadView('exports.surat-rekomendasi', $viewData)->setPaper('A4');

        // Opsi yang lebih ringan untuk render
        $dompdf = $pdf->getDomPDF();
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isFontSubsettingEnabled', true);
        $dompdf->set_option('dpi', 96);

        $filename = 'Surat_Rekomendasi_' . Str::of($submission->user->name ?? 'Pemohon')->slug('_') . '.pdf';

        // ?dl=1 -> paksa download (attachment) supaya tampil progress & tersimpan lokal
        if (request()->boolean('dl')) {
            $binary = $pdf->output();

            return response()->streamDownload(
                static function () use ($binary) {
                    echo $binary;
                },
                $filename,
                [
                    'Content-Type'            => 'application/pdf',
                    'Content-Disposition'     => 'attachment; filename="'.$filename.'"',
                    'X-Content-Type-Options'  => 'nosniff',
                    'Cache-Control'           => 'private, max-age=0, must-revalidate',
                    'Pragma'                  => 'public',
                ]
            );
        }

        // Default: inline preview (browser/iOS akan menampilkan viewer)
        return $pdf->stream($filename);
    }

    public function byCode(string $code)
    {
        $submission = EptSubmission::query()
            ->where('verification_code', $code)
            ->firstOrFail();

        abort_unless($submission->status === 'approved', 403, 'Belum disetujui');

        $submission->load(['user.prody']);

        $nomorSurat   = $submission->surat_nomor
            ?? ('001/II.3.AU/F/KET/LB_UMM/' . now()->year);

        $tanggalSurat = optional($submission->approved_at)
            ?->timezone(config('app.timezone', 'Asia/Jakarta'))
            ?->translatedFormat('d F Y');

        $viewData = [
            'submission'   => $submission,
            'nomorSurat'   => $nomorSurat,
            'tanggalSurat' => $tanggalSurat,
        ];

        $pdf = Pdf::loadView('exports.surat-rekomendasi', $viewData)->setPaper('A4');

        $dompdf = $pdf->getDomPDF();
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isFontSubsettingEnabled', true);
        $dompdf->set_option('dpi', 96);

        $filename = 'Surat_Rekomendasi_' . Str::of($submission->user->name ?? 'Pemohon')->slug('_') . '.pdf';

        if (request()->boolean('dl')) {
            $binary = $pdf->output();

            return response()->streamDownload(
                static function () use ($binary) {
                    echo $binary;
                },
                $filename,
                [
                    'Content-Type'            => 'application/pdf',
                    'Content-Disposition'     => 'attachment; filename="'.$filename.'"',
                    'X-Content-Type-Options'  => 'nosniff',
                    'Cache-Control'           => 'private, max-age=0, must-revalidate',
                    'Pragma'                  => 'public',
                ]
            );
        }

        // Public link default inline; kalau ingin paksa download, panggil dengan ?dl=1
        return $pdf->stream($filename);
    }
}
