<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('basic_listening_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('basic_listening_sessions')->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('session_id'); // 1 quiz per session
        });
    }
    public function down(): void {
        Schema::dropIfExists('basic_listening_quizzes');
    }
};
