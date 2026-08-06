<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basic_listening_survey_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('survey_id')
                ->constrained('basic_listening_surveys')
                ->cascadeOnDelete();

            // Tipe pertanyaan: skala 1–5 (likert) atau jawaban teks
            $table->enum('type', ['likert', 'text'])->default('likert');

            $table->string('question', 500);
            $table->json('options')->nullable();     // opsional kalau nanti mau pilihan khusus
            $table->boolean('is_required')->default(true);

            $table->unsignedSmallInteger('order')->default(1);

            $table->timestamps();

            $table->index(['survey_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basic_listening_survey_questions');
    }
};
