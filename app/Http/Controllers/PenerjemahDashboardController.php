<?php

namespace App\Http\Controllers;

use App\Models\Penerjemahan;
use Illuminate\Http\Request;

class PenerjemahDashboardController extends Controller
{
    /**
     * Dashboard utama Penerjemah
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Statistik tugas penerjemahan
        $totalTugas = Penerjemahan::where('translator_id', $user->id)->count();
        $selesai = Penerjemahan::where('translator_id', $user->id)
            ->where('status', 'Selesai')
            ->count();
        $dalamProses = Penerjemahan::where('translator_id', $user->id)
            ->whereIn('status', ['Disetujui', 'Diproses'])
            ->count();

        // 5 tugas terkini (yang belum selesai prioritas, lalu selesai)
        $tugasTerkini = Penerjemahan::where('translator_id', $user->id)
            ->orderByRaw("CASE 
                WHEN status = 'Disetujui' THEN 1 
                WHEN status = 'Diproses' THEN 2 
                WHEN status = 'Selesai' THEN 3 
                ELSE 4 
            END")
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        // Tugas Terkini per kategori tab
        $tugas = Penerjemahan::where('translator_id', $user->id)
            ->whereNull('translated_text')
            ->where('status', '!=', 'Selesai')
            ->orderByRaw("CASE WHEN status = 'Disetujui' THEN 1 WHEN status = 'Diproses' THEN 2 ELSE 3 END")
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $draft = Penerjemahan::where('translator_id', $user->id)
            ->whereNotNull('translated_text')
            ->where('status', '!=', 'Selesai')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $selesaiList = Penerjemahan::where('translator_id', $user->id)
            ->where('status', 'Selesai')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return view('dashboard.penerjemah', [
            'user' => $user,
            'totalTugas' => $totalTugas,
            'selesai' => $selesai,
            'dalamProses' => $dalamProses,
            'tugasTerkini' => $tugasTerkini,
            'tugas' => $tugas,
            'draft' => $draft,
            'selesaiList' => $selesaiList,
        ]);
    }

    /**
     * Daftar semua tugas penerjemahan
     */
    public function tugas(Request $request)
    {
        $user = $request->user();
        $filter = $request->get('filter', 'semua');

        $query = Penerjemahan::where('translator_id', $user->id)
            ->with('users');

        // Apply filter
        if ($filter === 'belum') {
            $query->whereIn('status', ['Disetujui', 'Diproses']);
        } elseif ($filter === 'selesai') {
            $query->where('status', 'Selesai');
        }

        $tugas = $query->orderByRaw("CASE 
                WHEN status = 'Disetujui' THEN 1 
                WHEN status = 'Diproses' THEN 2 
                WHEN status = 'Selesai' THEN 3 
                ELSE 4 
            END")
            ->orderByDesc('updated_at')
            ->paginate(10);

        return view('dashboard.penerjemah-tugas', [
            'user' => $user,
            'tugas' => $tugas,
            'filter' => $filter,
        ]);
    }

    /**
     * Halaman edit terjemahan (full Blade)
     */
    public function edit(Request $request, Penerjemahan $penerjemahan)
    {
        $user = $request->user();
        // dd($penerjemahan->translator_id, $user->id, $penerjemahan->translator_id != $user->id);
        // Pastikan penerjemah yang tepat
        if ($penerjemahan->translator_id != $user->id) {
            abort(403, 'Anda tidak memiliki akses ke tugas ini.');
        }

        return view('dashboard.penerjemah-edit', [
            'user' => $user,
            'tugas' => $penerjemahan->load('users'),
        ]);
    }

    /**
     * Update terjemahan
     */
    public function update(Request $request, Penerjemahan $penerjemahan)
    {
        $user = $request->user();

        // Pastikan penerjemah yang tepat
        if ($penerjemahan->translator_id != $user->id) {
            abort(403, 'Anda tidak memiliki akses ke tugas ini.');
        }

        $request->validate([
            'translated_text' => 'required|string|min:10',
        ], [
            'translated_text.required' => 'Hasil terjemahan wajib diisi.',
            'translated_text.min' => 'Hasil terjemahan minimal 10 karakter.',
        ]);

        $penerjemahan->update([
            'translated_text' => $request->translated_text,
            'status' => 'Diproses', // Update status ke Diproses saat ada perubahan
        ]);

        // Kirim notifikasi ke Admin/Staf/Kepala saat penerjemah mengajukan untuk diverifikasi
        if ($request->boolean('submit')) {
            $penerjemahan->update([
                'submitted_for_review_at' => now(),
            ]);

            $admins = \App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Staf Administrasi', 'Kepala Lembaga']))
                ->get();

            $pemohonName = $penerjemahan->users?->name ?? 'Pemohon';

            // Tulis langsung ke tabel notifications (sinkron) agar langsung muncul di bell Filament
            // tanpa menunggu queue worker.
            $data = \Filament\Notifications\Notification::make()
                ->title('Terjemahan Selesai')
                ->body("Terjemahan abstrak milik {$pemohonName} telah selesai dikerjakan oleh {$user->name}.")
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('open')
                        ->label('Lihat')
                        ->url(route('filament.admin.resources.penerjemahan.index')),
                ])
                ->getDatabaseMessage();

            $dbModel = \Illuminate\Notifications\DatabaseNotification::class;

            foreach ($admins as $admin) {
                $dbModel::query()->create([
                    'id'              => (string) \Illuminate\Support\Str::uuid(),
                    'type'            => \Filament\Notifications\DatabaseNotification::class,
                    'notifiable_type' => get_class($admin),
                    'notifiable_id'   => $admin->getKey(),
                    'data'            => $data,
                    'read_at'         => null,
                ]);
            }

            return redirect()
                ->route('dashboard.penerjemah')
                ->with('success', 'Terjemahan berhasil dikirim untuk diverifikasi admin. Pemohon akan dinotifikasi setelah terverifikasi.');
        }

        return redirect()
            ->route('dashboard.penerjemah.edit', $penerjemahan)
            ->with('success', 'Terjemahan berhasil disimpan sebagai draft!');
    }
}
