<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_categories', function (Blueprint $table) {
            $table->string('code_prefix', 10)->nullable()->after('slug');
        });

        // Remove unique constraint from verification_code (karena sekarang bisa sama untuk banyak sertifikat)
        Schema::table('manual_certificates', function (Blueprint $table) {
            $table->dropUnique(['verification_code']);
            $table->index('verification_code');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_categories', function (Blueprint $table) {
            $table->dropColumn('code_prefix');
        });

        Schema::table('manual_certificates', function (Blueprint $table) {
            $table->dropIndex(['verification_code']);
            $table->unique('verification_code');
        });
    }
};
