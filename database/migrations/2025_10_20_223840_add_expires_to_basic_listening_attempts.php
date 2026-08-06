<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tambah started_at jika belum ada
        Schema::table('basic_listening_attempts', function (Blueprint $t) {
            if (!Schema::hasColumn('basic_listening_attempts', 'started_at')) {
                // letakkan setelah quiz_id biar rapi (atau sesuaikan kolom yang pasti ada)
                $t->timestamp('started_at')->nullable()->index()->after('quiz_id');
            }
        });

        // Tambah expires_at; jangan pakai "after" supaya aman meski urutan kolom beda
        Schema::table('basic_listening_attempts', function (Blueprint $t) {
            if (!Schema::hasColumn('basic_listening_attempts', 'expires_at')) {
                $t->timestamp('expires_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_attempts', function (Blueprint $t) {
            if (Schema::hasColumn('basic_listening_attempts', 'expires_at')) {
                $t->dropColumn('expires_at');
            }
            // Hapus started_at hanya jika memang kamu ingin rollback penuh
            if (Schema::hasColumn('basic_listening_attempts', 'started_at')) {
                $t->dropColumn('started_at');
            }
        });
    }
};
