<?php

namespace App\Filament\Pages\Widgets;

use App\Models\BasicListeningSurvey;
use App\Models\BasicListeningSurveyAnswer;
use App\Models\BasicListeningSurveyResponse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BlSurveyResultsStats extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    public ?string $category = null;
    public ?int $tutorId = null;
    public ?int $supervisorId = null;

    protected function getStats(): array
    {
        $survey = BasicListeningSurvey::query()
            ->where('category', $this->category ?? 'tutor')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first();

        if (! $survey) {
            return [
                Stat::make('Total Responden', '0')
                    ->icon('heroicon-o-users'),
                Stat::make('Rata-rata Skor', '—')
                    ->icon('heroicon-o-star'),
                Stat::make('Total Pertanyaan', '0')
                    ->icon('heroicon-o-question-mark-circle'),
                Stat::make('Skor Rendah (<3.5)', '0 soal')
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }

        // Build query untuk responses
        $respQuery = BasicListeningSurveyResponse::query()->where('survey_id', $survey->id);

        if ($this->category === 'tutor' && $this->tutorId) {
            $respQuery->where('tutor_id', $this->tutorId);
        }

        if ($this->category === 'supervisor' && $this->supervisorId) {
            $respQuery->where('supervisor_id', $this->supervisorId);
        }

        $respondents = $respQuery->count();

        // Subquery
        $responseSubquery = function ($query) use ($survey) {
            $query->select('id')
                ->from('basic_listening_survey_responses')
                ->where('survey_id', $survey->id);

            if ($this->category === 'tutor' && $this->tutorId) {
                $query->where('tutor_id', $this->tutorId);
            }

            if ($this->category === 'supervisor' && $this->supervisorId) {
                $query->where('supervisor_id', $this->supervisorId);
            }
        };

        // Rata-rata
        $avg = BasicListeningSurveyAnswer::query()
            ->whereIn('response_id', $responseSubquery)
            ->avg('likert_value');

        // Low score questions
        $lowScoreCount = DB::table('basic_listening_survey_answers as a')
            ->join('basic_listening_survey_responses as r', 'a.response_id', '=', 'r.id')
            ->select('a.question_id')
            ->where('r.survey_id', $survey->id)
            ->when($this->category === 'tutor' && $this->tutorId, fn($q) => $q->where('r.tutor_id', $this->tutorId))
            ->when($this->category === 'supervisor' && $this->supervisorId, fn($q) => $q->where('r.supervisor_id', $this->supervisorId))
            ->whereNotNull('a.likert_value')
            ->groupBy('a.question_id')
            ->havingRaw('AVG(a.likert_value) < 3.5')
            ->get()
            ->count();

        $totalQuestions = $survey->questions()->count();

        return [
            Stat::make('Total Responden', number_format($respondents))
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Rata-rata Skor', $avg ? number_format($avg, 2) : '—')
                ->icon('heroicon-o-star')
                ->color($avg >= 4 ? 'success' : ($avg >= 3 ? 'warning' : 'danger')),

            Stat::make('Total Pertanyaan', number_format($totalQuestions))
                ->icon('heroicon-o-question-mark-circle')
                ->color('info'),

            Stat::make('Skor Rendah (<3.5)', $lowScoreCount . ' soal')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($lowScoreCount > 0 ? 'danger' : 'success'),
        ];
    }
}
