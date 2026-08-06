<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('basic_listening_answers', function (Blueprint $table) {
            // ubah tipe blank_index -> unsigned smallint
            $table->unsignedSmallInteger('blank_index')->nullable(false)->change();
        });

        // Pastikan unique lama di-drop hanya jika ada
        $indexes = collect(DB::select('SHOW INDEX FROM `basic_listening_answers`'))->groupBy('Key_name');
        $hasOld = $indexes->has('answers_attempt_question_blank_unique');
        if ($hasOld) {
            Schema::table('basic_listening_answers', function (Blueprint $table) {
                try { $table->dropUnique('answers_attempt_question_blank_unique'); } catch (\Throwable $e) {}
            });
        }

        // Buat unique baru jika belum ada
        $hasNew = $indexes->first(function ($grp, $name) {
            $isUnique = intval($grp->first()->Non_unique ?? 1) === 0;
            $cols = $grp->sortBy('Seq_in_index')->pluck('Column_name')->values()->all();
            return $isUnique && $cols === ['attempt_id','question_id','blank_index'];
        });

        if (! $hasNew) {
            Schema::table('basic_listening_answers', function (Blueprint $table) {
                $table->unique(['attempt_id', 'question_id', 'blank_index'], 'bla_attempt_question_blank_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('basic_listening_answers', function (Blueprint $table) {
            $table->dropUnique('bla_attempt_question_blank_unique');
            $table->unique(['question_id', 'blank_index'], 'answers_attempt_question_blank_unique');
            $table->string('blank_index', 255)->change();
        });
    }
};
