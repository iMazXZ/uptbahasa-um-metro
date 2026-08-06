<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('basic_listening_grades', function (Blueprint $table) {
            if (!Schema::hasColumn('basic_listening_grades', 'verification_code')) {
                $table->string('verification_code', 40)->nullable()->index();
            }
            if (!Schema::hasColumn('basic_listening_grades', 'verification_url')) {
                $table->string('verification_url', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_grades', function (Blueprint $table) {
            if (Schema::hasColumn('basic_listening_grades', 'verification_code')) {
                $table->dropColumn('verification_code');
            }
            if (Schema::hasColumn('basic_listening_grades', 'verification_url')) {
                $table->dropColumn('verification_url');
            }
        });
    }
};
