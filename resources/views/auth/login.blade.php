{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - UPT Bahasa UM Metro</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    
    <script src="{{ asset('vendor/tailwind/tailwind.js') }}"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        'um-blue': '#1e40af',
                        'um-dark-blue': '#1e3a8a',
                        'um-navy': '#172554',
                        'um-gold': '#f59e0b',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/inter/inter.css') }}">
  <style>
    @media (min-width: 1024px) and (max-width: 1366px) {
      html { font-size: 15px; }
    }
  </style>
</head>
<body class="font-sans antialiased bg-slate-50">
    
    <div class="min-h-screen lg:flex">
        {{-- Left: Branding Panel --}}
        <div class="hidden lg:flex lg:w-[45%] relative overflow-hidden bg-gradient-to-br from-um-blue via-um-dark-blue to-um-navy">
            {{-- Decorative elements --}}
            <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 28px 28px;"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-um-gold/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-32 -left-24 w-96 h-96 bg-blue-400/20 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 m-auto w-full max-w-md px-12 py-16">
                {{-- Brand --}}
                <div class="mb-12">
                    <img src="{{ asset('images/logo-um.png') }}" alt="Logo UM Metro" class="h-16 w-16 object-contain mb-5">
                    <h1 class="text-2xl font-extrabold tracking-tight text-white">
                        UPT <span class="text-um-gold">Bahasa</span>
                    </h1>
                    <p class="text-sm text-blue-200 mt-1">Universitas Muhammadiyah Metro</p>
                </div>

                {{-- Value proposition --}}
                <div class="space-y-5">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-headphones text-um-gold"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Basic Listening</p>
                            <p class="text-xs text-blue-200/80 mt-0.5">Kelas dan sertifikat dalam satu platform.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-file-signature text-um-gold"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Surat Rekomendasi</p>
                            <p class="text-xs text-blue-200/80 mt-0.5">Pengajuan nilai EPT secara online.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-language text-um-gold"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Penerjemahan</p>
                            <p class="text-xs text-blue-200/80 mt-0.5">Abstrak dan dokumen ilmiah.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Form Panel --}}
        <div class="flex-1 flex items-center justify-center px-4 sm:px-8 py-10">
            <div class="w-full max-w-md">
                {{-- Mobile brand --}}
                <div class="lg:hidden text-center mb-8">
                    <img src="{{ asset('images/logo-um.png') }}" alt="Logo UM Metro" class="h-12 w-12 object-contain mx-auto mb-3">
                    <div class="font-extrabold text-lg text-slate-900">UPT <span class="text-um-gold">Bahasa</span></div>
                    <div class="text-xs text-slate-500">Universitas Muhammadiyah Metro</div>
                </div>

                {{-- Form card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-7 sm:p-8">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-slate-900">Selamat Datang</h2>
                        <p class="text-sm text-slate-500 mt-1">Masuk untuk mengakses layanan Anda.</p>
                    </div>

                    @if (session('status'))
                        <div class="mb-4 p-3 rounded-lg bg-blue-50 border border-blue-100 text-blue-700 text-sm">
                            <i class="fa-solid fa-circle-info mr-1"></i>{{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="email" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Email / NPM / WhatsApp</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400"><i class="fa-solid fa-user text-sm"></i></span>
                                <input type="text" id="email" name="email" value="{{ old('email') }}"
                                    class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-300 bg-slate-50/50 focus:bg-white focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none text-sm transition placeholder:text-slate-400 @error('email') border-red-300 @enderror"
                                    placeholder="Masukkan email, NPM, atau nomor WA" autofocus required>
                            </div>
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Kata Sandi</label>
                            <div class="relative" x-data="{ show: false }">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400"><i class="fa-solid fa-lock text-sm"></i></span>
                                <input :type="show ? 'text' : 'password'" id="password" name="password"
                                    class="w-full pl-10 pr-9 py-2.5 rounded-lg border border-slate-300 bg-slate-50/50 focus:bg-white focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none text-sm transition placeholder:text-slate-400 @error('password') border-red-300 @enderror"
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
                            <a href="{{ route('password.reset.contact') }}" class="text-um-blue hover:underline font-medium">Lupa kata sandi?</a>
                        </div>

                        <button type="submit" class="w-full py-3 rounded-lg bg-um-blue text-white text-sm font-semibold hover:bg-um-dark-blue active:scale-[0.99] transition shadow-md shadow-um-blue/20 mt-2">
                            <i class="fa-solid fa-right-to-bracket mr-2"></i>Masuk
                        </button>
                    </form>

                    <div class="text-center mt-6 pt-5 border-t border-slate-100">
                        <p class="text-sm text-slate-600">
                            Belum punya akun?
                            <a href="{{ route('register') }}" class="text-um-blue font-semibold hover:underline">Daftar di sini</a>
                        </p>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <a href="{{ url('/') }}" class="text-sm text-slate-400 hover:text-um-blue transition">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Beranda
                    </a>
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
