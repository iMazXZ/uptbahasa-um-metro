<?php

namespace App\Http\Controllers;

use App\Models\EptOnlineAccessToken;
use App\Models\EptOnlineAttempt;
use App\Models\EptOnlineForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EptOnlineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $activeAttempts = EptOnlineAttempt::with('form')
            ->where('user_id', $user->id)
            ->whereIn('status', [
                EptOnlineAttempt::STATUS_DRAFT,
                EptOnlineAttempt::STATUS_IN_PROGRESS,
            ])
            ->latest('updated_at')
            ->get();

        $completedAttempts = EptOnlineAttempt::with(['form', 'result'])
            ->where('user_id', $user->id)
            ->where('status', EptOnlineAttempt::STATUS_SUBMITTED)
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        return view('ept-online.index', compact('activeAttempts', 'completedAttempts'));
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:128'],
        ], [
            'code.required' => 'Enter your test access code.',
        ]);

        $user = $request->user();
        $plainCode = trim((string) $request->input('code'));
        $codeHash = hash('sha256', $plainCode);

        /** @var EptOnlineAccessToken|null $token */
        $token = EptOnlineAccessToken::query()
            ->with(['form', 'registration'])
            ->where('token_hash', $codeHash)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->latest('id')
            ->first();

        if (! $token || ! $token->withinWindow()) {
            throw ValidationException::withMessages([
                'code' => 'Access code invalid or expired.',
            ]);
        }

        if (! $token->form || $token->form->status !== EptOnlineForm::STATUS_PUBLISHED) {
            throw ValidationException::withMessages([
                'code' => 'This test package is not available yet.',
            ]);
        }

        if ($token->user_id && (int) $token->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'code' => 'This code is not registered for your account.',
            ]);
        }

        if ($token->registration && (int) $token->registration->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'code' => 'This code does not match your EPT registration.',
            ]);
        }

        $attempt = DB::transaction(function () use ($token, $user, $request) {
            /** @var EptOnlineAccessToken $lockedToken */
            $lockedToken = EptOnlineAccessToken::query()
                ->whereKey($token->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingAttempt = EptOnlineAttempt::query()
                ->where('access_token_id', $lockedToken->id)
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    EptOnlineAttempt::STATUS_DRAFT,
                    EptOnlineAttempt::STATUS_IN_PROGRESS,
                ])
                ->latest('id')
                ->first();

            if ($existingAttempt) {
                return $existingAttempt;
            }

            if ((int) $lockedToken->used_attempts >= (int) $lockedToken->max_attempts) {
                throw ValidationException::withMessages([
                    'code' => 'This code has reached its attempt limit.',
                ]);
            }

            $form = EptOnlineForm::query()
                ->with(['sections' => fn ($query) => $query->orderBy('sort_order')])
                ->findOrFail($lockedToken->form_id);

            $firstSection = $form->sections->first();
            if (! $firstSection) {
                throw ValidationException::withMessages([
                    'code' => 'This test package does not have any available sections yet.',
                ]);
            }

            $attempt = EptOnlineAttempt::create([
                'form_id' => $form->id,
                'access_token_id' => $lockedToken->id,
                'user_id' => $user->id,
                'ept_registration_id' => $lockedToken->ept_registration_id,
                'ept_group_id' => $lockedToken->ept_group_id,
                'current_section_type' => $firstSection->type,
                'status' => EptOnlineAttempt::STATUS_DRAFT,
                'started_at' => null,
                'current_section_started_at' => null,
                'expires_at' => null,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            $lockedToken->forceFill([
                'used_attempts' => (int) $lockedToken->used_attempts + 1,
                'last_used_at' => now(),
            ])->save();

            return $attempt;
        });

        return redirect()->route('ept-online.attempt.show', [
            'attempt' => $attempt->public_id,
        ]);
    }
}
