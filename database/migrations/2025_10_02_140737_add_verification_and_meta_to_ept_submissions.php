<?php

// database/migrations/2025_10_02_000001_add_verification_and_meta_to_ept_submissions.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ept_submissions', function (Blueprint $table) {
            $table->string('verification_code', 64)->nullable()->unique()->after('catatan_admin');
            $table->string('verification_url')->nullable()->after('verification_code');
            $table->string('surat_nomor')->nullable()->after('verification_url');

            // kolom yang sudah direferensikan di resource-mu, sekalian pastikan ada
            $table->timestamp('approved_at')->nullable()->after('surat_nomor');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('ept_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'verification_code', 'verification_url', 'surat_nomor',
                'approved_at', 'approved_by', 'rejected_at', 'rejected_by',
            ]);
        });
    }
};

