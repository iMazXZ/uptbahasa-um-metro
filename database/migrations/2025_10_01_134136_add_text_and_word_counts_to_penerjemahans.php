<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('penerjemahans', function (Blueprint $t) {
            // Teks asli yang ditempel pendaftar (opsional)
            $t->longText('source_text')->nullable();
            $t->unsignedInteger('source_word_count')->default(0);

            // Teks hasil terjemahan yang diisi penerjemah
            $t->longText('translated_text')->nullable();
            $t->unsignedInteger('translated_word_count')->default(0);
        });
    }

    public function down(): void {
        Schema::table('penerjemahans', function (Blueprint $t) {
            $t->dropColumn([
                'source_text', 'source_word_count',
                'translated_text', 'translated_word_count',
            ]);
        });
    }
};
