<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_listening_connect_codes', function (Blueprint $table) {
            $table->foreignId('quiz_id')
                ->nullable()
                ->after('session_id')
                ->constrained('basic_listening_quizzes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_connect_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quiz_id');
        });
    }
};
