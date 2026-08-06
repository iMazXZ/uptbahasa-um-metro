<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penerjemahans', function (Blueprint $table) {
            // Hapus dua kolom ini
            $table->dropColumn(['dokumen_asli', 'dokumen_terjemahan']);
        });
    }

    public function down(): void
    {
        Schema::table('penerjemahans', function (Blueprint $table) {
            // Pulihkan kalau di-rollback (tipe sesuaikan kebutuhanmu)
            $table->string('dokumen_asli')->nullable();
            $table->string('dokumen_terjemahan')->nullable();
        });
    }
};
