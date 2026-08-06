<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    private const SEARCH_FULLTEXT_INDEX = 'posts_title_excerpt_body_fulltext';

    private static ?bool $supportsFullTextSearch = null;
    private static ?bool $hasNewsCategoryTable = null;
    private static ?array $newsCategoryOptions = null;
    private static ?array $activeNewsCategoryOptions = null;

    protected $fillable = [
        'author_id',
        'ept_group_id',
        'title',
        'slug',
        'type',
        'news_category',
        'event_date',
        'event_time',
        'event_location',
        'related_post_id',
        'excerpt',
        'body',
        'cover_path',
        'career_is_open',
        'career_deadline',
        'career_apply_url',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'event_date' => 'date',
        'event_time' => 'datetime:H:i',
        'career_is_open' => 'boolean',
        'career_deadline' => 'datetime',
        'views' => 'integer',
    ];

    // Konstanta label untuk dropdown / tampilan
    public const TYPES = [
        'news'     => 'Berita',
        'career'   => 'Karier',
        'schedule' => 'Jadwal Ujian',
        'scores'   => 'Nilai Ujian',
        'service'  => 'Informasi Layanan',
    ];

    public const DEFAULT_NEWS_CATEGORY = 'umum';

    public const NEWS_CATEGORIES = [
        'umum' => 'Umum',
        'pengumuman' => 'Pengumuman',
        'kegiatan' => 'Kegiatan',
        'prestasi' => 'Prestasi',
        'layanan' => 'Layanan',
    ];

    /**
     * Scope postingan yang sudah dipublikasikan.
     */
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)
                 ->whereNotNull('published_at')
                 ->where('published_at', '<=', now());
    }

    /**
     * Scope untuk filter berdasarkan jenis (news/schedule/scores).
     */
    public function scopeType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function scopeCareerOpen(Builder $q): Builder
    {
        $now = now();

        return $q
            ->where('career_is_open', true)
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('career_deadline')
                    ->orWhere('career_deadline', '>=', $now);
            });
    }

    public function scopeCareerClosed(Builder $q): Builder
    {
        $now = now();

        return $q->where(function (Builder $query) use ($now): void {
            $query
                ->where('career_is_open', false)
                ->orWhere(function (Builder $deadlineQuery) use ($now): void {
                    $deadlineQuery
                        ->whereNotNull('career_deadline')
                        ->where('career_deadline', '<', $now);
                });
        });
    }

    public function isCareerOpen(): bool
    {
        if ($this->type !== 'career') {
            return false;
        }

        if (! (bool) $this->career_is_open) {
            return false;
        }

        if ($this->career_deadline === null) {
            return true;
        }

        return $this->career_deadline->greaterThanOrEqualTo(now());
    }

    /**
     * Scope untuk filter berita berdasarkan kategori.
     */
    public function scopeNewsCategory(Builder $q, string $category): Builder
    {
        return $q->where('news_category', $category);
    }

    /**
     * Search title/excerpt/body with FULLTEXT when available.
     */
    public function scopeSearchText(Builder $q, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $q;
        }

        $tokens = static::tokenizeSearchTerms($search);

        if ($tokens === []) {
            return $q;
        }

        $booleanQuery = static::buildBooleanFullTextQuery($search);

        if ($booleanQuery !== null && static::supportsFullTextSearch()) {
            $shortTokens = array_values(array_filter(
                $tokens,
                fn (string $token): bool => mb_strlen($token) < 3
            ));

            return $q->where(function (Builder $searchQuery) use ($booleanQuery, $shortTokens): void {
                $searchQuery->whereRaw(
                    'MATCH(title, excerpt, body) AGAINST (? IN BOOLEAN MODE)',
                    [$booleanQuery]
                );

                foreach ($shortTokens as $token) {
                    $searchQuery->where(function (Builder $tokenQuery) use ($token): void {
                        $tokenQuery->where('title', 'like', "%{$token}%")
                            ->orWhere('excerpt', 'like', "%{$token}%")
                            ->orWhere('body', 'like', "%{$token}%");
                    });
                }
            });
        }

        return $q->where(function (Builder $searchQuery) use ($tokens): void {
            foreach ($tokens as $token) {
                $searchQuery->where(function (Builder $tokenQuery) use ($token): void {
                    $tokenQuery->where('title', 'like', "%{$token}%")
                        ->orWhere('excerpt', 'like', "%{$token}%")
                        ->orWhere('body', 'like', "%{$token}%");
                });
            }
        });
    }

    /**
     * Build boolean-mode fulltext query string from a raw search phrase.
     */
    public static function buildBooleanFullTextQuery(string $search): ?string
    {
        $tokens = array_map(
            fn (string $token): string => '+' . $token . '*',
            array_values(array_filter(
                static::tokenizeSearchTerms($search),
                fn (string $token): bool => mb_strlen($token) >= 3
            ))
        );

        if ($tokens === []) {
            return null;
        }

        return implode(' ', array_values(array_unique($tokens)));
    }

    /**
     * Normalize raw search input into distinct searchable tokens.
     *
     * @return array<int, string>
     */
    protected static function tokenizeSearchTerms(string $search): array
    {
        $parts = preg_split('/[^\pL\pN]+/u', mb_strtolower(trim($search)), -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $parts,
            fn ($part): bool => is_string($part) && $part !== ''
        )));
    }

    /**
     * True when database supports the configured FULLTEXT index.
     */
    public static function hasSearchFullTextIndex(): bool
    {
        return static::supportsFullTextSearch();
    }

    public static function newsCategoryOptions(bool $onlyActive = false): array
    {
        if (! static::hasNewsCategoryTable()) {
            return self::NEWS_CATEGORIES;
        }

        $cached = $onlyActive ? self::$activeNewsCategoryOptions : self::$newsCategoryOptions;
        if (is_array($cached)) {
            return $cached;
        }

        $query = NewsCategory::query()
            ->orderBy('position')
            ->orderBy('name');

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        $options = $query->pluck('name', 'slug')->all();
        if ($options === []) {
            $options = self::NEWS_CATEGORIES;
        }

        if ($onlyActive) {
            self::$activeNewsCategoryOptions = $options;
        } else {
            self::$newsCategoryOptions = $options;
        }

        return $options;
    }

    public static function defaultNewsCategorySlug(): string
    {
        $active = static::newsCategoryOptions(onlyActive: true);

        if (array_key_exists(self::DEFAULT_NEWS_CATEGORY, $active)) {
            return self::DEFAULT_NEWS_CATEGORY;
        }

        $first = array_key_first($active);

        return is_string($first) && $first !== '' ? $first : self::DEFAULT_NEWS_CATEGORY;
    }

    public static function isValidNewsCategory(?string $slug): bool
    {
        return is_string($slug) && array_key_exists($slug, static::newsCategoryOptions());
    }

    public static function normalizeNewsCategory(?string $slug): ?string
    {
        if (! is_string($slug)) {
            return null;
        }

        $slug = Str::slug(trim($slug));

        return $slug !== '' ? $slug : null;
    }

    public static function newsCategoryLabel(?string $slug): string
    {
        $options = static::newsCategoryOptions();
        $defaultSlug = static::defaultNewsCategorySlug();
        $fallback = $options[$defaultSlug] ?? self::NEWS_CATEGORIES[self::DEFAULT_NEWS_CATEGORY] ?? 'Umum';

        if (! is_string($slug)) {
            return $fallback;
        }

        return $options[$slug] ?? $fallback;
    }

    public static function flushNewsCategoryCache(): void
    {
        self::$hasNewsCategoryTable = null;
        self::$newsCategoryOptions = null;
        self::$activeNewsCategoryOptions = null;
    }

    /**
     * Relasi ke author (user).
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function eptGroup()
    {
        return $this->belongsTo(EptGroup::class, 'ept_group_id');
    }

    /**
     * Relasi ke post jadwal terkait (untuk post tipe scores).
     */
    public function relatedPost()
    {
        return $this->belongsTo(Post::class, 'related_post_id');
    }

    /**
     * Relasi ke post nilai terkait (untuk post tipe schedule).
     */
    public function relatedScores()
    {
        return $this->hasMany(Post::class, 'related_post_id')
            ->where('type', 'scores')
            ->orderByDesc('published_at');
    }

    /**
     * Auto-generate slug saat menyimpan.
     * - Jika slug kosong, diisi dari title.
     * - Jika slug tabrakan, tambahkan suffix -2, -3, dst.
     * - Saat edit judul, slug tidak otomatis berubah (SEO friendly).
     */
    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            $normalizedCategory = static::normalizeNewsCategory($post->news_category);

            if ($post->type === 'news') {
                $post->news_category = static::isValidNewsCategory($normalizedCategory)
                    ? $normalizedCategory
                    : static::defaultNewsCategorySlug();
            } else {
                $post->news_category = null;
            }

            // Jika slug kosong, isi dari title
            if (blank($post->slug) && filled($post->title)) {
                $post->slug = Str::slug($post->title);
            }

            // Pastikan slug unik
            if (filled($post->slug)) {
                $base = $post->slug;
                $i = 2;

                $exists = static::where('slug', $post->slug)
                    ->when($post->exists, fn ($q) => $q->where('id', '!=', $post->id))
                    ->exists();

                while ($exists) {
                    $post->slug = $base . '-' . $i++;
                    $exists = static::where('slug', $post->slug)
                        ->when($post->exists, fn ($q) => $q->where('id', '!=', $post->id))
                        ->exists();
                }
            }
        });
    }

    public function getNewsCategoryLabelAttribute(): string
    {
        if ($this->type !== 'news') {
            return '';
        }

        return self::newsCategoryLabel($this->news_category);
    }

     /** path relatif dari /public */
    public const DEFAULT_COVERS = [
        'news'     => 'images/covers/news.jpg',
        'schedule' => 'images/covers/schedule.jpg',
        'scores'   => 'images/covers/scores.jpg',
        'service'  => 'images/covers/service.jpg',
    ];

    public function getCoverUrlAttribute(): string
    {
        $cover = $this->normalizeCoverPathValue($this->cover_path);

        if (is_string($cover) && $cover !== '') {
            if (filter_var($cover, FILTER_VALIDATE_URL)) {
                return $cover;
            }

            if (Storage::disk('public')->exists($cover)) {
                return Storage::disk('public')->url($cover);
            }
        }

        $path = self::DEFAULT_COVERS[$this->type] ?? 'images/covers/default.jpg';
        return asset($path);
    }

    public function setCoverPathAttribute($value): void
    {
        $normalized = $this->normalizeCoverPathValue($value);

        if ($normalized === null) {
            $this->attributes['cover_path'] = null;
            return;
        }

        $this->attributes['cover_path'] = $normalized;
    }

    protected function normalizeCoverPathValue(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value['path'] ?? ($value[0]['path'] ?? null);
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || $value === 'Array') {
            return null;
        }

        // Biarkan URL absolut apa adanya (berguna untuk data lama/migrasi).
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($value, $appUrl . '/')) {
            $value = substr($value, strlen($appUrl) + 1);
        }

        $value = ltrim($value, '/');
        if (str_starts_with($value, 'storage/')) {
            $value = substr($value, 8);
        }

        return $value !== '' ? $value : null;
    }

    public function getExcerptAttribute($value)
    {
        if (!empty($value)) return $value;

        // fallback dari body bersih HTML
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($this->body))), 160);
    }

    private static function supportsFullTextSearch(): bool
    {
        if (self::$supportsFullTextSearch !== null) {
            return self::$supportsFullTextSearch;
        }

        $connection = (new static)->getConnection();
        if (!in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return self::$supportsFullTextSearch = false;
        }

        $databaseName = $connection->getDatabaseName();
        if (!is_string($databaseName) || $databaseName === '') {
            return self::$supportsFullTextSearch = false;
        }

        try {
            $exists = DB::connection($connection->getName())
                ->table('information_schema.statistics')
                ->where('table_schema', $databaseName)
                ->where('table_name', (new static)->getTable())
                ->where('index_name', self::SEARCH_FULLTEXT_INDEX)
                ->exists();

            return self::$supportsFullTextSearch = $exists;
        } catch (\Throwable) {
            return self::$supportsFullTextSearch = false;
        }
    }

    private static function hasNewsCategoryTable(): bool
    {
        if (self::$hasNewsCategoryTable !== null) {
            return self::$hasNewsCategoryTable;
        }

        try {
            return self::$hasNewsCategoryTable = Schema::hasTable('news_categories');
        } catch (\Throwable) {
            return self::$hasNewsCategoryTable = false;
        }
    }
}
