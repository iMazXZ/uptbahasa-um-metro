<?php

namespace App\Filament\Resources\PenerjemahanResource\Pages;

use App\Filament\Resources\PenerjemahanResource;
use App\Models\Penerjemahan;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class CropBukti extends Page
{
    protected static string $resource = PenerjemahanResource::class;
    
    protected static string $view = 'filament.resources.penerjemahan-resource.pages.crop-bukti';
    
    protected static ?string $title = 'Crop Bukti Pembayaran';
    
    public ?Penerjemahan $record = null;
    public ?string $imageUrl = null;

    public function mount(int|string $record): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Admin', 'Staf Administrasi']),
            403,
            'Anda tidak memiliki akses.'
        );
        
        $this->record = Penerjemahan::findOrFail($record);
        
        if ($this->record->bukti_pembayaran && Storage::disk('public')->exists($this->record->bukti_pembayaran)) {
            $this->imageUrl = Storage::disk('public')->url($this->record->bukti_pembayaran);
        }
    }
    
    public function saveCroppedImage(string $croppedData): void
    {
        if (!$this->record || !$this->record->bukti_pembayaran) {
            Notification::make()->danger()->title('Data tidak valid')->send();
            return;
        }

        try {
            // Decode base64 image
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $croppedData));
            
            // Get file path
            $filePath = Storage::disk('public')->path($this->record->bukti_pembayaran);
            
            // Save cropped image
            file_put_contents($filePath, $imageData);
            
            Notification::make()->success()->title('Gambar berhasil di-crop!')->send();
            
            // Redirect back to list
            $this->redirect(PenerjemahanResource::getUrl('index'));
            
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Gagal menyimpan')->body($e->getMessage())->send();
        }
    }

    public function getTitle(): string
    {
        return 'Crop Bukti Pembayaran - ' . ($this->record?->users?->name ?? 'Unknown');
    }
}
