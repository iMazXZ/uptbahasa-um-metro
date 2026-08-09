<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EptRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EptIdentityPhotoController extends Controller
{
    public function show(EptRegistration $registration, string $type)
    {
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
}
