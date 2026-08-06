<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basic_listening_legacy_scores', function (Blueprint $table): void {
            $table->id();
            $table->string('srn', 50)->nullable();
            $table->string('srn_normalized', 50)->nullable()->unique();
            $table->string('name')->nullable();
            $table->string('name_normalized')->nullable()->index();
            $table->string('study_program')->nullable();
            $table->unsignedSmallInteger('source_year')->nullable()->index();
            $table->decimal('score', 5, 2);
            $table->string('grade', 10)->nullable()->index();
            $table->string('source_file')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['source_year', 'score']);
            $table->fullText('name_normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basic_listening_legacy_scores');
    }
};
