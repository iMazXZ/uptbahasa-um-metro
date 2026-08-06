<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        // Cache stats for 24 hours (86400 seconds)
        $stats = Cache::remember('homepage_stats', 86400, function () {
            return [
                // Alumni = users dengan nilai BL lulus (>= 55) atau pernah ikut EPT
                'alumni' => User::where(function ($q) {
                    $q->whereHas('basicListeningGrade', fn($g) => $g->where('final_numeric_cached', '>=', 55))
                      ->orWhereHas('eptSubmissions');
                })->count(),
                
                // Instruktur = users dengan role tutor atau penerjemah
                'instruktur' => User::whereHas('roles', function ($q) {
                    $q->whereIn('name', ['tutor', 'Penerjemah']);
                })->count(),
                
                // Tahun pengalaman dihitung dari 2009
                'tahun_pengalaman' => now()->year - 2009,
            ];
        });

        return view('welcome', [
            'news'      => Post::published()->type('news')
                ->latest('published_at')->limit(6)
                ->get(['title','slug','type','news_category','excerpt','cover_path','published_at']),
            'schedules' => Post::published()->type('schedule')
                ->with([
                    'relatedScores' => fn ($query) => $query
                        ->published()
                        ->select(['id', 'slug', 'related_post_id', 'published_at', 'title']),
                ])
                ->latest('published_at')->limit(12)
                ->get(['id','title','slug','type','excerpt','cover_path','published_at','event_date','event_time','event_location']),
            'scores'    => Post::published()->type('scores')
                ->with([
                    'relatedPost' => fn ($query) => $query->select(['id', 'slug', 'title', 'event_date']),
                ])
                ->latest('published_at')->limit(6)
                ->get(['id','title','slug','type','excerpt','cover_path','published_at','related_post_id']),
            'services'  => Post::published()->type('service')
                ->latest('published_at')->limit(6)
                ->get(['id','title','slug','excerpt','cover_path','published_at']),
            'stats'     => $stats,
        ]);
    }
}
