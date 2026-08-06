<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     * Jika maintenance mode aktif, tampilkan halaman maintenance
     * kecuali untuk admin atau request ke panel admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah maintenance mode aktif
        if (!SiteSetting::isMaintenanceEnabled()) {
            return $next($request);
        }

        // Izinkan akses ke panel admin (Filament) - termasuk login
        $path = $request->path();
        if (str_starts_with($path, 'admin') || $request->is('admin*')) {
            return $next($request);
        }

        // Izinkan akses ke halaman livewire (untuk Filament)
        if ($request->is('livewire/*')) {
            return $next($request);
        }

        // Izinkan akses ke asset files
        if ($request->is('storage/*') || $request->is('css/*') || $request->is('js/*')) {
            return $next($request);
        }

        // Izinkan admin untuk bypass maintenance mode
        $user = $request->user();
        if ($user && $user->hasRole('Admin')) {
            return $next($request);
        }

        // Tampilkan halaman maintenance
        return response()->view('errors.maintenance', [], 503);
    }
}
