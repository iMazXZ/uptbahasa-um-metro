<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('basic_listening_questions', function (Blueprint $t) {
            // Sesuaikan tipe kolom dengan yang ada di DB kamu (text/varchar)
            if (Schema::hasColumn('basic_listening_questions','question'))   $t->text('question')->nullable()->change();
            if (Schema::hasColumn('basic_listening_questions','option_a'))   $t->text('option_a')->nullable()->change();
            if (Schema::hasColumn('basic_listening_questions','option_b'))   $t->text('option_b')->nullable()->change();
            if (Schema::hasColumn('basic_listening_questions','option_c'))   $t->text('option_c')->nullable()->change();
            if (Schema::hasColumn('basic_listening_questions','option_d'))   $t->text('option_d')->nullable()->change();
            if (Schema::hasColumn('basic_listening_questions','correct'))    $t->string('correct', 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_questions', function (Blueprint $t) {
            // Kembalikan ke NOT NULL jika memang ingin strict lagi (opsional)
            // $t->text('question')->nullable(false)->change();
            // dst...
        });
    }
};
