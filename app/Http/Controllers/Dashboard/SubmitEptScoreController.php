<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BasicListeningGrade;
use App\Models\EptSubmission;
use App\Models\SiteSetting;
use App\Support\ImageTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SubmitEptScoreController extends Controller
{
    /**
     * Halaman utama pengajuan surat rekomendasi.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $year         = (int) $user->year;
        $isS2         = $user->prody && str_starts_with($user->prody->name ?? '', 'S2');
        $biodataComplete = SiteSetting::isEptBiodataComplete($user);

        // ==== Cek keikutsertaan Basic Listening (angkatan ≥ 2025) ====
        $completedBL = true;

        // S2 tidak perlu Basic Listening
        if (!$isS2 && $year >= 2025) {
            $grade = BasicListeningGrade::query()
                ->where('user_id', $user->id)
                ->where('user_year', $user->year)
                ->first();

            $completedBL = $grade !== null
                && is_numeric($grade->attendance)
                && is_numeric($grade->final_test);
        }

        // ==== Status pengajuan ====
        $hasSubmissions = EptSubmission::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        $hasApproved = EptSubmission::where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();

        $submissions = EptSubmission::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $approvedSubmission = EptSubmission::where('user_id', $user->id)
            ->where('status', 'approved')
            ->orderByRaw('COALESCE(approved_at, created_at) DESC')
            ->first();

        return view('dashboard.submit-ept-score', [
            'user'               => $user,
            'year'               => $year,
            'biodataComplete'    => $biodataComplete,
            'completedBL'        => $completedBL,
            'hasSubmissions'     => $hasSubmissions,
            'hasApproved'        => $hasApproved,
            'submissions'        => $submissions,
            'approvedSubmission' => $approvedSubmission,
        ]);
    }

    /**
     * Simpan pengajuan baru.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Cek apakah sudah ada pengajuan pending/approved
        $existing = EptSubmission::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return back()
                ->with('error', 'Anda sudah memiliki pengajuan. Silakan menunggu proses atau hubungi admin jika perlu perubahan.')
                ->withInput();
        }

        // Validasi form (mengikuti aturan di Filament)
        $validated = $request->validate([
            'nilai_tes_1'   => ['required', 'integer', 'between:0,677'],
            'tanggal_tes_1' => ['required', 'date'],

            'nilai_tes_2'   => ['required', 'integer', 'between:0,677'],
            'tanggal_tes_2' => ['required', 'date', 'after_or_equal:tanggal_tes_1'],

            'nilai_tes_3'   => ['required', 'integer', 'between:0,677'],
            'tanggal_tes_3' => ['required', 'date', 'after_or_equal:tanggal_tes_2'],

            'foto_path_1'   => ['required', 'image', 'max:8192'],
            'foto_path_2'   => ['required', 'image', 'max:8192'],
            'foto_path_3'   => ['required', 'image', 'max:8192'],
        ], [
            'nilai_tes_1.between' => 'Nilai Tes 1 harus antara 0–677.',
            'nilai_tes_2.between' => 'Nilai Tes 2 harus antara 0–677.',
            'nilai_tes_3.between' => 'Nilai Tes 3 harus antara 0–677.',
            'tanggal_tes_2.after_or_equal' => 'Tanggal Tes 2 harus sama atau setelah Tanggal Tes 1.',
            'tanggal_tes_3.after_or_equal' => 'Tanggal Tes 3 harus sama atau setelah Tanggal Tes 2.',
            'foto_path_1.image' => 'Berkas Tes 1 harus berupa gambar.',
            'foto_path_2.image' => 'Berkas Tes 2 harus berupa gambar.',
            'foto_path_3.image' => 'Berkas Tes 3 harus berupa gambar.',
        ]);

        // Upload & konversi gambar ke webp (pakai helper yang sama)
        $namaSlug = Str::slug($user->name ?? 'pemohon', '_');

        $foto1 = ImageTransformer::toWebpFromUploaded(
            uploaded: $request->file('foto_path_1'),
            targetDisk: 'public',
            targetDir: 'ept/proofs',
            quality: 85,
            maxWidth: 1600,
            maxHeight: null,
            basename: "proof1_{$namaSlug}.webp"
        )['path'];

        $foto2 = ImageTransformer::toWebpFromUploaded(
            uploaded: $request->file('foto_path_2'),
            targetDisk: 'public',
            targetDir: 'ept/proofs',
            quality: 85,
            maxWidth: 1600,
            maxHeight: null,
            basename: "proof2_{$namaSlug}.webp"
        )['path'];

        $foto3 = ImageTransformer::toWebpFromUploaded(
            uploaded: $request->file('foto_path_3'),
            targetDisk: 'public',
            targetDir: 'ept/proofs',
            quality: 85,
            maxWidth: 1600,
            maxHeight: null,
            basename: "proof3_{$namaSlug}.webp"
        )['path'];

        $data = $validated;
        $data['user_id']     = $user->id;
        $data['status']      = 'pending';
        $data['foto_path_1'] = $foto1;
        $data['foto_path_2'] = $foto2;
        $data['foto_path_3'] = $foto3;

        EptSubmission::create($data);

        return redirect()
            ->route('dashboard.ept')
            ->with('success', 'Data berhasil dikirim! Silakan menunggu proses verifikasi dari Lembaga Bahasa.');
    }
}
