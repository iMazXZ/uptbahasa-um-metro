<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_nilai_tes', function (Blueprint $table) {
            $table->id();
            $table->integer('pendaftaran_grup_tes_id');
            $table->integer('listening_comprehension')->nullable();
            $table->integer('structure_written_expr')->nullable();
            $table->integer('reading_comprehension')->nullable();
            $table->integer('total_score')->nullable();
            $table->string('rank')->nullable();
            $table->timestamp('selesai_pada')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_nilai_tes');
    }
};
