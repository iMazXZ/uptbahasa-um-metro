<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penerjemahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CropBuktiController extends Controller
{
    public function show(Penerjemahan $penerjemahan)
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']),
            403
        );
        
        abort_unless(
            $penerjemahan->bukti_pembayaran && 
            Storage::disk('public')->exists($penerjemahan->bukti_pembayaran),
            404,
            'Bukti pembayaran tidak ditemukan'
        );
        
        $imageUrl = Storage::disk('public')->url($penerjemahan->bukti_pembayaran);
        
        // Check if backup exists
        $backupPath = $this->getBackupPath($penerjemahan->bukti_pembayaran);
        $hasBackup = Storage::disk('public')->exists($backupPath);
        
        return view('admin.crop-bukti', [
            'penerjemahan' => $penerjemahan,
            'imageUrl' => $imageUrl,
            'hasBackup' => $hasBackup,
        ]);
    }
    
    public function save(Request $request, Penerjemahan $penerjemahan)
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']),
            403
        );
        
        $request->validate([
            'cropped_image' => 'required|string',
        ]);
        
        try {
            $originalPath = $penerjemahan->bukti_pembayaran;
            $backupPath = $this->getBackupPath($originalPath);
            
            // Backup original if backup doesn't exist yet
            if (!Storage::disk('public')->exists($backupPath)) {
                Storage::disk('public')->copy($originalPath, $backupPath);
            }
            
            // Save cropped image
            $imageData = base64_decode(
                preg_replace('#^data:image/\w+;base64,#i', '', $request->cropped_image)
            );
            
            $filePath = Storage::disk('public')->path($originalPath);
            file_put_contents($filePath, $imageData);
            
            return response()->json([
                'success' => true, 
                'message' => 'Gambar berhasil di-crop! Backup tersedia untuk restore.',
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function restore(Penerjemahan $penerjemahan)
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']),
            403
        );
        
        $originalPath = $penerjemahan->bukti_pembayaran;
        $backupPath = $this->getBackupPath($originalPath);
        
        if (!Storage::disk('public')->exists($backupPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Backup tidak ditemukan. Tidak ada yang bisa di-restore.',
            ], 404);
        }
        
        try {
            // Copy backup to original
            Storage::disk('public')->copy($backupPath, $originalPath);
            
            // Delete backup after restore
            Storage::disk('public')->delete($backupPath);
            
            return response()->json([
                'success' => true,
                'message' => 'Gambar berhasil di-restore ke versi original!',
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function getBackupPath(string $originalPath): string
    {
        $pathInfo = pathinfo($originalPath);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_backup.' . ($pathInfo['extension'] ?? 'webp');
    }
}
