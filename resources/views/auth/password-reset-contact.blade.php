{{-- resources/views/auth/password-reset-contact.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Kata Sandi - UPT Bahasa UM Metro</title>
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
            <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 28px 28px;"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-um-gold/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-32 -left-24 w-96 h-96 bg-blue-400/20 rounded-full blur-3xl"></div>

            <div class="relative z-10 m-auto w-full max-w-md px-12 py-16">
                <div class="mb-12">
                    <img src="{{ asset('images/logo-um.png') }}" alt="Logo UM Metro" class="h-16 w-16 object-contain mb-5">
                    <h1 class="text-2xl font-extrabold tracking-tight text-white">
                        UPT <span class="text-um-gold">Bahasa</span>
                    </h1>
                    <p class="text-sm text-blue-200 mt-1">Universitas Muhammadiyah Metro</p>
                </div>

                <div class="space-y-5">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-headset text-um-gold"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Bantuan Langsung</p>
                            <p class="text-xs text-blue-200/80 mt-0.5">Admin siap membantu pemulihan akun Anda.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-shield-halved text-um-gold"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Verifikasi Identitas</p>
                            <p class="text-xs text-blue-200/80 mt-0.5">Keamanan akun tetap menjadi prioritas.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-clock text-um-gold"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Proses Cepat</p>
                            <p class="text-xs text-blue-200/80 mt-0.5">Reset diproses saat jam kerja layanan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Info Panel --}}
        <div class="flex-1 flex items-center justify-center px-4 sm:px-8 py-10">
            <div class="w-full max-w-md">
                {{-- Mobile brand --}}
                <div class="lg:hidden text-center mb-8">
                    <img src="{{ asset('images/logo-um.png') }}" alt="Logo UM Metro" class="h-12 w-12 object-contain mx-auto mb-3">
                    <div class="font-extrabold text-lg text-slate-900">UPT <span class="text-um-gold">Bahasa</span></div>
                    <div class="text-xs text-slate-500">Universitas Muhammadiyah Metro</div>
                </div>

                {{-- Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-7 sm:p-8 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 flex items-center justify-center mx-auto mb-5">
                        <i class="fa-solid fa-key text-2xl text-um-blue"></i>
                    </div>

                    <h2 class="text-xl font-bold text-slate-900">Reset Kata Sandi</h2>
                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                        Layanan reset kata sandi otomatis sedang tidak tersedia.<br>
                        Silakan hubungi admin UPT Bahasa melalui WhatsApp untuk pemulihan akun Anda.
                    </p>

                    {{-- Steps --}}
                    <div class="mt-6 p-4 rounded-xl bg-slate-50 border border-slate-100 text-left space-y-3">
                        <p class="text-[13px] font-semibold text-slate-700 mb-2">Siapkan informasi berikut:</p>
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-user text-xs text-um-blue"></i>
                            </div>
                            <span class="text-sm text-slate-600">Nama lengkap sesuai akun</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-id-card text-xs text-um-blue"></i>
                            </div>
                            <span class="text-sm text-slate-600">Nomor NPM / NIM</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-graduation-cap text-xs text-um-blue"></i>
                            </div>
                            <span class="text-sm text-slate-600">Program studi</span>
                        </div>
                    </div>

                    {{-- WhatsApp button --}}
                    <a href="https://wa.me/6287790740408?text={{ urlencode("Halo Admin UPT Bahasa UM Metro, saya ingin reset kata sandi akun.\n\nNama: \nNPM/NIM: \nProgram Studi: ") }}"
                       target="_blank" rel="noopener"
                       class="mt-6 w-full inline-flex items-center justify-center gap-2 py-3 rounded-lg bg-green-600 text-white text-sm font-semibold hover:bg-green-700 active:scale-[0.99] transition shadow-md shadow-green-600/20">
                        <i class="fa-brands fa-whatsapp text-lg"></i>
                        Hubungi Admin via WhatsApp
                    </a>

                    <div class="mt-4 p-3 rounded-lg bg-slate-50 border border-slate-100 text-sm text-slate-500">
                        <i class="fa-solid fa-phone text-xs mr-1"></i>
                        0877-9074-0408
                    </div>

                    <div class="text-center mt-6 pt-5 border-t border-slate-100">
                        <a href="{{ route('login') }}" class="text-sm text-um-blue font-semibold hover:underline">
                            <i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Halaman Masuk
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
