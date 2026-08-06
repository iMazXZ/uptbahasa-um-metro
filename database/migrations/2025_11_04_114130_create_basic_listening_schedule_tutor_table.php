<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi: membuat tabel pivot tutor-jadwal Basic Listening.
     */
    public function up(): void
    {
        Schema::create('basic_listening_schedule_tutor', function (Blueprint $table) {
            $table->id();

            // Relasi ke jadwal (BasicListeningSchedule)
            $table->foreignId('schedule_id')
                ->constrained('basic_listening_schedules')
                ->cascadeOnDelete();

            // Relasi ke user (hanya role tutor yang dipakai)
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Rollback migrasi: hapus tabel pivot.
     */
    public function down(): void
    {
        Schema::dropIfExists('basic_listening_schedule_tutor');
    }
};
