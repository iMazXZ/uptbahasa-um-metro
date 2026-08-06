<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\BasicListeningGrade;
use App\Support\BlCompute;
use App\Support\BlGrading;
use App\Models\BasicListeningSurvey;
use App\Models\BasicListeningSurveyResponse;

class StudentBasicListeningWidget extends Widget
{
    protected static string $view = 'filament.widgets.student-basic-listening-widget';
    protected int|string|array $columnSpan = 'full';

    /** Helper: ambil grade sekali saja */
    protected static function findGrade(int $userId, int $year): ?BasicListeningGrade
    {
        return BasicListeningGrade::query()
            ->where('user_id', $userId)
            ->where('user_year', $year)
            ->first();
    }

    public static function canView(): bool
    {
        $u = Auth::user();
        if (! $u || (int) ($u->year ?? 0) < 2025) {
            return false;
        }

        $grade = self::findGrade($u->id, (int) $u->year);
        return $grade !== null
            && is_numeric($grade->attendance)
            && is_numeric($grade->final_test);
    }

    protected function getViewData(): array
    {
        $u = Auth::user();

        $grade = self::findGrade($u->id, (int) $u->year);

        $attendance = is_numeric($grade?->attendance) ? (float) $grade->attendance : null;
        $finalTest  = is_numeric($grade?->final_test)  ? (float) $grade->final_test  : null;

        $daily = BlCompute::dailyAvgForUser($u->id, (int) $u->year);
        $daily = is_numeric($daily) ? (float) $daily : null;

        // Cache → fallback hitung bila kosong (agar UI tetap ada nilai)
        $finalNumeric = $grade?->final_numeric_cached;
        $finalLetter  = $grade?->final_letter_cached;

        if ($finalNumeric === null && is_numeric($attendance) && is_numeric($daily) && is_numeric($finalTest)) {
            $finalNumeric = BlGrading::computeFinalNumeric([
                'attendance' => $attendance,
                'daily'      => $daily,
                'final_test' => $finalTest,
            ]);
            $finalLetter = BlGrading::toLetter($finalNumeric);
        }

        // === Survey gate (final) ===
        $survey = BasicListeningSurvey::query()
            ->where('require_for_certificate', true)
            ->where('target', 'final')
            ->where('is_active', true)
            ->latest('id')
            ->first();

        $surveyRequired = $survey ? $survey->isOpen() : false;

        $surveyDone = false;
        if ($surveyRequired) {
            $surveyDone = BasicListeningSurveyResponse::where([
                'survey_id'  => $survey->id,
                'user_id'    => $u->id,
                'session_id' => null,
            ])->whereNotNull('submitted_at')->exists();
        }

        $baseEligible   = is_numeric($attendance) && is_numeric($finalTest);
        $meetsPassing   = is_numeric($finalNumeric) && $finalNumeric >= 55;
        $canDownload    = $baseEligible && $meetsPassing && (! $surveyRequired || $surveyDone);

        // Guard routes agar widget tak crash bila route belum ada
        $surveyUrl   = Route::has('bl.survey.required') ? route('bl.survey.required') : null;
        $downloadUrl = Route::has('bl.certificate.download') ? route('bl.certificate.download') : null;
        $previewUrl  = $downloadUrl ? ($downloadUrl . '?inline=1') : null;

        return [
            'user'           => $u,
            'attendance'     => $attendance,
            'daily'          => $daily,
            'finalTest'      => $finalTest,
            'finalNumeric'   => $finalNumeric,
            'finalLetter'    => $finalLetter,

            'canDownload'    => $canDownload,
            'surveyRequired' => $surveyRequired,
            'surveyDone'     => $surveyDone,
            'surveyUrl'      => $surveyUrl,
            'downloadUrl'    => $downloadUrl,
            'previewUrl'     => $previewUrl,
        ];
    }
}
