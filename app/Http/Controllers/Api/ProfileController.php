<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prody;
use App\Support\LegacyBasicListeningScores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Support\ImageTransformer;

class ProfileController extends Controller
{
    /**
     * Get current user profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load('prody');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'srn' => $user->srn,
                'whatsapp' => $user->whatsapp,
                'whatsapp_verified' => $user->whatsapp_verified_at !== null,
                'year' => $user->year,
                'prody_id' => $user->prody_id,
                'prody_name' => $user->prody->name ?? null,
                'nilaibasiclistening' => LegacyBasicListeningScores::effectiveScoreForUser($user),
                'photo' => $user->image ? url(Storage::url($user->image)) : null,
                'created_at' => $user->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update user profile (matches biodata.blade.php fields).
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'srn' => 'sometimes|nullable|string|max:50',
            'whatsapp' => 'sometimes|nullable|string|max:20',
            'year' => 'sometimes|nullable|integer|min:2015|max:2030',
            'prody_id' => 'sometimes|nullable|exists:prodies,id',
            'nilaibasiclistening' => 'sometimes|nullable|numeric|min:0|max:100',
        ]);

        $user->fill($request->only([
            'name', 
            'srn', 
            'whatsapp', 
            'year', 
            'prody_id',
            'nilaibasiclistening',
        ]));

        $prodyName = LegacyBasicListeningScores::resolveProdyName($user->prody_id ? (int) $user->prody_id : null);
        if (LegacyBasicListeningScores::requiresLegacyScore((int) ($user->year ?? 0), $prodyName)) {
            $resolved = LegacyBasicListeningScores::findBySrn($user->srn);
            if ($resolved && is_numeric($resolved->score)) {
                $user->nilaibasiclistening = (float) $resolved->score;
            }
        }

        $user->save();
        $user->load('prody');

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'srn' => $user->srn,
                'whatsapp' => $user->whatsapp,
                'year' => $user->year,
                'prody_id' => $user->prody_id,
                'prody_name' => $user->prody->name ?? null,
                'nilaibasiclistening' => LegacyBasicListeningScores::effectiveScoreForUser($user),
            ],
        ]);
    }

    /**
     * Update profile photo.
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:2048', // 2MB max like web
        ]);

        $user = $request->user();

        // Delete old photo
        if ($user->image && Storage::disk('public')->exists($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        // Convert to WebP and store
        $result = ImageTransformer::toWebpFromUploaded(
            uploaded: $request->file('photo'),
            targetDisk: 'public',
            targetDir: 'photos',
            quality: 85,
            maxWidth: 400
        );
        $photoPath = $result['path'];

        $user->image = $photoPath;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui',
            'data' => [
                'photo' => url(Storage::url($photoPath)),
            ],
        ]);
    }

    /**
     * Get list of prodies for dropdown.
     */
    public function prodies()
    {
        $prodies = Prody::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $prodies,
        ]);
    }
}
