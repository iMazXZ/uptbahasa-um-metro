<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EptOnlineAttempt;
use App\Models\EptRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengawasEptController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Token tes online yang di-assign ke pengawas ini
        $tokens = \App\Models\EptOnlineAccessToken::query()
            ->with(['form', 'group', 'registration.user.prody'])
            ->whereHas('proctors', fn ($q) => $q->whereKey($user->id))
            ->orderByDesc('id')
            ->get()
            ->map(function (\App\Models\EptOnlineAccessToken $token) {
                // Peserta dari registrasi yang terhubung ke token ini
                $registration = $token->registration;

                // Kalau token terkait grup, ambil semua peserta online di grup itu
                $registrations = collect();
                if ($token->ept_group_id) {
                    $registrations = $token->group
                        ? $token->group->allRegistrations()
                            ->with(['user.prody'])
                            ->orderByDesc('id')
                            ->get()
                            ->filter(fn ($r) => $r->mode === EptRegistration::MODE_ONLINE)
                            ->values()
                        : collect();
                } elseif ($registration) {
                    $registration->loadMissing('user.prody');
                    $registrations = collect([$registration])
                        ->filter(fn ($r) => $r->mode === EptRegistration::MODE_ONLINE)
                        ->values();
                }

                $token->registrations = $registrations;

                return $token;
            });

        return view('dashboard.pengawas-ept', [
            'user' => $user,
            'tokens' => $tokens,
        ]);
    }

    /**
     * Verifikasi identitas peserta → aktifkan akses tes.
     */
    public function verifyRegistration(Request $request, EptRegistration $registration)
    {
        $this->authorizeProctor($request->user(), $registration);

        if ($registration->mode !== EptRegistration::MODE_ONLINE) {
            return back()->with('error', 'Verifikasi pengawas hanya untuk peserta EPT Online.');
        }

        $registration->update([
            'proctor_verified_at' => now(),
            'proctor_verified_by' => $request->user()->id,
        ]);

        return back()->with('success', "Peserta {$registration->user->name} telah diverifikasi. Peserta dapat memulai tes dengan kode yang dimiliki.");
    }

    /**
     * Jeda (pause) attempt yang sedang berjalan.
     */
    public function pauseAttempt(Request $request, EptOnlineAttempt $attempt)
    {
        $this->authorizeAttemptProctor($request->user(), $attempt);

        if ($attempt->status !== EptOnlineAttempt::STATUS_IN_PROGRESS || $attempt->isPaused()) {
            return back()->with('error', 'Tes tidak dalam kondisi berjalan.');
        }

        $attempt->forceFill([
            'paused_at' => now(),
            'resumed_at' => null,
            'pause_reason' => $request->input('reason', 'Dihentikan sementara oleh pengawas'),
            'pause_controlled_by' => $request->user()->id,
            'meta' => array_merge(is_array($attempt->meta) ? $attempt->meta : [], [
                'paused_by_name' => $request->user()->name,
            ]),
        ])->save();

        return back()->with('success', 'Tes dihentikan sementara (pause). Peserta sedang ditegur.');
    }

    /**
     * Lanjutkan attempt yang di-pause.
     */
    public function resumeAttempt(Request $request, EptOnlineAttempt $attempt)
    {
        $this->authorizeAttemptProctor($request->user(), $attempt);

        if (! $attempt->isPaused()) {
            return back()->with('error', 'Tes tidak dalam keadaan pause.');
        }

        // Hitung durasi pause dan tambahkan ke expires_at agar waktu tidak hilang
        $pausedAt = $attempt->paused_at;
        $pausedSeconds = $pausedAt ? now()->diffInSeconds($pausedAt) : 0;

        $newExpiresAt = null;
        if ($attempt->expires_at) {
            $newExpiresAt = $attempt->expires_at->copy()->addSeconds($pausedSeconds);
        }

        $attempt->forceFill([
            'paused_at' => null,
            'resumed_at' => now(),
            'expires_at' => $newExpiresAt,
            'meta' => array_merge(is_array($attempt->meta) ? $attempt->meta : [], [
                'resumed_by_name' => $request->user()->name,
                'paused_seconds_compensated' => $pausedSeconds,
            ]),
        ])->save();

        return back()->with('success', 'Tes dilanjutkan. Waktu yang hilang selama pause telah dikompensasi.');
    }

    /**
     * Batalkan (disqualify) attempt secara permanen.
     */
    public function disqualifyAttempt(Request $request, EptOnlineAttempt $attempt)
    {
        $this->authorizeAttemptProctor($request->user(), $attempt);

        if (in_array($attempt->status, [EptOnlineAttempt::STATUS_SUBMITTED, EptOnlineAttempt::STATUS_DISQUALIFIED], true)) {
            return back()->with('error', 'Tes sudah selesai atau sudah didiskualifikasi.');
        }

        $attempt->forceFill([
            'status' => EptOnlineAttempt::STATUS_DISQUALIFIED,
            'paused_at' => null,
            'resumed_at' => null,
            'pause_reason' => $request->input('reason', 'Didiskualifikasi oleh pengawas'),
            'pause_controlled_by' => $request->user()->id,
            'meta' => array_merge(is_array($attempt->meta) ? $attempt->meta : [], [
                'disqualified_by_name' => $request->user()->name,
                'disqualified_at' => now()->toDateTimeString(),
            ]),
        ])->save();

        return back()->with('success', 'Peserta didiskualifikasi. Tes tidak dapat dilanjutkan.');
    }

    /**
     * Lihat foto KTP/selfie (protected).
     */
    public function identityPhoto(Request $request, EptRegistration $registration, string $type)
    {
        $this->authorizeProctor($request->user(), $registration);

        $field = $type === 'ktp' ? 'foto_ktp' : 'foto_selfie';
        $path = (string) ($registration->{$field} ?? '');

        if ($path === '' || ! Storage::disk('private')->exists($path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('private')->path($path),
            ['Content-Type' => 'image/webp']
        );
    }

    protected function authorizeProctor(User $proctor, EptRegistration $registration): void
    {
        $groupIds = $this->registrationGroupIds($registration);

        // Pengawas dianggap berwenang jika di-assign ke token yang terkait
        // grup ini, atau ke token yang menunjuk registrasi ini.
        $isAssigned = \App\Models\EptOnlineAccessToken::query()
            ->where(function ($q) use ($groupIds, $registration) {
                $q->whereIn('ept_group_id', $groupIds)
                  ->orWhere('ept_registration_id', $registration->id);
            })
            ->whereHas('proctors', fn ($q) => $q->whereKey($proctor->id))
            ->exists();

        abort_unless($isAssigned, 403, 'Anda tidak ditugaskan untuk peserta ini.');
    }

    protected function authorizeAttemptProctor(User $proctor, EptOnlineAttempt $attempt): void
    {
        $isAssigned = \App\Models\EptOnlineAccessToken::query()
            ->whereKey($attempt->access_token_id)
            ->whereHas('proctors', fn ($q) => $q->whereKey($proctor->id))
            ->exists();

        abort_unless($isAssigned, 403, 'Anda tidak ditugaskan untuk peserta ini.');
    }

    protected function registrationGroupIds(EptRegistration $registration): array
    {
        return array_values(array_filter([
            $registration->grup_1_id,
            $registration->grup_2_id,
            $registration->grup_3_id,
            $registration->grup_4_id,
        ]));
    }
}
