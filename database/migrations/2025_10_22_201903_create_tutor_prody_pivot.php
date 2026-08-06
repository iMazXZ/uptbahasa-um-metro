<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tutor_prody', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // pakai 'prodies' sesuai hasil Tinker
            $table->foreignId('prody_id')->constrained('prodies')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'prody_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_prody');
    }
};
