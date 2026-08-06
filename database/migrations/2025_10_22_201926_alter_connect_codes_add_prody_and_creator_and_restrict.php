<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('basic_listening_connect_codes', function (Blueprint $table) {
            // Scope connect code ke prodi tertentu (nullable agar backward-compatible)
            $table->foreignId('prody_id')
                ->nullable()
                ->after('quiz_id')
                ->constrained('prodies')
                ->nullOnDelete();

            // Catat pembuatnya (Tutor/Admin)
            $table->foreignId('created_by')
                ->nullable()
                ->after('prody_id')
                ->constrained('users')
                ->nullOnDelete();

            // Default true: kode dibatasi untuk prodi target
            $table->boolean('restrict_to_prody')
                ->default(true)
                ->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_connect_codes', function (Blueprint $table) {
            // Hapus FK & kolom
            $table->dropConstrainedForeignId('prody_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('restrict_to_prody');
        });
    }
};
