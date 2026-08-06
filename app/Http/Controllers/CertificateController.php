<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\BasicListeningGrade;
use App\Models\BasicListeningSurvey;
use App\Models\BasicListeningSurveyResponse;
use App\Support\BlCompute;
use App\Support\BlGrading;
use App\Support\BlSource;
use App\Support\LegacyBasicListeningScores;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CertificateController extends Controller
{
    /**
     * Download/preview sertifikat Basic Listening (dengan login)
     * ?inline=1 => preview di browser; default download.
     */
    public function basicListening(Request $request)
    {
        $user = $request->user();

        // --- Cek role ---
        if (!$this->hasAccess($user)) {
            abort(403, 'Akses ditolak.');
        }

        // --- Gatekeeper: wajib isi kuesioner sebelum unduh sertifikat ---
        $this->ensureSurveyCompletedForCertificate($user->id);

        try {
            $grade = $this->getOrCreateGrade($user);
            $this->validateUserData($user);

            // --- Ambil nilai final via helper ---
            [$finalNumeric, $finalLetter, $attendance, $finalTest, $daily] = $this->resolveScore($user, $grade);

            if ($finalNumeric === null || $finalLetter === null) {
                throw new \Exception('Nilai belum lengkap untuk diterbitkan sertifikat.');
            }

            // Harus lulus (minimal 55)
            if ($finalNumeric < 55) {
                abort(403, 'Nilai akhir belum mencapai 55, sertifikat belum tersedia.');
            }

            $pdfData = $this->preparePdfData($user, $grade, $attendance, $daily, $finalTest, $finalNumeric, $finalLetter);
            return $this->generatePdfResponse($pdfData, $user, $request->boolean('inline'));
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Certificate generation failed for user ' . $user->id . ': ' . $e->getMessage());
            return back()->with('danger', 'Terjadi kesalahan saat menghasilkan sertifikat: ' . $e->getMessage());
        }
    }

    /**
     * Public by code: digunakan dari halaman verifikasi (tanpa login).
     * ?inline=1 => preview di browser; default download.
     */
    public function basicListeningByCode(Request $request, string $code)
    {
        try {
            $grade = BasicListeningGrade::with('user')->where('verification_code', $code)->firstOrFail();
            $user  = $grade->user;

            // --- Gatekeeper: jika ingin konsisten menahan jalur publik juga, aktifkan ini ---
            $this->ensureSurveyCompletedForCertificate($user->id);

            $this->validateUserData($user);

            [$finalNumeric, $finalLetter, $attendance, $finalTest, $daily] = $this->resolveScore($user, $grade);

            if ($finalNumeric === null || $finalLetter === null) {
                abort(404, 'Data nilai belum lengkap untuk sertifikat.');
            }

            if ($finalNumeric < 55) {
                abort(403, 'Nilai akhir belum mencapai 55, sertifikat belum tersedia.');
            }

            $pdfData = $this->preparePdfData($user, $grade, $attendance, $daily, $finalTest, $finalNumeric, $finalLetter);
            return $this->generatePdfResponse($pdfData, $user, $request->boolean('inline'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Sertifikat tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Certificate generation failed for code ' . $code . ': ' . $e->getMessage());
            abort(500, 'Terjadi kesalahan sistem saat menghasilkan sertifikat.');
        }
    }

    /** Check if user has access to certificate */
    private function hasAccess($user): bool
    {
        return $user->hasAnyRole(['pendaftar', 'Admin', 'tutor']);
    }

    /** Get or create grade record for user */
    private function getOrCreateGrade($user)
    {
        return BasicListeningGrade::firstOrCreate([
            'user_id'   => $user->id,
            'user_year' => $user->year,
        ]);
    }

    /** Validate user data completeness */
    private function validateUserData($user): void
    {
        if (empty($user->name) || empty($user->srn)) {
            throw new \Exception('Data profil user tidak lengkap.');
        }
    }

    /**
     * Wajibkan kuesioner selesai sebelum unduh sertifikat.
     * - Cari survey aktif yang require_for_certificate
     * - Cek apakah user sudah submit (submitted_at != null)
     * - Jika belum, abort 403
     */
    private function ensureSurveyCompletedForCertificate(int $userId): void
    {
        $survey = BasicListeningSurvey::query()
            ->where('require_for_certificate', true)
            ->where('target', 'final')      // default: survey akhir
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        // Jika tidak ada kuesioner aktif → tidak membatasi
        if (! $survey) {
            return;
        }

        $done = BasicListeningSurveyResponse::where([
                'survey_id'  => $survey->id,
                'user_id'    => $userId,
                'session_id' => null,        // final
            ])
            ->whereNotNull('submitted_at')
            ->exists();

        abort_unless(
            $done,
            403,
            'Silakan isi kuesioner terlebih dahulu sebelum mengunduh sertifikat.'
        );
    }

    /**
     * Tentukan nilai akhir berdasarkan tahun:
     * - ≤ 2024: ambil dari field nilaibasiclistening di users
     * - ≥ 2025: hitung dari attendance, daily, dan final_test
     */
    private function resolveScore($user, $grade): array
    {
        $attendance = is_numeric($grade->attendance) ? (float) $grade->attendance : null;
        $finalTest  = is_numeric($grade->final_test)  ? (float) $grade->final_test  : null;
        $daily      = BlCompute::dailyAvgForUser($user->id, $user->year);
        $daily      = is_numeric($daily) ? (float) $daily : null;

        $year = (int) $user->year;
        $finalNumeric = null;
        $finalLetter  = null;

        if ($year <= 2024) {
            // Nilai manual legacy (diprioritaskan dari tabel nilai manual)
            $manual = LegacyBasicListeningScores::effectiveScoreForUser($user);
            if ($manual !== null) {
                $finalNumeric = round($manual, 2);
                $finalLetter  = BlGrading::letter($manual);
            }
        } else {
            // Nilai otomatis (Basic Listening modern)
            $parts = array_values(array_filter([$attendance, $daily, $finalTest], fn ($v) => $v !== null));
            if ($parts) {
                $finalNumeric = round(array_sum($parts) / count($parts), 2);
                $finalLetter  = BlGrading::letter($finalNumeric);

                // Cache agar tidak dihitung ulang
                $grade->final_numeric_cached = $finalNumeric;
                $grade->final_letter_cached  = $finalLetter;
                $grade->save();
            }
        }

        return [$finalNumeric, $finalLetter, $attendance, $finalTest, $daily];
    }

    /**
     * Siapkan data lengkap untuk PDF
     */
    private function preparePdfData($user, $grade, $attendance, $daily, $finalTest, $finalNumeric, $finalLetter): array
    {
        // Pastikan kode & URL verifikasi ada
        if (empty($grade->verification_code)) {
            $srn = $user->srn ?? (string) $user->id;
            $grade->verification_code = "BL-{$srn}";
        }

        if (empty($grade->verification_url)) {
            $grade->verification_url = route('verification.show', ['code' => $grade->verification_code], true);
        }

        $userId   = $user->id;
        $group    = $user->nomor_grup_bl ?? 'ISI NOMOR GRUP';
        $prodyId  = $user->prody_id ?? 'ISI PRODI';

        $certificateNumber = "{$userId}.{$group}.{$prodyId}/II.3.AU/A/EPP.LB.2026";

        $grade->save();

        return [
            'user'             => $user,
            'attendance'       => $attendance,
            'daily'            => $daily,
            'finalTest'        => $finalTest,
            'finalNumeric'     => $finalNumeric,
            'finalLetter'      => $finalLetter,
            'issuedAt'         => now(),
            'verificationCode' => $grade->verification_code,
            'verificationUrl'  => $grade->verification_url,
            'logoPath'         => public_path('images/logo-um.png'),
            'signPath'         => public_path('images/ttd_ketua.png'),
            'stampPath'        => public_path('images/stempel.png'),
            'certificateNumber'=> $certificateNumber,
        ];
    }

    /**
     * Generate PDF and return response
     */
    private function generatePdfResponse(array $data, $user, bool $inline = false)
    {
        try {
            $pdf = Pdf::loadView('pdf.basic-listening-certificate', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled'    => true,
                    'isRemoteEnabled'         => false,
                    'isFontSubsettingEnabled' => true,
                    'defaultFont'             => 'Helvetica',
                    'dpi'                     => 72,
                    'defaultMediaType'        => 'screen',
                    'chroot'                  => public_path(),
                ]);

            $binary = $pdf->output();
            $nameFor   = Str::of($user->name ?? 'Peserta')->slug('_');
            $filename  = 'Sertifikat_Basic_Listening_' . $nameFor . '.pdf';
            $disposition = $inline ? 'inline' : 'attachment';

            return response($binary, 200, [
                'Content-Type'           => 'application/pdf',
                'Content-Disposition'    => $disposition . '; filename="' . $filename . '"',
                'Content-Length'         => (string) strlen($binary),
                'Cache-Control'          => 'private, max-age=0, must-revalidate',
                'Pragma'                 => 'public',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Exception $e) {
            Log::error('PDF rendering failed: ' . $e->getMessage());
            throw new \Exception('Gagal merender dokumen PDF.');
        }
    }
}
