<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * List posts with optional type filter and pagination.
     */
    public function index(Request $request)
    {
        $requestedType = (string) $request->query('type', '');
        $requestedCategory = trim((string) $request->query('news_category'));

        $query = Post::published()
            ->select(['id', 'title', 'slug', 'type', 'news_category', 'excerpt', 'cover_path', 'published_at', 'views'])
            ->with(['author:id,name']);

        // Filter by type (news, schedule, scores)
        if ($requestedType !== '') {
            $query->type($requestedType);
        }

        if ($requestedType === 'news' && $requestedCategory !== '' && Post::isValidNewsCategory($requestedCategory)) {
            $query->newsCategory($requestedCategory);
        }

        // Search
        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        // Sort
        $sort = $request->query('sort', 'new');
        if ($sort === 'old') {
            $query->oldest('published_at');
        } elseif ($sort === 'popular') {
            $query->orderByDesc('views');
        } else {
            $query->latest('published_at');
        }

        $perPage = min($request->input('per_page', 15), 50);
        $posts = $query->paginate($perPage);

        $data = $posts->through(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'type' => $post->type,
                'type_label' => Post::TYPES[$post->type] ?? $post->type,
                'news_category' => $post->news_category,
                'news_category_label' => $post->type === 'news' ? Post::newsCategoryLabel($post->news_category) : null,
                'excerpt' => $post->excerpt,
                'cover_url' => $post->cover_url,
                'published_at' => $post->published_at->toIso8601String(),
                'views' => $post->views ?? 0,
                'author' => $post->author->name ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Show single post by slug.
     */
    public function show(string $slug)
    {
        $post = Post::published()
            ->where('slug', $slug)
            ->with(['author:id,name'])
            ->firstOrFail();

        // Increment views
        $post->increment('views');

        // Get related posts
        $related = Post::published()
            ->type($post->type)
            ->where('id', '!=', $post->id)
            ->when(
                $post->type === 'news' && filled($post->news_category),
                fn ($query) => $query->where('news_category', $post->news_category)
            )
            ->latest('published_at')
            ->limit(4)
            ->get(['id', 'title', 'slug', 'type', 'news_category', 'cover_path', 'published_at'])
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                    'news_category' => $p->news_category,
                    'news_category_label' => $p->type === 'news' ? Post::newsCategoryLabel($p->news_category) : null,
                    'cover_url' => $p->cover_url,
                    'published_at' => $p->published_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'type' => $post->type,
                'type_label' => Post::TYPES[$post->type] ?? $post->type,
                'news_category' => $post->news_category,
                'news_category_label' => $post->type === 'news' ? Post::newsCategoryLabel($post->news_category) : null,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'cover_url' => $post->cover_url,
                'published_at' => $post->published_at->toIso8601String(),
                'views' => $post->views ?? 0,
                'author' => $post->author->name ?? null,
                'related' => $related,
            ],
        ]);
    }
}
