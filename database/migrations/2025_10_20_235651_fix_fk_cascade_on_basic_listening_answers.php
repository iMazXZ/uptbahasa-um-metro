<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Pastikan kolom bertipe sama (unsignedBigInteger)
        Schema::table('basic_listening_answers', function (Blueprint $t) {
            // Sesuaikan kalau tipenya sudah benar; kalau belum:
            // $t->unsignedBigInteger('attempt_id')->change();
        });

        // Matikan FK checks sebentar supaya drop/create aman
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('basic_listening_answers', function (Blueprint $t) {
            // Drop FK lama (nama bisa berbeda di DB, yang umum: basic_listening_answers_attempt_id_foreign)
            $t->dropForeign('basic_listening_answers_attempt_id_foreign');
            // Buat lagi dengan CASCADE
            $t->foreign('attempt_id')
                ->references('id')->on('basic_listening_attempts')
                ->onDelete('cascade');   // ⬅️ penting
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('basic_listening_answers', function (Blueprint $t) {
            $t->dropForeign('basic_listening_answers_attempt_id_foreign');
            // Kembalikan ke RESTRICT (opsional)
            $t->foreign('attempt_id')
                ->references('id')->on('basic_listening_attempts')
                ->onDelete('restrict');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
