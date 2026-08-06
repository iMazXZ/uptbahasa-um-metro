<?php

namespace App\Http\Controllers;

use App\Models\BasicListeningSurvey;
use App\Models\BasicListeningSurveyAnswer;
use App\Models\BasicListeningSurveyResponse;
use App\Models\User;
use App\Models\BasicListeningSupervisor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlSurveyResultsExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $category     = $request->query('category', 'tutor');
        $mode         = $request->query('mode', 'overall');
        $tutorId      = $request->query('tutor');
        $supervisorId = $request->query('supervisor');
        $allSuggestions = $request->boolean('all_suggestions');

        $survey = BasicListeningSurvey::query()
            ->where('category', $category)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->firstOrFail();

        $segments = $this->buildSegments($survey, $category, $mode, $tutorId, $supervisorId, $allSuggestions);

        // Perbaikan: Nama view disesuaikan dengan file 'resources/views/exports/bl-survey-results.blade.php'
        $pdf = Pdf::loadView('exports.bl-survey-results', [
            'segments' => $segments,
            'category' => $category,
        ])->setPaper('a4', 'portrait');

        $fileName = 'Hasil_Kuesioner_' . $category . '_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($fileName);
    }

    private function buildSegments(BasicListeningSurvey $survey, string $category, string $mode, ?int $tutorId, ?int $supervisorId, bool $allSuggestions): array
    {
        $mode = $mode ?: 'overall';

        if ($mode === 'per_entity') {
            return $this->buildPerEntitySegments($survey, $category, $allSuggestions);
        }

        $singleSegment = $this->buildSingleSegment(
            $survey,
            $category,
            $mode === 'single' && $category === 'tutor' ? $tutorId : null,
            $mode === 'single' && $category === 'supervisor' ? $supervisorId : null,
            $allSuggestions
        );

        return [$singleSegment];
    }

    private function buildPerEntitySegments(BasicListeningSurvey $survey, string $category, bool $allSuggestions): array
    {
        if ($category === 'tutor') {
            $ids = BasicListeningSurveyResponse::query()
                ->where('survey_id', $survey->id)
                ->whereNotNull('tutor_id')
                ->distinct()
                ->pluck('tutor_id')
                ->filter()
                ->all();
        } elseif ($category === 'supervisor') {
            $ids = BasicListeningSurveyResponse::query()
                ->where('survey_id', $survey->id)
                ->whereNotNull('supervisor_id')
                ->distinct()
                ->pluck('supervisor_id')
                ->filter()
                ->all();
        } else {
            return [$this->buildSingleSegment($survey, $category, null, null, $allSuggestions)];
        }

        $segments = [];
        foreach ($ids as $id) {
            $segment = $this->buildSingleSegment(
                $survey,
                $category,
                $category === 'tutor' ? $id : null,
                $category === 'supervisor' ? $id : null,
                $allSuggestions
            );

            if ($segment['hasResponses']) {
                $segments[] = $segment;
            }
        }

        return $segments ?: [$this->buildSingleSegment($survey, $category, null, null, $allSuggestions)];
    }

    private function buildSingleSegment(BasicListeningSurvey $survey, string $category, ?int $tutorId, ?int $supervisorId, bool $allSuggestions): array
    {
        // 1. Ambil Data Statistik Per Baris (Likert)
        $rows = BasicListeningSurveyAnswer::query()
            ->select([
                'basic_listening_survey_questions.id as id',
                'basic_listening_survey_questions.question as question_text',
                DB::raw('AVG(basic_listening_survey_answers.likert_value) as avg_score'),
                DB::raw('COUNT(DISTINCT basic_listening_survey_responses.user_id) as responses_count'),
                DB::raw('SUM(CASE WHEN basic_listening_survey_answers.likert_value = 1 THEN 1 ELSE 0 END) as c1'),
                DB::raw('SUM(CASE WHEN basic_listening_survey_answers.likert_value = 2 THEN 1 ELSE 0 END) as c2'),
                DB::raw('SUM(CASE WHEN basic_listening_survey_answers.likert_value = 3 THEN 1 ELSE 0 END) as c3'),
                DB::raw('SUM(CASE WHEN basic_listening_survey_answers.likert_value = 4 THEN 1 ELSE 0 END) as c4'),
                DB::raw('SUM(CASE WHEN basic_listening_survey_answers.likert_value = 5 THEN 1 ELSE 0 END) as c5'),
            ])
            ->join('basic_listening_survey_responses', 'basic_listening_survey_answers.response_id', '=', 'basic_listening_survey_responses.id')
            ->join('basic_listening_survey_questions', 'basic_listening_survey_answers.question_id', '=', 'basic_listening_survey_questions.id')
            ->where('basic_listening_survey_responses.survey_id', $survey->id)
            ->whereNotNull('basic_listening_survey_answers.likert_value')
            ->when($category === 'tutor' && $tutorId, fn($q) => $q->where('basic_listening_survey_responses.tutor_id', $tutorId))
            ->when($category === 'supervisor' && $supervisorId, fn($q) => $q->where('basic_listening_survey_responses.supervisor_id', $supervisorId))
            ->groupBy('basic_listening_survey_questions.id', 'basic_listening_survey_questions.question')
            ->orderBy('basic_listening_survey_questions.id')
            ->get();

        // 2. Ambil Top 5 Saran Terpanjang (Text)
        $suggestionsQuery = BasicListeningSurveyAnswer::query()
            ->select([
                'basic_listening_survey_questions.question',
                'basic_listening_survey_answers.text_value',
                DB::raw('CHAR_LENGTH(basic_listening_survey_answers.text_value) as len_text'),
            ])
            ->join('basic_listening_survey_responses', 'basic_listening_survey_answers.response_id', '=', 'basic_listening_survey_responses.id')
            ->join('basic_listening_survey_questions', 'basic_listening_survey_answers.question_id', '=', 'basic_listening_survey_questions.id')
            ->where('basic_listening_survey_responses.survey_id', $survey->id)
            ->where('basic_listening_survey_questions.type', 'text')
            ->whereNotNull('basic_listening_survey_answers.text_value')
            ->when($category === 'tutor' && $tutorId, fn($q) => $q->where('basic_listening_survey_responses.tutor_id', $tutorId))
            ->when($category === 'supervisor' && $supervisorId, fn($q) => $q->where('basic_listening_survey_responses.supervisor_id', $supervisorId))
            ->orderByDesc('len_text');

        if (! $allSuggestions) {
            $suggestionsQuery->limit(10);
        }

        $suggestions = $suggestionsQuery
            ->get()
            ->map(fn ($s) => [
                'question' => $s->question,
                'text'     => $s->text_value,
            ]);

        // 3. Metadata
        $respQuery = BasicListeningSurveyResponse::query()->where('survey_id', $survey->id);
        if ($category === 'tutor' && $tutorId) $respQuery->where('tutor_id', $tutorId);
        if ($category === 'supervisor' && $supervisorId) $respQuery->where('supervisor_id', $supervisorId);

        $responseIds = $respQuery->pluck('id');
        $avgOverall  = BasicListeningSurveyAnswer::query()
            ->whereIn('response_id', $responseIds)
            ->avg('likert_value');

        $tutorName = $tutorId ? (User::find($tutorId)?->name ?? 'Tutor ID ' . $tutorId) : 'Semua Tutor';
        $supervisorName = $supervisorId ? (BasicListeningSupervisor::find($supervisorId)?->name ?? 'Supervisor ID ' . $supervisorId) : 'Semua Supervisor';

        $meta = [
            'category'    => ucfirst($category),
            'tutor'       => $category === 'tutor' ? $tutorName : '-',
            'supervisor'  => $category === 'supervisor' ? $supervisorName : '-',
            'respondents' => $respQuery->count(),
            'avg'         => $avgOverall ? number_format((float) $avgOverall, 2) : '-',
            'generatedAt' => now()->timezone(config('app.timezone','Asia/Jakarta'))->format('d M Y H:i'),
            'surveyTitle' => $survey->title ?? 'Kuesioner ' . ucfirst($category),
        ];

        return [
            'meta'         => $meta,
            'rows'         => $rows,
            'hasResponses' => $responseIds->isNotEmpty(),
            'suggestions'  => $suggestions,
        ];
    }
}
