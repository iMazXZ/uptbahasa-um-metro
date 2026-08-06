<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BasicListeningAttempt;
use App\Models\BasicListeningCodeUsage;
use App\Models\BasicListeningConnectCode;
use App\Models\BasicListeningSession;
use Illuminate\Database\UniqueConstraintViolationException;

class BasicListeningConnectController extends Controller
{
    public function showForm(BasicListeningSession $session)
    {
        if (! $session->isOpen()) {
            return back()->withErrors(['session' => 'Sesi belum dibuka atau sudah ditutup.']);
        }

        return view('bl.code', compact('session'));
    }

    /**
     * Verifikasi Connect Code & arahkan ke quiz.
     * REVISI: Menghapus percabangan FIB/MC. Semua diarahkan ke bl.quiz.show.
     */
    public function verify(Request $request, BasicListeningSession $session)
    {
        if (! $session->isOpen()) {
            return back()->withErrors(['session' => 'Sesi belum dibuka atau sudah ditutup.']);
        }

        $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ], [
            'code.required' => 'Silakan masukkan Kode Akses.',
        ]);

        $plain = trim((string) $request->input('code'));
        $hash  = hash('sha256', $plain);
        $now = now();

        // === Ambil SEMUA kode untuk sesi ini ===
        $codes = BasicListeningConnectCode::query()
            ->where('session_id', $session->id)
            ->get();

        /** @var \App\Models\BasicListeningConnectCode|null $connect */
        $connect = $codes->first(fn ($c) => hash_equals($c->code_hash, $hash));

        if (! $connect) {
            return back()
                ->withErrors(['code' => 'Kode salah atau kedaluwarsa.'])
                ->withInput();
        }

        // ===== Guardrail: Validasi pembatasan prodi =====
        if ($connect->restrict_to_prody && $connect->prody_id) {
            $user = $request->user();

            if (! $user || ! $user->prody_id) {
                return back()
                    ->withErrors(['code' => 'Kode ini khusus untuk mahasiswa prodi tertentu. Pastikan biodata Anda lengkap.'])
                    ->withInput();
            }

            if ((int) $user->prody_id !== (int) $connect->prody_id) {
                $targetProdi = $connect->prody?->name ?? 'prodi tertentu';
                return back()
                    ->withErrors(['code' => "Kode ini hanya untuk mahasiswa {$targetProdi}."])
                    ->withInput();
            }
        }

        // Tentukan quiz yang akan dibuka
        $quiz = $connect->quiz
            ?? $session->quizzes()->active()->latest('id')->first();

        // Flag status "kode masih bisa dipakai"
        $isWithinWindow =
            (is_null($connect->starts_at) || $connect->starts_at <= $now) &&
            (is_null($connect->ends_at)   || $connect->ends_at   >= $now);

        $isUsable = $connect->is_active && $isWithinWindow;

        // === Kalau kode SUDAH TIDAK USABLE ===
        if (! $isUsable) {
            if ($quiz) {
                // Cek history
                $completed = BasicListeningAttempt::where('user_id', $request->user()->id)
                    ->where('session_id', $session->id)
                    ->where('quiz_id', $quiz->id)
                    ->whereNotNull('submitted_at')
                    ->latest('submitted_at')
                    ->first();

                if ($completed) {
                    return redirect()
                        ->route('bl.history.show', $completed->id)
                        ->with('warning', 'Kodenya sudah kedaluwarsa, menampilkan hasil attempt yang sudah Anda selesaikan.');
                }
            }
            return back()->withErrors(['code' => 'Kode salah atau kedaluwarsa.'])->withInput();
        }

        if (! $quiz || ! $quiz->is_active) {
            return back()->withErrors(['code' => 'Quiz belum tersedia.'])->withInput();
        }

        // Batas pemakaian
        if (! is_null($connect->max_uses)) {
            $uses = BasicListeningCodeUsage::where('connect_code_id', $connect->id)->count();
            if ($uses >= (int) $connect->max_uses) {
                return back()->withErrors(['code' => 'Kode sudah mencapai batas pemakaian.'])->withInput();
            }
        }

        // Cek attempt aktif (resume)
        $existingAttempt = BasicListeningAttempt::where('user_id', $request->user()->id)
            ->where('session_id', $session->id)
            ->where('quiz_id', $quiz->id)
            ->whereNull('submitted_at')
            ->first();

        if ($existingAttempt) {
            return redirect()->route('bl.quiz.show', $existingAttempt);
        }

        // Cek attempt aktif di quiz lain dalam sesi sama
        $otherActiveAttempt = BasicListeningAttempt::where('user_id', $request->user()->id)
            ->where('session_id', $session->id)
            ->where('quiz_id', '!=', $quiz->id)
            ->whereNull('submitted_at')
            ->first();

        if ($otherActiveAttempt) {
            return back()
                ->withErrors(['code' => 'Anda masih memiliki attempt aktif untuk quiz lain di sesi ini.'])
                ->withInput();
        }

        // Catat penggunaan code
        BasicListeningCodeUsage::create([
            'connect_code_id' => $connect->id,
            'user_id'         => $request->user()->id,
            'used_at'         => $now,
            'ip'              => $request->ip(),
            'ua'              => substr((string) $request->userAgent(), 0, 255),
        ]);

        // Buat attempt baru (Satu logika untuk SEMUA tipe soal)
        try {
            // Hitung durasi (opsional, untuk kolom expires_at jika perlu)
            // Di controller quiz baru, timer dihitung dinamis via session->duration_minutes,
            // tapi kita simpan expires_at sebagai cadangan data di DB.
            $durationSeconds = max(60, (int) ($session->duration_minutes ?? 10) * 60);

            $attempt = BasicListeningAttempt::firstOrCreate(
                [
                    'user_id'    => $request->user()->id,
                    'session_id' => $session->id,
                    'quiz_id'    => $quiz->id,
                ],
                [
                    'connect_code_id' => $connect->id,
                    'started_at'      => now(),
                    'expires_at'      => now()->addSeconds($durationSeconds),
                    'submitted_at'    => null,
                ]
            );

            // Redirect ke satu-satunya controller kuis yang valid sekarang
            return redirect()->route('bl.quiz.show', $attempt);

        } catch (UniqueConstraintViolationException $e) {
            // Fallback race condition
            $existingAttempt = BasicListeningAttempt::where('user_id', $request->user()->id)
                ->where('session_id', $session->id)
                ->where('quiz_id', $quiz->id)
                ->first();

            if ($existingAttempt) {
                return redirect()->route('bl.quiz.show', $existingAttempt);
            }

            return back()
                ->withErrors(['code' => 'Terjadi kesalahan sistem. Silakan coba lagi.'])
                ->withInput();
        }
    }
}