<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('penerjemahans', function (Blueprint $t) {
            $t->string('verification_code', 40)->nullable()->unique();
            $t->string('verification_url')->nullable();
            $t->string('pdf_path')->nullable();
            $t->string('pdf_sha256', 64)->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->timestamp('issued_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('penerjemahans', function (Blueprint $t) {
            $t->dropColumn([
                'verification_code','verification_url','pdf_path','pdf_sha256',
                'version','issued_at','revoked_at',
            ]);
        });
    }
};