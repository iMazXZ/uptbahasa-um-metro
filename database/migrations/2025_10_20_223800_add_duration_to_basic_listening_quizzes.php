<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('basic_listening_quizzes', function (Blueprint $t) {
            if (!Schema::hasColumn('basic_listening_quizzes', 'duration_seconds')) {
                $t->integer('duration_seconds')->default(600)->after('id'); // default 10 menit
            }
        });
    }
    public function down(): void {
        Schema::table('basic_listening_quizzes', function (Blueprint $t) {
            if (Schema::hasColumn('basic_listening_quizzes', 'duration_seconds')) {
                $t->dropColumn('duration_seconds');
            }
        });
    }
};
