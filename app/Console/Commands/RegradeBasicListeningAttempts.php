<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BasicListeningAttempt;

class RegradeBasicListeningAttempts extends Command
{
    /**
     * Nama command di artisan.
     *
     * --only-weird=1 : hanya attempt yang score NULL / 0 / belum submit
     * --prody=ID     : filter berdasarkan prody_id user
     * --session=ID   : filter berdasarkan session_id
     * --attempt=ID   : filter spesifik ke satu attempt_id
     * --user=ID      : filter berdasarkan user_id
     * --connect=ID   : filter berdasarkan connect_code_id
     */
    protected $signature = 'bl:regrade-attempts
        {--only-weird=1 : Hanya attempt yang skor null / 0 / belum submit}
        {--prody= : Hanya attempt dari prodi tertentu (prody_id)}
        {--session= : Hanya attempt dari session tertentu (session_id)}
        {--attempt= : Hanya attempt dengan ID tertentu}
        {--user= : Hanya attempt milik user dengan ID tertentu}
        {--connect= : Hanya attempt dengan connect_code_id tertentu}';

    protected $description = 'Hitung ulang skor & is_correct untuk Basic Listening Attempt secara massal.';

    public function handle(): int
    {
        $onlyWeird = (bool) $this->option('only-weird');
        $prodyId   = $this->option('prody');
        $sessionId = $this->option('session');
        $attemptId = $this->option('attempt');
        $userId    = $this->option('user');
        $connectId = $this->option('connect');

        $query = BasicListeningAttempt::with([
            'quiz.questions',
            'answers',
            'user',
        ]);

        // Filter ATTEMPT SPESIFIK
        if (! empty($attemptId)) {
            $query->where('id', $attemptId);
        }

        // Filter USER
        if (! empty($userId)) {
            $query->where('user_id', $userId);
        }

        // Filter CONNECT CODE
        if (! empty($connectId)) {
            $query->where('connect_code_id', $connectId);
        }

        // Filter PRODI (user.prody_id)
        if (! empty($prodyId)) {
            $query->whereHas('user', function ($q) use ($prodyId) {
                $q->where('prody_id', $prodyId);
            });
        }

        // Filter SESSION
        if (! empty($sessionId)) {
            $query->where('session_id', $sessionId);
        }

        // Hanya yang "aneh" (skor null/0/belum submit)
        // Kalau mau regrade SEMUA, panggil dengan --only-weird=0
        if ($onlyWeird) {
            $query->where(function ($q) {
                $q->whereNull('score')
                  ->orWhere('score', 0)
                  ->orWhereNull('submitted_at');
            });
        }

        $attempts = $query->get();

        if ($attempts->isEmpty()) {
            $this->info('Tidak ada attempt yang cocok dengan filter untuk diregrade.');
            return Command::SUCCESS;
        }

        $this->info('Mulai regrade ' . $attempts->count() . ' attempt...');
        if ($attemptId) $this->line('  - Filter attempt   : ' . $attemptId);
        if ($userId)    $this->line('  - Filter user      : ' . $userId);
        if ($connectId) $this->line('  - Filter connect   : ' . $connectId);
        if ($prodyId)   $this->line('  - Filter prodi     : ' . $prodyId);
        if ($sessionId) $this->line('  - Filter session   : ' . $sessionId);
        $this->line('  - Hanya yang aneh : ' . ($onlyWeird ? 'YA' : 'TIDAK'));
        $this->newLine();

        $bar = $this->output->createProgressBar($attempts->count());
        $bar->start();

        foreach ($attempts as $attempt) {
            $this->regradeAttempt($attempt);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Regrade selesai.');

        return Command::SUCCESS;
    }

    /**
     * Regrade 1 attempt (copy logika dari finalize()).
     */
    protected function regradeAttempt(BasicListeningAttempt $attempt): void
    {
        if (! $attempt->quiz) {
            return;
        }

        $questions  = $attempt->quiz->questions ?? collect();
        $allAnswers = $attempt->answers->groupBy('question_id');

        $totalScore    = 0;
        $totalMaxScore = 0;

        foreach ($questions as $q) {
            if ($q->type !== 'fib_paragraph') {
                // --- PENILAIAN MC / TRUE FALSE ---
                $ans = $allAnswers->get($q->id)?->first();
                $isCorrect = $ans && ($ans->answer === $q->correct);

                if ($ans) {
                    $ans->is_correct = $isCorrect;
                    $ans->save();
                }

                if ($isCorrect) {
                    $totalScore++;
                }
                $totalMaxScore++;
            } else {
                // --- PENILAIAN FIB (sinkron dengan controller) ---
                $userAnswers = $allAnswers->get($q->id);

                $keys    = $q->fib_answer_key ?? [];
                $weights = $q->fib_weights ?? [];
                $scoring = $q->fib_scoring ?? [];

                // Normalisasi kunci & bobot jadi array 0-based
                $normalizedKeys    = array_values($keys);
                $normalizedWeights = array_values($weights);

                $qScore     = 0;
                $qMaxWeight = 0;

                foreach ($normalizedKeys as $seqIndex => $correctKey) {
                    $w = (float) ($normalizedWeights[$seqIndex] ?? 1);
                    $qMaxWeight += $w;

                    // ⚠️ Perbaikan: pakai firstWhere dengan integer index
                    $uAns = $userAnswers?->firstWhere('blank_index', $seqIndex);
                    $uVal = $uAns ? $uAns->answer : '';

                    $isCorrect = $this->checkFibAnswer($uVal, $correctKey, $scoring);

                    if ($uAns) {
                        $uAns->is_correct = $isCorrect;
                        $uAns->save();
                    }

                    if ($isCorrect) {
                        $qScore += $w;
                    }
                }

                if ($qMaxWeight > 0) {
                    // Maksimal 1 poin per paragraf
                    $totalScore += ($qScore / $qMaxWeight);
                }
                $totalMaxScore++;
            }
        }

        $finalScore = $totalMaxScore > 0
            ? (int) round(($totalScore / $totalMaxScore) * 100)
            : 0;

        // Kalau belum submit tapi sudah ada jawaban, anggap submit paksa
        if (is_null($attempt->submitted_at) && $attempt->answers->isNotEmpty()) {
            $attempt->submitted_at = $attempt->submitted_at ?? $attempt->updated_at ?? now();
        }

        $attempt->score = $finalScore;
        $attempt->save();
    }

    /**
     * Copy helper dari BasicListeningQuizController::checkFibAnswer()
     */
    private function checkFibAnswer($userVal, $key, $scoring): bool
    {
        $caseSensitive = filter_var($scoring['case_sensitive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $allowTrim     = filter_var($scoring['allow_trim'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $stripPunct    = filter_var($scoring['strip_punctuation'] ?? true, FILTER_VALIDATE_BOOLEAN);

        // Normalisasi User Input
        $u = (string) $userVal;
        if ($allowTrim) {
            $u = trim($u);
        }
        if (! $caseSensitive) {
            $u = mb_strtolower($u);
        }
        if ($stripPunct) {
            $u = preg_replace('/[\p{P}\p{S}]+/u', '', $u);
        }
        $u = preg_replace('/\s+/u', ' ', $u);

        $keys = is_array($key) ? $key : [$key];

        foreach ($keys as $k) {
            // Dukungan regex: ['regex' => '...']
            if (is_array($k) && isset($k['regex'])) {
                if (@preg_match('/' . $k['regex'] . '/ui', $userVal)) {
                    return true;
                }
                continue;
            }

            $kStr = (string) $k;
            if ($allowTrim) {
                $kStr = trim($kStr);
            }
            if (! $caseSensitive) {
                $kStr = mb_strtolower($kStr);
            }
            if ($stripPunct) {
                $kStr = preg_replace('/[\p{P}\p{S}]+/u', '', $kStr);
            }
            $kStr = preg_replace('/\s+/u', ' ', $kStr);

            if ($u === $kStr) {
                return true;
            }
        }

        return false;
    }
}
