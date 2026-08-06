<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('basic_listening_connect_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('basic_listening_sessions')->cascadeOnDelete();
            $table->string('code_hash', 128); // sha256 hex
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('max_uses')->nullable(); // null = unlimited
            $table->boolean('is_active')->default(true);
            $table->json('rules')->nullable(); // opsional filter prodi/angkatan
            $table->timestamps();

            $table->index(['session_id','is_active']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('basic_listening_connect_codes');
    }
};
