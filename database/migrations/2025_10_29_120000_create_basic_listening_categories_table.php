<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('basic_listening_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Ubah kolom category di surveys menjadi string bebas, plus tambahkan sort_order
        Schema::table('basic_listening_surveys', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('category');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE basic_listening_surveys MODIFY category VARCHAR(100) NOT NULL DEFAULT 'tutor'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE basic_listening_surveys ALTER COLUMN category TYPE VARCHAR(100)");
            DB::statement("ALTER TABLE basic_listening_surveys ALTER COLUMN category SET DEFAULT 'tutor'");
        }

        // Seed kategori bawaan jika belum ada
        $defaults = [
            ['name' => 'Tutor', 'slug' => 'tutor', 'position' => 1],
            ['name' => 'Supervisor', 'slug' => 'supervisor', 'position' => 2],
            ['name' => 'Lembaga', 'slug' => 'institute', 'position' => 3],
        ];

        foreach ($defaults as $cat) {
            DB::table('basic_listening_categories')->updateOrInsert(
                ['slug' => $cat['slug']],
                [
                    'name'      => $cat['name'],
                    'position'  => $cat['position'],
                    'is_active' => true,
                    'updated_at'=> now(),
                    'created_at'=> now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Pastikan value kategori valid sebelum revert ke enum
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'pgsql') {
            DB::table('basic_listening_surveys')
                ->whereNotIn('category', ['tutor', 'supervisor', 'institute'])
                ->update(['category' => 'tutor']);
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE basic_listening_surveys MODIFY category ENUM('tutor','supervisor','institute') NOT NULL DEFAULT 'tutor'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE basic_listening_surveys ALTER COLUMN category TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE basic_listening_surveys ALTER COLUMN category SET DEFAULT 'tutor'");
        }

        Schema::table('basic_listening_surveys', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::dropIfExists('basic_listening_categories');
    }
};
