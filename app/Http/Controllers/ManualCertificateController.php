<?php

namespace App\Http\Controllers;

use App\Models\ManualCertificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManualCertificateController extends Controller
{
    /**
     * Download PDF sertifikat manual
     */
    public function download(ManualCertificate $certificate)
    {
        $certificate->load('category');

        // Pilih template berdasarkan kategori, default ke epp-certificate
        $template = $certificate->category?->pdf_template ?? 'epp-certificate';
        $viewPath = "pdf.{$template}";

        // Fallback jika template tidak ada
        if (!view()->exists($viewPath)) {
            $viewPath = 'pdf.epp-certificate';
        }

        $data = [
            'certificate' => $certificate,
            'category' => $certificate->category,
            'logoPath' => public_path('images/logo-um.png'),
            'signPath' => public_path('images/ttd_ketua.png'),
            'stampPath' => public_path('images/stempel.png'),
            'chairName' => 'Dedy Subandowo, M.A., Ph.D.',
            'chairNip' => '0215068603',
        ];

        $pdf = Pdf::loadView($viewPath, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'Helvetica',
                'dpi' => 72,
                'chroot' => public_path(),
            ]);

        $filename = 'Sertifikat_' . Str::slug($certificate->name) . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Public download by verification code (tanpa login)
     */
    public function downloadByCode(string $code)
    {
        $certificate = ManualCertificate::with('category')
            ->where('verification_code', $code)
            ->firstOrFail();

        return $this->generatePdf($certificate);
    }

    /**
     * Generate PDF (shared logic)
     */
    private function generatePdf(ManualCertificate $certificate)
    {
        $template = $certificate->category?->pdf_template ?? 'epp-certificate';
        $viewPath = "pdf.{$template}";

        if (!view()->exists($viewPath)) {
            $viewPath = 'pdf.epp-certificate';
        }

        $data = [
            'certificate' => $certificate,
            'category' => $certificate->category,
            'logoPath' => public_path('images/logo-um.png'),
            'signPath' => public_path('images/ttd_ketua.png'),
            'stampPath' => public_path('images/stempel.png'),
            'chairName' => 'Dedy Subandowo, M.A., Ph.D.',
            'chairNip' => '0215068603',
        ];

        $pdf = Pdf::loadView($viewPath, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'Helvetica',
                'dpi' => 72,
                'chroot' => public_path(),
            ]);

        $filename = 'Sertifikat_' . Str::slug($certificate->name) . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Public download by certificate ID (untuk individual semester)
     */
    public function downloadById(int $id)
    {
        $certificate = ManualCertificate::with('category')->findOrFail($id);
        return $this->generatePdf($certificate);
    }
}
