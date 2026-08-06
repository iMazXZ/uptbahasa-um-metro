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
        Schema::create('master_grup_tes', function (Blueprint $table) {
            $table->id();
            $table->integer('group_number')->nullable();
            $table->string('instructional_year')->nullable();
            $table->datetime('tanggal_tes')->nullable();
            $table->string('ruangan_tes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_grup_tes');
    }
};
