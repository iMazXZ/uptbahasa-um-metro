<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Untuk post tipe "schedule" - tanggal pelaksanaan tes
            $table->date('event_date')->nullable()->after('type');
            
            // Untuk post tipe "scores" - relasi ke post jadwal terkait
            $table->foreignId('related_post_id')
                  ->nullable()
                  ->after('event_date')
                  ->constrained('posts')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['related_post_id']);
            $table->dropColumn(['event_date', 'related_post_id']);
        });
    }
};
