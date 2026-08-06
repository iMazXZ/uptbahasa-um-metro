<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('basic_listening_quizzes', function (Blueprint $table) {
            // 1) Lepas foreign key dulu (biar index bisa diutak-atik)
            // Nama constraint biasanya: basic_listening_quizzes_session_id_foreign
            // Gunakan array agar Laravel resolve nama yang tepat.
            $table->dropForeign(['session_id']);

            // 2) Hapus UNIQUE di session_id
            // Pakai array agar Laravel resolve nama index (…_unique) otomatis.
            $table->dropUnique(['session_id']);
        });

        Schema::table('basic_listening_quizzes', function (Blueprint $table) {
            // 3) Pasang lagi FOREIGN KEY biasa (non-unique)
            $table->foreign('session_id')
                ->references('id')
                ->on('basic_listening_sessions')
                ->cascadeOnDelete();

            // 4) (Opsional) Cegah judul duplikat di session yang sama
            //     Kalau ingin memperbolehkan judul kembar, hapus baris ini.
            $table->unique(['session_id', 'title'], 'quizzes_session_title_unique');
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_quizzes', function (Blueprint $table) {
            // rollback opsional unique gabungan
            if (Schema::hasTable('basic_listening_quizzes')) {
                $table->dropUnique('quizzes_session_title_unique');
            }

            // lepas FK baru
            $table->dropForeign(['session_id']);

            // pasang lagi UNIQUE lama & FK lama (seperti kondisi awal salah)
            $table->unique('session_id'); // mengembalikan masalah lama (untuk completeness)
            $table->foreign('session_id')
                ->references('id')
                ->on('basic_listening_sessions')
                ->cascadeOnDelete();
        });
    }
};