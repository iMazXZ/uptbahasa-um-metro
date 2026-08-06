<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ept_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Data untuk Tes 1 (Wajib)
            $table->integer('nilai_tes_1');
            $table->date('tanggal_tes_1');
            $table->string('foto_path_1');

            // Data untuk Tes 2 (SEKARANG WAJIB)
            $table->integer('nilai_tes_2'); // <-- Hapus nullable()
            $table->date('tanggal_tes_2'); // <-- Hapus nullable()
            $table->string('foto_path_2'); // <-- Hapus nullable()

            // Data untuk Tes 3 (SEKARANG WAJIB)
            $table->integer('nilai_tes_3'); // <-- Hapus nullable()
            $table->date('tanggal_tes_3'); // <-- Hapus nullable()
            $table->string('foto_path_3'); // <-- Hapus nullable()

            $table->string('status')->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ept_submissions');
    }
};
