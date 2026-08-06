<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewsCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'position',
        'is_active',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'news_category', 'slug')
            ->where('type', 'news');
    }

    protected static function booted(): void
    {
        static::saving(function (NewsCategory $category): void {
            if (blank($category->slug) && filled($category->name)) {
                $category->slug = Str::slug($category->name);
            }

            $baseSlug = Str::slug((string) $category->slug);
            if ($baseSlug === '') {
                $baseSlug = 'kategori-berita';
            }

            $category->slug = $baseSlug;
            $suffix = 2;

            while (
                static::query()
                    ->where('slug', $category->slug)
                    ->when($category->exists, fn ($query) => $query->whereKeyNot($category->id))
                    ->exists()
            ) {
                $category->slug = $baseSlug . '-' . $suffix++;
            }
        });

        static::updating(function (NewsCategory $category): void {
            if (! $category->isDirty('slug')) {
                return;
            }

            $originalSlug = (string) $category->getOriginal('slug');
            $newSlug = (string) $category->slug;

            if ($originalSlug === '' || $newSlug === '' || $originalSlug === $newSlug) {
                return;
            }

            DB::table('posts')
                ->where('type', 'news')
                ->where('news_category', $originalSlug)
                ->update(['news_category' => $newSlug]);
        });

        static::saved(function (): void {
            Post::flushNewsCategoryCache();
        });

        static::deleted(function (): void {
            Post::flushNewsCategoryCache();
        });
    }
}
