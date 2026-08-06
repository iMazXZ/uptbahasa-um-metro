{{-- resources/views/auth/register.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - UPT Bahasa UM Metro</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        'um-blue': '#1e40af',
                        'um-dark-blue': '#1e3a8a',
                        'um-gold': '#f59e0b',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .login-bg { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%); }
    </style>
</head>
<body class="font-sans antialiased">
    
    <div class="min-h-screen flex">
        {{-- Left: Branding (PC Only) - Matching Hero Style --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden items-center justify-center p-8">
            {{-- Background matching hero --}}
            <div class="absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-indigo-900"></div>
                {{-- Dot pattern --}}
                <div class="absolute inset-0" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px; opacity: 0.1;"></div>
                {{-- Glow effects --}}
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-blue-400 rounded-full blur-3xl opacity-20"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-indigo-400 rounded-full blur-3xl opacity-20"></div>
            </div>
            
            <div class="relative z-10 text-center text-white max-w-md">
                {{-- Logo & Title --}}
                <div class="mb-8">
                    <img src="{{ asset('images/logo-um.png') }}" alt="Logo" class="w-20 h-20 mx-auto mb-4 object-contain">
                    <h1 class="text-3xl font-black tracking-tight mb-2">
                        UPT <span class="text-blue-300">Bahasa</span>
                    </h1>
                    <p class="text-blue-100 font-medium">Universitas Muhammadiyah Metro</p>
                    <p class="text-blue-200/70 text-sm italic mt-1">"Supports Your Success"</p>
                </div>
                
                {{-- Features --}}
                <div class="space-y-3 text-left">
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/10 hover:bg-white/15 transition">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/30 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-check text-lg text-emerald-300"></i>
                        </div>
                        <div>
                            <p class="font-bold text-sm">Gratis & Mudah</p>
                            <p class="text-xs text-blue-200">Daftar dalam hitungan menit</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/10 hover:bg-white/15 transition">
                        <div class="w-10 h-10 rounded-xl bg-green-500/30 flex items-center justify-center shrink-0">
                            <i class="fa-brands fa-whatsapp text-lg text-green-300"></i>
                        </div>
                        <div>
                            <p class="font-bold text-sm">Notifikasi WhatsApp</p>
                            <p class="text-xs text-blue-200">Update status langsung ke HP</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/10 hover:bg-white/15 transition">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/30 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-shield-halved text-lg text-amber-300"></i>
                        </div>
                        <div>
                            <p class="font-bold text-sm">Aman & Terpercaya</p>
                            <p class="text-xs text-blue-200">Data Anda terlindungi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Right: Form --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 bg-white">
            <div class="w-full max-w-md">
                {{-- Mobile Logo --}}
                <div class="lg:hidden text-center mb-6">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
                        <img src="{{ asset('images/logo-um.png') }}" alt="Logo" class="h-10 w-10 object-contain">
                        <div class="text-left leading-tight">
                            <div class="font-extrabold text-lg text-slate-900"><span>UPT </span><span class="text-um-gold">Bahasa</span></div>
                            <div class="text-slate-500 text-[10px]">Universitas Muhammadiyah Metro</div>
                        </div>
                    </a>
                </div>
                
                {{-- Form Card --}}
                <div class="bg-white rounded-2xl p-6">
                    <div class="text-center mb-5">
                        <h2 class="text-xl font-bold text-slate-800">Buat Akun Baru</h2>
                        <p class="text-slate-500 text-sm mt-1">Isi data berikut untuk mendaftar</p>
                    </div>
                    
                    @if (session('status'))
                        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm">
                            <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ session('status') }}
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('register') }}" class="space-y-3">
                        @csrf
                        
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fa-solid fa-user text-sm"></i></span>
                                <input type="text" id="name" name="name" value="{{ old('name') }}"
                                    class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-200 focus:border-um-blue focus:ring-1 focus:ring-um-blue/30 outline-none text-sm @error('name') border-red-300 @enderror"
                                    placeholder="Nama lengkap sesuai KTP" required>
                            </div>
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fa-solid fa-envelope text-sm"></i></span>
                                <input type="email" id="email" name="email" value="{{ old('email') }}"
                                    class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-200 focus:border-um-blue focus:ring-1 focus:ring-um-blue/30 outline-none text-sm @error('email') border-red-300 @enderror"
                                    required>
                            </div>
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        
                        <div>
                            <label for="whatsapp" class="block text-sm font-medium text-slate-700 mb-1">WhatsApp Aktif <span class="text-slate-400">(opsional)</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fa-brands fa-whatsapp text-sm"></i></span>
                                <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}"
                                    class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-200 focus:border-um-blue focus:ring-1 focus:ring-um-blue/30 outline-none text-sm @error('whatsapp') border-red-300 @enderror">
                            </div>
                            <p class="mt-0.5 text-[10px] text-slate-400">Untuk notifikasi dan verifikasi akun</p>
                            @error('whatsapp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi</label>
                                <div class="relative" x-data="{ show: false }">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fa-solid fa-lock text-sm"></i></span>
                                    <input :type="show ? 'text' : 'password'" id="password" name="password"
                                        class="w-full pl-10 pr-8 py-2.5 rounded-lg border border-slate-200 focus:border-um-blue focus:ring-1 focus:ring-um-blue/30 outline-none text-sm @error('password') border-red-300 @enderror"
                                        placeholder="Min 8 karakter" required>
                                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-slate-600">
                                        <i class="fa-solid text-xs" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi</label>
                                <div class="relative" x-data="{ show: false }">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fa-solid fa-lock text-sm"></i></span>
                                    <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation"
                                        class="w-full pl-10 pr-8 py-2.5 rounded-lg border border-slate-200 focus:border-um-blue focus:ring-1 focus:ring-um-blue/30 outline-none text-sm"
                                        placeholder="Ulangi sandi" required>
                                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-slate-600">
                                        <i class="fa-solid text-xs" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full py-3 rounded-lg bg-um-blue text-white font-semibold hover:bg-um-dark-blue transition shadow-md mt-2">
                            <i class="fa-solid fa-user-plus mr-2"></i>Daftar Sekarang
                        </button>
                    </form>
                    
                    <div class="text-center mt-4 pt-4 border-t border-slate-100">
                        <p class="text-sm text-slate-600">Sudah punya akun? <a href="{{ route('login') }}" class="text-um-blue font-semibold hover:underline">Masuk</a></p>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="{{ url('/') }}" class="text-sm text-slate-400 hover:text-um-blue transition"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        document.querySelectorAll('input[required]').forEach(input => {
            input.addEventListener('invalid', () => {
                if (input.type === 'email' && input.validity.typeMismatch) {
                    input.setCustomValidity('Format email tidak valid.');
                } else {
                    input.setCustomValidity('Harap isi kolom ini.');
                }
            });
            input.addEventListener('input', () => {
                input.setCustomValidity('');
            });
        });
    </script>
</body>
</html>
