<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prodies', function (Blueprint $table) {
            if (! Schema::hasColumn('prodies', 'deleted_at')) {
                $table->softDeletes(); // tambah kolom deleted_at
            }
        });
    }

    public function down(): void
    {
        Schema::table('prodies', function (Blueprint $table) {
            if (Schema::hasColumn('prodies', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
