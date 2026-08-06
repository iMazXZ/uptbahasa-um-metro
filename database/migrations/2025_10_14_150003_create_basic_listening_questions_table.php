<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('basic_listening_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('basic_listening_quizzes')->cascadeOnDelete();
            $table->text('question');
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->char('correct', 1); // A/B/C/D
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('basic_listening_questions');
    }
};
