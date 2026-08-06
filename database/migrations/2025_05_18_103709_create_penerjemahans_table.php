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
        Schema::create('penerjemahans', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('bukti_pembayaran')->nullable();
            $table->string('dokumen_asli')->nullable();
            $table->string('dokumen_terjemahan')->nullable();
            $table->dateTime('submission_date')->nullable();
            $table->string('status')->nullable();
            $table->integer('translator_id')->nullable();
            $table->dateTime('completion_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerjemahans');
    }
};
