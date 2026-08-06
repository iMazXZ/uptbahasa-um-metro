<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_listening_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('basic_listening_schedules', 'session_id')) {
                // hapus FK + kolom (nama constraint aman pakai helper ini)
                $table->dropConstrainedForeignId('session_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('basic_listening_schedules', 'session_id')) {
                $table->foreignId('session_id')
                      ->nullable()
                      ->constrained('basic_listening_sessions')
                      ->nullOnDelete();
            }
        });
    }
};
