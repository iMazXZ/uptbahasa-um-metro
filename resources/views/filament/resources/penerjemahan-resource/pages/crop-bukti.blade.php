<x-filament-panels::page>
    <div 
        x-data="imageCropper()"
        x-init="initCropper()"
        class="space-y-6"
    >
        @if($imageUrl)
            {{-- Cropper Container --}}
            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <div class="max-h-[60vh] overflow-hidden flex items-center justify-center bg-gray-100 dark:bg-gray-900 rounded">
                    <img id="cropper-image" src="{{ $imageUrl }}" class="max-w-full" alt="Bukti Pembayaran">
                </div>
            </div>

            {{-- Toolbar --}}
            <div class="bg-white rounded-lg shadow p-4 dark:bg-gray-800">
                <div class="flex flex-wrap gap-3 justify-center">
                    <button type="button" @click="rotate(-90)" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Rotate Kiri
                    </button>
                    <button type="button" @click="rotate(90)" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"/>
                        </svg>
                        Rotate Kanan
                    </button>
                    <button type="button" @click="flipH()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Flip Horizontal
                    </button>
                    <button type="button" @click="reset()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Reset
                    </button>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 justify-end">
                <a href="{{ \App\Filament\Resources\PenerjemahanResource::getUrl('index') }}" 
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    Batal
                </a>
                <button 
                    type="button" 
                    @click="saveCrop()"
                    :disabled="saving"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition disabled:opacity-50"
                >
                    <template x-if="saving">
                        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </template>
                    <template x-if="!saving">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                    <span x-text="saving ? 'Menyimpan...' : 'Simpan Crop'"></span>
                </button>
            </div>
        @else
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6 text-center">
                <p class="text-yellow-700 dark:text-yellow-400">Gambar bukti pembayaran tidak ditemukan.</p>
                <a href="{{ \App\Filament\Resources\PenerjemahanResource::getUrl('index') }}" 
                   class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg font-medium hover:bg-yellow-200 transition">
                    Kembali ke Daftar
                </a>
            </div>
        @endif
    </div>

    {{-- Cropper.js CDN --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
        <style>
            .cropper-container { max-height: 60vh; }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
        <script>
            function imageCropper() {
                return {
                    cropper: null,
                    saving: false,
                    
                    initCropper() {
                        this.$nextTick(() => {
                            const image = document.getElementById('cropper-image');
                            if (image) {
                                this.cropper = new Cropper(image, {
                                    viewMode: 1,
                                    dragMode: 'crop',
                                    autoCropArea: 0.9,
                                    responsive: true,
                                    restore: false,
                                    guides: true,
                                    center: true,
                                    highlight: true,
                                    cropBoxMovable: true,
                                    cropBoxResizable: true,
                                    background: true,
                                });
                            }
                        });
                    },
                    
                    rotate(degrees) {
                        if (this.cropper) this.cropper.rotate(degrees);
                    },
                    
                    flipH() {
                        if (this.cropper) {
                            const data = this.cropper.getData();
                            this.cropper.scaleX(data.scaleX === -1 ? 1 : -1);
                        }
                    },
                    
                    reset() {
                        if (this.cropper) this.cropper.reset();
                    },
                    
                    saveCrop() {
                        if (!this.cropper || this.saving) return;
                        
                        this.saving = true;
                        
                        const canvas = this.cropper.getCroppedCanvas({
                            maxWidth: 2000,
                            maxHeight: 2000,
                            imageSmoothingEnabled: true,
                            imageSmoothingQuality: 'high',
                        });
                        
                        if (canvas) {
                            const croppedData = canvas.toDataURL('image/jpeg', 0.9);
                            @this.call('saveCroppedImage', croppedData);
                        } else {
                            this.saving = false;
                        }
                    }
                }
            }
        </script>
    @endpush
</x-filament-panels::page>
