<!DOCTYPE html>
<html lang="id">
@php
    $pageTitle = $pageTitle ?? 'Layout Designer - Export Bukti';
    $backUrl = $backUrl ?? url('/admin/penerjemahan');
    $backLabel = $backLabel ?? 'Kembali';
    $cropSaveRoute = $cropSaveRoute ?? route('admin.export-bukti.crop-save');
    $generateRoute = $generateRoute ?? route('admin.export-bukti.generate');
    $downloadButtonText = $downloadButtonText ?? 'Preview PDF';
    $processingText = $processingText ?? 'Membuat PDF...';
    $rowsPerPageOptions = $rowsPerPageOptions ?? [2, 3, 4, 5];
    $rowsPerPageOptions = collect($rowsPerPageOptions)
        ->map(fn ($value) => (int) $value)
        ->filter(fn ($value) => $value > 0)
        ->unique()
        ->values()
        ->all();
    $defaultRowsPerPage = (int) ($defaultRowsPerPage ?? 3);
    if (!in_array($defaultRowsPerPage, $rowsPerPageOptions, true)) {
        $defaultRowsPerPage = $rowsPerPageOptions[0] ?? 3;
    }
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Preview Export Bukti Pembayaran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <style>
        .row-container { min-height: 80px; }
        .item-card { transition: all 0.15s; position: relative; }
        .item-card:hover { transform: scale(1.02); }
        .item-card:hover .edit-btn { opacity: 1; }
        .edit-btn { opacity: 0; transition: opacity 0.15s; }
        .cropper-container { max-height: 70vh; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
        {{-- Header --}}
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $pageTitle }}</h1>
                    <p class="text-sm text-gray-500">Klik gambar untuk crop/rotate • Drag untuk atur urutan • {{ $records->count() }} gambar (maks {{ $maxItems }})</p>
                </div>
                <a href="{{ $backUrl }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    <i class="fa-solid fa-arrow-left mr-1"></i> {{ $backLabel }}
                </a>
            </div>
        </div>

        {{-- Quick Export Settings --}}
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <label for="rowsPerPage" class="font-medium text-gray-700">Baris per Halaman:</label>
                    <select id="rowsPerPage" class="border rounded-lg px-3 py-2 text-sm">
                        @foreach($rowsPerPageOptions as $option)
                            <option value="{{ $option }}" @selected($option === $defaultRowsPerPage)>
                                {{ $option }} Baris
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="button" onclick="previewPdf()" id="downloadBtn" class="px-6 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition disabled:opacity-50">
                    <i class="fa-solid fa-eye mr-2"></i> {{ $downloadButtonText }}
                </button>
            </div>
        </div>

        {{-- Available Items (Source) --}}
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-gray-700"><i class="fa-solid fa-images mr-2"></i>Gambar Tersedia</h2>
                <button onclick="autoDistribute()" class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded text-sm font-medium hover:bg-blue-200">
                    <i class="fa-solid fa-magic mr-1"></i> Auto Susun
                </button>
            </div>
            <div id="source-items" class="flex flex-wrap gap-2 min-h-[60px] p-2 bg-gray-50 rounded border-2 border-dashed border-gray-200">
                @foreach($records as $record)
                    @php
                        $previewUrl = filled($record->preview_bukti_url) ? ($record->preview_bukti_url . '?t=' . time()) : '';
                        $displayName = $record->display_name ?? $record->users?->name ?? $record->user?->name ?? '-';
                        $displaySrn = $record->display_srn ?? $record->users?->srn ?? $record->user?->srn ?? '-';
                        $displayStatusLabel = $record->display_status_label ?? null;
                    @endphp
                    <div class="item-card bg-white border rounded-lg p-2 cursor-move shadow-sm flex items-center gap-2" 
                         data-id="{{ $record->id }}" 
                         data-name="{{ $displayName }}"
                         data-srn="{{ $displaySrn }}"
                         data-status-label="{{ $displayStatusLabel }}"
                         data-image="{{ $previewUrl }}">
                        @if($previewUrl !== '')
                            <div class="relative">
                                <img src="{{ $previewUrl }}" 
                                     class="w-14 h-14 object-cover rounded item-thumb"
                                     onclick='openCropModal({{ $record->id }}, null, @json($displayName))'>
                                <button type="button" 
                                        class="edit-btn absolute -top-1 -right-1 w-5 h-5 bg-yellow-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-yellow-600"
                                        onclick='openCropModal({{ $record->id }}, null, @json($displayName))'>
                                    <i class="fa-solid fa-pen text-[8px]"></i>
                                </button>
                            </div>
                        @endif
                        <div class="text-xs">
                            <div class="font-medium truncate max-w-[120px]">{{ Str::limit($displayName, 18) }}</div>
                            <div class="text-gray-500">{{ $displaySrn }}</div>
                            @if(filled($displayStatusLabel))
                                <div class="text-gray-400 truncate max-w-[120px]">{{ $displayStatusLabel }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Rows Layout --}}
        <div id="rows-container" class="space-y-4 mb-6"></div>

        {{-- Add Row Button --}}
        <div class="flex justify-center mb-6">
            <button onclick="addRow()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition">
                <i class="fa-solid fa-plus mr-2"></i> Tambah Baris
            </button>
        </div>

        {{-- Loading Overlay --}}
        <div id="loading" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg p-6 text-center">
                <div class="animate-spin w-10 h-10 border-4 border-green-600 border-t-transparent rounded-full mx-auto mb-4"></div>
                <p class="text-gray-700" id="loading-text">Memproses...</p>
            </div>
        </div>

        {{-- Crop Modal --}}
        <div id="crop-modal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden">
                <div class="bg-gray-100 px-4 py-3 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800" id="crop-modal-title">Crop/Rotate Gambar</h3>
                    <button onclick="closeCropModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-4">
                    <div class="mb-4 flex justify-center gap-2 flex-wrap">
                        <button onclick="rotateImage(-90)" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
                            <i class="fa-solid fa-rotate-left mr-1"></i> Rotate Kiri
                        </button>
                        <button onclick="rotateImage(90)" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
                            <i class="fa-solid fa-rotate-right mr-1"></i> Rotate Kanan
                        </button>
                        <button onclick="flipImage('h')" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
                            <i class="fa-solid fa-arrows-left-right mr-1"></i> Flip H
                        </button>
                        <button onclick="flipImage('v')" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
                            <i class="fa-solid fa-arrows-up-down mr-1"></i> Flip V
                        </button>
                        <button onclick="setCropFull()" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            <i class="fa-solid fa-expand mr-1"></i> Full
                        </button>
                        <button onclick="resetCrop()" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
                            <i class="fa-solid fa-undo mr-1"></i> Reset
                        </button>
                    </div>
                    <div class="bg-gray-900 rounded-lg overflow-hidden" style="max-height: 60vh;">
                        <img id="crop-image" src="" class="max-w-full">
                    </div>
                </div>
                <div class="bg-gray-100 px-4 py-3 flex justify-end gap-2">
                    <button onclick="closeCropModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                        Batal
                    </button>
                    <button onclick="saveCrop()" id="save-crop-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fa-solid fa-check mr-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let rowId = 0;
        let sourceSortable;
        let rowSortables = {};
        let cropper = null;
        let currentCropId = null;
        const selectionToken = @json($selectionToken);
        const maxItems = {{ (int) $maxItems }};
        const cropSaveRoute = @json($cropSaveRoute);
        const generateRoute = @json($generateRoute);
        const processingText = @json($processingText);
        const cropOutputMaxSize = 1800;
        const cropWebpQuality = 0.82;
        const cropJpegQuality = 0.86;
        
        document.addEventListener('DOMContentLoaded', function() {
            sourceSortable = new Sortable(document.getElementById('source-items'), {
                group: 'items',
                animation: 150,
                ghostClass: 'opacity-50',
            });
            addRow();
        });
        
        function addRow(columns = 2) {
            const container = document.getElementById('rows-container');
            const id = ++rowId;
            
            const rowHtml = `
                <div class="row-wrapper bg-white rounded-lg shadow p-4" data-row-id="${id}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-700">Baris ${id}</span>
                            <div class="flex border rounded overflow-hidden">
                                <button onclick="setRowColumns(${id}, 1)" class="col-btn px-2 py-1 text-xs ${columns === 1 ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}" data-col="1">1 Kol</button>
                                <button onclick="setRowColumns(${id}, 2)" class="col-btn px-2 py-1 text-xs ${columns === 2 ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}" data-col="2">2 Kol</button>
                                <button onclick="setRowColumns(${id}, 3)" class="col-btn px-2 py-1 text-xs ${columns === 3 ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}" data-col="3">3 Kol</button>
                            </div>
                        </div>
                        <button onclick="removeRow(${id})" class="text-red-400 hover:text-red-600 text-sm">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <div id="row-${id}" class="row-container grid grid-cols-${columns} gap-2 p-3 bg-gray-50 rounded border-2 border-dashed border-gray-300 min-h-[80px]" data-columns="${columns}">
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', rowHtml);
            rowSortables[id] = new Sortable(document.getElementById(`row-${id}`), {
                group: 'items',
                animation: 150,
                ghostClass: 'opacity-50',
            });
        }
        
        function setRowColumns(rowId, columns) {
            const rowEl = document.getElementById(`row-${rowId}`);
            const wrapper = rowEl.closest('.row-wrapper');
            rowEl.classList.remove('grid-cols-1', 'grid-cols-2', 'grid-cols-3');
            rowEl.classList.add(`grid-cols-${columns}`);
            rowEl.dataset.columns = columns;
            wrapper.querySelectorAll('.col-btn').forEach(btn => {
                if (parseInt(btn.dataset.col) === columns) {
                    btn.className = 'col-btn px-2 py-1 text-xs bg-blue-500 text-white';
                } else {
                    btn.className = 'col-btn px-2 py-1 text-xs bg-gray-100 text-gray-600 hover:bg-gray-200';
                }
            });
        }
        
        function removeRow(id) {
            const wrapper = document.querySelector(`[data-row-id="${id}"]`);
            const rowEl = document.getElementById(`row-${id}`);
            const items = rowEl.querySelectorAll('.item-card');
            const source = document.getElementById('source-items');
            items.forEach(item => source.appendChild(item));
            wrapper.remove();
            delete rowSortables[id];
        }
        
        function autoDistribute() {
            const source = document.getElementById('source-items');
            const items = Array.from(source.querySelectorAll('.item-card'));
            if (items.length === 0) return;
            
            document.querySelectorAll('.row-wrapper').forEach(row => {
                const rowEl = row.querySelector('.row-container');
                const rowItems = rowEl.querySelectorAll('.item-card');
                rowItems.forEach(item => source.appendChild(item));
                row.remove();
            });
            rowId = 0;
            rowSortables = {};
            
            const itemsPerRow = 2;
            for (let i = 0; i < items.length; i += itemsPerRow) {
                addRow(2);
                const rowEl = document.getElementById(`row-${rowId}`);
                for (let j = i; j < Math.min(i + itemsPerRow, items.length); j++) {
                    rowEl.appendChild(items[j]);
                }
            }
        }
        
        // Crop Modal Functions
        function openCropModal(id, imageUrl, name) {
            const card = document.querySelector(`[data-id="${id}"]`);
            if (!imageUrl && card) {
                imageUrl = card.dataset.image;
            }

            if (!imageUrl) {
                alert('Gambar tidak ditemukan untuk data ini.');
                return;
            }

            currentCropId = id;
            document.getElementById('crop-modal-title').textContent = 'Crop/Rotate: ' + name;
            document.getElementById('crop-image').src = imageUrl;
            document.getElementById('crop-modal').classList.remove('hidden');
            
            setTimeout(() => {
                const image = document.getElementById('crop-image');
                if (cropper) cropper.destroy();
                cropper = new Cropper(image, {
                    viewMode: 0,
                    dragMode: 'move',
                    autoCrop: true,
                    autoCropArea: 1,    // Full area on init
                    responsive: true,
                    background: true,
                    checkOrientation: false,
                    rotatable: true,
                    scalable: true,
                    ready: function() {
                        // Set crop box to cover full canvas on init
                        setCropFull();
                    }
                });
            }, 100);
        }
        
        function closeCropModal() {
            document.getElementById('crop-modal').classList.add('hidden');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            currentCropId = null;
        }
        
        function rotateImage(degree) {
            if (!cropper) return;
            cropper.rotate(degree);
            // Reset crop box to cover full rotated image  
            setTimeout(() => setCropFull(), 100);
        }
        
        function flipImage(dir) {
            if (!cropper) return;
            const data = cropper.getData();
            if (dir === 'h') {
                cropper.scaleX(data.scaleX === 1 ? -1 : 1);
            } else {
                cropper.scaleY(data.scaleY === 1 ? -1 : 1);
            }
        }
        
        function resetCrop() {
            if (!cropper) return;
            cropper.reset();
            setCropFull();
        }
        
        function setCropFull() {
            if (!cropper) return;
            const canvasData = cropper.getCanvasData();
            cropper.setCropBoxData({
                left: canvasData.left,
                top: canvasData.top,
                width: canvasData.width,
                height: canvasData.height
            });
        }
        
        function saveCrop() {
            if (!cropper || !currentCropId) return;
            
            const canvas = cropper.getCroppedCanvas({
                maxWidth: cropOutputMaxSize,
                maxHeight: cropOutputMaxSize,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
            if (!canvas) {
                alert('Tidak dapat memproses gambar');
                return;
            }
            
            showLoading('Menyimpan...');
            document.getElementById('save-crop-btn').disabled = true;
            
            buildOptimizedCropBlob(canvas).then(({ blob, filename }) => {
                if (!blob) {
                    throw new Error('Gagal membuat file gambar.');
                }

                const formData = new FormData();
                formData.append('image', blob, filename);
                formData.append('id', currentCropId);
                formData.append('selection_token', selectionToken);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                
                fetch(cropSaveRoute, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Server error: ' + res.status);
                    }
                    const contentType = res.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Server tidak mengembalikan JSON. Mungkin session expired, coba refresh halaman.');
                    }
                    return res.json();
                })
                .then(data => {
                    hideLoading();
                    document.getElementById('save-crop-btn').disabled = false;
                    
                    if (data.success) {
                        // Update thumbnail
                        const newUrl = data.url + '?t=' + Date.now();
                        const card = document.querySelector(`[data-id="${currentCropId}"]`);
                        if (card) {
                            const img = card.querySelector('.item-thumb');
                            if (img) img.src = newUrl;
                            card.dataset.image = newUrl;
                        }
                        closeCropModal();
                    } else {
                        alert(data.message || 'Gagal menyimpan');
                    }
                })
                .catch(err => {
                    hideLoading();
                    document.getElementById('save-crop-btn').disabled = false;
                    alert('Error: ' + err.message);
                });
            }).catch((err) => {
                hideLoading();
                document.getElementById('save-crop-btn').disabled = false;
                alert('Error: ' + err.message);
            });
        }

        function buildOptimizedCropBlob(canvas) {
            return new Promise((resolve, reject) => {
                canvas.toBlob((webpBlob) => {
                    if (webpBlob && webpBlob.size > 0) {
                        resolve({ blob: webpBlob, filename: 'cropped.webp' });
                        return;
                    }

                    canvas.toBlob((jpegBlob) => {
                        if (jpegBlob && jpegBlob.size > 0) {
                            resolve({ blob: jpegBlob, filename: 'cropped.jpg' });
                            return;
                        }

                        reject(new Error('Tidak dapat mengekspor hasil crop.'));
                    }, 'image/jpeg', cropJpegQuality);
                }, 'image/webp', cropWebpQuality);
            });
        }
        
        function showLoading(text = 'Memproses...') {
            document.getElementById('loading-text').textContent = text;
            document.getElementById('loading').classList.remove('hidden');
        }
        
        function hideLoading() {
            document.getElementById('loading').classList.add('hidden');
        }
        
        function previewPdf() {
            const rows = [];
            document.querySelectorAll('.row-container').forEach(rowEl => {
                const columns = parseInt(rowEl.dataset.columns);
                const items = Array.from(rowEl.querySelectorAll('.item-card'))
                    .map(item => Number.parseInt(item.dataset.id, 10))
                    .filter(id => Number.isInteger(id) && id > 0);
                if (items.length > 0) {
                    rows.push({ columns, items });
                }
            });
            
            if (rows.length === 0) {
                alert('Tidak ada gambar yang diatur. Drag gambar ke baris terlebih dahulu.');
                return;
            }

            const uniqueIds = [...new Set(rows.flatMap(row => row.items))];
            if (uniqueIds.length > maxItems) {
                alert(`Maksimal ${maxItems} gambar per export. Kurangi jumlah gambar terlebih dahulu.`);
                return;
            }
            
            const rowsPerPage = document.getElementById('rowsPerPage').value;
            showLoading(processingText);
            document.getElementById('downloadBtn').disabled = true;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = generateRoute;
            form.target = '_blank';
            form.style.display = 'none';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrfInput);
            
            const rowsInput = document.createElement('input');
            rowsInput.type = 'hidden';
            rowsInput.name = 'rows';
            rowsInput.value = JSON.stringify(rows);
            form.appendChild(rowsInput);
            
            const rppInput = document.createElement('input');
            rppInput.type = 'hidden';
            rppInput.name = 'rows_per_page';
            rppInput.value = rowsPerPage;
            form.appendChild(rppInput);

            const selectionTokenInput = document.createElement('input');
            selectionTokenInput.type = 'hidden';
            selectionTokenInput.name = 'selection_token';
            selectionTokenInput.value = selectionToken;
            form.appendChild(selectionTokenInput);
            
            document.body.appendChild(form);
            form.submit();
            
            setTimeout(() => {
                hideLoading();
                document.getElementById('downloadBtn').disabled = false;
            }, 1500);
        }
    </script>
</body>
</html>
