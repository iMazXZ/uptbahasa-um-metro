<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('certificate_categories')->cascadeOnDelete();
            $table->string('certificate_number')->unique();  // Auto-generated
            $table->unsignedTinyInteger('semester')->nullable();  // 1-6
            
            // Data peserta
            $table->string('name');
            $table->string('srn')->nullable();           // NPM
            $table->string('study_program')->nullable(); // Prodi
            
            // Nilai (JSON untuk fleksibilitas per kategori)
            $table->json('scores')->nullable();          // {"listening":82,"speaking":80,...}
            $table->unsignedInteger('total_score')->nullable();
            $table->decimal('average_score', 5, 2)->nullable();
            $table->string('grade')->nullable();         // "A EXCELLENT"
            
            $table->date('issued_at');
            $table->string('verification_code')->unique()->nullable();
            $table->timestamps();
            
            $table->index(['category_id', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_certificates');
    }
};
