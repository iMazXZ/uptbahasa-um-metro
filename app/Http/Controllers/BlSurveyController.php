<?php

namespace App\Http\Controllers;

use App\Models\BasicListeningSurvey;
use App\Models\BasicListeningSurveyAnswer;
use App\Models\BasicListeningSurveyResponse;
use App\Models\BasicListeningSupervisor;
use App\Models\BasicListeningGrade;
use App\Support\BlCompute;
use App\Support\BlGrading;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BlSurveyController extends Controller
{
    /**
     * GET /bl/survey/start
     * Form pemilihan Tutor (maks 2) & Supervisor (1)
     */
    public function start(Request $request)
    {
        $user = auth()->user();
        $userPrody = $user?->prody_id;

        // Filter tutor berdasarkan prody user melalui pivot tutor_prody
        if ($userPrody) {
            // Ambil tutor yang di-assign ke prody yang sama dengan user
            $tutorIds = \DB::table('tutor_prody')
                ->where('prody_id', $userPrody)
                ->pluck('user_id')
                ->toArray();

            if (!empty($tutorIds)) {
                $tutors = User::query()
                    ->role('tutor')
                    ->whereIn('id', $tutorIds)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            } else {
                // Fallback: jika tidak ada tutor untuk prody ini, tampilkan semua
                $tutors = User::query()->role('tutor')->orderBy('name')->get(['id', 'name']);
            }
        } else {
            // Jika user belum punya prody, tampilkan semua tutor
            $tutors = User::query()->role('tutor')->orderBy('name')->get(['id', 'name']);
        }

        $supervisors = BasicListeningSupervisor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $ids = (array) $request->session()->get('bl_selected_tutor_ids', []);
        if (empty($ids) && $request->session()->has('bl_selected_tutor_id')) {
            $ids = [(int) $request->session()->get('bl_selected_tutor_id')];
        }

        $prefill = [
            'tutor_ids'     => array_values(array_unique(array_map('intval', $ids))),
            'supervisor_id' => (int) $request->session()->get('bl_selected_supervisor_id'),
        ];

        return view('bl.survey_start', compact('tutors', 'supervisors', 'prefill'));
    }

    /**
     * POST /bl/survey/start
     * Simpan pilihan Tutor (max 2) & Supervisor ke session
     */
    public function startSubmit(Request $request)
    {
        $data = $request->validate([
            'tutor_ids'     => ['required', 'array', 'min:1', 'max:2'],
            'tutor_ids.*'   => ['integer', Rule::exists('users', 'id')],
            'supervisor_id' => ['required', 'integer', Rule::exists('basic_listening_supervisors', 'id')->where('is_active', true)],
        ]);

        $ids = collect($data['tutor_ids'])->map(fn ($v) => (int) $v)->unique()->values();
        $validCount = User::query()->role('tutor')->whereIn('id', $ids)->count();
        abort_unless($validCount === $ids->count(), 422, 'Pilihan tutor tidak valid.');

        $request->session()->put('bl_selected_tutor_ids', $ids->all());
        $request->session()->put('bl_selected_tutor_id', (int) $ids->first());
        $request->session()->put('bl_selected_supervisor_id', (int) $data['supervisor_id']);

        return redirect()
            ->route('bl.survey.required')
            ->with('success', 'Pilihan tersimpan. Silakan lanjut mengisi kuesioner.');
    }

    /**
     * GET /bl/survey/required
     * Arahkan user ke survey wajib berikutnya (urut sesuai master kategori aktif)
     */
    public function redirectToRequired(Request $request)
    {
        // Sanitize session vs DB (jika data DB dihapus, jangan loncat langsung ke show)
        $tutorIds = $this->selectedTutorIds($request);
        if (! empty($tutorIds)) {
            $validTutorIds = User::query()
                ->role('tutor')
                ->whereIn('id', $tutorIds)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();

            if (empty($validTutorIds)) {
                $request->session()->forget(['bl_selected_tutor_id', 'bl_selected_tutor_ids']);
            } else {
                $request->session()->put('bl_selected_tutor_ids', $validTutorIds);
                $request->session()->put('bl_selected_tutor_id', (int) $validTutorIds[0]);
            }
        }

        $sid = (int) $request->session()->get('bl_selected_supervisor_id');
        if ($sid) {
            $isActive = BasicListeningSupervisor::query()->whereKey($sid)->where('is_active', true)->exists();
            if (! $isActive) {
                $request->session()->forget('bl_selected_supervisor_id');
            }
        }

        $userId = (int) auth()->id();

        if ($survey = $this->nextPendingSurveyFor($request, $userId)) {
            if ($survey->category === 'tutor' && empty($this->selectedTutorIds($request))) {
                return redirect()->route('bl.survey.start')->with('info', 'Pilih tutor & lembaga terlebih dahulu.');
            }
            if ($survey->category === 'supervisor' && ! $request->session()->get('bl_selected_supervisor_id')) {
                return redirect()->route('bl.survey.start')->with('info', 'Pilih tutor & lembaga terlebih dahulu.');
            }
            return redirect()->route('bl.survey.show', $survey);
        }

        return redirect()->route('bl.survey.success');
    }

    /**
     * GET /bl/survey/{survey}
     * Tampilkan survey dan buat/ambil draft response.
     * Auto set tutor/supervisor sesuai session.
     */
    public function show(Request $request, BasicListeningSurvey $survey)
    {
        abort_unless($survey->isOpen(), 403, 'Kuesioner belum/tidak tersedia.');

        $sessionId = null;
        if ($survey->target === 'session') {
            $sessionId = (int) $request->query('session_id', $survey->session_id);
        }

        // === Tentukan key dasar (tanpa tutor_id)
        $baseKey = [
            'survey_id'  => $survey->id,
            'user_id'    => (int) auth()->id(),
            'session_id' => $sessionId,
        ];

        // === Kategori tutor: tentukan tutor aktif LALU buat draft per-tutor
        if ($survey->category === 'tutor') {
            $tutorIds = $this->selectedTutorIds($request);
            if (empty($tutorIds)) {
                return redirect()->route('bl.survey.start')
                    ->with('info', 'Pilih tutor & lembaga terlebih dahulu.');
            }

            // 1) kandidat dari query (prioritas)
            $activeTutorId = (int) $request->query('tutor_id', 0);

            // 2) jika belum ada, pilih tutor berikutnya yang BELUM disubmit
            if (! $activeTutorId) {
                $submittedTutorIds = BasicListeningSurveyResponse::query()
                    ->where($baseKey)
                    ->whereIn('tutor_id', $tutorIds)
                    ->whereNotNull('submitted_at')
                    ->pluck('tutor_id')
                    ->map(fn ($v) => (int) $v)
                    ->all();

                $activeTutorId = collect($tutorIds)
                    ->map(fn ($v) => (int) $v)
                    ->first(fn ($id) => ! in_array($id, $submittedTutorIds, true)) ?? 0;
            }

            // 3) fallback: jika hanya 1 tutor
            if (! $activeTutorId && count($tutorIds) === 1) {
                $activeTutorId = (int) $tutorIds[0];
            }

            // 4) validasi: kalau multi tutor, pastikan termasuk pilihan awal
            if (count($tutorIds) > 1 && $activeTutorId && ! in_array($activeTutorId, $tutorIds, true)) {
                abort(422, 'Tutor tidak sesuai pilihan awal.');
            }

            // 5) jika tetap tidak ada tutor yang aktif (artinya semua sudah disubmit) → lanjut required
            if (! $activeTutorId) {
                return redirect()->route('bl.survey.required');
            }

            // === Key per-tutor (inilah kuncinya)
            $key = $baseKey + ['tutor_id' => $activeTutorId];

            // Buat/ambil draft PER TUTOR
            $response = BasicListeningSurveyResponse::firstOrCreate($key);

        } else {
            // Non tutor: draft tunggal per survey/user/session
            $response = BasicListeningSurveyResponse::firstOrCreate($baseKey);

            // Kategori supervisor → auto isi supervisor_id jika kosong
            if ($survey->category === 'supervisor' && empty($response->supervisor_id)) {
                $sid = (int) $request->session()->get('bl_selected_supervisor_id');
                if ($sid) {
                    $response->supervisor_id = $sid;
                    $response->save();
                }
            }
        }

        // Siapkan pertanyaan & jawaban existing untuk prefill
        $survey->load(['questions' => fn ($q) => $q->orderBy('order')]);
        $answers = BasicListeningSurveyAnswer::query()
            ->where('response_id', $response->id)
            ->get()
            ->keyBy('question_id');

        return view('bl.survey_show', [
            'survey'   => $survey,
            'response' => $response,
            'answers'  => $answers,
        ]);
    }

    /**
     * POST /bl/survey/{survey}
     * Submit jawaban, lalu chaining ke survey berikutnya.
     */
    public function submit(Request $request, BasicListeningSurvey $survey)
    {
        abort_unless($survey->isOpen(), 403, 'Kuesioner sudah tidak tersedia.');

        $sessionId = null;
        if ($survey->target === 'session') {
            $sessionId = (int) $request->input('session_id', $survey->session_id);
        }

        $baseKey = [
            'survey_id'  => $survey->id,
            'user_id'    => (int) auth()->id(),
            'session_id' => $sessionId,
        ];

        if ($survey->category === 'tutor') {
            $tutorIds = $this->selectedTutorIds($request);

            // tentukan tutor_id aktif dari hidden/query atau dari kunci row yang sudah ada
            $activeTutorId = (int) $request->input('tutor_id', (int) $request->query('tutor_id', 0));

            if (! $activeTutorId && count($tutorIds) === 1) {
                $activeTutorId = (int) $tutorIds[0];
            }

            // validasi ketat kalau multi
            if (count($tutorIds) > 1) {
                abort_unless($activeTutorId && in_array($activeTutorId, $tutorIds, true), 422, 'Tutor tidak sesuai pilihan awal.');
            }

            // Ambil/buat DRAFT PER-TUTOR
            $response = BasicListeningSurveyResponse::firstOrCreate($baseKey + ['tutor_id' => $activeTutorId]);
        } else {
            $response = BasicListeningSurveyResponse::firstOrCreate($baseKey);

            if ($survey->category === 'supervisor' && empty($response->supervisor_id)) {
                $sid = (int) $request->session()->get('bl_selected_supervisor_id');
                abort_if(! $sid, 422, 'Silakan pilih lembaga di halaman awal kuesioner.');
                $response->supervisor_id = $sid;
                $response->save();
            }
        }

        // Validasi dinamis
        $survey->load(['questions' => fn ($q) => $q->orderBy('order')]);
        $rules = [];
        foreach ($survey->questions as $q) {
            $key = "q.{$q->id}";
            $required = (bool) ($q->is_required ?? false);
            if (($q->type ?? 'text') === 'likert') {
                $rules[$key] = $required ? 'required|integer|between:1,5' : 'nullable|integer|between:1,5';
            } else {
                $rules[$key] = $required ? 'required|string|max:2000' : 'nullable|string|max:2000';
            }
        }
        $validated = $request->validate($rules);

        DB::transaction(function () use ($survey, $response, $validated) {
            foreach ($survey->questions as $q) {
                $value = $validated['q'][$q->id] ?? null;
                BasicListeningSurveyAnswer::updateOrCreate(
                    ['response_id' => $response->id, 'question_id' => $q->id],
                    (($q->type ?? 'text') === 'likert')
                        ? ['likert_value' => $value, 'text_value' => null]
                        : ['likert_value' => null, 'text_value' => $value]
                );
            }
            $response->forceFill(['submitted_at' => now()])->save();
        });

        // 🔁 lanjut ke survey berikutnya (perhatikan progres per-tutor)
        if ($next = $this->nextPendingSurveyFor($request, (int) auth()->id())) {
            return redirect()
                ->route('bl.survey.show', $next)
                ->with('success', 'Kuesioner tersimpan. Lanjutkan kuesioner berikutnya.');
        }

        return redirect()->route('bl.survey.success');
    }

    /**
     * GET /bl/survey/success
     * Halaman sukses setelah semua kuesioner selesai
     */
    public function success(Request $request)
    {
        $userId = (int) auth()->id();

        $completedCount = BasicListeningSurveyResponse::query()
            ->where('user_id', $userId)
            ->whereNotNull('submitted_at')
            ->whereNull('session_id')
            ->count();

        $tutorId = (int) $request->session()->get('bl_selected_tutor_id');
        $supervisorId = (int) $request->session()->get('bl_selected_supervisor_id');
        $tutor = $tutorId ? User::find($tutorId) : null;
        $supervisor = $supervisorId ? BasicListeningSupervisor::find($supervisorId) : null;

        $user = auth()->user();
        $year = (int) ($user->year ?? 0);
        $canDownloadCertificate = false;
        $meetsPassing = false;
        
        // Initialize grade variables
        $daily = null;
        $finalTest = null;
        $finalNumeric = null;
        $finalLetter = null;
        $attendance = null;
        
        if ($year >= 2025) {
            $grade = BasicListeningGrade::query()
                ->where('user_id', $userId)
                ->where('user_year', $year)
                ->first();
            $attendance = is_numeric($grade?->attendance) ? (float) $grade->attendance : null;
            $finalTest  = is_numeric($grade?->final_test)  ? (float) $grade->final_test  : null;
            $daily      = BlCompute::dailyAvgForUser($userId, $year);
            $daily      = is_numeric($daily) ? (float) $daily : null;

            $finalNumeric = $grade?->final_numeric_cached;
            $finalLetter  = $grade?->final_letter_cached;
            
            if ($finalNumeric === null && is_numeric($attendance) && is_numeric($daily) && is_numeric($finalTest)) {
                $finalNumeric = BlGrading::computeFinalNumeric([
                    'attendance' => $attendance,
                    'daily'      => $daily,
                    'final_test' => $finalTest,
                ]);
                $finalLetter = $finalNumeric !== null ? BlGrading::toLetter($finalNumeric) : null;
            }

            $baseEligible   = is_numeric($attendance) && is_numeric($finalTest);
            $meetsPassing   = is_numeric($finalNumeric) && $finalNumeric >= 55;

            if ($baseEligible && $meetsPassing) {
                $canDownloadCertificate = true;
            }
        }

        return view('bl.survey_success', compact(
            'completedCount',
            'tutor',
            'supervisor',
            'canDownloadCertificate',
            'meetsPassing',
            'daily',
            'finalTest',
            'finalNumeric',
            'finalLetter'
        ));
    }

    /**
     * GET /bl/survey/edit-choice
     * Form ubah pilihan dari dalam survey
     */
    public function editChoice(Request $request)
    {
        $user = auth()->user();
        $userPrody = $user?->prody_id;

        // Filter tutor berdasarkan prody user melalui pivot tutor_prody
        if ($userPrody) {
            $tutorIds = \DB::table('tutor_prody')
                ->where('prody_id', $userPrody)
                ->pluck('user_id')
                ->toArray();

            if (!empty($tutorIds)) {
                $tutors = User::query()
                    ->role('tutor')
                    ->whereIn('id', $tutorIds)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            } else {
                $tutors = User::query()->role('tutor')->orderBy('name')->get(['id', 'name']);
            }
        } else {
            $tutors = User::query()->role('tutor')->orderBy('name')->get(['id', 'name']);
        }

        $supervisors = BasicListeningSupervisor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $currentTutorId = (int) $request->session()->get('bl_selected_tutor_id');
        $currentSupervisorId = (int) $request->session()->get('bl_selected_supervisor_id');
        $currentTutor = $currentTutorId ? User::find($currentTutorId) : null;
        $currentSupervisor = $currentSupervisorId ? BasicListeningSupervisor::find($currentSupervisorId) : null;

        $returnUrl = $request->query('return', route('bl.survey.required'));

        return view('bl.survey_edit_choice', compact(
            'tutors',
            'supervisors',
            'currentTutor',
            'currentSupervisor',
            'currentTutorId',
            'currentSupervisorId',
            'returnUrl'
        ));
    }

    /**
     * POST /bl/survey/edit-choice
     */
    public function updateChoice(Request $request)
    {
        $data = $request->validate([
            'tutor_id'      => ['required', 'integer', Rule::exists('users', 'id')],
            'supervisor_id' => ['required', 'integer', Rule::exists('basic_listening_supervisors', 'id')->where('is_active', true)],
            'return_url'    => ['nullable', 'string'],
        ]);

        $isTutor = User::query()->role('tutor')->whereKey($data['tutor_id'])->exists();
        abort_unless($isTutor, 422, 'Pilihan tutor tidak valid.');

        $request->session()->put('bl_selected_tutor_id', (int) $data['tutor_id']);
        $request->session()->put('bl_selected_tutor_ids', [(int) $data['tutor_id']]);
        $request->session()->put('bl_selected_supervisor_id', (int) $data['supervisor_id']);

        $userId = (int) auth()->id();
        BasicListeningSurveyResponse::query()
            ->where('user_id', $userId)
            ->whereNull('submitted_at')
            ->whereNull('session_id')
            ->update([
                'tutor_id'      => (int) $data['tutor_id'],
                'supervisor_id' => (int) $data['supervisor_id'],
            ]);

        $returnUrl = $data['return_url'] ?? route('bl.survey.required');
        return redirect($returnUrl)->with('success', 'Pilihan tutor dan supervisor berhasil diperbarui.');
    }

    /** Reset pilihan di session */
    public function resetChoice(Request $request)
    {
        $userId = (int) auth()->id();

        // Hapus seluruh response & jawaban final (target=null) milik user agar benar-benar mulai ulang.
        $responses = \App\Models\BasicListeningSurveyResponse::query()
            ->where('user_id', $userId)
            ->whereNull('session_id') // final (bukan per-sesi)
            ->get();

        foreach ($responses as $resp) {
            $resp->delete(); // booted() akan menghapus answers
        }

        $request->session()->forget([
            'bl_selected_tutor_id',
            'bl_selected_tutor_ids',
            'bl_selected_supervisor_id',
        ]);

        return redirect()->route('bl.survey.start')->with('success', 'Kuesioner direset. Silakan pilih tutor/supervisor kembali.');
    }

    /**
     * Helper: cari survey final berikutnya yang pending.
     * - Untuk kategori 'tutor', cek progres per tutor_id (distinct).
     */
    private function nextPendingSurveyFor(Request $request, int $userId): ?BasicListeningSurvey
    {
        $categories = \App\Models\BasicListeningCategory::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('slug')
            ->all();

        if (empty($categories)) {
            // Fallback urutan default
            $categories = ['tutor', 'materi', 'supervisor', 'institute'];
        }

        foreach ($categories as $cat) {
            $survey = BasicListeningSurvey::query()
                ->where('require_for_certificate', true)
                ->where('target', 'final')
                ->where('category', $cat)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->first();

            if (! $survey || ! $survey->isOpen()) {
                continue;
            }

            if ($cat === 'tutor') {
                $tutorIds = $this->selectedTutorIds($request); // 1–2 item
                if (empty($tutorIds)) {
                    // biar guard di redirectToRequired/show yang arahkan ke start
                    return $survey;
                }

                $doneCount = BasicListeningSurveyResponse::query()
                    ->where('survey_id', $survey->id)
                    ->where('user_id', $userId)
                    ->whereIn('tutor_id', $tutorIds)
                    ->whereNull('session_id')
                    ->whereNotNull('submitted_at')
                    ->distinct('tutor_id')
                    ->count('tutor_id');

                if ($doneCount < count($tutorIds)) {
                    // masih ada tutor yang belum disubmit → tetap minta kategori tutor
                    return $survey;
                }

                // semua tutor selesai → lanjut ke kategori berikutnya
                continue;
            }

            // kategori non-tutor (supervisor, institute): cukup cek 1 kali submit
            $alreadySubmitted = BasicListeningSurveyResponse::query()
                ->where('survey_id', $survey->id)
                ->where('user_id', $userId)
                ->whereNull('session_id')
                ->whereNotNull('submitted_at')
                ->exists();

            if (! $alreadySubmitted) {
                return $survey;
            }
        }

        return null;
    }

    /** Helper: gabungkan single & multi tutor dari session */
    private function selectedTutorIds(Request $request): array
    {
        return collect((array) $request->session()->get('bl_selected_tutor_ids', []))
            ->when($request->session()->has('bl_selected_tutor_id'), function ($c) use ($request) {
                $c->push((int) $request->session()->get('bl_selected_tutor_id'));
            })
            ->filter(fn ($v) => (int) $v > 0)
            ->unique()
            ->values()
            ->all();
    }
}
