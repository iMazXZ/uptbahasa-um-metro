<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL as FacadeURL;   // ⬅️ alias agar tak bentrok
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url as SitemapUrl;        // ⬅️ alias agar tak bentrok

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate {--path= : Custom output path (default: public/sitemap.xml)}';
    protected $description = 'Generate sitemap.xml for the public site';

    public function handle(): int
    {
        // Pastikan URL generator pakai APP_URL (https + domain) di production
        if (App::environment('production')) {
            FacadeURL::forceRootUrl(config('app.url'));
            FacadeURL::forceScheme('https');
        }

        $outPath = $this->option('path') ?: public_path('sitemap.xml');
        $this->info('Generating sitemap to: ' . $outPath);

        $now = now();

        $sitemap = Sitemap::create()

            // ===== Halaman statis utama =====
            ->add(
                SitemapUrl::create(route('front.home'))
                    ->setPriority(1.0)
                    ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_DAILY)
                    ->setLastModificationDate($now)
            )

            // ===== Halaman indeks per-kategori =====
            ->add(
                SitemapUrl::create(route('front.news'))
                    ->setPriority(0.9)
                    ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_DAILY)
                    ->setLastModificationDate($now)
            )
            ->add(
                SitemapUrl::create(route('front.career'))
                    ->setPriority(0.85)
                    ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_DAILY)
                    ->setLastModificationDate($now)
            )
            ->add(
                SitemapUrl::create(route('front.schedule'))
                    ->setPriority(0.8)
                    ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_WEEKLY)
                    ->setLastModificationDate($now)
            )
            ->add(
                SitemapUrl::create(route('front.scores'))
                    ->setPriority(0.8)
                    ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_WEEKLY)
                    ->setLastModificationDate($now)
            );

        // ===== Halaman kategori berita =====
        $newsCategoryCounts = Post::query()
            ->published()
            ->where('type', 'news')
            ->selectRaw('news_category, COUNT(*) as total')
            ->groupBy('news_category')
            ->pluck('total', 'news_category');

        foreach (Post::newsCategoryOptions(onlyActive: true) as $slug => $label) {
            if ((int) ($newsCategoryCounts[$slug] ?? 0) <= 0) {
                continue;
            }

            $sitemap->add(
                SitemapUrl::create(route('front.news.category', ['newsCategory' => $slug]))
                    ->setPriority(0.85)
                    ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_DAILY)
                    ->setLastModificationDate($now)
            );
        }

        // ===== Detail Post indexable (news + career) =====
        // Schedule/scores detail mengikuti kebijakan noindex, jadi tidak dimasukkan ke sitemap.
        Post::query()
            ->published()
            ->whereIn('type', ['news', 'career'])
            ->orderByDesc('updated_at')
            ->chunk(500, function ($posts) use ($sitemap) {
                /** @var \App\Models\Post $post */
                foreach ($posts as $post) {
                    $detailUrl = $post->type === 'career'
                        ? route('front.career.show', $post->slug)
                        : route('front.post.show', $post->slug);

                    $sitemap->add(
                        SitemapUrl::create($detailUrl)
                            ->setPriority(0.8)
                            ->setChangeFrequency(SitemapUrl::CHANGE_FREQUENCY_DAILY)
                            ->setLastModificationDate($post->updated_at ?? $post->published_at ?? $post->created_at)
                    );
                }
            });

        // Tulis file
        $sitemap->writeToFile($outPath);

        $publicUrl = str($outPath)
            ->replace(public_path(), '')
            ->ltrim('/')
            ->prepend(rtrim(config('app.url'), '/') . '/');

        $this->info('Sitemap generated successfully.');
        $this->line('Public URL: ' . $publicUrl);

        return self::SUCCESS;
    }
}
