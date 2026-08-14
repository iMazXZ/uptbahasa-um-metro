<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EptGroup;
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

        // Grup yang di-assign ke pengawas ini
        $groups = EptGroup::query()
            ->whereHas('proctors', fn ($q) => $q->whereKey($user->id))
            ->orderByDesc('id')
            ->get()
            ->map(function (EptGroup $group) {
                $group->registrations = $group->allRegistrations()
                    ->with(['user.prody'])
                    ->orderByDesc('id')
                    ->get()
                    ->filter(fn ($r) => $r->mode === EptRegistration::MODE_ONLINE)
                    ->values();

                return $group;
            });

        return view('dashboard.pengawas-ept', [
            'user' => $user,
            'groups' => $groups,
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

        $isAssigned = EptGroup::query()
            ->whereIn('id', $groupIds)
            ->whereHas('proctors', fn ($q) => $q->whereKey($proctor->id))
            ->exists();

        abort_unless($isAssigned, 403, 'Anda tidak ditugaskan untuk grup peserta ini.');
    }

    protected function authorizeAttemptProctor(User $proctor, EptOnlineAttempt $attempt): void
    {
        $groupIds = [];
        if ($attempt->ept_group_id) {
            $groupIds[] = $attempt->ept_group_id;
        }
        if ($attempt->ept_registration_id) {
            $groupIds = array_merge($groupIds, $this->registrationGroupIds($attempt->registration));
        }

        $isAssigned = EptGroup::query()
            ->whereIn('id', array_unique($groupIds))
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
