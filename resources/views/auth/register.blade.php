{{-- resources/views/auth/register.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - UPT Bahasa UM Metro</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    
    <script src="https://cdn.tailwindcss.com"></script>
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
                            <i class="fa-solid fa-graduation-cap text-um-gold"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Layanan Kebahasaan Terpadu</p>
                            <p class="text-xs text-blue-200/80 mt-0.5">EPT, Penerjemahan, dan Pelatihan Bahasa dalam satu platform.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-file-circle-check text-um-gold"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Proses Terverifikasi</p>
                            <p class="text-xs text-blue-200/80 mt-0.5">Setiap layanan diproses dan diverifikasi oleh staf resmi.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-lock text-um-gold"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Data Terlindungi</p>
                            <p class="text-xs text-blue-200/80 mt-0.5">Informasi pribadi Anda dijaga kerahasiaannya.</p>
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
                        <h2 class="text-xl font-bold text-slate-900">Buat Akun Baru</h2>
                        <p class="text-sm text-slate-500 mt-1">Lengkapi data berikut untuk mulai menggunakan layanan.</p>
                    </div>

                    @if (session('status'))
                        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm">
                            <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="name" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400"><i class="fa-solid fa-user text-sm"></i></span>
                                <input type="text" id="name" name="name" value="{{ old('name') }}"
                                    class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-300 bg-slate-50/50 focus:bg-white focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none text-sm transition placeholder:text-slate-400 @error('name') border-red-300 @enderror"
                                    placeholder="Nama lengkap sesuai KTP" required>
                            </div>
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="email" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Alamat Email</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400"><i class="fa-solid fa-envelope text-sm"></i></span>
                                <input type="email" id="email" name="email" value="{{ old('email') }}"
                                    class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-300 bg-slate-50/50 focus:bg-white focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none text-sm transition placeholder:text-slate-400 @error('email') border-red-300 @enderror"
                                    placeholder="nama@gmail.com" required>
                            </div>
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="whatsapp" class="block text-[13px] font-semibold text-slate-700 mb-1.5">
                                Nomor WhatsApp <span class="text-xs font-normal text-slate-400">(opsional)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400"><i class="fa-brands fa-whatsapp text-sm"></i></span>
                                <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}"
                                    class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-300 bg-slate-50/50 focus:bg-white focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none text-sm transition placeholder:text-slate-400 @error('whatsapp') border-red-300 @enderror"
                                    placeholder="08xxxxxxxxxx">
                            </div>
                            @error('whatsapp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label for="password" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Kata Sandi</label>
                                <div class="relative" x-data="{ show: false }">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400"><i class="fa-solid fa-lock text-sm"></i></span>
                                    <input :type="show ? 'text' : 'password'" id="password" name="password"
                                        class="w-full pl-10 pr-9 py-2.5 rounded-lg border border-slate-300 bg-slate-50/50 focus:bg-white focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none text-sm transition placeholder:text-slate-400 @error('password') border-red-300 @enderror"
                                        placeholder="Min. 8 karakter" required>
                                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                        <i class="fa-solid text-sm" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Konfirmasi</label>
                                <div class="relative" x-data="{ show: false }">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400"><i class="fa-solid fa-lock text-sm"></i></span>
                                    <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation"
                                        class="w-full pl-10 pr-9 py-2.5 rounded-lg border border-slate-300 bg-slate-50/50 focus:bg-white focus:border-um-blue focus:ring-2 focus:ring-um-blue/20 outline-none text-sm transition placeholder:text-slate-400"
                                        placeholder="Ulangi sandi" required>
                                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                        <i class="fa-solid text-sm" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 rounded-lg bg-um-blue text-white text-sm font-semibold hover:bg-um-dark-blue active:scale-[0.99] transition shadow-md shadow-um-blue/20 mt-2">
                            <i class="fa-solid fa-user-plus mr-2"></i>Daftar Sekarang
                        </button>
                    </form>

                    <div class="text-center mt-6 pt-5 border-t border-slate-100">
                        <p class="text-sm text-slate-600">
                            Sudah punya akun?
                            <a href="{{ route('login') }}" class="text-um-blue font-semibold hover:underline">Masuk di sini</a>
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
