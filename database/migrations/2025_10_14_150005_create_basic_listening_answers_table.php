<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('basic_listening_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('basic_listening_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('basic_listening_questions')->cascadeOnDelete();
            $table->char('answer', 1)->nullable(); // A/B/C/D
            $table->boolean('is_correct')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id','question_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('basic_listening_answers');
    }
};
