<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('basic_listening_manual_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('user_year', 20)->nullable()->index();

            // meeting 1..5 (bisa diperluas nanti)
            $table->unsignedTinyInteger('meeting')->comment('1..5');
            $table->unsignedTinyInteger('score')->nullable()->comment('0..100');

            $table->timestamps();

            // 1 baris unik per (user, year, meeting)
            $table->unique(['user_id', 'user_year', 'meeting']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basic_listening_manual_scores');
    }
};
