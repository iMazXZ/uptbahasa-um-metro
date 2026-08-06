<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Cek dulu apakah kolom 'thumbnail' ada
        $hasThumbnail = Schema::hasColumn('posts', 'thumbnail');

        Schema::table('posts', function (Blueprint $table) use ($hasThumbnail) {
            if (! Schema::hasColumn('posts', 'views')) {
                $column = $table->unsignedBigInteger('views')->default(0);
                // Taruh setelah 'thumbnail' hanya jika kolomnya ada
                if ($hasThumbnail) {
                    $column->after('thumbnail');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'views')) {
                $table->dropColumn('views');
            }
        });
    }
};
