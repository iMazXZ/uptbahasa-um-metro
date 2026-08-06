<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // integer kecil sudah cukup, nullable karena awalnya belum diisi
            $position = Schema::hasColumn('users', 'prody_id') ? 'prody_id' : 'prody';

            $table->unsignedSmallInteger('nomor_grup_bl')
                ->nullable()
                ->after($position)
                ->comment('Nomor grup Basic Listening (diisi mahasiswa berdasarkan pembagian kelas kampus)');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nomor_grup_bl');
        });
    }
};
