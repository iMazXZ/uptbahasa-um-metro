<?php

namespace App\Http\Controllers;

use App\Models\BasicListeningSession;
use App\Models\BasicListeningAttempt;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class BasicListeningController extends Controller
{

    public function index()
    {
        $sessions = BasicListeningSession::query()
            ->addSelect([
                'worked_count' => BasicListeningAttempt::query()
                    ->selectRaw('COUNT(DISTINCT user_id)')
                    ->whereColumn('basic_listening_attempts.session_id', 'basic_listening_sessions.id')
                    ->whereNotNull('submitted_at'),
            ])
            ->where('show_on_landing', true)
            ->orderBy('number')
            ->get();

        $quizEnabled = SiteSetting::isBlQuizEnabled();

        return view('bl.index', compact('sessions', 'quizEnabled'));
    }

    public function show(BasicListeningSession $session)
    {
        return view('bl.show', compact('session'));
    }

    public function continue(BasicListeningAttempt $attempt)
    {
        if ($attempt->submitted_at) {
            return redirect()
                ->route('bl.history.show', $attempt)
                ->with('error', 'Quiz sudah disubmit.');
        }

        // redirect ke halaman quiz sesuai attempt
        return redirect()->route('bl.quiz.show', [
            'attempt' => $attempt->id,
        ]);
    }
}
