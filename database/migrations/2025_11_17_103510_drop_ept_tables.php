<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Matikan sementara constraint FK biar bisa drop tabel dengan aman
        Schema::disableForeignKeyConstraints();

        // Urutan dari tabel "anak" ke "induk" (meski FK sudah diabaikan juga)
        Schema::dropIfExists('data_nilai_tes');
        Schema::dropIfExists('pendaftaran_grup_tes');
        Schema::dropIfExists('master_grup_tes');
        Schema::dropIfExists('pendaftaran_ept');

        // Nyalakan lagi constraint FK
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Dikosongkan saja.
        // Kalau nanti mau balikin, bisa bikin migration baru untuk re-create tabelnya.
    }
};
