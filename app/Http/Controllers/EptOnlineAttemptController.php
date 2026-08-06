<?php

namespace App\Http\Controllers;

use App\Models\EptOnlineAnswer;
use App\Models\EptOnlineAttempt;
use App\Models\EptOnlineQuestion;
use App\Models\EptOnlineSection;
use App\Support\EptOnlineAttemptFinalizer;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EptOnlineAttemptController extends Controller
{
    public function show(EptOnlineAttempt $attempt, Request $request)
    {
        $this->authorizeAttempt($attempt, $request);
        app(EptOnlineAttemptFinalizer::class)->catchUpExpiredAttempt($attempt);
        $attempt->refresh();

        if ($attempt->status === EptOnlineAttempt::STATUS_SUBMITTED || $attempt->submitted_at) {
            return redirect()->route('ept-online.attempt.finished', $this->attemptRouteParams($attempt));
        }

        $section = $this->currentSection($attempt);
        if (! $section) {
            abort(404, 'Test section not found.');
        }

        if ($this->shouldShowListeningIntro($attempt, $section)) {
            $introMeta = $this->listeningIntroMeta($section);

            return view('ept-online.listening-intro', [
                'attempt' => $attempt,
                'section' => $section,
                'orderedSections' => $this->orderedSections($attempt),
                'introMeta' => $introMeta,
                'availableListeningParts' => $this->orderedListeningPartKeys($introMeta),
            ]);
        }

        $this->ensureSectionTiming($attempt, $section);

        if ($redirectUrl = $this->handleExpiredSection($attempt, $section)) {
            return redirect()->to($redirectUrl);
        }

        $attempt->refresh();
        $section = $this->currentSection($attempt);
        if (! $section) {
            abort(404, 'Test section not found.');
        }

        $questions = $section->questions()
            ->with('passage')
            ->orderBy('sort_order')
            ->get();

        if ($questions->isEmpty()) {
            abort(404, 'There are no questions in this section yet.');
        }

        $currentIndex = max(0, (int) $request->query('q', 0));
        $currentIndex = min($currentIndex, max(0, $questions->count() - 1));

        $question = $questions[$currentIndex] ?? abort(404);
        $answer = EptOnlineAnswer::firstOrNew([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);

        $answeredIds = $attempt->answers()
            ->where('section_id', $section->id)
            ->whereNotNull('selected_option')
            ->pluck('question_id')
            ->all();

        $unansweredCount = $questions->count() - count(array_unique($answeredIds));
        $remainingSeconds = $this->remainingSeconds($attempt, $section);
        $nextSectionType = $this->nextSectionType($attempt, $section->type);
        $orderedSections = $this->orderedSections($attempt);
        $sectionIntroGate = $this->sectionIntroGate($attempt, $section, $currentIndex);
        $listeningPartTransition = $this->listeningPartTransition($attempt, $section, $questions, $currentIndex);
        $sectionPartTransition = $this->sectionPartTransition($section, $questions, $currentIndex);

        $requestedAudioStartAt = $this->normalizeAudioPosition($request->query('audio'));
        $storedAudioStartAt = $section->type === EptOnlineSection::TYPE_LISTENING
            ? $this->storedSectionAudioPosition($attempt, $section->type)
            : null;
        $audioStartAt = $requestedAudioStartAt ?? $storedAudioStartAt;
        $usedStoredAudioState = $requestedAudioStartAt === null && $storedAudioStartAt !== null;
        $showAudioRecoveryPrompt = $section->type === EptOnlineSection::TYPE_LISTENING
            && $usedStoredAudioState
            && $audioStartAt !== null
            && $audioStartAt > 0.5
            && ! $request->boolean('autoplay');
        $audioUrl = $section->type === EptOnlineSection::TYPE_LISTENING
            ? $this->attemptAudioUrl($attempt, $section)
            : null;

        return view('ept-online.quiz', [
            'attempt' => $attempt,
            'section' => $section,
            'question' => $question,
            'questions' => $questions,
            'answer' => $answer,
            'currentIndex' => $currentIndex,
            'answeredIds' => $answeredIds,
            'unansweredCount' => $unansweredCount,
            'remainingSeconds' => $remainingSeconds,
            'nextSectionType' => $nextSectionType,
            'orderedSections' => $orderedSections,
            'autoplayAudio' => $request->boolean('autoplay'),
            'sectionIntroGate' => $sectionIntroGate,
            'listeningPartTransition' => $listeningPartTransition,
            'sectionPartTransition' => $sectionPartTransition,
            'audioStartAt' => $audioStartAt,
            'resumeAudio' => $request->boolean('resume_audio') || $request->boolean('autoplay'),
            'showAudioRecoveryPrompt' => $showAudioRecoveryPrompt,
            'audioRecoveryLabel' => $showAudioRecoveryPrompt ? $this->formatAudioPositionLabel($audioStartAt) : null,
            'audioUrl' => $audioUrl,
        ]);
    }

    public function audio(EptOnlineAttempt $attempt, Request $request): BinaryFileResponse
    {
        $this->authorizeAttempt($attempt, $request);

        if ($attempt->status === EptOnlineAttempt::STATUS_SUBMITTED || $attempt->submitted_at) {
            abort(404);
        }

        $section = $this->currentSection($attempt);
        if (! $section || $section->type !== EptOnlineSection::TYPE_LISTENING) {
            abort(404);
        }

        $audioFile = $this->resolveAttemptAudioFile($attempt, $section);
        if (! $audioFile) {
            abort(404);
        }

        $this->recordIntegrityEvent($attempt, 'audio_stream_opened', [
            'section' => $section->type,
            'disk' => $audioFile['disk'],
        ]);

        $response = response()->file($audioFile['absolute_path'], [
            'Content-Type' => $audioFile['mime_type'],
            'Content-Disposition' => 'inline; filename="' . basename($audioFile['absolute_path']) . '"',
            'Accept-Ranges' => 'bytes',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);

        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }

    public function startSection(EptOnlineAttempt $attempt, Request $request)
    {
        $this->authorizeAttempt($attempt, $request);

        if ($attempt->status === EptOnlineAttempt::STATUS_SUBMITTED || $attempt->submitted_at) {
            return redirect()->route('ept-online.attempt.finished', $this->attemptRouteParams($attempt));
        }

        $section = $this->currentSection($attempt);
        if (! $section) {
            abort(404, 'Test section not found.');
        }

        if (! $this->shouldShowListeningIntro($attempt, $section)) {
            return redirect()->route('ept-online.attempt.show', $this->attemptRouteParams($attempt));
        }

        $attempt->loadMissing('accessToken');
        if (! $attempt->started_at && $attempt->accessToken && ! $attempt->accessToken->withinWindow()) {
            return redirect()
                ->route('ept-online.index')
                ->withErrors(['code' => 'The test access window expired before listening could begin.']);
        }

        $now = now();
        $duration = (int) $section->duration_minutes;

        $attempt->forceFill([
            'status' => EptOnlineAttempt::STATUS_IN_PROGRESS,
            'started_at' => $attempt->started_at ?: $now,
            'current_section_started_at' => $attempt->current_section_started_at ?: $now,
            'expires_at' => $duration > 0 ? $now->copy()->addMinutes($duration) : null,
        ])->save();

        return redirect()->route('ept-online.attempt.show', [
            'attempt' => $attempt->public_id,
            'autoplay' => 1,
        ]);
    }

    public function answer(EptOnlineAttempt $attempt, Request $request)
    {
        $this->authorizeAttempt($attempt, $request);

        $isAjax = $request->wantsJson() || $request->ajax();

        if ($attempt->status === EptOnlineAttempt::STATUS_SUBMITTED || $attempt->submitted_at) {
            return $this->redirectResponse(
                route('ept-online.attempt.finished', $this->attemptRouteParams($attempt)),
                $isAjax,
                409,
                'This test has already been submitted.',
            );
        }

        $section = $this->currentSection($attempt);
        if (! $section) {
            abort(404, 'Test section not found.');
        }

        $this->ensureSectionTiming($attempt, $section);

        if ($redirectUrl = $this->handleExpiredSection($attempt, $section)) {
            return $this->redirectResponse($redirectUrl, $isAjax, 408, 'This section time has expired.');
        }

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'q' => ['nullable', 'integer'],
            'answer' => ['nullable', 'in:A,B,C,D'],
            'selected_answer' => ['nullable', 'in:A,B,C,D'],
            'audio_position' => ['nullable', 'numeric', 'min:0'],
            'audio_playing' => ['nullable', 'boolean'],
        ]);

        $question = EptOnlineQuestion::query()
            ->whereKey($data['question_id'])
            ->where('section_id', $section->id)
            ->firstOrFail();

        $submittedAnswer = $data['answer'] ?? $data['selected_answer'] ?? null;
        $audioPosition = $this->normalizeAudioPosition($data['audio_position'] ?? null);
        $audioPlaying = (bool) ($data['audio_playing'] ?? false);

        if ($section->type === EptOnlineSection::TYPE_LISTENING) {
            $this->storeSectionAudioState($attempt, $section->type, $audioPosition, $audioPlaying);
        }

        if (! filled($submittedAnswer)) {
            $redirectParams = [
                'attempt' => $attempt->public_id,
                'q' => max(0, (int) ($data['q'] ?? 0)),
            ];

            if ($audioPosition !== null) {
                $redirectParams['audio'] = $audioPosition;
            }

            if ($audioPlaying) {
                $redirectParams['resume_audio'] = 1;
            }

            return $this->redirectResponse(
                route('ept-online.attempt.show', $this->attemptRouteParams($attempt, $redirectParams)),
                $isAjax,
                422,
                'No answer was received. Please choose an option again.',
            );
        }

        EptOnlineAnswer::updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            [
                'section_id' => $section->id,
                'selected_option' => $submittedAnswer,
                'answered_at' => now(),
            ]
        );

        $total = $section->questions()->count();
        $currentIndex = max(0, (int) ($data['q'] ?? 0));
        $nextIndex = min($currentIndex + 1, max(0, $total - 1));

        $redirectParams = [
            'attempt' => $attempt->public_id,
            'q' => $nextIndex,
        ];

        if ($audioPosition !== null) {
            $redirectParams['audio'] = $audioPosition;
        }

        if ($audioPlaying) {
            $redirectParams['resume_audio'] = 1;
        }

        return $this->redirectResponse(
            route('ept-online.attempt.show', $this->attemptRouteParams($attempt, $redirectParams)),
            $isAjax,
        );
    }

    public function revealListeningPart(EptOnlineAttempt $attempt, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAttempt($attempt, $request);
        $isAjax = $request->wantsJson() || $request->ajax();

        $data = $request->validate([
            'part' => ['required', 'string', 'size:1', 'in:A,B,C'],
            'q' => ['nullable', 'integer', 'min:0'],
            'audio_position' => ['nullable', 'numeric', 'min:0'],
            'audio_playing' => ['nullable', 'boolean'],
        ]);

        $section = $this->currentSection($attempt);
        if (! $section || $section->type !== EptOnlineSection::TYPE_LISTENING) {
            abort(404, 'Listening section not found.');
        }

        $part = strtoupper((string) $data['part']);
        $this->markListeningPartAcknowledged($attempt, $part);

        $redirectParams = [
            'attempt' => $attempt->public_id,
            'q' => (int) ($data['q'] ?? 0),
        ];

        if (($audioPosition = $this->normalizeAudioPosition($data['audio_position'] ?? null)) !== null) {
            $redirectParams['audio'] = $audioPosition;
        }

        $audioPlaying = (bool) ($data['audio_playing'] ?? true);

        $this->storeSectionAudioState($attempt, $section->type, $audioPosition, $audioPlaying);

        if ($audioPlaying) {
            $redirectParams['resume_audio'] = 1;
        }

        return $this->redirectResponse(
            route('ept-online.attempt.show', $this->attemptRouteParams($attempt, $redirectParams)),
            $isAjax,
        );
    }

    public function acknowledgeSectionIntro(EptOnlineAttempt $attempt, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAttempt($attempt, $request);
        $isAjax = $request->wantsJson() || $request->ajax();

        $data = $request->validate([
            'q' => ['nullable', 'integer', 'min:0'],
        ]);

        $section = $this->currentSection($attempt);
        if (! $section) {
            abort(404, 'Test section not found.');
        }

        $this->markSectionIntroAcknowledged($attempt, $section->type);

        return $this->redirectResponse(
            route('ept-online.attempt.show', [
                'attempt' => $attempt->public_id,
                'q' => (int) ($data['q'] ?? 0),
            ]),
            $isAjax,
        );
    }

    public function progress(EptOnlineAttempt $attempt, Request $request)
    {
        $this->authorizeAttempt($attempt, $request);

        if ($attempt->status === EptOnlineAttempt::STATUS_SUBMITTED || $attempt->submitted_at) {
            return redirect()->route('ept-online.attempt.finished', $this->attemptRouteParams($attempt));
        }

        $redirectUrl = $this->advanceOrFinalize($attempt);

        return redirect()->to($redirectUrl);
    }

    public function ping(EptOnlineAttempt $attempt, Request $request): JsonResponse
    {
        $this->authorizeAttempt($attempt, $request);
        app(EptOnlineAttemptFinalizer::class)->catchUpExpiredAttempt($attempt);
        $attempt->refresh();

        if ($attempt->status === EptOnlineAttempt::STATUS_SUBMITTED || $attempt->submitted_at) {
            return response()->json([
                'expired' => true,
                'redirect' => route('ept-online.attempt.finished', $this->attemptRouteParams($attempt)),
            ]);
        }

        $section = $this->currentSection($attempt);
        if (! $section) {
            return response()->json([
                'expired' => true,
                'redirect' => route('ept-online.index'),
            ]);
        }

        $data = $request->validate([
            'audio_position' => ['nullable', 'numeric', 'min:0'],
            'audio_playing' => ['nullable', 'boolean'],
        ]);

        $this->ensureSectionTiming($attempt, $section);

        if ($redirectUrl = $this->handleExpiredSection($attempt, $section)) {
            return response()->json([
                'expired' => true,
                'redirect' => $redirectUrl,
            ]);
        }

        if ($section->type === EptOnlineSection::TYPE_LISTENING) {
            $audioPosition = $this->normalizeAudioPosition($data['audio_position'] ?? null);
            $audioPlaying = array_key_exists('audio_playing', $data)
                ? (bool) $data['audio_playing']
                : null;

            $this->storeSectionAudioState($attempt, $section->type, $audioPosition, $audioPlaying);
        }

        return response()->json(['ok' => true]);
    }

    public function integrity(EptOnlineAttempt $attempt, Request $request): JsonResponse
    {
        $this->authorizeAttempt($attempt, $request);

        if ($attempt->status === EptOnlineAttempt::STATUS_SUBMITTED || $attempt->submitted_at) {
            return response()->json([
                'ok' => true,
                'submitted' => true,
                'redirect' => route('ept-online.attempt.finished', $this->attemptRouteParams($attempt)),
            ]);
        }

        $data = $request->validate([
            'event' => ['required', 'string', 'max:80'],
            'context' => ['nullable', 'array'],
        ]);

        $context = is_array($data['context'] ?? null) ? $data['context'] : [];
        $response = ['ok' => true];

        if ($data['event'] === 'tab_switch_violation') {
            $count = $this->recordTabSwitchViolation($attempt, $context);

            $response['tab_switch_count'] = $count;
            $response['tab_switch_limit'] = EptOnlineAttempt::TAB_SWITCH_LIMIT;

            if ($count >= EptOnlineAttempt::TAB_SWITCH_LIMIT) {
                app(EptOnlineAttemptFinalizer::class)->finalize($attempt, now(), [
                    'finalized_reason' => 'integrity_tab_switch_limit',
                    'finalized_by' => 'integrity_guard',
                    'tab_switch_count' => $count,
                ]);

                $attempt->refresh();

                $response['submitted'] = true;
                $response['redirect'] = route('ept-online.attempt.finished', $this->attemptRouteParams($attempt));
            }

            return response()->json($response);
        }

        $this->recordIntegrityEvent(
            $attempt,
            $data['event'],
            $context
        );

        return response()->json($response);
    }

    public function finished(EptOnlineAttempt $attempt, Request $request)
    {
        $this->authorizeAttempt($attempt, $request);
        app(EptOnlineAttemptFinalizer::class)->catchUpExpiredAttempt($attempt);
        $attempt->refresh();

        if ($attempt->status !== EptOnlineAttempt::STATUS_SUBMITTED || ! $attempt->submitted_at) {
            return redirect()->route('ept-online.attempt.show', $this->attemptRouteParams($attempt));
        }

        $attempt->load(['form', 'result']);

        return view('ept-online.finished', [
            'attempt' => $attempt,
            'result' => $attempt->result,
        ]);
    }

    private function authorizeAttempt(EptOnlineAttempt $attempt, Request $request): void
    {
        abort_unless($request->user() && (int) $attempt->user_id === (int) $request->user()->id, 403);
    }

    private function shouldShowListeningIntro(EptOnlineAttempt $attempt, EptOnlineSection $section): bool
    {
        return $section->type === EptOnlineSection::TYPE_LISTENING
            && ! $attempt->current_section_started_at;
    }

    private function listeningIntroMeta(EptOnlineSection $section): array
    {
        $meta = is_array($section->meta) ? $section->meta : [];

        return [
            'intro' => is_array($meta['intro'] ?? null) ? $meta['intro'] : [],
            'part_instructions' => is_array($meta['part_instructions'] ?? null) ? $meta['part_instructions'] : [],
            'part_examples' => $this->normalizePartExamples($meta),
        ];
    }

    private function orderedListeningPartKeys(array $introMeta): array
    {
        $partInstructions = is_array($introMeta['part_instructions'] ?? null) ? $introMeta['part_instructions'] : [];
        $partExamples = is_array($introMeta['part_examples'] ?? null) ? $introMeta['part_examples'] : [];

        return collect(['A', 'B', 'C'])
            ->filter(fn (string $part): bool => filled($partInstructions[$part] ?? null) || ! empty($partExamples[$part] ?? []))
            ->values()
            ->all();
    }

    private function normalizePartExamples(array $meta): array
    {
        $partExamples = is_array($meta['part_examples'] ?? null) ? $meta['part_examples'] : [];
        $normalized = [];

        foreach (['A', 'B', 'C'] as $part) {
            $value = $partExamples[$part] ?? null;
            if (! is_array($value) || $value === []) {
                continue;
            }

            if (array_is_list($value)) {
                $items = collect($value)
                    ->filter(fn ($item) => is_array($item) && $item !== [])
                    ->values()
                    ->all();

                if ($items !== []) {
                    $normalized[$part] = $items;
                }

                continue;
            }

            $normalized[$part] = [$value];
        }

        if ($normalized === [] && is_array($meta['example'] ?? null) && $meta['example'] !== []) {
            $normalized['A'] = [$meta['example']];
        }

        return $normalized;
    }

    private function listeningPartTransition(EptOnlineAttempt $attempt, EptOnlineSection $section, Collection $questions, int $currentIndex): ?array
    {
        if ($section->type !== EptOnlineSection::TYPE_LISTENING) {
            return null;
        }

        /** @var EptOnlineQuestion|null $question */
        $question = $questions->get($currentIndex);
        if (! $question || ! filled($question->part_label)) {
            return null;
        }

        $part = strtoupper((string) $question->part_label);
        $previousPart = strtoupper((string) optional($questions->get($currentIndex - 1))->part_label);
        $isFirstQuestionOfPart = $currentIndex === 0 || $previousPart !== $part;

        if (! $isFirstQuestionOfPart) {
            return null;
        }

        $introMeta = $this->listeningIntroMeta($section);
        $instruction = $introMeta['part_instructions'][$part] ?? $question->instruction;
        $examples = $introMeta['part_examples'][$part] ?? [];

        if (! filled($instruction) && $examples === []) {
            return null;
        }

        return [
            'part' => $part,
            'instruction' => $instruction,
            'examples' => $examples,
            'is_acknowledged' => $this->isListeningPartAcknowledged($attempt, $part),
        ];
    }

    private function sectionIntroGate(EptOnlineAttempt $attempt, EptOnlineSection $section, int $currentIndex): ?array
    {
        if ($section->type !== EptOnlineSection::TYPE_STRUCTURE || $currentIndex !== 0) {
            return null;
        }

        if ($this->isSectionIntroAcknowledged($attempt, $section->type)) {
            return null;
        }

        if (! filled($section->title) && ! filled($section->instructions)) {
            return null;
        }

        return [
            'title' => $section->title,
            'instructions' => $section->instructions,
            'is_acknowledged' => false,
        ];
    }

    private function sectionPartTransition(EptOnlineSection $section, Collection $questions, int $currentIndex): ?array
    {
        if ($section->type !== EptOnlineSection::TYPE_STRUCTURE) {
            return null;
        }

        /** @var EptOnlineQuestion|null $question */
        $question = $questions->get($currentIndex);
        if (! $question || ! filled($question->part_label)) {
            return null;
        }

        $part = strtoupper((string) $question->part_label);
        $previousPart = strtoupper((string) optional($questions->get($currentIndex - 1))->part_label);
        $isFirstQuestionOfPart = $currentIndex === 0 || $previousPart !== $part;

        if (! $isFirstQuestionOfPart) {
            return null;
        }

        $meta = is_array($section->meta) ? $section->meta : [];
        $partInstructions = is_array($meta['part_instructions'] ?? null) ? $meta['part_instructions'] : [];
        $fallbackInstruction = optional(
            $questions->first(
                fn (EptOnlineQuestion $candidate): bool => strtoupper((string) $candidate->part_label) === $part
                    && filled($candidate->instruction)
            )
        )->instruction;

        $instruction = $partInstructions[$part] ?? $question->instruction ?? $fallbackInstruction;

        if (! filled($instruction)) {
            return null;
        }

        return [
            'part' => $part,
            'instruction' => $instruction,
        ];
    }

    private function isListeningPartAcknowledged(?EptOnlineAttempt $attempt, string $part): bool
    {
        if (! $attempt) {
            return false;
        }

        $meta = is_array($attempt->meta) ? $attempt->meta : [];
        $parts = is_array($meta['listening_part_intro_seen'] ?? null) ? $meta['listening_part_intro_seen'] : [];

        return (bool) ($parts[strtoupper($part)] ?? false);
    }

    private function isSectionIntroAcknowledged(?EptOnlineAttempt $attempt, string $sectionType): bool
    {
        if (! $attempt) {
            return false;
        }

        $meta = is_array($attempt->meta) ? $attempt->meta : [];
        $sections = is_array($meta['section_intro_seen'] ?? null) ? $meta['section_intro_seen'] : [];

        return (bool) ($sections[$sectionType] ?? false);
    }

    private function markListeningPartAcknowledged(EptOnlineAttempt $attempt, string $part): void
    {
        $meta = is_array($attempt->meta) ? $attempt->meta : [];
        $parts = is_array($meta['listening_part_intro_seen'] ?? null) ? $meta['listening_part_intro_seen'] : [];
        $part = strtoupper($part);

        if (($parts[$part] ?? false) === true) {
            return;
        }

        $parts[$part] = true;
        $meta['listening_part_intro_seen'] = $parts;

        $attempt->forceFill(['meta' => $meta])->save();
    }

    private function markSectionIntroAcknowledged(EptOnlineAttempt $attempt, string $sectionType): void
    {
        $meta = is_array($attempt->meta) ? $attempt->meta : [];
        $sections = is_array($meta['section_intro_seen'] ?? null) ? $meta['section_intro_seen'] : [];

        if (($sections[$sectionType] ?? false) === true) {
            return;
        }

        $sections[$sectionType] = true;
        $meta['section_intro_seen'] = $sections;

        $attempt->forceFill(['meta' => $meta])->save();
    }

    private function orderedSections(EptOnlineAttempt $attempt): Collection
    {
        $attempt->loadMissing([
            'form.sections' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return $attempt->form?->sections
            ? $attempt->form->sections->sortBy('sort_order')->values()
            : collect();
    }

    private function currentSection(EptOnlineAttempt $attempt): ?EptOnlineSection
    {
        $sections = $this->orderedSections($attempt);

        return $sections->firstWhere('type', $attempt->current_section_type)
            ?? $sections->first();
    }

    private function nextSectionType(EptOnlineAttempt $attempt, string $currentType): ?string
    {
        $sections = $this->orderedSections($attempt)->values();
        $currentIndex = $sections->search(fn (EptOnlineSection $section) => $section->type === $currentType);

        if (! is_int($currentIndex)) {
            return null;
        }

        $next = $sections->get($currentIndex + 1);

        return $next?->type;
    }

    private function ensureSectionTiming(EptOnlineAttempt $attempt, EptOnlineSection $section): void
    {
        $dirty = false;

        if (! $attempt->started_at) {
            $attempt->started_at = now();
            $dirty = true;
        }

        if (! $attempt->current_section_started_at) {
            $attempt->current_section_started_at = now();
            $dirty = true;
        }

        if ($attempt->status !== EptOnlineAttempt::STATUS_IN_PROGRESS) {
            $attempt->status = EptOnlineAttempt::STATUS_IN_PROGRESS;
            $dirty = true;
        }

        $newExpiresAt = now()->copy()->setTimestamp(
            $attempt->current_section_started_at->copy()->addMinutes((int) $section->duration_minutes)->timestamp
        );

        if (! $attempt->expires_at || ! $attempt->expires_at->equalTo($newExpiresAt)) {
            $attempt->expires_at = $newExpiresAt;
            $dirty = true;
        }

        if ($dirty) {
            $attempt->save();
        }
    }

    private function remainingSeconds(EptOnlineAttempt $attempt, EptOnlineSection $section): ?int
    {
        if (! $attempt->current_section_started_at || (int) $section->duration_minutes <= 0) {
            return null;
        }

        $deadline = $attempt->current_section_started_at
            ->copy()
            ->addMinutes((int) $section->duration_minutes);

        return max(0, now()->diffInSeconds($deadline, false));
    }

    private function handleExpiredSection(EptOnlineAttempt $attempt, EptOnlineSection $section): ?string
    {
        $remainingSeconds = $this->remainingSeconds($attempt, $section);
        if ($remainingSeconds === null || $remainingSeconds > 0) {
            return null;
        }

        app(EptOnlineAttemptFinalizer::class)->catchUpExpiredAttempt($attempt);
        $attempt->refresh();

        if ($attempt->status === EptOnlineAttempt::STATUS_SUBMITTED || $attempt->submitted_at) {
            return route('ept-online.attempt.finished', $this->attemptRouteParams($attempt));
        }

        return route('ept-online.attempt.show', [
            'attempt' => $attempt->public_id,
            'q' => 0,
        ]);
    }

    private function advanceOrFinalize(EptOnlineAttempt $attempt): string
    {
        $attempt->refresh();
        if ($attempt->status === EptOnlineAttempt::STATUS_SUBMITTED || $attempt->submitted_at) {
            return route('ept-online.attempt.finished', $this->attemptRouteParams($attempt));
        }

        $section = $this->currentSection($attempt);
        if (! $section) {
            return route('ept-online.index');
        }

        $nextType = $this->nextSectionType($attempt, $section->type);

        if ($nextType) {
            $nextSection = $this->orderedSections($attempt)->firstWhere('type', $nextType);
            $duration = (int) ($nextSection?->duration_minutes ?? 0);

            $attempt->forceFill([
                'current_section_type' => $nextType,
                'current_section_started_at' => now(),
                'expires_at' => $duration > 0 ? now()->copy()->addMinutes($duration) : null,
                'status' => EptOnlineAttempt::STATUS_IN_PROGRESS,
            ])->save();

            return route('ept-online.attempt.show', [
                'attempt' => $attempt->public_id,
                'q' => 0,
            ]);
        }

        $this->finalize($attempt);

        return route('ept-online.attempt.finished', $this->attemptRouteParams($attempt));
    }

    private function finalize(EptOnlineAttempt $attempt): void
    {
        app(EptOnlineAttemptFinalizer::class)->finalize($attempt);
    }

    private function redirectResponse(
        string $redirectUrl,
        bool $isAjax,
        int $statusCode = 200,
        ?string $statusText = null,
    ) {
        if ($isAjax) {
            return response()->json([
                'status' => $statusText ? 'redirect' : 'saved',
                'message' => $statusText,
                'redirect' => $redirectUrl,
            ], $statusCode);
        }

        return redirect()->to($redirectUrl);
    }

    private function normalizeAudioPosition(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return max(0, round((float) $value, 3));
    }

    private function formatAudioPositionLabel(?float $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $seconds = max(0, (int) round($value));
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    private function storedSectionAudioPosition(EptOnlineAttempt $attempt, string $sectionType): ?float
    {
        $state = $this->sectionAudioState($attempt, $sectionType);

        return $state['position'];
    }

    private function storedSectionAudioWasPlaying(EptOnlineAttempt $attempt, string $sectionType): bool
    {
        $state = $this->sectionAudioState($attempt, $sectionType);

        return $state['was_playing'];
    }

    private function storeSectionAudioState(
        EptOnlineAttempt $attempt,
        string $sectionType,
        ?float $position,
        ?bool $wasPlaying = null,
    ): void {
        if ($position === null && $wasPlaying === null) {
            return;
        }

        $meta = is_array($attempt->meta) ? $attempt->meta : [];
        $states = is_array($meta['section_audio_state'] ?? null) ? $meta['section_audio_state'] : [];
        $sectionType = strtolower($sectionType);
        $state = is_array($states[$sectionType] ?? null) ? $states[$sectionType] : [];

        if ($position !== null) {
            $state['position'] = $position;
        }

        if ($wasPlaying !== null) {
            $state['was_playing'] = $wasPlaying;
        }

        if ($state === []) {
            return;
        }

        $states[$sectionType] = $state;
        $meta['section_audio_state'] = $states;

        $attempt->forceFill(['meta' => $meta])->save();
    }

    private function sectionAudioState(EptOnlineAttempt $attempt, string $sectionType): array
    {
        $meta = is_array($attempt->meta) ? $attempt->meta : [];
        $states = is_array($meta['section_audio_state'] ?? null) ? $meta['section_audio_state'] : [];
        $state = is_array($states[strtolower($sectionType)] ?? null) ? $states[strtolower($sectionType)] : [];

        return [
            'position' => $this->normalizeAudioPosition($state['position'] ?? null),
            'was_playing' => (bool) ($state['was_playing'] ?? false),
        ];
    }

    private function attemptAudioUrl(EptOnlineAttempt $attempt, EptOnlineSection $section): ?string
    {
        if (! $this->resolveAttemptAudioFile($attempt, $section)) {
            return null;
        }

        $expiresAt = now()->addMinutes(max(15, ((int) $section->duration_minutes) + 30));

        return app(UrlGenerator::class)->temporarySignedRoute(
            'ept-online.attempt.audio',
            $expiresAt,
            $this->attemptRouteParams($attempt),
        );
    }

    private function resolveAttemptAudioFile(EptOnlineAttempt $attempt, EptOnlineSection $section): ?array
    {
        $audioPath = trim((string) ($section->audio_path ?: $attempt->form?->listening_audio_path));
        if ($audioPath === '' || Str::startsWith($audioPath, ['http://', 'https://'])) {
            return null;
        }

        foreach ($this->attemptAudioDisks() as $diskName) {
            $disk = Storage::disk($diskName);

            if (! $disk->exists($audioPath)) {
                continue;
            }

            return [
                'disk' => $diskName,
                'absolute_path' => $this->filesystemAbsolutePath($disk, $audioPath),
                'mime_type' => $disk->mimeType($audioPath) ?: 'audio/mpeg',
            ];
        }

        return null;
    }

    private function attemptAudioDisks(): array
    {
        $defaultDisk = (string) config('filament.default_filesystem_disk', config('filesystems.default', 'local'));

        return collect([$defaultDisk, 'local', 'public'])
            ->filter(fn (string $disk): bool => $disk !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function filesystemAbsolutePath(Filesystem $disk, string $path): string
    {
        if (method_exists($disk, 'path')) {
            return $disk->path($path);
        }

        abort(500, 'Audio filesystem path resolver is not supported.');
    }

    private function recordIntegrityEvent(EptOnlineAttempt $attempt, string $event, array $context = []): array
    {
        $eventKey = Str::of($event)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        if ($eventKey === '') {
            return [];
        }

        $flags = is_array($attempt->integrity_flags) ? $attempt->integrity_flags : [];
        $entry = is_array($flags[$eventKey] ?? null) ? $flags[$eventKey] : [];

        if (! isset($entry['first_at'])) {
            $entry['first_at'] = now()->toIso8601String();
        }

        $entry['count'] = (int) ($entry['count'] ?? 0) + 1;
        $entry['last_at'] = now()->toIso8601String();

        $sanitizedContext = $this->sanitizeIntegrityContext($context);
        if ($sanitizedContext !== []) {
            $entry['last_context'] = $sanitizedContext;
        }

        $flags[$eventKey] = $entry;

        $attempt->forceFill([
            'integrity_flags' => $flags,
        ])->save();

        return $entry;
    }

    private function recordTabSwitchViolation(EptOnlineAttempt $attempt, array $context = []): int
    {
        $cycleId = trim((string) ($context['cycle_id'] ?? ''));
        $meta = is_array($attempt->meta) ? $attempt->meta : [];
        $seenCycleIds = collect(is_array($meta['tab_switch_cycles'] ?? null) ? $meta['tab_switch_cycles'] : [])
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();

        if ($cycleId !== '' && in_array($cycleId, $seenCycleIds, true)) {
            $flags = is_array($attempt->integrity_flags) ? $attempt->integrity_flags : [];
            $entry = is_array($flags['tab_switch_violation'] ?? null) ? $flags['tab_switch_violation'] : [];

            return (int) ($entry['count'] ?? 0);
        }

        $entry = $this->recordIntegrityEvent($attempt, 'tab_switch_violation', $context);

        if ($cycleId !== '') {
            $seenCycleIds[] = $cycleId;
            $meta['tab_switch_cycles'] = array_slice(array_values(array_unique($seenCycleIds)), -25);
            $attempt->forceFill(['meta' => $meta])->save();
        }

        return (int) ($entry['count'] ?? 0);
    }

    private function sanitizeIntegrityContext(array $context): array
    {
        $allowedKeys = [
            'page',
            'section',
            'key',
            'shortcut',
            'visibility',
            'reason',
            'width_gap',
            'height_gap',
            'user_agent',
            'source',
            'audio_state',
            'tag',
            'cycle_id',
            'hidden_ms',
        ];

        return collect($context)
            ->only($allowedKeys)
            ->map(function (mixed $value): mixed {
                if (is_bool($value) || is_numeric($value)) {
                    return $value;
                }

                if (! is_scalar($value)) {
                    return null;
                }

                return Str::limit((string) $value, 160, '');
            })
            ->reject(fn (mixed $value): bool => $value === null || $value === '')
            ->all();
    }

    private function attemptRouteParams(EptOnlineAttempt $attempt, array $extra = []): array
    {
        return array_merge([
            'attempt' => $attempt->public_id,
        ], $extra);
    }
}
