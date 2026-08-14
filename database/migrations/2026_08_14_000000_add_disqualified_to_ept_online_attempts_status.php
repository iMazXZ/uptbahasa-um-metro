<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ept_online_attempts MODIFY COLUMN status ENUM('draft','in_progress','submitted','expired','cancelled','disqualified') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ept_online_attempts MODIFY COLUMN status ENUM('draft','in_progress','submitted','expired','cancelled') NOT NULL DEFAULT 'draft'");
    }
};
