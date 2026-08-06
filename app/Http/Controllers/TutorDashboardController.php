<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BasicListeningSession;
use App\Models\BasicListeningAttempt;

class TutorDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Ambil ID prodi binaan tutor dari helper di model User
        $prodyIds = $user->assignedProdyIds(); // [] kalau tidak ada

        $totalMahasiswa = 0;
        $attemptStats   = collect();

        if (! empty($prodyIds)) {
            // Ambil semua attempt yang sudah submit,
            // user-nya dari prodi binaan tutor,
            // plus relasi quiz->session untuk dikelompokkan per session.
            $attempts = BasicListeningAttempt::query()
                ->with(['quiz.session', 'user'])
                ->whereNotNull('submitted_at')
                ->whereHas('user', function ($q) use ($prodyIds) {
                    $q->whereIn('prody_id', $prodyIds);
                })
                ->get();

            // Hitung total distinct mahasiswa binaan yang pernah attempt BL
            $totalMahasiswa = $attempts
                ->pluck('user_id')
                ->unique()
                ->count();

            // Kelompokkan attempt per session (via relasi quiz->session)
            $grouped = $attempts->groupBy(function ($attempt) {
                return optional(optional($attempt->quiz)->session)->id;
            });

            // Bentuk objek sederhana berisi session + student_count
            $attemptStats = $grouped->map(function ($group, $sessionId) {
                $obj = new \stdClass();
                $obj->session = optional($group->first()->quiz)->session;
                $obj->student_count = $group->pluck('user_id')->unique()->count();

                return $obj;
            })->values();
        }

        // Ambil sesi BL terbaru (untuk list di bawah)
        $sessions = BasicListeningSession::query()
            ->latest('id')
            ->take(5)
            ->get();

        return view('dashboard.tutor', [
            'user'           => $user,
            'prodyIds'       => $prodyIds,
            'totalMahasiswa' => $totalMahasiswa,
            'attemptStats'   => $attemptStats,
            'sessions'       => $sessions,
        ]);
    }
}
