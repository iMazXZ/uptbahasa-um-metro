{{-- resources/views/partials/footer.blade.php --}}
<footer class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-900 text-white">
  {{-- Decorative gradient glow (matching hero) --}}
  <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-35">
    <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full blur-3xl bg-blue-400/30"></div>
    <div class="absolute -bottom-28 -right-20 h-72 w-72 rounded-full blur-3xl bg-indigo-400/30"></div>
  </div>

  <div class="relative py-12 lg:py-16">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">

        {{-- Brand + tagline --}}
        <div>
          <a href="{{ route('front.home') }}" class="group flex items-center gap-3 mb-4">
            <img
              src="{{ asset('images/logo-um.png') }}"
              alt="Logo UM Metro"
              class="h-10 w-10 md:h-12 md:w-12 object-contain"
              loading="lazy">
            <div class="leading-tight">
              <div class="font-extrabold tracking-tight text-[18px] md:text-[20px] text-white">
                <span>UPT </span><span class="text-um-gold">Bahasa</span>
              </div>
              <div class="text-white/80 text-[11px] md:text-[10px] -mt-0.5">
                Universitas Muhammadiyah Metro
              </div>
              <div class="italic text-white/90 text-[12px] md:text-[10px]">
                Supports Your Success
              </div>
            </div>
          </a>

          <p class="text-blue-100/90 text-sm leading-relaxed max-w-xs">
            Layanan pengujian EPT, penerjemahan dokumen, dan pembelajaran bahasa untuk sivitas akademika dan umum di Kota Metro.
          </p>

          {{-- Social (opsional, isi jika tersedia) --}}
          {{-- <div class="mt-4 flex items-center gap-3 text-blue-100">
            <a href="#" class="hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40 rounded p-1" aria-label="Instagram UPT Bahasa">
              <i class="fa-brands fa-instagram text-xl"></i>
            </a>
            <a href="#" class="hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40 rounded p-1" aria-label="YouTube UPT Bahasa">
              <i class="fa-brands fa-youtube text-xl"></i>
            </a>
          </div> --}}
        </div>

        {{-- Tautan cepat (tanpa mempublikasikan pendaftaran EPT) --}}
        <div>
          <h4 class="text-lg font-bold mb-4">Tautan</h4>
          <ul class="space-y-2 text-blue-100 text-sm">
            <li>
              <a href="{{ route('front.home') }}#berita" class="hover:text-white transition">
                Berita, Jadwal &amp; Nilai EPT
              </a>
            </li>
            <li>
              <a href="{{ route('bl.index') }}" class="hover:text-white transition">
                Basic Listening
              </a>
            </li>
            <li>
              <a href="{{ route('verification.index') }}" class="hover:text-white transition">
                Verifikasi Dokumen
              </a>
            </li>
            <li>
              <a href="{{ route('front.home') }}#profil" class="hover:text-white transition">
                Profil UPT Bahasa
              </a>
            </li>
            <li>
              <a href="{{ route('front.home') }}#kontak" class="hover:text-white transition">
                Kontak
              </a>
            </li>
          </ul>
        </div>

        {{-- Jam Pelayanan --}}
        <div>
          <h4 class="text-lg font-bold mb-4">Jam Pelayanan</h4>
          <p class="text-blue-100 text-sm mb-2">Senin–Kamis: 08.00–15.30 WIB</p>
          <p class="text-blue-100 text-sm mb-2">Jumat: 08.00–11.30 WIB</p>
          <p class="text-blue-100 text-sm">Pelayanan Online: 24 Jam</p>
        </div>

        {{-- Kontak & Alamat --}}
        <div>
          <h4 class="text-lg font-bold mb-4">Kontak Kami</h4>
          <address class="not-italic text-blue-100 text-sm space-y-2">
            <p>
              Jalan Gatot Subroto No. 100, Yosodadi<br>
              Metro, Lampung, Indonesia
            </p>
            <p>
              WhatsApp:
              <a href="https://wa.me/6287790740408?text=Halo%20UPT%20Bahasa%2C%20saya%20ingin%20bertanya."
                 class="underline underline-offset-4 hover:text-white transition"
                 target="_blank" rel="noopener">
                0877-9074-0408
              </a>
            </p>
            <p>
              Email:
              <a href="mailto:uptbahasa@ummetro.ac.id"
                 class="underline underline-offset-4 hover:text-white transition">
                uptbahasa@ummetro.ac.id
              </a>
            </p>
            <p>
              <a href="https://maps.google.com/?q=Jalan%20Gatot%20Subroto%20No.%20100%2C%20Yosodadi%2C%20Metro"
                 class="inline-flex items-center gap-2 text-blue-100 hover:text-white transition"
                 target="_blank" rel="noopener">
                <i class="fa-solid fa-location-dot"></i>
                Lihat peta lokasi
              </a>
            </p>
          </address>
        </div>
      </div>

      {{-- Bottom bar --}}
      <div class="mt-10 pt-6 border-t border-white/10 flex flex-col lg:flex-row items-center justify-between gap-4">
        <p class="text-blue-200 text-sm">
          © {{ now()->year }} UPT Bahasa UM Metro. Hak cipta dilindungi.
        </p>
        <nav aria-label="Legal" class="text-blue-200 text-sm">
          <ul class="flex items-center gap-4">
            <li><a href="{{ route('legal.privacy') }}" class="hover:text-white transition">Kebijakan Privasi</a></li>
            <li><a href="{{ route('legal.terms') }}" class="hover:text-white transition">Syarat Layanan</a></li>
          </ul>
        </nav>
      </div>
    </div>
  </div>

  <!-- {{-- Floating WhatsApp --}}
  <a href="https://wa.me/6287790740408?text=Halo%20UPT%20Bahasa%2C%20saya%20ingin%20bertanya."
     target="_blank" rel="noopener"
     class="fixed bottom-6 right-6 w-14 h-14 lg:w-16 lg:h-16 bg-green-500 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-green-600 transition-colors z-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
     aria-label="Chat WhatsApp UPT Bahasa">
    <i class="fab fa-whatsapp text-xl lg:text-2xl" aria-hidden="true"></i>
  </a> -->
</footer>
