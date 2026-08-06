<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoleDashboardRedirectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('pendaftar')) {
            // SELALU menang dulu kalau punya role ini
            return redirect()->route('dashboard.pendaftar');
        }

        if ($user->hasRole('Penerjemah')) {
            // Redirect ke dashboard Blade khusus Penerjemah
            return redirect()->route('dashboard.penerjemah');
        }

        if ($user->hasRole('tutor')) {
            return redirect()->route('filament.admin.pages.2'); // panel tutor/admin
        }

        if ($user->hasAnyRole(['Admin', 'Staf Administrasi', 'Kepala Lembaga'])) {
            return redirect()->route('filament.admin.pages.2');
        }

        return redirect()->route('dashboard.pendaftar');
    }
}
