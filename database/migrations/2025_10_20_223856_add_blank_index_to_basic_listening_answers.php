<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('basic_listening_answers', function (Blueprint $t) {
            if (!Schema::hasColumn('basic_listening_answers','blank_index')) {
                $t->string('blank_index')->nullable()->after('question_id')->index();
            }
            // pastikan kolom-kolom penting terindeks
            if (!Schema::hasColumn('basic_listening_answers','is_correct')) {
                $t->boolean('is_correct')->nullable()->after('answer')->index();
            }
            // tipe kolom answer bila perlu dipanjangkan
            if (Schema::hasColumn('basic_listening_answers','answer')) {
                $t->text('answer')->change();
            }
        });
    }
    public function down(): void {
        Schema::table('basic_listening_answers', function (Blueprint $t) {
            if (Schema::hasColumn('basic_listening_answers','blank_index')) $t->dropColumn('blank_index');
            if (Schema::hasColumn('basic_listening_answers','is_correct')) $t->dropColumn('is_correct');
        });
    }
};
