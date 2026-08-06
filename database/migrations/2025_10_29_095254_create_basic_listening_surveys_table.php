<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basic_listening_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            // Wajib diisi sebelum unduh sertifikat?
            $table->boolean('require_for_certificate')->default(true);

            // Sasaran kuesioner: final (sekali saja) atau per session
            $table->enum('target', ['final', 'session'])->default('final');

            // Jika target=session, bisa diikat ke sesi tertentu (opsional)
            $table->foreignId('session_id')
                ->nullable()
                ->constrained('basic_listening_sessions')
                ->nullOnDelete();

            // Periode aktif (opsional)
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basic_listening_surveys');
    }
};
