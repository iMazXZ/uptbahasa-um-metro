<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('basic_listening_connect_codes', function (Blueprint $table) {
            $table->string('code_hint', 64)->nullable()->after('code_hash'); // hanya petunjuk
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_connect_codes', function (Blueprint $table) {
            $table->dropColumn('code_hint');
        });
    }
};
