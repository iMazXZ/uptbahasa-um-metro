<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('basic_listening_survey_responses', function (Blueprint $table) {
            // Hapus constraint lama (ke users) kalau ada
            try {
                $table->dropForeign(['supervisor_id']);
            } catch (\Throwable $e) {
                // ignore jika belum ada
            }

            // Pastikan kolom ada
            if (! Schema::hasColumn('basic_listening_survey_responses', 'supervisor_id')) {
                $table->foreignId('supervisor_id')->nullable()->after('tutor_id');
            }

            // Tambah FK ke tabel supervisors baru
            $table->foreign('supervisor_id')
                ->references('id')
                ->on('basic_listening_supervisors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_survey_responses', function (Blueprint $table) {
            // Kembalikan ke users (opsional)
            try {
                $table->dropForeign(['supervisor_id']);
            } catch (\Throwable $e) {}

            $table->foreign('supervisor_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
