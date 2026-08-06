<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Pastikan tabel prodies tersedia sebelum menambah FK
        if (! Schema::hasTable('prodies')) {
            Schema::create('prodies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('users', 'prody_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('prody_id')
                    ->nullable()
                    ->after('srn')
                    ->constrained('prodies')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'prody_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('prody_id');
            });
        }
    }
};
