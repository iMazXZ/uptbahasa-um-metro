<?php

namespace App\Support;

use App\Models\EptOnlineAttempt;
use App\Models\EptOnlineResult;
use App\Models\EptOnlineSection;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class EptOnlineAttemptFinalizer
{
    public function finalizeExpiredAttempts(int $limit = 250): int
    {
        $asOf = now();
        $count = 0;

        EptOnlineAttempt::query()
            ->where('status', EptOnlineAttempt::STATUS_IN_PROGRESS)
            ->whereNull('submitted_at')
            ->whereNotNull('current_section_started_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $asOf)
            ->with(['form.sections', 'answers'])
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get()
            ->each(function (EptOnlineAttempt $attempt) use ($asOf, &$count): void {
                if ($this->catchUpExpiredAttempt($attempt, $asOf)) {
                    $count++;
                }
            });

        return $count;
    }

    public function catchUpExpiredAttempt(EptOnlineAttempt $attempt, ?CarbonInterface $asOf = null): bool
    {
        $asOf ??= now();

        $attempt->refresh();
        if ($attempt->status === EptOnlineAttempt::STATUS_SUBMITTED || $attempt->submitted_at) {
            return false;
        }

        $attempt->loadMissing(['form.sections']);
        $sections = $attempt->form?->sections instanceof Collection
            ? $attempt->form->sections->values()
            : collect();

        if ($sections->isEmpty() || ! $attempt->current_section_type || ! $attempt->current_section_started_at) {
            return false;
        }

        $changed = false;

        while (true) {
            $sectionIndex = $sections->search(
                fn (EptOnlineSection $section): bool => $section->type === $attempt->current_section_type
            );

            if ($sectionIndex === false) {
                return $changed;
            }

            /** @var EptOnlineSection $section */
            $section = $sections->get($sectionIndex);
            $duration = (int) $section->duration_minutes;

            if ($duration <= 0 || ! $attempt->current_section_started_at) {
                return $changed;
            }

            $deadline = $attempt->current_section_started_at->copy()->addMinutes($duration);

            if ($deadline->greaterThan($asOf)) {
                if (! $attempt->expires_at || ! $attempt->expires_at->equalTo($deadline)) {
                    $attempt->forceFill(['expires_at' => $deadline])->save();
                }

                return $changed;
            }

            $nextSection = $sections->get($sectionIndex + 1);
            if (! $nextSection) {
                $this->finalize($attempt, $deadline, [
                    'finalized_reason' => 'section_time_expired',
                    'finalized_by' => 'server_expiry_sweep',
                ]);

                return true;
            }

            $nextDuration = (int) $nextSection->duration_minutes;
            $attempt->forceFill([
                'status' => EptOnlineAttempt::STATUS_IN_PROGRESS,
                'current_section_type' => $nextSection->type,
                'current_section_started_at' => $deadline,
                'expires_at' => $nextDuration > 0 ? $deadline->copy()->addMinutes($nextDuration) : null,
                'meta' => $this->mergeAttemptMeta($attempt, [
                    'last_auto_advanced_at' => $asOf->toDateTimeString(),
                    'last_auto_advanced_from' => $section->type,
                    'last_auto_advanced_to' => $nextSection->type,
                ]),
            ])->save();

            $attempt->refresh();
            $changed = true;
        }
    }

    public function finalize(
        EptOnlineAttempt $attempt,
        ?CarbonInterface $submittedAt = null,
        array $meta = [],
    ): void {
        $attempt->refresh();
        if ($attempt->status === EptOnlineAttempt::STATUS_SUBMITTED || $attempt->submitted_at) {
            return;
        }

        $submittedAt ??= now();

        $attempt->loadMissing([
            'form.sections',
            'answers',
        ]);

        $questions = $attempt->form
            ->questions()
            ->get(['id', 'section_id', 'correct_option']);

        $sectionsById = $attempt->form->sections->keyBy('id');
        $answersByQuestionId = $attempt->answers->keyBy('question_id');

        $rawScores = [
            EptOnlineSection::TYPE_LISTENING => 0,
            EptOnlineSection::TYPE_STRUCTURE => 0,
            EptOnlineSection::TYPE_READING => 0,
        ];

        foreach ($questions as $question) {
            $sectionType = $sectionsById->get($question->section_id)?->type;
            if (! $sectionType || ! array_key_exists($sectionType, $rawScores)) {
                continue;
            }

            $selected = $answersByQuestionId->get($question->id)?->selected_option;
            if ($selected && $selected === $question->correct_option) {
                $rawScores[$sectionType]++;
            }
        }

        $listeningScaled = EptOnlineResult::scaleSectionScore(
            EptOnlineSection::TYPE_LISTENING,
            $rawScores[EptOnlineSection::TYPE_LISTENING],
        );
        $structureScaled = EptOnlineResult::scaleSectionScore(
            EptOnlineSection::TYPE_STRUCTURE,
            $rawScores[EptOnlineSection::TYPE_STRUCTURE],
        );
        $readingScaled = EptOnlineResult::scaleSectionScore(
            EptOnlineSection::TYPE_READING,
            $rawScores[EptOnlineSection::TYPE_READING],
        );
        $totalScaled = EptOnlineResult::calculateTotalScaled(
            $listeningScaled,
            $structureScaled,
            $readingScaled,
        );

        EptOnlineResult::updateOrCreate(
            ['attempt_id' => $attempt->id],
            [
                'listening_raw' => $rawScores[EptOnlineSection::TYPE_LISTENING],
                'structure_raw' => $rawScores[EptOnlineSection::TYPE_STRUCTURE],
                'reading_raw' => $rawScores[EptOnlineSection::TYPE_READING],
                'listening_scaled' => $listeningScaled,
                'structure_scaled' => $structureScaled,
                'reading_scaled' => $readingScaled,
                'total_scaled' => $totalScaled,
                'scale_version' => EptOnlineResult::SCALE_VERSION_AUTO,
                'is_published' => false,
                'published_at' => null,
                'meta' => array_merge([
                    'scoring_mode' => 'auto_conversion_table',
                    'finalized_at' => $submittedAt->toDateTimeString(),
                ], $meta),
            ],
        );

        $attempt->forceFill([
            'status' => EptOnlineAttempt::STATUS_SUBMITTED,
            'submitted_at' => $submittedAt,
            'expires_at' => null,
        ])->save();
    }

    private function mergeAttemptMeta(EptOnlineAttempt $attempt, array $meta): array
    {
        return array_merge(is_array($attempt->meta) ? $attempt->meta : [], $meta);
    }
}
