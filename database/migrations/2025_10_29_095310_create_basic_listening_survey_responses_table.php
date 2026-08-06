<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basic_listening_survey_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('survey_id')
                ->constrained('basic_listening_surveys')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Jika target=session, simpan session_id; kalau target=final biarkan null
            $table->foreignId('session_id')
                ->nullable()
                ->constrained('basic_listening_sessions')
                ->nullOnDelete();

            // Null sampai user submit final; bisa dipakai sebagai penanda "draft vs final"
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            // Satu response per (survey,user,session) — session_id bisa null (untuk target=final)
            $table->unique(['survey_id', 'user_id', 'session_id'], 'bl_survey_response_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basic_listening_survey_responses');
    }
};
