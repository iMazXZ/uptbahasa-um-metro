<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_listening_schedules', function (Blueprint $table) {
            // Hapus kolom text legacy jika masih ada
            if (Schema::hasColumn('basic_listening_schedules', 'prodi')) {
                $table->dropColumn('prodi');
            }
            if (Schema::hasColumn('basic_listening_schedules', 'asisten')) {
                $table->dropColumn('asisten');
            }
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_schedules', function (Blueprint $table) {
            // Kembalikan kolom (optional; tipe disamakan seperti awal)
            if (! Schema::hasColumn('basic_listening_schedules', 'prodi')) {
                $table->string('prodi')->nullable(); // nullable agar tidak mengulang error
            }
            if (! Schema::hasColumn('basic_listening_schedules', 'asisten')) {
                $table->string('asisten')->nullable();
            }
        });
    }
};
