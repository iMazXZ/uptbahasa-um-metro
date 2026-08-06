<?php

namespace App\Http\Livewire;

use App\Models\Penerjemahan;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ImageCropper extends Component
{
    public ?int $recordId = null;
    public ?string $imageUrl = null;
    public ?string $imagePath = null;
    public bool $showModal = false;

    protected $listeners = ['openCropper' => 'openCropper'];

    public function openCropper(int $recordId): void
    {
        $record = Penerjemahan::find($recordId);
        
        if (!$record || !$record->bukti_pembayaran) {
            $this->dispatch('notify', type: 'error', message: 'Gambar tidak ditemukan');
            return;
        }

        $this->recordId = $recordId;
        $this->imagePath = $record->bukti_pembayaran;
        $this->imageUrl = Storage::disk('public')->url($record->bukti_pembayaran);
        $this->showModal = true;
    }

    public function saveCroppedImage(string $croppedData): void
    {
        if (!$this->recordId || !$this->imagePath) {
            $this->dispatch('notify', type: 'error', message: 'Data tidak valid');
            return;
        }

        try {
            // Decode base64 image
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $croppedData));
            
            // Get file path
            $filePath = Storage::disk('public')->path($this->imagePath);
            
            // Save cropped image
            file_put_contents($filePath, $imageData);
            
            $this->showModal = false;
            $this->dispatch('notify', type: 'success', message: 'Gambar berhasil di-crop');
            $this->dispatch('refreshTable');
            
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['recordId', 'imageUrl', 'imagePath']);
    }

    public function render()
    {
        return view('livewire.image-cropper');
    }
}
