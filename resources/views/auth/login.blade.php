{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - UPT Bahasa UM Metro</title>
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
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}">
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
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-headphones text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-sm">Basic Listening</p>
                            <p class="text-xs text-blue-200">Kelas & Sertifikat Online</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/10 hover:bg-white/15 transition">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-file-signature text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-sm">Surat Rekomendasi</p>
                            <p class="text-xs text-blue-200">Pengajuan EPT Online</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/10 hover:bg-white/15 transition">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-globe text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-sm">Penerjemahan</p>
                            <p class="text-xs text-blue-200">Abstrak & Dokumen Ilmiah</p>
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
                        <h2 class="text-xl font-bold text-slate-800">Selamat Datang!</h2>
                        <p class="text-slate-500 text-sm mt-1">Masuk ke akun Anda</p>
                    </div>
                    
                    @if (session('status'))
                        <div class="mb-4 p-3 rounded-lg bg-blue-50 border border-blue-100 text-blue-700 text-sm">
                            <i class="fa-solid fa-info-circle mr-1"></i>{{ session('status') }}
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('login') }}" class="space-y-4">
                        @csrf
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email / NPM / WhatsApp</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fa-solid fa-user text-sm"></i></span>
                                <input type="text" id="email" name="email" value="{{ old('email') }}"
                                    class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-200 focus:border-um-blue focus:ring-1 focus:ring-um-blue/30 outline-none text-sm @error('email') border-red-300 @enderror"
                                    placeholder="Masukkan email, NPM, atau nomor WA" autofocus required>
                            </div>
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi</label>
                            <div class="relative" x-data="{ show: false }">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="fa-solid fa-lock text-sm"></i></span>
                                <input :type="show ? 'text' : 'password'" id="password" name="password"
                                    class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-slate-200 focus:border-um-blue focus:ring-1 focus:ring-um-blue/30 outline-none text-sm @error('password') border-red-300 @enderror"
                                    placeholder="Masukkan kata sandi" required>
                                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                    <i class="fa-solid text-sm" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        
                        <div class="flex items-center justify-between text-sm">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-um-blue focus:ring-um-blue/20">
                                <span class="text-slate-600">Ingat saya</span>
                            </label>
                            <a href="{{ route('filament.admin.auth.password-reset.request') }}" class="text-um-blue hover:underline font-medium">Lupa kata sandi?</a>
                        </div>
                        
                        <button type="submit" class="w-full py-3 rounded-lg bg-um-blue text-white font-semibold hover:bg-um-dark-blue transition shadow-md">
                            <i class="fa-solid fa-right-to-bracket mr-2"></i>Masuk
                        </button>
                    </form>
                    
                    <div class="text-center mt-5 pt-4 border-t border-slate-100">
                        <p class="text-sm text-slate-600">Belum punya akun? <a href="{{ route('register') }}" class="text-um-blue font-semibold hover:underline">Daftar sekarang</a></p>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="{{ url('/') }}" class="text-sm text-slate-400 hover:text-um-blue transition"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="{{ asset('vendor/alpine/alpine.min.js') }}" defer></script>
    <script>
        document.querySelectorAll('input[required]').forEach(input => {
            input.addEventListener('invalid', () => {
                input.setCustomValidity('Harap isi kolom ini.');
            });
            input.addEventListener('input', () => {
                input.setCustomValidity('');
            });
        });
    </script>
</body>
</html>
