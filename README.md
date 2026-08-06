# 🏛️ Web Lembaga Bahasa UM Metro

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-3.x-F59E0B?style=for-the-badge&logo=php&logoColor=white)
![Node.js](https://img.shields.io/badge/Node.js-18+-339933?style=for-the-badge&logo=nodedotjs&logoColor=white)
![WhatsApp](https://img.shields.io/badge/WhatsApp-Baileys-25D366?style=for-the-badge&logo=whatsapp&logoColor=white)

**Sistem Informasi Terintegrasi Lembaga Bahasa Universitas Muhammadiyah Metro**

[Fitur](#-fitur-unggulan) • [Tech Stack](#-tech-stack) • [Instalasi](#-instalasi) • [WhatsApp Service](#-whatsapp-service-integration)

</div>

---

## 📋 Tentang Project

Web Lembaga Bahasa UM Metro adalah platform digital komprehensif yang memfasilitasi seluruh layanan kebahasaan di UM Metro. Sistem ini tidak hanya menangani administrasi kursus, tetapi juga mengintegrasikan layanan **EPT (English Proficiency Test)**, **Penerjemahan Dokumen**, dan **Basic Listening** secara terpusat dengan notifikasi real-time via WhatsApp.

## 🌟 Fitur Unggulan

### 📝 **Surat Rekomendasi EPT**
- **Pengajuan Mandiri**: Mahasiswa menginput skor EPT yang dimiliki untuk mengajukan surat rekomendasi.
- **Verifikasi Admin**: Pengecekan bukti skor dan data mahasiswa oleh admin.
- **Generate Otomatis**:
  - Surat Rekomendasi langsung terbit (PDF) dengan barcode verifikasi setelah disetujui.
  - Notifikasi WA otomatis (Approved/Rejected) beserta catatan dari admin.

### 📖 **Layanan Penerjemahan**
- **Submission Dokumen**: Upload dokumen abstrak/jurnal untuk diterjemahkan.
- **Tracking Status**: Status real-time (Diproses, Selesai, Ditolak).
- **Download Hasil**: Link download hasil terjemahan dikirim langsung via WhatsApp saat status "Selesai".

### 🎧 **Basic Listening**
- **Ujian Online**: Platform ujian listening dengan audio player terintegrasi.
- **Bank Soal**: Manajemen soal listening (Multiple Choice, True/False, dll).
- **Penilaian Otomatis**: Skor langsung keluar setelah ujian selesai.

### 🔔 **Notifikasi Cerdas (WhatsApp Agnostic)**
- **Prioritas Channel**: Mengirim notifikasi ke **WhatsApp** jika nomor terverifikasi, fallback ke **Email** jika tidak.
- **OTP Verification**: Verifikasi nomor HP menggunakan kode OTP via WhatsApp.
- **Professional Messages**: Format pesan standar institusi tanpa emoji berlebih.

## 🛠️ Tech Stack

| Komponen | Teknologi | Deskripsi |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel 11.x | Core system logic & API |
| **Admin Panel** | FilamentPHP 3.x | Dashboard admin & manajemen data |
| **Database** | MySQL 8.0+ | Relational database management |
| **Microservice** | Node.js + Baileys | Layanan independen untuk koneksi WhatsApp |
| **Frontend** | Blade + Alpine.js | Interface pengguna (User Dashboard) |
| **Queue** | Database/Redis | Asynchronous job processing (wajib untuk notifikasi) |

## 🚀 Instalasi

### Prasyarat
- PHP 8.2+
- Composer
- Node.js 18+ & NPM
- MySQL
- Git

### 1️⃣ Setup Laravel (Main App)

```bash
# Clone repository
git clone https://github.com/username/web-lembaga-bahasa-um-metro.git
cd web-lembaga-bahasa-um-metro

# Install dependencies
composer install
npm install

# Setup Environment
cp .env.example .env
php artisan key:generate

# Konfigurasi Database di .env
# DB_DATABASE=lembaga_bahasa ...

# Migrate & Seed
php artisan migrate --seed

# Build Assets
npm run build

# Link Storage
php artisan storage:link
```

### 2️⃣ Setup WhatsApp Service (Microservice)

Aplikasi ini membutuhkan service Node.js berjalan untuk mengirim pesan WhatsApp.

```bash
# Masuk ke folder service
cd whatsapp-service

# Install dependencies
npm install

# Setup Env Service
# Buat file .env di dalam folder whatsapp-service
echo "PORT=3001" > .env
echo "API_KEY=your-secret-api-key" >> .env

# Jalankan Service
node index.js
# Atau untuk production gunakan PM2:
# pm2 start index.js --name whatsapp-service
```

### 3️⃣ Konfigurasi Integrasi

Pastikan file `.env` di **Laravel** memiliki konfigurasi berikut agar terhubung dengan service Node.js:

```env
# URL tempat service Node.js berjalan
WHATSAPP_SERVICE_URL=http://localhost:3001

# API Key harus sama dengan yang ada di .env Node.js
WHATSAPP_API_KEY=your-secret-api-key

# Aktifkan fitur WA
WHATSAPP_ENABLED=true
```

### 4️⃣ Jalankan Queue Worker (PENTING)

Notifikasi dikirim melalui antrian (queue) agar tidak memperlambat aplikasi. Worker harus selalu berjalan:

```bash
php artisan queue:work
```

## 📱 Cara Koneksi WhatsApp

1. Pastikan service Node.js berjalan.
2. Lihat terminal (atau log pm2) service Node.js.
3. QR Code akan muncul di terminal.
4. Scan menggunakan WhatsApp pada HP yang akan dijadikan sender (Admin).
5. Jika berhasil, status akan menjadi `Connected`.

## 🤝 Kontribusi

Silakan buat Pull Request untuk fitur baru atau perbaikan bug. Pastikan mengikuti coding standard PSR-12 dan menyertakan tes jika memungkinkan.

## 📄 Lisensi

[MIT License](LICENSE)
