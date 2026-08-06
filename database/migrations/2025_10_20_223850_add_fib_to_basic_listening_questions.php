<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('basic_listening_questions', function (Blueprint $t) {
            if (!Schema::hasColumn('basic_listening_questions', 'type')) {
                $t->string('type')->default('multiple_choice')->index()->after('quiz_id');
            }
            if (!Schema::hasColumn('basic_listening_questions', 'paragraph_text')) {
                $t->longText('paragraph_text')->nullable()->after('type');
            }
            if (!Schema::hasColumn('basic_listening_questions', 'fib_placeholders')) {
                $t->json('fib_placeholders')->nullable()->after('paragraph_text');
            }
            if (!Schema::hasColumn('basic_listening_questions', 'fib_answer_key')) {
                $t->json('fib_answer_key')->nullable()->after('fib_placeholders');
            }
            if (!Schema::hasColumn('basic_listening_questions', 'fib_scoring')) {
                $t->json('fib_scoring')->nullable()->after('fib_answer_key');
            }
            if (!Schema::hasColumn('basic_listening_questions', 'fib_weights')) {
                $t->json('fib_weights')->nullable()->after('fib_scoring');
            }
        });
    }
    public function down(): void {
        Schema::table('basic_listening_questions', function (Blueprint $t) {
            foreach (['type','paragraph_text','fib_placeholders','fib_answer_key','fib_scoring','fib_weights'] as $col) {
                if (Schema::hasColumn('basic_listening_questions', $col)) $t->dropColumn($col);
            }
        });
    }
};

