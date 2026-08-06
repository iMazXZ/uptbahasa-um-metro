<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crop Bukti Pembayaran - {{ $penerjemahan->users?->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        {{-- Header --}}
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Crop Bukti Pembayaran</h1>
                    <p class="text-sm text-gray-500">{{ $penerjemahan->users?->name }} — {{ $penerjemahan->users?->srn }}</p>
                </div>
                <a href="{{ url('/admin/penerjemahan') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>

        {{-- Cropper Area --}}
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="max-h-[60vh] overflow-hidden flex items-center justify-center bg-gray-50 rounded">
                <img id="cropper-image" src="{{ $imageUrl }}" class="max-w-full" alt="Bukti Pembayaran">
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="flex flex-wrap gap-3 justify-center">
                <button type="button" onclick="rotate(-90)" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                    <i class="fa-solid fa-rotate-left"></i> Rotate Kiri
                </button>
                <button type="button" onclick="rotate(90)" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                    <i class="fa-solid fa-rotate-right"></i> Rotate Kanan
                </button>
                <button type="button" onclick="flipH()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                    <i class="fa-solid fa-arrows-left-right"></i> Flip Horizontal
                </button>
                <button type="button" onclick="resetCrop()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                    <i class="fa-solid fa-arrow-rotate-left"></i> Reset
                </button>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="flex justify-end gap-3">
            <a href="{{ url('/admin/penerjemahan') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition">
                Batal
            </a>
            <button type="button" onclick="saveCrop()" id="saveBtn" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50">
                <i class="fa-solid fa-check mr-1"></i> Simpan Crop
            </button>
        </div>

        {{-- Loading Overlay --}}
        <div id="loading" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg p-6 text-center">
                <div class="animate-spin w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full mx-auto mb-4"></div>
                <p class="text-gray-700">Menyimpan gambar...</p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        let cropper = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            const image = document.getElementById('cropper-image');
            cropper = new Cropper(image, {
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
        });
        
        function rotate(degrees) {
            if (cropper) cropper.rotate(degrees);
        }
        
        function flipH() {
            if (cropper) {
                const data = cropper.getData();
                cropper.scaleX(data.scaleX === -1 ? 1 : -1);
            }
        }
        
        function resetCrop() {
            if (cropper) cropper.reset();
        }
        
        function saveCrop() {
            if (!cropper) return;
            
            const saveBtn = document.getElementById('saveBtn');
            const loading = document.getElementById('loading');
            
            saveBtn.disabled = true;
            loading.classList.remove('hidden');
            
            const canvas = cropper.getCroppedCanvas({
                maxWidth: 2000,
                maxHeight: 2000,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
            
            if (canvas) {
                const croppedData = canvas.toDataURL('image/jpeg', 0.9);
                
                fetch('{{ route("admin.crop-bukti.save", $penerjemahan) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ cropped_image: croppedData })
                })
                .then(res => res.json())
                .then(data => {
                    loading.classList.add('hidden');
                    if (data.success) {
                        alert('Gambar berhasil di-crop!');
                        window.location.href = '{{ url("/admin/penerjemahan") }}';
                    } else {
                        alert('Gagal: ' + data.message);
                        saveBtn.disabled = false;
                    }
                })
                .catch(err => {
                    loading.classList.add('hidden');
                    alert('Error: ' + err.message);
                    saveBtn.disabled = false;
                });
            }
        }
    </script>
</body>
</html>
