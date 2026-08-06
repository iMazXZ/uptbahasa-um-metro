<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE posts MODIFY COLUMN type ENUM('news', 'career', 'schedule', 'scores', 'service') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Catatan: rollback akan gagal jika masih ada data type = 'career'.
        DB::statement("ALTER TABLE posts MODIFY COLUMN type ENUM('news', 'schedule', 'scores', 'service') NOT NULL");
    }
};

