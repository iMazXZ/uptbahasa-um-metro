<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('basic_listening_grades', function (Blueprint $table) {
            $table->id();

            // Mahasiswa
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Tahun angkatan / cohort yang diambil dari users.year (string atau int, kita simpan string aman)
            $table->string('user_year', 20)->nullable()->index();

            // Komponen nilai (0..100)
            $table->unsignedTinyInteger('attendance')->nullable();
            $table->unsignedTinyInteger('final_test')->nullable();

            // Opsional cache hasil akhir (boleh dibiarkan null)
            $table->decimal('final_numeric_cached', 5, 2)->nullable();
            $table->string('final_letter_cached', 2)->nullable();

            $table->timestamps();

            // Satu baris per user per tahun
            $table->unique(['user_id', 'user_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basic_listening_grades');
    }
};
