<?php

namespace App\Http\Controllers;

use App\Models\EptSubmission;
use App\Models\Penerjemahan;
use Illuminate\Http\Request;

class PendaftarDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Penerjemahan terakhir milik user
        $latestTranslation = Penerjemahan::where('user_id', $user->id)
            ->orderByDesc('submission_date')
            ->orderByDesc('created_at')
            ->first();

        // Pengajuan surat rekomendasi EPT terakhir milik user
        $latestEptSubmission = EptSubmission::where('user_id', $user->id)
            ->orderByRaw('COALESCE(approved_at, created_at) DESC')
            ->first();

        $latestNotifications = $user->notifications()
            ->limit(8)
            ->get();

        $unreadNotificationsCount = $user->unreadNotifications()
            ->count();

        return view('dashboard.pendaftar', [
            'user'                => $user,
            'latestTranslation'   => $latestTranslation,
            'latestEptSubmission' => $latestEptSubmission,
            'latestNotifications' => $latestNotifications,
            'unreadNotificationsCount' => $unreadNotificationsCount,
        ]);
    }
}
