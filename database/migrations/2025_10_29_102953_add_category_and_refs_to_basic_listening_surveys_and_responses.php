<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ===== surveys: tambah category (tutor/supervisor/institute) =====
        Schema::table('basic_listening_surveys', function (Blueprint $table) {
            // default 'final' tetap dipakai di kolom target yang sudah ada
            $table->enum('category', ['tutor', 'supervisor', 'institute'])
                ->default('tutor')
                ->after('target');
        });

        // ===== responses: simpan relasi pilihan tutor/supervisor & meta =====
        Schema::table('basic_listening_survey_responses', function (Blueprint $table) {
            // asumsikan tutor/supervisor adalah user juga
            $table->foreignId('tutor_id')->nullable()->constrained('users')->nullOnDelete()->after('user_id');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete()->after('tutor_id');
            $table->json('meta')->nullable()->after('submitted_at'); // mis: ruangan, catatan
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_surveys', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('basic_listening_survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tutor_id');
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropColumn('meta');
        });
    }
};
