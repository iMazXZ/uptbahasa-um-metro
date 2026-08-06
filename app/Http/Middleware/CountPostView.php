<?php

namespace App\Http\Middleware;

use App\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class CountPostView
{
    public function handle(Request $request, Closure $next)
    {
        // Lanjutkan request dulu supaya 404/abort tetap bekerja normal
        $response = $next($request);

        // Jangan hitung view untuk response non-sukses/redirect.
        if (method_exists($response, 'isSuccessful') && ! $response->isSuccessful()) {
            return $response;
        }

        // Ambil model Post dari parameter route (implicit binding)
        // Pastikan nama parameter route kamu adalah {post}
        $routeParam = $request->route('post');

        /** @var Post|null $post */
        $post = $routeParam instanceof Post
            ? $routeParam
            : (is_numeric($routeParam) ? Post::find($routeParam) : null);

        if (! $post) {
            return $response;
        }

        // ====== Guard ringan: skip bot & prefetch ======
        $ua = strtolower($request->userAgent() ?? '');
        $botNeedles = [
            'bot','crawl','spider','slurp','bingbot','duckduckbot',
            'facebookexternalhit','mediapartners-google','curl','wget',
            'python-requests','httpclient','go-http-client','java',
            'screaming frog','axios','postman',
        ];
        $isBot = Str::contains($ua, $botNeedles);

        $purpose  = strtolower($request->header('Purpose', ''));
        $secPurp  = strtolower($request->header('Sec-Purpose', ''));
        $xPurpose = strtolower($request->header('X-Purpose', ''));
        $isPrefetch = Str::contains($purpose, 'prefetch')
            || Str::contains($secPurp, 'prefetch')
            || Str::contains($xPurpose, 'preview')
            || $request->header('X-Moz') === 'prefetch';

        if ($isBot || $isPrefetch) {
            return $response;
        }

        // ====== Rate limit per IP+UA per menit ======
        $ip = $request->ip() ?? '0.0.0.0';
        $key = 'post_hit:' . $post->id . ':' . sha1($ip . '|' . $ua);

        // Maksimal 60 hit / menit per (IP+UA) untuk post ini
        $allowed = ! RateLimiter::tooManyAttempts($key, 60);
        RateLimiter::hit($key, 60); // expire 60 detik

        if ($allowed) {
            // Increment "per-refresh" (selama tidak melewati limit)
            $post->increment('views');
        }

        return $response;
    }
}
