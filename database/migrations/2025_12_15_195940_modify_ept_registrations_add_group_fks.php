<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ept_registrations', function (Blueprint $table) {
            // Tambah FK ke ept_groups
            $table->foreignId('grup_1_id')->nullable()->constrained('ept_groups')->nullOnDelete();
            $table->foreignId('grup_2_id')->nullable()->constrained('ept_groups')->nullOnDelete();
            $table->foreignId('grup_3_id')->nullable()->constrained('ept_groups')->nullOnDelete();
        });

        // Hapus kolom lama setelah FK ditambahkan
        Schema::table('ept_registrations', function (Blueprint $table) {
            $columns = ['grup_1', 'jadwal_1', 'grup_2', 'jadwal_2', 'grup_3', 'jadwal_3'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('ept_registrations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('ept_registrations', function (Blueprint $table) {
            // Kembalikan kolom lama
            $table->string('grup_1')->nullable();
            $table->dateTime('jadwal_1')->nullable();
            $table->string('grup_2')->nullable();
            $table->dateTime('jadwal_2')->nullable();
            $table->string('grup_3')->nullable();
            $table->dateTime('jadwal_3')->nullable();

            // Hapus FK
            $table->dropForeign(['grup_1_id']);
            $table->dropForeign(['grup_2_id']);
            $table->dropForeign(['grup_3_id']);
            $table->dropColumn(['grup_1_id', 'grup_2_id', 'grup_3_id']);
        });
    }
};
