<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Tampilkan daftar semua layanan yang tersedia.
     */
    public function index()
    {
        $services = Post::published()
            ->type('service')
            ->latest('published_at')
            ->get(['id', 'title', 'slug', 'excerpt', 'cover_path', 'published_at']);

        return view('front.services.index', compact('services'));
    }

    /**
     * Tampilkan detail layanan berdasarkan slug.
     */
    public function show(Post $service)
    {
        // Pastikan post bertipe service dan sudah dipublikasi
        abort_unless($service->type === 'service' && $service->is_published, 404);

        // Ambil layanan terkait lainnya (exclude current)
        $related = Post::published()
            ->type('service')
            ->where('id', '!=', $service->id)
            ->latest('published_at')
            ->limit(4)
            ->get(['id', 'title', 'slug', 'excerpt', 'cover_path', 'published_at']);

        return view('front.services.show', [
            'service' => $service,
            'body'    => $service->body,
            'related' => $related,
        ]);
    }
}
