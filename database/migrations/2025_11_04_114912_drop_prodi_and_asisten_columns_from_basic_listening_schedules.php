<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_listening_schedules', function (Blueprint $table) {
            // Hilangkan kolom lama yang tidak dipakai
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
            // Pulihkan jika rollback
            if (! Schema::hasColumn('basic_listening_schedules', 'prodi')) {
                $table->string('prodi')->nullable();
            }
            if (! Schema::hasColumn('basic_listening_schedules', 'asisten')) {
                $table->string('asisten')->nullable();
            }
        });
    }
};
