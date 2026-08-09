<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->string('mode', 20)->default('offline')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('ept_registrations', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
