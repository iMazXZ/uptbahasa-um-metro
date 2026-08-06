<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom prody_id ke tabel basic_listening_schedules.
     */
    public function up(): void
    {
        Schema::table('basic_listening_schedules', function (Blueprint $table) {
            // Tambah kolom relasi ke tabel prody
            $table->foreignId('prody_id')
                ->nullable()
                ->after('session_id')
                ->constrained('prodies') // pastikan nama tabelnya sesuai di project kamu
                ->cascadeOnDelete();
        });
    }

    /**
     * Rollback perubahan.
     */
    public function down(): void
    {
        Schema::table('basic_listening_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prody_id');
        });
    }
};
