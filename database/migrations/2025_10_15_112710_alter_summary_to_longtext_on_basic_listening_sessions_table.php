<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_listening_sessions', function (Blueprint $table) {
            // ubah jadi LONGTEXT (lebih aman untuk konten HTML + tag <img>)
            $table->longText('summary')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('basic_listening_sessions', function (Blueprint $table) {
            // fallback: kalau sebelumnya TEXT, bisa kembalikan ke text
            $table->text('summary')->nullable()->change();
            // kalau awalnya VARCHAR(255), dan kamu ingin kembali ke string:
            // $table->string('summary', 255)->nullable()->change();
        });
    }
};
