<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan field waktu dan lokasi tes untuk post tipe schedule.
     * Field ini bisa dihapus di masa depan dengan migration rollback.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Waktu pelaksanaan tes (misal: 08:00, 13:00)
            $table->time('event_time')->nullable()->after('event_date');
            
            // Lokasi/ruangan tes (misal: Ruang 101, Gedung B Lt.2)
            $table->string('event_location', 255)->nullable()->after('event_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['event_time', 'event_location']);
        });
    }
};
