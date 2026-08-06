<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basic_listening_survey_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('response_id')
                ->constrained('basic_listening_survey_responses')
                ->cascadeOnDelete();

            $table->foreignId('question_id')
                ->constrained('basic_listening_survey_questions')
                ->cascadeOnDelete();

            // Untuk pertanyaan likert (1..5)
            $table->unsignedTinyInteger('likert_value')->nullable();

            // Untuk pertanyaan teks
            $table->text('text_value')->nullable();

            $table->timestamps();

            // Satu jawaban per (response,question)
            $table->unique(['response_id', 'question_id'], 'bl_survey_answer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basic_listening_survey_answers');
    }
};
