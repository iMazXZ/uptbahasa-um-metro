<div>
    @if($showModal)
        <div 
            x-data="imageCropper()"
            x-init="initCropper('{{ $imageUrl }}')"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                {{-- Modal Panel --}}
                <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-3xl sm:w-full">
                    {{-- Header --}}
                    <div class="bg-gray-50 px-4 py-3 border-b flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Crop Bukti Pembayaran</h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Content --}}
                    <div class="p-4">
                        <div class="max-h-[60vh] overflow-hidden">
                            <img id="cropper-image" src="{{ $imageUrl }}" class="max-w-full" alt="Bukti Pembayaran">
                        </div>
                        
                        {{-- Toolbar --}}
                        <div class="mt-4 flex flex-wrap gap-2 justify-center">
                            <button type="button" @click="rotate(-90)" class="px-3 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                                Rotate Left
                            </button>
                            <button type="button" @click="rotate(90)" class="px-3 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"/>
                                </svg>
                                Rotate Right
                            </button>
                            <button type="button" @click="reset()" class="px-3 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Reset
                            </button>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse gap-2 border-t">
                        <button 
                            type="button" 
                            @click="saveCrop()"
                            class="w-full sm:w-auto inline-flex justify-center rounded-lg px-4 py-2 bg-blue-600 text-white font-medium hover:bg-blue-700 focus:outline-none"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan Crop
                        </button>
                        <button 
                            type="button" 
                            wire:click="closeModal"
                            class="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-lg px-4 py-2 bg-white border border-gray-300 text-gray-700 font-medium hover:bg-gray-50"
                        >
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Load Cropper.js from CDN --}}
    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
        <script>
            function imageCropper() {
                return {
                    cropper: null,
                    
                    initCropper(imageUrl) {
                        this.$nextTick(() => {
                            const image = document.getElementById('cropper-image');
                            if (image && !this.cropper) {
                                this.cropper = new Cropper(image, {
                                    viewMode: 1,
                                    dragMode: 'crop',
                                    autoCropArea: 1,
                                    responsive: true,
                                    restore: false,
                                    guides: true,
                                    center: true,
                                    highlight: true,
                                    cropBoxMovable: true,
                                    cropBoxResizable: true,
                                    toggleDragModeOnDblclick: false,
                                });
                            }
                        });
                    },
                    
                    rotate(degrees) {
                        if (this.cropper) {
                            this.cropper.rotate(degrees);
                        }
                    },
                    
                    reset() {
                        if (this.cropper) {
                            this.cropper.reset();
                        }
                    },
                    
                    saveCrop() {
                        if (this.cropper) {
                            const canvas = this.cropper.getCroppedCanvas({
                                maxWidth: 2000,
                                maxHeight: 2000,
                                imageSmoothingEnabled: true,
                                imageSmoothingQuality: 'high',
                            });
                            
                            if (canvas) {
                                const croppedData = canvas.toDataURL('image/jpeg', 0.9);
                                @this.call('saveCroppedImage', croppedData);
                            }
                        }
                    },
                    
                    destroy() {
                        if (this.cropper) {
                            this.cropper.destroy();
                            this.cropper = null;
                        }
                    }
                }
            }
        </script>
    @endpush
</div>
