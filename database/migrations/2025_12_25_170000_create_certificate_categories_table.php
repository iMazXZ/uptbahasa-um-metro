<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                     // Nama kategori (e.g. "Interactive Class Bahasa Inggris")
            $table->string('slug')->unique();           // Slug unik
            $table->string('number_format');            // Format nomor: {seq}.{semester}/II.3.AU/A/EPP.LB.{year}
            $table->unsignedInteger('last_sequence')->default(0);  // Auto-increment per kategori
            $table->json('semesters')->nullable();      // [1,2,3,4,5,6] atau null jika tidak pakai semester
            $table->json('score_fields')->nullable();   // Field nilai yang dibutuhkan kategori ini
            $table->string('pdf_template')->nullable(); // Nama template PDF (e.g. "epp-certificate")
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_categories');
    }
};
