<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ept_submissions', function (Blueprint $table) {
            // Track siapa yang edit nomor surat terakhir
            $table->foreignId('surat_nomor_updated_by')->nullable()->constrained('users')->nullOnDelete()->after('surat_nomor');
            $table->timestamp('surat_nomor_updated_at')->nullable()->after('surat_nomor_updated_by');
            
            // History perubahan nomor surat (JSON format)
            $table->json('surat_nomor_history')->nullable()->after('surat_nomor_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('ept_submissions', function (Blueprint $table) {
            $table->dropColumn(['surat_nomor_updated_by', 'surat_nomor_updated_at', 'surat_nomor_history']);
        });
    }
};
