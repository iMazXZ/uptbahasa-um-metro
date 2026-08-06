<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('news_category', 60)->nullable()->after('type');
            $table->index(
                ['type', 'news_category', 'is_published', 'published_at'],
                'posts_type_news_category_published_idx'
            );
        });

        DB::table('posts')
            ->where('type', 'news')
            ->where(function ($query): void {
                $query->whereNull('news_category')->orWhere('news_category', '');
            })
            ->update(['news_category' => 'umum']);

        DB::table('posts')
            ->where('type', '!=', 'news')
            ->update(['news_category' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex('posts_type_news_category_published_idx');
            $table->dropColumn('news_category');
        });
    }
};
