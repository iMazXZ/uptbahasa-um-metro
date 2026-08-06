<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('basic_listening_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('basic_listening_sessions')->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained('basic_listening_quizzes')->cascadeOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedSmallInteger('score')->nullable(); // 0-100
            $table->timestamps();

            $table->unique(['user_id','session_id']); // 1 attempt per user per sesi
        });
    }
    public function down(): void {
        Schema::dropIfExists('basic_listening_attempts');
    }
};
