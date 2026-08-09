<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->string('foto_ktp', 255)->nullable()->after('bukti_pembayaran');
            $table->string('foto_selfie', 255)->nullable()->after('foto_ktp');
        });
    }

    public function down(): void
    {
        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->dropColumn(['foto_ktp', 'foto_selfie']);
        });
    }
};
