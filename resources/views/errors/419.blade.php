<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Halaman Kadaluwarsa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'um-blue': '#1e40af',
                        'um-green': '#059669',
                        'um-gold': '#f59e0b',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-slate-800 via-slate-900 to-gray-900 flex items-center justify-center min-h-screen text-white">

    <div class="text-center p-8 max-w-md">
        <div class="w-20 h-20 bg-amber-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-clock-rotate-left text-4xl text-amber-400"></i>
        </div>
        <h1 class="text-7xl font-black text-white/90 mb-3">419</h1>
        <h2 class="text-xl font-bold mb-4">Sesi Telah Berakhir</h2>
        <p class="text-gray-400 text-sm mb-8 leading-relaxed">
            Halaman ini kedaluwarsa karena tidak ada aktivitas. Silakan muat ulang halaman untuk melanjutkan.
        </p>
        
        <div class="flex flex-col gap-3">
            {{-- Refresh halaman sebelumnya dengan token baru --}}
            <a href="{{ url()->previous() }}" 
               class="inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-full font-semibold transition-all shadow-lg">
                <i class="fas fa-rotate-right"></i>
                Muat Ulang Halaman
            </a>
            
            {{-- Atau kembali ke beranda --}}
            <a href="{{ url('/') }}" 
               class="inline-flex items-center justify-center gap-2 text-gray-400 hover:text-white px-6 py-2 font-medium transition-colors text-sm">
                <i class="fas fa-home"></i>
                Kembali ke Beranda
            </a>
        </div>
        
        <p class="text-gray-500 text-xs mt-8">
            <i class="fas fa-shield-halved mr-1"></i>
            Ini adalah langkah keamanan untuk melindungi data Anda
        </p>
    </div>

</body>
</html>