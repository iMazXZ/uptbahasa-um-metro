<?php

namespace App\Http\Controllers;

use App\Models\BasicListeningAttempt;
use Illuminate\Http\Request;

class BasicListeningHistoryController extends Controller
{
    public function index(Request $request)
    {
        // Guard: wajib isi nomor grup BL terlebih dahulu
        if (is_null($request->user()->nomor_grup_bl)) {
            return redirect()->route('bl.index')
                ->with('warning', 'Silakan isi nomor grup Basic Listening Anda terlebih dahulu sebelum melihat riwayat.');
        }

        $attempts = BasicListeningAttempt::with('session')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return view('bl.history', compact('attempts'));
    }

    public function show(BasicListeningAttempt $attempt, Request $request)
    {
        // Guard: wajib isi nomor grup BL terlebih dahulu
        if (is_null($request->user()->nomor_grup_bl)) {
            return redirect()->route('bl.index')
                ->with('warning', 'Silakan isi nomor grup Basic Listening Anda terlebih dahulu sebelum melihat detail riwayat.');
        }
        
        // dd($attempt->user_id != $request->user()->id);
        if ($attempt->user_id != $request->user()->id) {
            abort(403);
        }

        $attempt->load(['session', 'quiz.questions', 'answers']);
        $questions = $attempt->quiz->questions()->orderBy('order')->get();
        $answers   = $attempt->answers()->get();

        return view('bl.history-show', [
            'attempt'   => $attempt,
            'questions' => $questions,
            'answers'   => $answers,
        ]);
    }
}
