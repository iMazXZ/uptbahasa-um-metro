<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('news_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $seedRows = [
            ['name' => 'Umum', 'slug' => 'umum', 'position' => 0],
            ['name' => 'Pengumuman', 'slug' => 'pengumuman', 'position' => 1],
            ['name' => 'Kegiatan', 'slug' => 'kegiatan', 'position' => 2],
            ['name' => 'Prestasi', 'slug' => 'prestasi', 'position' => 3],
            ['name' => 'Layanan', 'slug' => 'layanan', 'position' => 4],
        ];

        DB::table('news_categories')->insert(
            collect($seedRows)->map(function (array $row) use ($now): array {
                return [
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'position' => $row['position'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all()
        );

        // Tambahkan slug kategori yang mungkin sudah ada di tabel posts namun belum ada di master.
        $existingSlugs = DB::table('news_categories')->pluck('slug')->all();

        $postCategorySlugs = DB::table('posts')
            ->where('type', 'news')
            ->whereNotNull('news_category')
            ->where('news_category', '!=', '')
            ->distinct()
            ->pluck('news_category')
            ->all();

        $missingSlugs = array_values(array_diff($postCategorySlugs, $existingSlugs));

        if ($missingSlugs !== []) {
            DB::table('news_categories')->insert(
                collect($missingSlugs)->values()->map(function (string $slug, int $index) use ($now): array {
                    $normalized = Str::slug($slug);
                    $finalSlug = $normalized !== '' ? $normalized : 'kategori-' . ($index + 1);

                    return [
                        'name' => Str::headline(str_replace('-', ' ', $finalSlug)),
                        'slug' => $finalSlug,
                        'position' => 100 + $index,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all()
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_categories');
    }
};
