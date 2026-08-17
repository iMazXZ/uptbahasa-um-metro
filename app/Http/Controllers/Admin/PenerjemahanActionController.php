<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penerjemahan;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PenerjemahanActionController extends Controller
{
    public function approve(Penerjemahan $penerjemahan)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']), 403);

        $penerjemahan->update([
            'status' => 'Disetujui',
            'rejection_reason' => null,
        ]);

        Notification::make()->title("Pembayaran disetujui untuk {$penerjemahan->users?->name}")->success()->send();

        return back();
    }

    public function rejectPayment(Request $request, Penerjemahan $penerjemahan)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']), 403);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        // Hapus file bukti pembayaran dari storage
        if ($penerjemahan->bukti_pembayaran && Storage::disk('public')->exists($penerjemahan->bukti_pembayaran)) {
            Storage::disk('public')->delete($penerjemahan->bukti_pembayaran);
        }

        $penerjemahan->update([
            'status'           => 'Ditolak - Pembayaran Tidak Valid',
            'rejection_reason' => $data['rejection_reason'],
            'translator_id'    => null,
            'bukti_pembayaran' => null,
        ]);

        $penerjemahan->users?->notify(new \App\Notifications\PenerjemahanStatusNotification(
            status: 'Ditolak - Pembayaran Tidak Valid',
            rejectionReason: $data['rejection_reason'],
        ));

        Notification::make()->title('Ditolak, bukti pembayaran dihapus, dan notifikasi diproses.')->danger()->send();

        return back();
    }

    public function rejectDocument(Request $request, Penerjemahan $penerjemahan)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']), 403);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $penerjemahan->update([
            'status'           => 'Ditolak - Dokumen Tidak Valid',
            'rejection_reason' => $data['rejection_reason'],
            'translator_id'    => null,
        ]);

        $penerjemahan->users?->notify(new \App\Notifications\PenerjemahanStatusNotification(
            status: 'Ditolak - Dokumen Tidak Valid',
            rejectionReason: $data['rejection_reason'],
        ));

        Notification::make()->title('Ditolak dan notifikasi diproses.')->danger()->send();

        return back();
    }

    public function assignTranslator(Request $request, Penerjemahan $penerjemahan)
    {
        abort_unless(auth()->user()?->hasRole('Admin'), 403);

        $data = $request->validate([
            'translator_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $translator = User::find($data['translator_id']);

        $penerjemahan->update([
            'status'        => 'Diproses',
            'translator_id' => $data['translator_id'],
        ]);

        // Notifikasi ke pemohon
        $penerjemahan->users?->notify(new \App\Notifications\PenerjemahanStatusNotification('Diproses'));

        // Notifikasi ke penerjemah
        if ($translator) {
            $translator->notify(new \App\Notifications\TranslatorAssignedNotification(
                pemohonName: $penerjemahan->users?->name ?? 'Pemohon',
                wordCount: $penerjemahan->source_word_count ?? 0,
                dashboardUrl: route('dashboard.penerjemah')
            ));
        }

        Notification::make()->title('Penerjemahan diproses. Notifikasi pemohon dan penerjemah diproses.')->success()->send();

        return back();
    }

    public function setSelesai(Penerjemahan $penerjemahan)
    {
        abort_unless(auth()->user()?->hasRole('Admin'), 403);

        $penerjemahan->ensureVerification();
        $penerjemahan->update([
            'status'          => 'Selesai',
            'completion_date' => now(),
        ]);

        $penerjemahan->users?->notify(new \App\Notifications\PenerjemahanStatusNotification('Selesai', $penerjemahan->verification_url));

        Notification::make()->title('Status selesai dan notifikasi diproses.')->success()->send();

        return back();
    }
}
