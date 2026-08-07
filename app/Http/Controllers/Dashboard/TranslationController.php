<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Penerjemahan;
use App\Models\SiteSetting;
use App\Support\ImageTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TranslationController extends Controller
{
    /* =================== Helpers sama seperti di ListPenerjemahans =================== */

    protected function userHasCompleteBiodata(): bool
    {
        $u = Auth::user();
        return $u ? SiteSetting::isEptBiodataComplete($u) : false;
    }

    protected function userHasCompletedBasicListening(): bool
    {
        $u = Auth::user();
        return $u ? SiteSetting::hasCompletedBasicListening($u) : false;
    }

    protected function basicListeningRequirementMessage(): string
    {
        $u = Auth::user();
        $year = (int) ($u?->year ?? 0);

        if ($year <= 2024) {
            return 'Your archived or manual Basic Listening score has not been recorded yet. Please contact the Language Center if you have already completed the requirement.';
        }

        return 'The request button will appear automatically after your Attendance and Final Test scores have been recorded.';
    }

    /* ============================= INDEX =================================== */

    public function index(Request $request)
    {
        $user = $request->user();

        $biodataComplete = $this->userHasCompleteBiodata();
        $completedBL     = $this->userHasCompletedBasicListening();

        // daftar permohonan milik user
        $records = Penerjemahan::query()
            ->where('user_id', $user->id)
            ->orderByDesc('submission_date')
            ->get();

        // boleh buat permintaan baru?
        $canCreate = $biodataComplete && $completedBL;

        return view('dashboard.translation.index', [
            'user'            => $user,
            'records'         => $records,
            'biodataComplete' => $biodataComplete,
            'completedBL'     => $completedBL,
            'canCreate'       => $canCreate,
            'basicListeningRequirementMessage' => $this->basicListeningRequirementMessage(),
        ]);
    }

    /* ============================= CREATE =================================== */

    public function create(Request $request)
    {
        $user = $request->user();

        if (! $this->userHasCompleteBiodata()) {
            return redirect()->route('dashboard.translation')
                ->with('error', 'Lengkapi biodata terlebih dahulu sebelum mengajukan penerjemahan.');
        }

        if (! $this->userHasCompletedBasicListening()) {
            return redirect()->route('dashboard.translation')
                ->with('error', $this->basicListeningRequirementMessage());
        }

        return view('dashboard.translation.create', [
            'user' => $user,
        ]);
    }

    /* ============================= STORE =================================== */

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $this->userHasCompleteBiodata() || ! $this->userHasCompletedBasicListening()) {
            return redirect()->route('dashboard.translation')
                ->with('error', 'Anda belum memenuhi syarat untuk mengajukan penerjemahan.');
        }

        $validated = $request->validate([
            'bukti_pembayaran' => ['required', 'image', 'max:8192'],
            'source_text'      => ['required', 'string'],
        ], [
            'bukti_pembayaran.required' => 'Bukti pembayaran wajib diunggah.',
            'bukti_pembayaran.image'    => 'Bukti pembayaran harus berupa gambar (PNG/JPG).',
            'bukti_pembayaran.max'      => 'Ukuran bukti pembayaran maksimal 8MB.',
            'source_text.required'      => 'Abstrak yang ingin diterjemahkan wajib diisi.',
        ]);

        // Hitung jumlah kata (copy dari Resource)
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $validated['source_text'])));
        $sourceWordCount = $plain === '' ? 0 : str_word_count(
            $plain,
            0,
            'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'
        );

        // Simpan & kompres bukti pembayaran → webp
        $file = $request->file('bukti_pembayaran');
        $nama = Str::slug($user->name ?? 'pemohon', '_');
        $base = "proof_{$nama}.webp";

        $path = ImageTransformer::toWebpFromUploaded(
            uploaded: $file,
            targetDisk: 'public',
            targetDir: 'penerjemahan/images/payments',
            quality: 85,
            maxWidth: 1600,
            maxHeight: null,
            basename: $base
        )['path'];

        $record = Penerjemahan::create([
            'user_id'            => $user->id,
            'status'             => 'Menunggu',
            'bukti_pembayaran'   => $path,
            'source_text'        => $validated['source_text'],
            'source_word_count'  => $sourceWordCount,
            'submission_date'    => now(),
            // kolom lain (translated_text, translator_id, dll) biarkan default/null
        ]);

        return redirect()
            ->route('dashboard.translation')
            ->with('success', 'Permohonan penerjemahan berhasil dikirim. Silakan menunggu proses verifikasi dari UPT Bahasa.')->with('status_reminder', true);
    }

    /* ============================= EDIT / UPDATE (opsional) ================= */

    public function edit(Penerjemahan $penerjemahan)
    {
        $user = Auth::user();

        if ($penerjemahan->user_id != $user->id) {
            abort(403);
        }

        // logika selaras dengan EditPenerjemahan: hanya relevan saat ditolak
        return view('dashboard.translation.edit', [
            'user'         => $user,
            'penerjemahan' => $penerjemahan,
        ]);
    }

    public function update(Request $request, Penerjemahan $penerjemahan)
    {
        $user = $request->user();

        if ($penerjemahan->user_id != $user->id) {
            abort(403);
        }

        // Di sini kamu bisa batasi: hanya boleh update saat status "Ditolak - ..."
        $rules = [
            'bukti_pembayaran' => ['nullable', 'image', 'max:8192'],
            'source_text'      => ['nullable', 'string'],
        ];

        $data = $request->validate($rules);

        if ($request->hasFile('bukti_pembayaran')) {
            $nama = Str::slug($user->name ?? 'pemohon', '_');
            $base = "proof_{$nama}.webp";

            $path = ImageTransformer::toWebpFromUploaded(
                uploaded: $request->file('bukti_pembayaran'),
                targetDisk: 'public',
                targetDir: 'penerjemahan/images/payments',
                quality: 85,
                maxWidth: 1600,
                maxHeight: null,
                basename: $base
            )['path'];

            $penerjemahan->bukti_pembayaran = $path;
        }

        if (! empty($data['source_text'])) {
            $penerjemahan->source_text = $data['source_text'];

            $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $data['source_text'])));
            $penerjemahan->source_word_count = $plain === '' ? 0 : str_word_count(
                $plain,
                0,
                'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'
            );
        }

        // Setelah diperbaiki, status bisa kamu kembalikan ke "Menunggu"
        $penerjemahan->status = 'Menunggu';
        $penerjemahan->rejection_reason = null;

        $penerjemahan->save();

        return redirect()
            ->route('dashboard.translation')
            ->with('success', 'Permohonan penerjemahan berhasil diperbarui.')->with('status_reminder', true);
    }
}
