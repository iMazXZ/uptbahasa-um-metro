<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi: membuat tabel basic_listening_schedules.
     */
    public function up(): void
    {
        Schema::create('basic_listening_schedules', function (Blueprint $table) {
            $table->id();

            // Relasi ke session Basic Listening
            $table->foreignId('session_id')
                ->constrained('basic_listening_sessions')
                ->cascadeOnDelete();

            // Informasi utama jadwal
            $table->string('prodi');          // contoh: "PGSD C", "Manajemen B"
            $table->string('asisten');        // contoh: "Wefi & Luthfi"
            $table->string('hari');           // contoh: "Senin", "Selasa", dst

            // Rentang waktu pengajaran
            $table->time('jam_mulai');
            $table->time('jam_selesai');

            $table->timestamps();
        });
    }

    /**
     * Rollback migrasi: hapus tabel basic_listening_schedules.
     */
    public function down(): void
    {
        Schema::dropIfExists('basic_listening_schedules');
    }
};
