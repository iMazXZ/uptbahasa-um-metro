<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('whatsapp_verified_at')->nullable()->after('whatsapp');
            $table->string('whatsapp_otp', 6)->nullable()->after('whatsapp_verified_at');
            $table->timestamp('whatsapp_otp_expires_at')->nullable()->after('whatsapp_otp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_verified_at', 'whatsapp_otp', 'whatsapp_otp_expires_at']);
        });
    }
};
