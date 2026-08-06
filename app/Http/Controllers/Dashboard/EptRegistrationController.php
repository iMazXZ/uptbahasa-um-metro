<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EptRegistration;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\ImageTransformer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EptRegistrationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $latestRegistration = EptRegistration::with(['grup1', 'grup2', 'grup3', 'grup4'])
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        $blockingRegistration = EptRegistration::with(['grup1', 'grup2', 'grup3', 'grup4'])
            ->where('user_id', $user->id)
            ->active()
            ->latest('id')
            ->get()
            ->first(fn (EptRegistration $registration) => $registration->blocksNewRegistration());

        $registration = $blockingRegistration ?? $latestRegistration;
        [$allowed, $reason] = SiteSetting::checkEptEligibility($user);
        $canCreateRegistration = ($allowed ?? false) && (! $registration || ! $registration->blocksNewRegistration());

        if (! $registration && ! $allowed) {
            abort(403, $reason ?? 'Anda tidak memenuhi syarat pendaftaran EPT.');
        }

        return view('dashboard.ept-registration.index', [
            'user' => $user,
            'registration' => $registration,
            'canCreateRegistration' => $canCreateRegistration,
            'eligibilityReason' => $reason,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        [$allowed, $reason] = SiteSetting::checkEptEligibility($user);
        if (! $allowed) {
            return back()->with('error', $reason ?? 'Anda tidak memenuhi syarat pendaftaran EPT.');
        }

        $request->validate([
            'student_status' => ['required', 'in:' . implode(',', array_keys(EptRegistration::studentStatusOptions()))],
            'bukti_pembayaran' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ], [
            'student_status.required' => 'Status peserta wajib dipilih.',
            'student_status.in' => 'Status peserta tidak valid.',
            'bukti_pembayaran.required' => 'Bukti pembayaran wajib diunggah.',
            'bukti_pembayaran.image' => 'File harus berupa gambar.',
            'bukti_pembayaran.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'bukti_pembayaran.max' => 'Ukuran file maksimal 8MB.',
        ]);

        $createdRegistration = DB::transaction(function () use ($request, $user): ?EptRegistration {
            $user->newQuery()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            $blockingRegistration = EptRegistration::query()
                ->with(['grup1', 'grup2', 'grup3', 'grup4'])
                ->where('user_id', $user->id)
                ->active()
                ->latest('id')
                ->lockForUpdate()
                ->get()
                ->first(fn (EptRegistration $registration) => $registration->blocksNewRegistration());

            if ($blockingRegistration) {
                return null;
            }

            $file = $request->file('bukti_pembayaran');
            $basename = 'ept_payment_' . Str::of($user->id)->padLeft(6, '0') . '_' . time() . '.webp';

            $result = ImageTransformer::toWebpFromUploaded(
                uploaded: $file,
                targetDisk: 'public',
                targetDir: 'ept-registrations/payments',
                quality: 85,
                maxWidth: 1600,
                maxHeight: null,
                basename: $basename
            );

            return EptRegistration::create([
                'user_id' => $user->id,
                'student_status' => $request->string('student_status')->toString(),
                'test_quota' => EptRegistration::defaultTestQuotaForStudentStatus(
                    $request->string('student_status')->toString()
                ),
                'bukti_pembayaran' => $result['path'],
                'status' => 'pending',
            ]);
        });

        if (! $createdRegistration) {
            return back()->with('error', 'Anda sudah memiliki pendaftaran aktif.');
        }

        $this->sendNtfyNotification($createdRegistration, $user);

        return redirect()->route('dashboard.ept-registration.index')
            ->with('success', 'Pendaftaran berhasil! Silakan tunggu verifikasi dari admin.');
    }

    private function sendNtfyNotification(EptRegistration $registration, User $user): void
    {
        $topicUrl = trim((string) config('services.ntfy.topic_url'));
        if ($topicUrl === '') {
            return;
        }

        $user->loadMissing('prody');

        $timeout = max(1, (int) config('services.ntfy.timeout_seconds', 5));
        $token = trim((string) config('services.ntfy.auth_token'));
        $prodyName = $user->prody?->name ?? '-';
        $statusLabel = EptRegistration::studentStatusLabel($registration->student_status);
        $adminUrl = trim((string) config('services.ntfy.click_url'));
        if ($adminUrl === '') {
            $adminUrl = url('/admin/ept-registrations');
        }
        $iconUrl = trim((string) config('services.ntfy.icon_url'));

        $nameLine = trim((string) ($user->name ?? '-'));
        $srnLine = trim((string) ($user->srn ?? '-'));

        if ($nameLine === '') {
            $nameLine = '-';
        }
        if ($srnLine === '') {
            $srnLine = '-';
        }

        $message = implode("\n", [
            $nameLine . ' (' . $srnLine . ')',
            'Prodi: ' . $prodyName,
            'Status: ' . $statusLabel,
        ]);

        try {
            $headers = [
                'Title' => 'Pendaftaran EPT Baru',
                'Priority' => 'default',
                'Click' => $adminUrl,
            ];

            if ($iconUrl !== '') {
                $headers['Icon'] = $iconUrl;
            }

            $request = Http::timeout($timeout)
                ->withHeaders($headers);

            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request
                ->withBody($message, 'text/plain; charset=utf-8')
                ->post($topicUrl);

            if (! $response->successful()) {
                Log::warning('Ntfy notification failed for EPT registration.', [
                    'topic_url' => $topicUrl,
                    'status' => $response->status(),
                    'registration_id' => $registration->id,
                    'user_id' => $user->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Ntfy notification error for EPT registration.', [
                'topic_url' => $topicUrl,
                'registration_id' => $registration->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function kartuPeserta(Request $request)
    {
        $user = Auth::user();
        $jadwalNum = $request->query('jadwal', 1);
        
        $registration = EptRegistration::with(['grup1', 'grup2', 'grup3', 'grup4'])
            ->where('user_id', $user->id)
            ->approved()
            ->latest()
            ->first();
            
        if (!$registration) {
            abort(404, 'Pendaftaran tidak ditemukan.');
        }
        
        // Get the specific grup based on jadwal number
        $grup = match((int)$jadwalNum) {
            1 => $registration->grup1,
            2 => $registration->grup2,
            3 => $registration->grup3,
            4 => $registration->grup4,
            default => null,
        };
        
        if (!$grup || !$grup->jadwal) {
            abort(404, 'Jadwal belum ditetapkan.');
        }
        
        $pdf = Pdf::loadView('pdf.kartu-peserta-ept', [
            'user' => $user,
            'registration' => $registration,
            'grup' => $grup,
            'jadwalNum' => $jadwalNum,
        ]);
        
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('Kartu_Peserta_EPT_' . Str::slug($user->name) . '_Jadwal' . $jadwalNum . '.pdf');
    }

}
