<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penerjemahan;
use App\Models\EptSubmission;
use App\Models\BasicListeningGrade;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Verify document by code (public endpoint).
     */
    public function verify(string $code)
    {
        // A. Penerjemahan
        if ($rec = Penerjemahan::with(['users.prody'])->where('verification_code', $code)->first()) {
            $status = $rec->status === 'Selesai' ? 'VALID' : 'PENDING';
            
            return response()->json([
                'success' => true,
                'data' => [
                    'type' => 'penerjemahan',
                    'title' => 'Dokumen Penerjemahan Abstrak',
                    'status' => $status,
                    'reason' => $status === 'VALID' 
                        ? 'Data cocok dan status dokumen telah diselesaikan.'
                        : 'Dokumen ditemukan, namun status belum selesai.',
                    'applicant_name' => $rec->users->name ?? '-',
                    'srn' => $rec->users->srn ?? '-',
                    'prody' => $rec->users->prody->name ?? '-',
                    'status_text' => $rec->status ?? '-',
                    'done_at' => optional($rec->completion_date)?->toIso8601String(),
                    'verification_code' => $rec->verification_code,
                    'pdf_url' => $rec->pdf_path ? url('storage/' . $rec->pdf_path) : null,
                ],
            ]);
        }

        // B. Basic Listening Certificate
        if ($rec = BasicListeningGrade::with(['user.prody'])->where('verification_code', $code)->first()) {
            $u = $rec->user;
            $isComplete = is_numeric($rec->attendance) && is_numeric($rec->final_test);
            $status = $isComplete ? 'VALID' : 'PENDING';

            return response()->json([
                'success' => true,
                'data' => [
                    'type' => 'basic_listening',
                    'title' => 'Sertifikat Basic Listening',
                    'status' => $status,
                    'reason' => $isComplete
                        ? 'Data cocok dan komponen nilai wajib sudah lengkap.'
                        : 'Dokumen ditemukan, namun nilai belum lengkap.',
                    'applicant_name' => $u->name ?? '-',
                    'srn' => $u->srn ?? '-',
                    'prody' => $u->prody->name ?? '-',
                    'status_text' => $status,
                    'verification_code' => $rec->verification_code,
                    'pdf_url' => $isComplete 
                        ? route('bl.certificate.bycode', ['code' => $code])
                        : null,
                    'scores' => [
                        ['label' => 'Attendance', 'value' => $rec->attendance],
                        ['label' => 'Final Test', 'value' => $rec->final_test],
                    ],
                ],
            ]);
        }

        // C. EPT Submission
        if ($rec = EptSubmission::with(['user.prody'])->where('verification_code', $code)->first()) {
            $status = $rec->status === 'approved' ? 'VALID' : 'PENDING';

            return response()->json([
                'success' => true,
                'data' => [
                    'type' => 'ept',
                    'title' => 'Surat Rekomendasi EPT',
                    'status' => $status,
                    'reason' => $status === 'VALID'
                        ? 'Surat telah disetujui.'
                        : 'Pengajuan ditemukan, namun belum disetujui.',
                    'applicant_name' => $rec->user->name ?? '-',
                    'srn' => $rec->user->srn ?? '-',
                    'prody' => $rec->user->prody->name ?? '-',
                    'status_text' => $rec->status ?? '-',
                    'done_at' => optional($rec->approved_at)?->toIso8601String(),
                    'verification_code' => $rec->verification_code,
                    'nomor_surat' => $rec->surat_nomor ?? '-',
                    'pdf_url' => route('verification.ept.pdf', ['code' => $code]),
                    'scores' => [
                        ['label' => 'Tes I', 'date' => $rec->tanggal_tes_1, 'value' => $rec->nilai_tes_1],
                        ['label' => 'Tes II', 'date' => $rec->tanggal_tes_2, 'value' => $rec->nilai_tes_2],
                        ['label' => 'Tes III', 'date' => $rec->tanggal_tes_3, 'value' => $rec->nilai_tes_3],
                    ],
                ],
            ]);
        }

        // D. Not found
        return response()->json([
            'success' => false,
            'data' => [
                'type' => null,
                'title' => 'Verifikasi Dokumen',
                'status' => 'INVALID',
                'reason' => 'Kode verifikasi tidak ditemukan.',
            ],
        ], 404);
    }
}
