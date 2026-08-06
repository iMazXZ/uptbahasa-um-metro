<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('basic_listening_attempts', function (Blueprint $table) {
            $table->foreignId('connect_code_id')
                  ->nullable()
                  ->after('quiz_id')
                  ->constrained('basic_listening_connect_codes')
                  ->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('basic_listening_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('connect_code_id');
        });
    }
};
