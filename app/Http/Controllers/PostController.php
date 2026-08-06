<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function newsCategory(Request $request, string $newsCategory)
    {
        return $this->index($request, 'news', $newsCategory);
    }

    /**
     * List postingan per kategori (news|career|schedule|scores)
     * + dukung ?q=search & ?sort=new|old|az
     */
    public function index(Request $request, string $type, ?string $newsCategory = null)
    {
        abort_unless(in_array($type, ['news', 'career', 'schedule', 'scores'], true), 404);

        $activeNewsCategory = null;
        $careerStatus = null;
        if ($type === 'news') {
            $requestedNewsCategory = $newsCategory ?: trim((string) $request->query('kategori'));

            if ($requestedNewsCategory !== '') {
                abort_unless(Post::isValidNewsCategory($requestedNewsCategory), 404);
                $activeNewsCategory = $requestedNewsCategory;
            }
        } elseif ($type === 'career') {
            $requestedStatus = strtolower(trim((string) $request->query('status', 'open')));
            $careerStatus = in_array($requestedStatus, ['open', 'closed', 'all'], true)
                ? $requestedStatus
                : 'open';
        }

        $query = Post::published()
            ->type($type) // karena $type selalu salah satu dari tiga di atas
            ->select([
                'id',
                'title',
                'slug',
                'excerpt',
                'cover_path',
                'published_at',
                'author_id',
                'type',
                'news_category',
                'views',
                'event_date',
                'event_time',
                'event_location',
                'career_is_open',
                'career_deadline',
                'career_apply_url',
                'related_post_id',
            ])
            ->with(['author:id,name']);

        if ($type === 'news' && $activeNewsCategory !== null) {
            $query->newsCategory($activeNewsCategory);
        }

        if ($type === 'career') {
            if ($careerStatus === 'open') {
                $query->careerOpen();
            } elseif ($careerStatus === 'closed') {
                $query->careerClosed();
            }
        }

        if ($type === 'schedule') {
            $query->with([
                'relatedScores' => fn ($q) => $q
                    ->published()
                    ->select(['id', 'slug', 'related_post_id', 'published_at', 'title']),
            ]);
        } elseif ($type === 'scores') {
            $query->with([
                'relatedPost' => fn ($q) => $q->select(['id', 'slug', 'title', 'event_date']),
            ]);
        }

        // Pencarian (opsional)
        $search = trim((string) $request->query('q'));
        $fullTextSearch = null;
        if ($search !== '') {
            $scheduleGroupNumber = $type === 'schedule'
                ? $this->extractScheduleGroupNumber($search)
                : null;

            if ($scheduleGroupNumber !== null) {
                $query->whereRaw(
                    'LOWER(title) REGEXP ?',
                    [$this->buildScheduleGroupTitlePattern($scheduleGroupNumber)]
                );
            } else {
                $query->searchText($search);

                $candidate = Post::buildBooleanFullTextQuery($search);
                if ($candidate !== null && Post::hasSearchFullTextIndex()) {
                    $fullTextSearch = $candidate;
                }
            }
        }

        if ($fullTextSearch !== null) {
            $query->orderByRaw(
                'MATCH(title, excerpt, body) AGAINST (? IN BOOLEAN MODE) DESC',
                [$fullTextSearch]
            );
        }

        // Sort (opsional)
        $sort = $request->query('sort', 'new'); // new|old|az
        if ($sort === 'old') {
            $query->oldest('published_at');
        } elseif ($sort === 'az') {
            $query->orderBy('title');
        } else {
            $query->latest('published_at');
        }

        $posts = $query->paginate(12)->withQueryString();

        $title = Post::TYPES[$type] ?? ucfirst($type);
        if ($type === 'news' && $activeNewsCategory !== null) {
            $title = 'Berita: ' . Post::newsCategoryLabel($activeNewsCategory);
        }

        // untuk komponen card di index
        $category = $type;
        $newsCategoryMenu = [];
        $careerStatusMenu = [];
        if ($type === 'news') {
            $newsCounts = Post::published()
                ->type('news')
                ->selectRaw('news_category, COUNT(*) as total')
                ->groupBy('news_category')
                ->pluck('total', 'news_category');

            foreach (Post::newsCategoryOptions(onlyActive: true) as $slug => $label) {
                $count = (int) ($newsCounts[$slug] ?? 0);
                if ($count === 0 && $activeNewsCategory !== $slug) {
                    continue;
                }

                $newsCategoryMenu[] = [
                    'slug' => $slug,
                    'label' => $label,
                    'count' => $count,
                    'url' => route('front.news.category', ['newsCategory' => $slug]),
                    'active' => $activeNewsCategory === $slug,
                ];
            }
        } elseif ($type === 'career') {
            $careerBaseQuery = Post::published()->type('career');

            $counts = [
                'all' => (clone $careerBaseQuery)->count(),
                'open' => (clone $careerBaseQuery)->careerOpen()->count(),
                'closed' => (clone $careerBaseQuery)->careerClosed()->count(),
            ];

            $buildCareerUrl = function (string $status) use ($request): string {
                $params = $request->query();
                unset($params['page'], $params['status']);

                if ($status !== 'open') {
                    $params['status'] = $status;
                }

                return route('front.career', $params);
            };

            $careerStatusMenu = [
                [
                    'key' => 'open',
                    'label' => 'Dibuka',
                    'count' => (int) $counts['open'],
                    'url' => $buildCareerUrl('open'),
                    'active' => $careerStatus === 'open',
                ],
                [
                    'key' => 'closed',
                    'label' => 'Ditutup',
                    'count' => (int) $counts['closed'],
                    'url' => $buildCareerUrl('closed'),
                    'active' => $careerStatus === 'closed',
                ],
                [
                    'key' => 'all',
                    'label' => 'Semua',
                    'count' => (int) $counts['all'],
                    'url' => $buildCareerUrl('all'),
                    'active' => $careerStatus === 'all',
                ],
            ];
        }

        return view(
            'front.posts.index',
            compact(
                'posts',
                'title',
                'category',
                'activeNewsCategory',
                'newsCategoryMenu',
                'careerStatus',
                'careerStatusMenu'
            )
        );
    }

    public function show(Post $post)
    {
        if ($post->type === 'career') {
            return redirect()->route('front.career.show', ['post' => $post], 301);
        }

        return $this->renderPost($post);
    }

    public function showCareer(Post $post)
    {
        abort_unless($post->type === 'career', 404);

        return $this->renderPost($post);
    }

    private function extractScheduleGroupNumber(string $search): ?string
    {
        $normalized = mb_strtolower(trim($search));

        if (! preg_match('/\bgrup\s+(\d{1,4})\b/u', $normalized, $matches)) {
            return null;
        }

        return $matches[1] ?? null;
    }

    private function buildScheduleGroupTitlePattern(string $groupNumber): string
    {
        return 'grup[[:space:]]+' . preg_quote($groupNumber, '/') . '([^0-9]|$)';
    }

    private function renderPost(Post $post)
    {
        // Lengkapi relasi bila belum dimuat
        $post->loadMissing(['author:id,name', 'eptGroup:id,name,quota,jadwal,lokasi']);

        $relatedSelect = [
            'id',
            'title',
            'slug',
            'type',
            'news_category',
            'excerpt',
            'cover_path',
            'published_at',
            'event_date',
            'event_time',
            'event_location',
            'career_is_open',
            'career_deadline',
            'career_apply_url',
        ];

        $relatedBaseQuery = Post::published()
            ->type($post->type)
            ->where('id', '!=', $post->id);

        $related = collect();
        if ($post->type === 'news' && filled($post->news_category)) {
            $related = (clone $relatedBaseQuery)
                ->where('news_category', $post->news_category)
                ->latest('published_at')
                ->limit(6)
                ->get($relatedSelect);

            if ($related->count() < 6) {
                $remaining = 6 - $related->count();

                $extra = (clone $relatedBaseQuery)
                    ->where(function ($query) use ($post): void {
                        $query
                            ->whereNull('news_category')
                            ->orWhere('news_category', '!=', $post->news_category);
                    })
                    ->whereNotIn('id', $related->pluck('id')->all())
                    ->latest('published_at')
                    ->limit($remaining)
                    ->get($relatedSelect);

                $related = $related->concat($extra);
            }
        } else {
            $related = (clone $relatedBaseQuery)
                ->latest('published_at')
                ->limit(6)
                ->get($relatedSelect);
        }

        $body = $this->formatBody($post->body ?? '');

        $scheduleParticipants = collect();

        if ($post->type === 'schedule' && $post->eptGroup) {
            $scheduleParticipants = $post->eptGroup
                ->allRegistrations()
                ->approved()
                ->orderByRaw('COALESCE(approved_at, created_at) ASC')
                ->orderBy('id')
                ->with([
                    'user:id,name,srn,year,prody_id',
                    'user.prody:id,name',
                ])
                ->get()
                ->values();
        }

        return view('front.posts.show', compact('post', 'related', 'body', 'scheduleParticipants'));
    }

    /**
     * Merapikan HTML body agar bersih untuk ditampilkan.
     */
    private function formatBody(string $html): string
    {
        if ($html === '') return $html;

        // Buang class/id tempelan paste, tapi tetap pertahankan style aman dari purifier.
        $html = preg_replace('/\s(?:class|id)="[^"]*"/i', '', $html);

        // bersihkan baris “nama file + ukuran”
        $html = preg_replace(
            '/(?:^|>)[^<\S]*(?:IMG|DSC|PXL|PHOTO|WhatsApp Image|Screenshot)[^<\s]*\.(?:jpe?g|png|gif|webp)(?:[^<]*?(?:KB|MB))?[^<\S]*(?=<|$)/im',
            '',
            $html
        );

        // hapus anchor ke file gambar (jika tidak membungkus <img>)
        $html = preg_replace_callback('/<a\b([^>]*)>(.*?)<\/a>/is', function ($m) {
            $attrs = $m[1];
            $inner = $m[2];
            if (stripos($inner, '<img') !== false) return $m[0];

            $hrefHasExt = preg_match('/href="[^"]+\.(?:jpe?g|png|gif|webp)(?:\?[^"]*)?"/i', $attrs);
            $text = trim(strip_tags($inner));
            $textLooksLikeImage =
                preg_match('/\.(?:jpe?g|png|gif|webp)$/i', $text) ||
                preg_match('/^\d{1,4}(?:[.,]\d{1,2})?\s?(KB|MB)$/i', $text) ||
                preg_match('/^(?:WhatsApp Image|Screenshot).*/i', $text);

            return ($hrefHasExt || $textLooksLikeImage) ? '' : $m[0];
        }, $html);

        // angka ukuran file berdiri sendiri
        $html = preg_replace('/(?:^|>)[^<\S]*\d{1,4}(?:[.,]\d{1,2})?\s?(?:KB|MB)[^<\S]*(?=<|$)/im', '', $html);
        // nama file gambar berdiri sendiri
        $html = preg_replace('/(?:^|>)\s*[^<>\s]+\.(?:jpe?g|png|gif|webp)\s*(?=<|$)/im', '', $html);

        // rapikan img
        $html = preg_replace_callback('/<img\b([^>]*)>/i', function ($m) {
            $attrs = preg_replace('/\s(?:class|loading|decoding)="[^"]*"/i', '', $m[1]);
            if (!preg_match('/\balt="/i', $attrs)) $attrs .= ' alt=""';
            $attrs .= ' loading="lazy" decoding="async" class="mx-auto my-6 rounded-2xl shadow-md"';
            return '<img' . $attrs . '>';
        }, $html);

        // buang paragraf kosong bertumpuk
        $html = preg_replace('/(<p>\s*<\/p>)+/i', '', $html);

        return $html;
    }
}
