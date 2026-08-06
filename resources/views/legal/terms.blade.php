@extends('layouts.front')

@section('title', 'Syarat Layanan - Lembaga Bahasa UM Metro')

@section('meta')
  <meta name="description" content="Syarat Layanan Lembaga Bahasa UM Metro yang mengatur akses, penggunaan situs, dan kewajiban pengguna.">
  <meta name="robots" content="index,follow">
@endsection

@section('content')
<section class="relative overflow-hidden py-14 lg:py-20">
  <div class="absolute inset-0">
    <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-indigo-900"></div>
    <div class="absolute inset-0" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px; opacity: 0.1;"></div>
  </div>

  <div class="relative max-w-4xl mx-auto px-4 lg:px-8 text-white">
    <span class="inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-4 py-1.5 text-sm font-medium">
      <i class="fas fa-file-contract text-blue-300"></i>
      Dokumen Legal
    </span>
    <h1 class="mt-4 text-3xl md:text-4xl lg:text-5xl font-black">Syarat Layanan</h1>
    <p class="mt-3 text-blue-100">Berlaku sejak: 14 Februari 2026</p>
  </div>
</section>

<section class="py-10 lg:py-14 bg-gray-50">
  <div class="max-w-4xl mx-auto px-4 lg:px-8">
    <article class="bg-white border border-gray-100 shadow-sm rounded-2xl p-6 lg:p-10 prose prose-slate max-w-none">
      <p>
        Dengan mengakses situs Lembaga Bahasa UM Metro, Anda menyetujui syarat layanan ini. Jika tidak setuju,
        silakan hentikan penggunaan situs.
      </p>

      <h2>1. Penggunaan Situs</h2>
      <ul>
        <li>Pengguna wajib menggunakan situs untuk tujuan yang sah dan tidak melanggar hukum.</li>
        <li>Pengguna dilarang melakukan aktivitas yang dapat mengganggu keamanan atau ketersediaan layanan.</li>
        <li>Pengguna bertanggung jawab atas kebenaran data yang dikirimkan.</li>
      </ul>

      <h2>2. Akun Dan Akses</h2>
      <ul>
        <li>Jika layanan membutuhkan akun, pengguna bertanggung jawab menjaga kerahasiaan kredensial.</li>
        <li>Kami berhak menangguhkan atau menghentikan akses jika ditemukan pelanggaran.</li>
      </ul>

      <h2>3. Konten Dan Hak Kekayaan Intelektual</h2>
      <ul>
        <li>Seluruh konten situs ini dimiliki atau digunakan secara sah oleh Lembaga Bahasa UM Metro.</li>
        <li>Dilarang menyalin, memodifikasi, atau mendistribusikan konten tanpa izin tertulis.</li>
      </ul>

      <h2>4. Layanan Dan Perubahan</h2>
      <p>
        Kami dapat memperbarui, menambah, atau menghentikan sebagian layanan sewaktu-waktu untuk kebutuhan operasional
        dan peningkatan kualitas.
      </p>

      <h2>5. Batasan Tanggung Jawab</h2>
      <p>
        Situs disediakan sebagaimana adanya. Kami berupaya menjaga keakuratan informasi, namun tidak menjamin bahwa
        seluruh informasi selalu bebas dari kesalahan atau gangguan teknis.
      </p>

      <h2>6. Tautan Pihak Ketiga</h2>
      <p>
        Situs dapat berisi tautan ke layanan pihak ketiga. Kami tidak bertanggung jawab atas isi, kebijakan, atau
        praktik pada situs pihak ketiga.
      </p>

      <h2>7. Hukum Yang Berlaku</h2>
      <p>
        Syarat layanan ini ditafsirkan sesuai peraturan perundang-undangan yang berlaku di Republik Indonesia.
      </p>

      <h2>8. Perubahan Syarat Layanan</h2>
      <p>
        Kami berhak mengubah syarat layanan ini sewaktu-waktu. Perubahan berlaku setelah dipublikasikan di halaman ini.
      </p>

      <h2>9. Kontak</h2>
      <p>Untuk pertanyaan terkait syarat layanan, Anda dapat menghubungi:</p>
      <ul>
        <li>Lembaga Bahasa Universitas Muhammadiyah Metro.</li>
        <li>Alamat: Jl. Ki Hajar Dewantara, Iringmulyo, Kec. Metro Timur, Kota Metro, Lampung.</li>
        <li>Telepon: 0858-9666-6588.</li>
        <li>Situs: <a href="https://ummetro.ac.id" target="_blank" rel="noopener">ummetro.ac.id</a>.</li>
      </ul>
    </article>
  </div>
</section>
@endsection
