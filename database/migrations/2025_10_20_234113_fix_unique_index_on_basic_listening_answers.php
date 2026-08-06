<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Migration ini sudah digantikan oleh
        // 2025_11_04_112549_fix_blank_index_and_unique_on_basic_listening_answers.
        // Kosongkan supaya fresh migrate di MySQL 8 tidak error.
    }

    public function down(): void
    {
        //
    }
};
