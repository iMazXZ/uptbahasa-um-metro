<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureBlProfileComplete
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if (! $u) {
            return redirect()->route('login');
        }

        // Syarat minimal (sesuaikan kalau field kamu beda nama)
        $missing = [];
        if (empty($u->prody_id)) $missing[] = 'Program Studi';
        if (empty($u->srn))      $missing[] = 'SRN';
        if (empty($u->year))     $missing[] = 'Tahun Angkatan';

        if ($missing) {
            // simpan tujuan semula (kita lempar ke halaman lengkapi biodata)
            $nextUrl = $request->fullUrl();

            return redirect()
                ->route('bl.profile.complete', ['next' => $nextUrl])
                ->with('warning', 'Lengkapi biodata: ' . implode(', ', $missing));
        }

        return $next($request);
    }
}
