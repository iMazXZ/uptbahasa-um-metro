<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Tambah blank_index jika belum ada
        if (! Schema::hasColumn('basic_listening_answers', 'blank_index')) {
            Schema::table('basic_listening_answers', function (Blueprint $table) {
                $table->unsignedSmallInteger('blank_index')->after('question_id');
            });
        }

        // 2) Ubah kolom answer -> string(191) nullable
        Schema::table('basic_listening_answers', function (Blueprint $table) {
            if (Schema::hasColumn('basic_listening_answers', 'answer')) {
                $table->string('answer', 191)->nullable()->change();
            } else {
                $table->string('answer', 191)->nullable()->after('blank_index');
            }
        });

        // 3) Pastikan is_correct nullable
        Schema::table('basic_listening_answers', function (Blueprint $table) {
            if (Schema::hasColumn('basic_listening_answers', 'is_correct')) {
                $table->boolean('is_correct')->nullable()->change();
            } else {
                $table->boolean('is_correct')->nullable()->after('answer');
            }
        });

        // 4) Handle UNIQUE dengan deteksi yang ada di DB
        //    - Drop unique lama (kalau ada) untuk (attempt_id, question_id)
        //    - Buat unique baru untuk (attempt_id, question_id, blank_index) jika belum ada
        $indexes = collect(DB::select('SHOW INDEX FROM `basic_listening_answers`'));

        // Cek ada unique lama (attempt_id, question_id) dgn Key_name apapun
        $hasOldUnique = $indexes
            ->groupBy('Key_name')
            ->first(function ($grp, $keyName) {
                // unique?
                $isUnique = intval($grp->first()->Non_unique ?? 1) === 0;
                if (! $isUnique) return false;

                $cols = $grp->sortBy('Seq_in_index')->pluck('Column_name')->values()->all();
                return $cols === ['attempt_id', 'question_id'];
            });

        if ($hasOldUnique) {
            // Pastikan ada index individual agar FK tidak kehilangan index ketika unique di-drop
            Schema::table('basic_listening_answers', function (Blueprint $table) {
                try { $table->index('attempt_id'); } catch (\Throwable $e) {}
                try { $table->index('question_id'); } catch (\Throwable $e) {}
            });

            $oldName = $indexes
                ->groupBy('Key_name')
                ->first(function ($grp, $keyName) use ($hasOldUnique) {
                    $isUnique = intval($grp->first()->Non_unique ?? 1) === 0;
                    $cols = $grp->sortBy('Seq_in_index')->pluck('Column_name')->values()->all();
                    return $isUnique && $cols === ['attempt_id', 'question_id'];
                })
                ->first()->Key_name ?? null;

            // Drop unique lama; jika MySQL masih mengeluh karena FK, kita fallback disable FK sementara
            $dropped = false;
            if ($oldName) {
                try {
                    Schema::table('basic_listening_answers', function (Blueprint $table) use ($oldName) {
                        $table->dropUnique($oldName);
                    });
                    $dropped = true;
                } catch (\Throwable $e) {
                    // fallback hard drop dengan FK check off
                }
            }

            if (! $dropped) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                Schema::table('basic_listening_answers', function (Blueprint $table) {
                    $table->dropUnique(['attempt_id','question_id']);
                });
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }

        // Cek apakah unique baru sudah ada
        $hasNewUnique = $indexes
            ->groupBy('Key_name')
            ->first(function ($grp, $keyName) {
                $isUnique = intval($grp->first()->Non_unique ?? 1) === 0;
                if (! $isUnique) return false;

                $cols = $grp->sortBy('Seq_in_index')->pluck('Column_name')->values()->all();
                return $cols === ['attempt_id', 'question_id', 'blank_index'];
            });

        if (! $hasNewUnique) {
            Schema::table('basic_listening_answers', function (Blueprint $table) {
                $table->unique(['attempt_id','question_id','blank_index'], 'bla_attempt_question_blank_unique');
            });
        }

        // 5) Index bantu (optional)
        Schema::table('basic_listening_answers', function (Blueprint $table) {
            try { $table->index(['attempt_id','question_id']); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_answers', function (Blueprint $table) {
            try { $table->dropUnique('bla_attempt_question_blank_unique'); } catch (\Throwable $e) {}
        });
    }
};
