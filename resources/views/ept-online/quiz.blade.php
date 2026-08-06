@extends('layouts.front')
@section('title', 'EPT Online')
@section('hide_navbar', '1')
@section('hide_footer', '1')
@section('translate_no', '1')

@include('ept-online.partials.mobile-device-guard')
@include('ept-online.partials.exam-guard')

@push('styles')
<style>
    body { padding-top: 0 !important; background: #f8fafc; }
    .exam-shell { min-height: 100vh; background: #f8fafc; }
    .exam-topbar {
        position: fixed; inset: 0 0 auto 0; z-index: 50; height: 76px;
        backdrop-filter: blur(14px);
        background: rgba(255,255,255,0.9);
        border-bottom: 1px solid #e2e8f0;
        box-shadow: 0 10px 30px rgba(15,23,42,.06);
    }
    .exam-content { width: 100%; padding: 96px 24px 48px; }
    .timer-pill {
        display: inline-flex; align-items: center; gap: .5rem;
        border-radius: 999px; padding: .55rem .95rem; font-weight: 800;
        font-variant-numeric: tabular-nums;
        background: #fff7ed; color: #c2410c; border: 1px solid #fdba74;
    }
    .question-grid {
        display: grid; gap: .5rem;
        grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
    }
    .qbox {
        display: flex; align-items: center; justify-content: center;
        height: 36px; border-radius: 10px; font-weight: 800; font-size: .78rem;
        text-decoration: none; border: 1px solid #cbd5e1; color: #475569;
        background: white; transition: all .18s ease;
    }
    .qbox.current { border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,.12); color: #0f766e; }
    .qbox.answered { background: #ecfdf5; border-color: #6ee7b7; color: #047857; }
    .qbox.current.answered { border-color: #0f766e; color: #0f766e; }
    .qbox.unanswered { background: #fff7ed; border-color: #fdba74; color: #c2410c; }
    .option-btn {
        width: 100%; text-align: left; padding: 1rem 1rem; border-radius: 18px;
        border: 1px solid #cbd5e1; background: white; transition: all .18s ease;
    }
    .option-btn:hover { border-color: #0f766e; transform: translateY(-1px); box-shadow: 0 12px 24px rgba(15,23,42,.06); }
    .option-btn.selected { border-color: #0f766e; background: #f0fdfa; box-shadow: 0 0 0 3px rgba(15,118,110,.10); }
    .listening-question-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        background: #f8fafc;
        padding: .95rem 1rem;
    }
    .listening-question-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid #99f6e4;
        background: #ecfdf5;
        padding: .45rem .8rem;
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
        color: #0f766e;
    }
    .audio-mini-btn {
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 999px; border: 1px solid #cbd5e1; background: white;
        padding: .55rem .95rem; font-size: .75rem; font-weight: 800;
        letter-spacing: .14em; text-transform: uppercase; color: #0f172a;
        transition: all .18s ease;
    }
    .audio-mini-btn:hover { background: #f8fafc; border-color: #94a3b8; }
    .reading-nav-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        background: white;
        padding: .55rem .95rem;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #0f172a;
        transition: all .18s ease;
    }
    .reading-nav-toggle:hover { background: #f8fafc; border-color: #94a3b8; }
    .reading-nav-drawer {
        position: fixed;
        top: 88px;
        right: 24px;
        z-index: 70;
        width: min(340px, calc(100vw - 32px));
        max-height: calc(100vh - 112px);
        overflow: auto;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        background: rgba(255,255,255,0.98);
        box-shadow: 0 24px 80px rgba(15,23,42,.18);
        backdrop-filter: blur(16px);
    }
    .reading-nav-overlay {
        position: fixed;
        inset: 76px 0 0 0;
        z-index: 65;
        background: rgba(15,23,42,.18);
    }
    .reading-passage-wrap {
        overflow-x: hidden;
        overflow-y: visible;
    }
    .reading-passage-frame {
        min-width: 0;
        width: 100%;
        padding-left: 1rem;
    }
    .reading-passage-label {
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
        color: #64748b;
    }
    .reading-line {
        position: relative;
        font-family: Georgia, "Times New Roman", ui-serif, serif;
        font-size: .98rem;
        line-height: 1.72;
        color: #334155;
    }
    .reading-line.paragraph-gap {
        min-height: 1rem;
    }
    .reading-line-number {
        user-select: none;
        position: absolute;
        left: -1rem;
        top: 0;
        width: .75rem;
        text-align: right;
        font-weight: 600;
        color: rgba(148, 163, 184, .72);
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        font-size: .72rem;
        line-height: 1.72;
    }
    .reading-line-text {
        display: block;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: normal;
    }
    [data-reading-mode="1"] #questionStage {
        border: 0;
        background: transparent;
        box-shadow: none;
        padding: 0;
    }
    [data-reading-mode="1"] .reading-flat-bar {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
        padding: .9rem 1.1rem;
    }
    [data-reading-mode="1"] .reading-flat-directions {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
        padding: .95rem 1.1rem;
        font-size: .92rem;
        line-height: 1.7;
        color: #475569;
    }
    [data-reading-mode="1"] .reading-passage-panel,
    [data-reading-mode="1"] .reading-question-panel {
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        background: #fff;
        box-shadow: none;
        padding: 1rem;
    }
    [data-reading-mode="1"] .reading-question-title {
        font-size: 1.2rem;
        line-height: 1.45;
    }
    [data-reading-mode="1"] .option-btn {
        padding: .85rem .9rem;
        border-radius: 14px;
    }
    [data-reading-mode="1"] .option-btn > div {
        gap: .75rem;
    }
    [data-reading-mode="1"] .option-btn > div > span:first-child {
        width: 2.05rem;
        height: 2.05rem;
        font-size: .82rem;
    }
    [data-reading-mode="1"] .option-btn > div > span:last-child {
        padding-top: 0;
        font-size: .95rem;
        line-height: 1.55;
    }
    @media (min-width: 1280px) {
        .exam-content { padding-left: 32px; padding-right: 32px; }
    }
    @media (max-width: 1279px) {
        .reading-nav-drawer {
            top: 84px;
            right: 16px;
            width: min(360px, calc(100vw - 24px));
            max-height: calc(100vh - 104px);
        }
        .reading-nav-overlay {
            inset-top: 76px;
        }
    }
</style>
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    $progressPercent = round((($currentIndex + 1) / max(1, $questions->count())) * 100, 2);
    $isLastQuestion = $currentIndex >= $questions->count() - 1;
    $autoplayAudio = $autoplayAudio ?? false;
    $audioSrc = $audioUrl ?? null;
    $audioStartAt = is_numeric($audioStartAt ?? null) ? max(0, (float) $audioStartAt) : null;
    $resumeAudio = (bool) ($resumeAudio ?? false);
    $listeningIntro = is_array($section->meta['intro'] ?? null) ? $section->meta['intro'] : [];
    $listeningIntroText = $listeningIntro['text'] ?? $section->instructions;
    $hideSectionIntroAnswers = ($sectionIntroGate ?? null) && !($sectionIntroGate['is_acknowledged'] ?? false);
    $charLength = static fn (string $value): int => function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    $charSlice = static fn (string $value, int $start, ?int $length = null): string => function_exists('mb_substr')
        ? mb_substr($value, $start, $length)
        : ($length === null ? substr($value, $start) : substr($value, $start, $length));
    $normalizeTextMarkers = static function (?string $value): string {
        $text = trim((string) $value);

        $text = preg_replace('/\[(?:p|para)\]/i', "\n\n", $text) ?? $text;
        $text = preg_replace('/\[(?:br|lb)\]/i', "\n", $text) ?? $text;

        return str_replace(["\r\n", "\r"], "\n", $text);
    };
    $applyTextMarkup = static function (?string $value) use ($normalizeTextMarkers): string {
        $escaped = e($normalizeTextMarkers($value));

        return preg_replace('/\[u\](.*?)\[\/u\]/is', '<u>$1</u>', $escaped) ?? $escaped;
    };
    $formatMultiline = static fn (?string $value): string => nl2br($applyTextMarkup($value));
    $formatInline = static fn (?string $value): string => $applyTextMarkup($value);
    $wrapPassageLines = static function (?string $value, int $maxChars = 74) use ($charLength, $charSlice, $normalizeTextMarkers): array {
        $rawValue = (string) $value;
        $hasManualLineMarkers = preg_match('/\[(?:br|lb|p|para)\]/i', $rawValue) === 1;
        $text = $normalizeTextMarkers($rawValue);

        if ($text === '') {
            return [];
        }

        $paragraphs = preg_split("/\n{2,}/u", $text) ?: [];
        $lines = [];
        $lastParagraphIndex = count($paragraphs) - 1;

        foreach ($paragraphs as $paragraphIndex => $paragraph) {
            $hardLines = preg_split("/\n/u", trim($paragraph)) ?: [];

            foreach ($hardLines as $hardLine) {
                $hardLine = trim(preg_replace('/\s+/u', ' ', $hardLine) ?? $hardLine);

                if ($hardLine === '') {
                    continue;
                }

                if ($hasManualLineMarkers) {
                    $lines[] = $hardLine;
                    continue;
                }

                $words = preg_split('/\s+/u', $hardLine) ?: [];
                $currentLine = '';

                foreach ($words as $word) {
                    $word = trim((string) $word);

                    if ($word === '') {
                        continue;
                    }

                    while ($charLength($word) > $maxChars) {
                        if ($currentLine !== '') {
                            $lines[] = $currentLine;
                            $currentLine = '';
                        }

                        $lines[] = $charSlice($word, 0, $maxChars);
                        $word = ltrim($charSlice($word, $maxChars));
                    }

                    if ($currentLine === '') {
                        $currentLine = $word;
                        continue;
                    }

                    $candidate = $currentLine . ' ' . $word;

                    if ($charLength($candidate) <= $maxChars) {
                        $currentLine = $candidate;
                        continue;
                    }

                    $lines[] = $currentLine;
                    $currentLine = $word;
                }

                if ($currentLine !== '') {
                    $lines[] = $currentLine;
                }
            }

            if ($paragraphIndex < $lastParagraphIndex) {
                $lines[] = null;
            }
        }

        return $lines;
    };
    $readingPassageLines = $question->passage && $section->type === \App\Models\EptOnlineSection::TYPE_READING
        ? $wrapPassageLines($question->passage->content)
        : [];
    $displayQuestionNumber = $question->number_in_section ?: ($currentIndex + 1);
    $passageQuestionNumbers = collect();
    if ($question->passage && $section->type === \App\Models\EptOnlineSection::TYPE_READING) {
        $passageQuestionNumbers = $questions
            ->filter(fn ($candidate) => $candidate->passage_id === $question->passage_id)
            ->pluck('number_in_section')
            ->filter()
            ->sort()
            ->values();
    }
    $readingPassageLabel = null;
    if ($passageQuestionNumbers->isNotEmpty()) {
        $firstPassageQuestion = $passageQuestionNumbers->first();
        $lastPassageQuestion = $passageQuestionNumbers->last();
        $readingPassageLabel = $firstPassageQuestion === $lastPassageQuestion
            ? 'This passage is for question ' . $firstPassageQuestion . '.'
            : 'This passage is for questions ' . $firstPassageQuestion . '-' . $lastPassageQuestion . '.';
    }

    $hideListeningAnswers = $section->type === 'listening'
        && $listeningPartTransition
        && !($listeningPartTransition['is_acknowledged'] ?? false);
    $isReadingSplitLayout = $section->type === \App\Models\EptOnlineSection::TYPE_READING
        && ! $hideSectionIntroAnswers
        && ! $hideListeningAnswers;
@endphp

<div
    class="exam-shell"
    data-exam-guard
    data-reading-mode="{{ $isReadingSplitLayout ? '1' : '0' }}"
    data-integrity-url="{{ route('ept-online.attempt.integrity', ['attempt' => $attempt->public_id]) }}"
    data-integrity-page="quiz"
    data-integrity-section="{{ $section->type }}"
    data-tab-switch-guard="1"
    data-tab-switch-limit="{{ \App\Models\EptOnlineAttempt::TAB_SWITCH_LIMIT }}"
>
    <div class="exam-topbar">
        <div class="flex h-full items-center justify-between gap-4 px-4 lg:px-8">
            <div class="min-w-0" id="examMeta">
                <div class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">EPT Online</div>
                <div class="truncate text-sm font-bold text-slate-900 sm:text-base">{{ $attempt->form?->title ?? 'Online Test Package' }}</div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">{{ strtoupper($section->type) }} • Question {{ $currentIndex + 1 }}/{{ $questions->count() }}</div>
            </div>

            <div class="flex items-center gap-3">
                @if ($isReadingSplitLayout)
                    <button type="button" id="readingNavToggle" class="reading-nav-toggle">
                        Questions
                    </button>
                @endif

                @if ($audioSrc && $section->type === 'listening')
                    <button type="button" id="audioPlayButton" class="audio-mini-btn hidden">
                        Play Audio
                    </button>
                @endif

                @if (!is_null($remainingSeconds))
                    <div class="timer-pill" id="timerBadge">
                        <span>Time</span>
                        <span id="timerText">--:--</span>
                    </div>
                @endif
            </div>
        </div>
        <div class="h-1 w-full bg-slate-100">
            <div id="examProgressBar" class="h-full bg-gradient-to-r from-emerald-500 to-sky-500 transition-all" style="width: {{ $progressPercent }}%"></div>
        </div>
    </div>

    <div class="exam-content">
        @if ($isReadingSplitLayout)
            <div id="readingNavOverlay" class="reading-nav-overlay hidden"></div>
            <div id="readingNavDrawer" class="reading-nav-drawer hidden">
                <div id="questionSidebar" class="rounded-[28px] p-4">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Questions</div>
                        <div class="text-xs font-semibold text-slate-500">{{ $questions->count() - $unansweredCount }}/{{ $questions->count() }}</div>
                    </div>
                    <div class="question-grid mt-4">
                        @foreach ($questions as $i => $navQuestion)
                            @php
                                $classes = ['qbox'];
                                $classes[] = in_array($navQuestion->id, $answeredIds, true) ? 'answered' : 'unanswered';
                                if ($i === $currentIndex) {
                                    $classes[] = 'current';
                                }
                            @endphp
                            <a href="{{ route('ept-online.attempt.show', ['attempt' => $attempt->public_id, 'q' => $i]) }}" class="{{ implode(' ', $classes) }}" data-attempt-nav="1">
                                {{ $i + 1 }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 {{ $isReadingSplitLayout ? 'xl:grid-cols-[minmax(0,1fr)]' : 'xl:grid-cols-[264px_minmax(0,1fr)]' }}">
            @unless($isReadingSplitLayout)
                <aside class="space-y-6">
                    <div id="questionSidebar" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Questions</div>
                            <div class="text-xs font-semibold text-slate-500">{{ $questions->count() - $unansweredCount }}/{{ $questions->count() }}</div>
                        </div>
                        <div class="question-grid mt-4">
                            @foreach ($questions as $i => $navQuestion)
                                @php
                                    $classes = ['qbox'];
                                    $classes[] = in_array($navQuestion->id, $answeredIds, true) ? 'answered' : 'unanswered';
                                    if ($i === $currentIndex) {
                                        $classes[] = 'current';
                                    }
                                @endphp
                                <a href="{{ route('ept-online.attempt.show', ['attempt' => $attempt->public_id, 'q' => $i]) }}" class="{{ implode(' ', $classes) }}" data-attempt-nav="1">
                                    {{ $i + 1 }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </aside>
            @endunless

            <main class="space-y-6">
                @if ($audioSrc && $section->type === 'listening')
                    <audio id="listeningAudio" class="hidden" preload="auto" playsinline>
                        <source src="{{ $audioSrc }}" type="audio/mpeg">
                    </audio>
                @endif

                <div id="questionStage" class="{{ $isReadingSplitLayout ? 'space-y-4' : 'rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8' }}">
                    @if ($showAudioRecoveryPrompt ?? false)
                        <div id="audioRecoveryCard" class="mb-5 rounded-[24px] border border-amber-200 bg-amber-50 px-5 py-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="text-xs font-black uppercase tracking-[0.18em] text-amber-700">Session Restored</div>
                                    <div class="mt-1 text-sm leading-6 text-amber-900">
                                        The last saved audio position was {{ $audioRecoveryLabel ?? 'the previous position' }}. Press the button to continue from that point.
                                    </div>
                                </div>
                                <button type="button" id="audioResumeButton" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                                    Resume Audio
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($hideSectionIntroAnswers)
                        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-6">
                            @if ($section->title)
                                <div class="text-2xl font-black tracking-tight text-slate-950">
                                    {{ $section->title }}
                                </div>
                            @endif

                            @if ($section->instructions)
                                <div class="mt-4 text-sm leading-7 text-slate-700">{!! $formatMultiline($section->instructions) !!}</div>
                            @endif

                            <form method="POST" action="{{ route('ept-online.attempt.section-intro', ['attempt' => $attempt->public_id]) }}" class="mt-6" data-attempt-ajax="1">
                                @csrf
                                <input type="hidden" name="q" value="{{ $currentIndex }}">
                                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                                    Start Answering
                                </button>
                            </form>
                        </div>
                    @elseif ($hideListeningAnswers && $listeningPartTransition)
                        @php($transitionExamples = $listeningPartTransition['examples'] ?? [])
                        @if ($currentIndex === 0 && ($section->title || $listeningIntroText))
                            <div class="mb-5 rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                                @if ($section->title)
                                    <div class="mt-3 text-2xl font-bold tracking-tight text-slate-900">
                                        {{ $section->title }}
                                    </div>
                                @endif

                                @if ($listeningIntroText)
                                    <div class="mt-4 text-sm leading-7 text-slate-700">{!! $formatMultiline($listeningIntroText) !!}</div>
                                @endif
                            </div>
                        @endif

                        <div class="rounded-[24px] border border-emerald-200 bg-emerald-50/60 p-5">
                            <div class="flex items-center justify-between gap-3 border-b border-emerald-200 pb-4">
                                <div>
                                    <div class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Part {{ $listeningPartTransition['part'] }}</div>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700">
                                    New Part
                                </span>
                            </div>

                            @if ($listeningPartTransition['instruction'] ?? null)
                                <div class="mt-4 text-sm leading-7 text-slate-700">{!! $formatMultiline($listeningPartTransition['instruction']) !!}</div>
                            @endif

                            @foreach ($transitionExamples as $index => $example)
                                @if (($example['audio_text'] ?? null) || ($example['book_text'] ?? null) || ($example['explanation'] ?? null))
                                    <div class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 p-4">
                                        <div class="text-xs font-black uppercase tracking-[0.18em] text-sky-700">
                                            {{ $example['title'] ?? ('Example ' . ($index + 1)) }}
                                        </div>

                                        @if ($example['audio_text'] ?? null)
                                            <div class="mt-3">
                                                <div class="text-xs font-black uppercase tracking-[0.18em] text-sky-700">
                                                    {{ $example['audio_label'] ?? 'On the recording, you will hear:' }}
                                                </div>
                                                <div class="mt-2 text-sm leading-7 text-slate-700">{!! $formatMultiline($example['audio_text']) !!}</div>
                                            </div>
                                        @endif

                                        @if ($example['book_text'] ?? null)
                                            <div class="mt-4">
                                                <div class="text-xs font-black uppercase tracking-[0.18em] text-sky-700">
                                                    {{ $example['book_label'] ?? 'In your test book, you will read:' }}
                                                </div>
                                                <div class="mt-2 text-sm leading-7 text-slate-700">{!! $formatMultiline($example['book_text']) !!}</div>
                                            </div>
                                        @endif

                                        @if ($example['explanation'] ?? null)
                                            <div class="mt-4 rounded-2xl border border-sky-200 bg-white px-4 py-3 text-sm leading-7 text-slate-700">{!! $formatMultiline($example['explanation']) !!}</div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach

                            @if ($hideListeningAnswers)
                                <form method="POST" action="{{ route('ept-online.attempt.part-intro', ['attempt' => $attempt->public_id]) }}" class="mt-5" data-preserve-audio="1" data-attempt-ajax="1">
                                    @csrf
                                    <input type="hidden" name="part" value="{{ $listeningPartTransition['part'] }}">
                                    <input type="hidden" name="q" value="{{ $currentIndex }}">
                                    <input type="hidden" name="audio_position" value="" data-audio-position>
                                    <input type="hidden" name="audio_playing" value="0" data-audio-playing>
                                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                                        {{ $currentIndex === 0 ? 'Start Question 1' : 'Show Question ' . ($currentIndex + 1) }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        @if ($section->type !== 'listening' && $section->title && ($section->type !== 'reading' || $currentIndex === 0))
                            <div class="{{ $section->type === 'reading' ? 'reading-flat-bar text-sm font-medium uppercase tracking-[0.02em] text-slate-700' : 'rounded-[24px] border border-slate-200 bg-slate-50 px-5 py-4 text-base font-medium uppercase tracking-[0.02em] text-slate-700' }}">
                                {{ \Illuminate\Support\Str::upper($section->title) }}{{ $section->duration_minutes ? ' - TIME (' . $section->duration_minutes . ' Minutes)' : '' }}
                            </div>
                        @endif

                        @if (($sectionPartTransition['instruction'] ?? null) && $section->type === 'structure')
                            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                                {!! $formatMultiline($sectionPartTransition['instruction']) !!}
                            </div>
                        @elseif ($section->type !== 'listening' && $question->instruction && ($section->type !== 'reading' || $currentIndex === 0))
                            <div class="mt-5 {{ $section->type === 'reading' ? 'reading-flat-directions' : 'rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900' }}">
                                {!! $formatMultiline($question->instruction) !!}
                            </div>
                        @elseif ($section->instructions && $section->type === 'reading' && $currentIndex === 0)
                            <div class="mt-5 reading-flat-directions">
                                {!! nl2br(e($section->instructions)) !!}
                            </div>
                        @endif
                    @endif

                    @unless($hideSectionIntroAnswers || $hideListeningAnswers)
                        @if ($isReadingSplitLayout)
                            <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.22fr)_minmax(360px,0.78fr)]">
                                <div class="reading-passage-panel xl:max-h-[calc(100vh-150px)] xl:overflow-y-auto">
                                    @if ($question->passage)
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="reading-passage-label">{{ $readingPassageLabel ?? 'Read the passage below.' }}</div>
                                        </div>
                                        @if ($readingPassageLines !== [])
                                            <div class="reading-passage-wrap mt-3">
                                                <div class="reading-passage-frame space-y-1">
                                                    @php($displayLine = 0)
                                                    @foreach ($readingPassageLines as $lineText)
                                                        @if ($lineText === null)
                                                            <div class="reading-line paragraph-gap" aria-hidden="true">
                                                                <div class="reading-line-number"></div>
                                                                <div class="reading-line-text"></div>
                                                            </div>
                                                        @else
                                                            @php($displayLine++)
                                                            <div class="reading-line">
                                                                <div class="reading-line-number">{{ $displayLine % 5 === 0 ? $displayLine : '' }}</div>
                                                                <div class="reading-line-text">{!! $formatInline($lineText) !!}</div>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <div class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700 sm:text-[15px]">{{ $question->passage->content }}</div>
                                        @endif
                                    @endif
                                </div>

                                <div class="reading-question-panel xl:max-h-[calc(100vh-150px)] xl:overflow-y-auto">
                                    <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">
                                        Question {{ $currentIndex + 1 }} of {{ $questions->count() }}
                                    </div>
                                    <div class="reading-question-title mt-3 font-bold text-slate-900">
                                        {!! $formatMultiline($question->prompt) !!}
                                    </div>

                                    <form method="POST" action="{{ route('ept-online.attempt.answer', ['attempt' => $attempt->public_id]) }}" class="mt-6 space-y-3" id="answerForm" data-preserve-audio="1" data-attempt-ajax="1" data-attempt-answer-form="1">
                                        @csrf
                                        <input type="hidden" name="question_id" value="{{ $question->id }}">
                                        <input type="hidden" name="q" value="{{ $currentIndex }}">
                                        <input type="hidden" name="selected_answer" value="" data-selected-answer>
                                        <input type="hidden" name="audio_position" value="" data-audio-position>
                                        <input type="hidden" name="audio_playing" value="0" data-audio-playing>

                                        @foreach (['A' => $question->option_a, 'B' => $question->option_b, 'C' => $question->option_c, 'D' => $question->option_d] as $key => $text)
                                            <button type="submit" name="answer" value="{{ $key }}" class="option-btn {{ ($answer->selected_option ?? null) === $key ? 'selected' : '' }}">
                                                <div class="flex items-start gap-4">
                                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-sm font-black text-slate-700">{{ $key }}</span>
                                                    <span class="pt-1 text-left text-sm leading-6 text-slate-700 sm:text-base">{!! $formatMultiline($text) !!}</span>
                                                </div>
                                            </button>
                                        @endforeach
                                    </form>

                                    <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6">
                                        @if ($currentIndex > 0)
                                            <a href="{{ route('ept-online.attempt.show', ['attempt' => $attempt->public_id, 'q' => $currentIndex - 1]) }}" class="inline-flex items-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50" data-attempt-nav="1">
                                                Previous Question
                                            </a>
                                        @else
                                            <span></span>
                                        @endif

                                        @if ($isLastQuestion)
                                            <form method="POST" action="{{ route('ept-online.attempt.progress', ['attempt' => $attempt->public_id]) }}" data-allow-unload="1" data-section-progress-form="1" onsubmit="return confirmSectionProgress({{ $unansweredCount }}, {{ $nextSectionType ? 'true' : 'false' }}, @js($section->type));">
                                                @csrf
                                                <button type="submit" data-allow-unload="1" class="inline-flex items-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                                                    {{ $nextSectionType ? 'Finish Section & Continue' : 'Finish & Submit' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            @if ($question->passage)
                                <div class="mt-6 rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                                    <div class="reading-passage-label">{{ $readingPassageLabel ?? 'Read the passage below.' }}</div>
                                    @if ($readingPassageLines !== [])
                                        <div class="reading-passage-wrap mt-4">
                                            <div class="reading-passage-frame space-y-1">
                                                @php($displayLine = 0)
                                                @foreach ($readingPassageLines as $lineText)
                                                    @if ($lineText === null)
                                                        <div class="reading-line paragraph-gap" aria-hidden="true">
                                                            <div class="reading-line-number"></div>
                                                            <div class="reading-line-text"></div>
                                                        </div>
                                                    @else
                                                        @php($displayLine++)
                                                        <div class="reading-line">
                                                            <div class="reading-line-number">{{ $displayLine % 5 === 0 ? $displayLine : '' }}</div>
                                                            <div class="reading-line-text">{!! $formatInline($lineText) !!}</div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700 sm:text-[15px]">{{ $question->passage->content }}</div>
                                    @endif
                                </div>
                            @endif

                            @if ($section->type !== 'listening')
                                <div class="mt-7 text-xl font-bold leading-snug text-slate-900">
                                    {!! $formatMultiline($question->prompt) !!}
                                </div>
                            @else
                                <div class="listening-question-banner">
                                    <div>
                                        <div class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Listening Question</div>
                                        <div class="mt-2 text-2xl font-black tracking-tight text-slate-900">Question {{ $displayQuestionNumber }}</div>
                                    </div>

                                    @if ($question->part_label)
                                        <span class="listening-question-pill">Part {{ strtoupper((string) $question->part_label) }}</span>
                                    @endif
                                </div>
                            @endif

                            <form method="POST" action="{{ route('ept-online.attempt.answer', ['attempt' => $attempt->public_id]) }}" class="mt-{{ $section->type === 'listening' ? '7' : '6' }} space-y-3" id="answerForm" data-preserve-audio="1" data-attempt-ajax="1" data-attempt-answer-form="1">
                                @csrf
                                <input type="hidden" name="question_id" value="{{ $question->id }}">
                                <input type="hidden" name="q" value="{{ $currentIndex }}">
                                <input type="hidden" name="selected_answer" value="" data-selected-answer>
                                <input type="hidden" name="audio_position" value="" data-audio-position>
                                <input type="hidden" name="audio_playing" value="0" data-audio-playing>

                                @foreach (['A' => $question->option_a, 'B' => $question->option_b, 'C' => $question->option_c, 'D' => $question->option_d] as $key => $text)
                                    <button type="submit" name="answer" value="{{ $key }}" class="option-btn {{ ($answer->selected_option ?? null) === $key ? 'selected' : '' }}">
                                        <div class="flex items-start gap-4">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-sm font-black text-slate-700">{{ $key }}</span>
                                            <span class="pt-1 text-left text-sm leading-6 text-slate-700 sm:text-base">{!! $formatMultiline($text) !!}</span>
                                        </div>
                                    </button>
                                @endforeach
                            </form>

                            <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6">
                                @if ($currentIndex > 0)
                                    <a href="{{ route('ept-online.attempt.show', ['attempt' => $attempt->public_id, 'q' => $currentIndex - 1]) }}" class="inline-flex items-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50" data-attempt-nav="1">
                                        Previous Question
                                    </a>
                                @else
                                    <span></span>
                                @endif

                                @if ($isLastQuestion)
                                    <form method="POST" action="{{ route('ept-online.attempt.progress', ['attempt' => $attempt->public_id]) }}" data-allow-unload="1" data-section-progress-form="1" onsubmit="return confirmSectionProgress({{ $unansweredCount }}, {{ $nextSectionType ? 'true' : 'false' }}, @js($section->type));">
                                        @csrf
                                        <button type="submit" data-allow-unload="1" class="inline-flex items-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                                            {{ $nextSectionType ? 'Finish Section & Continue' : 'Finish & Submit' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    @endunless
                </div>
            </main>
        </div>
    </div>
</div>
<div id="sectionExitModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/50 px-4">
    <div class="w-full max-w-lg rounded-[28px] border border-slate-200 bg-white p-6 shadow-2xl">
        <div id="sectionExitBadge" class="text-xs font-black uppercase tracking-[0.18em] text-amber-700">Confirmation</div>
        <h2 id="sectionExitTitle" class="mt-3 text-2xl font-black text-slate-950">Continue?</h2>
        <div class="mt-4 space-y-3 text-sm leading-7 text-slate-700">
            <p id="sectionExitBody">Confirm this action.</p>
            <p id="sectionExitUnanswered" class="{{ $unansweredCount > 0 ? '' : 'hidden' }}"></p>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
            <button type="button" id="sectionExitCancel" class="inline-flex items-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="sectionExitConfirm" class="inline-flex items-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                Continue
            </button>
        </div>
    </div>
</div>
<div id="tabSwitchModal" class="fixed inset-0 z-[85] hidden items-center justify-center bg-slate-950/60 px-4">
    <div class="w-full max-w-lg rounded-[28px] border border-slate-200 bg-white p-6 shadow-2xl">
        <div class="text-xs font-black uppercase tracking-[0.18em] text-amber-700">Focus Warning</div>
        <h2 class="mt-3 text-2xl font-black text-slate-950">Tab switch detected</h2>
        <div class="mt-4 space-y-3 text-sm leading-7 text-slate-700">
            <p id="tabSwitchBody">You left the test tab. Stay on this page while the test is in progress.</p>
            <p id="tabSwitchCountText" class="font-semibold text-slate-900"></p>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="button" id="tabSwitchAcknowledge" class="inline-flex items-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                Return to Test
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const remainingSeconds = @json($remainingSeconds);
    const pingUrl = @json(route('ept-online.attempt.ping', ['attempt' => $attempt->public_id]));
    const timerText = document.getElementById('timerText');
    const listeningAudio = document.getElementById('listeningAudio');
    const audioPlayButton = document.getElementById('audioPlayButton');
    const audioResumeButton = document.getElementById('audioResumeButton');
    const audioRecoveryCard = document.getElementById('audioRecoveryCard');
    const audioStateKey = @json('ept-online-listening:' . $attempt->id . ':' . $section->type);
    const initialAudioStartAt = @json($audioStartAt);
    const shouldResumeAudio = @json($resumeAudio);
    const showAudioRecoveryPrompt = @json($showAudioRecoveryPrompt ?? false);
    const examMeta = document.getElementById('examMeta');
    const examProgressBar = document.getElementById('examProgressBar');
    const questionSidebar = document.getElementById('questionSidebar');
    const questionStage = document.getElementById('questionStage');
    const readingNavToggle = document.getElementById('readingNavToggle');
    const readingNavDrawer = document.getElementById('readingNavDrawer');
    const readingNavOverlay = document.getElementById('readingNavOverlay');
    const csrfToken = @json(csrf_token());
    const sectionExitModal = document.getElementById('sectionExitModal');
    const sectionExitBadge = document.getElementById('sectionExitBadge');
    const sectionExitTitle = document.getElementById('sectionExitTitle');
    const sectionExitBody = document.getElementById('sectionExitBody');
    const sectionExitCancel = document.getElementById('sectionExitCancel');
    const sectionExitConfirm = document.getElementById('sectionExitConfirm');
    const sectionExitUnanswered = document.getElementById('sectionExitUnanswered');
    let currentSeconds = typeof remainingSeconds === 'number' ? remainingSeconds : null;
    let attemptNavigationLocked = false;
    let sectionExitApproved = false;
    let syncAttemptInputs = () => {};
    let withAttemptStateUrl = (rawUrl) => rawUrl;
    let beforeAttemptTransition = () => {};
    let afterAttemptFragmentsReplaced = () => {};
    let closeReadingNavDrawer = () => {};
    let timeoutRedirectHandled = false;

    const allowExamUnload = () => {
        if (typeof window.__eptAllowUnload === 'function') {
            window.__eptAllowUnload();
            return;
        }

        window.dispatchEvent(new CustomEvent('ept:allow-unload'));
    };

    function formatSectionLabel(sectionType) {
        const labels = {
            listening: 'Listening',
            structure: 'Structure',
            reading: 'Reading',
        };

        return labels[sectionType] || 'Section';
    }

    function formatTime(totalSeconds) {
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    function confirmSectionProgress(unanswered, hasNextSection, sectionType = null) {
        const currentUnanswered = document.querySelectorAll('#questionSidebar .qbox.unanswered').length;

        if (sectionExitApproved) {
            return true;
        }

        if (!sectionExitModal) {
            if (currentUnanswered <= 0) return true;

            const actionText = hasNextSection ? 'continue to the next section' : 'submit the test';
            return window.confirm(`There are still ${currentUnanswered} unanswered questions. Do you still want to ${actionText}?`);
        }

        const sectionLabel = formatSectionLabel(sectionType);

        if (sectionExitBadge) {
            sectionExitBadge.textContent = hasNextSection ? 'Section Transition' : 'Submission Confirmation';
        }

        if (sectionExitTitle) {
            sectionExitTitle.textContent = hasNextSection ? 'Continue to the next section?' : 'Submit the test now?';
        }

        if (sectionExitBody) {
            sectionExitBody.innerHTML = hasNextSection
                ? `If you continue, the <strong>${sectionLabel}</strong> section will be closed and you <strong>will not be able to return</strong> to it.`
                : 'If you submit the test now, all saved answers will be locked and can no longer be changed.';
        }

        if (sectionExitUnanswered) {
            if (currentUnanswered > 0) {
                sectionExitUnanswered.classList.remove('hidden');
                sectionExitUnanswered.innerHTML = `There are still <strong>${currentUnanswered}</strong> unanswered questions in the ${sectionLabel.toLowerCase()} section.`;
            } else {
                sectionExitUnanswered.classList.add('hidden');
                sectionExitUnanswered.textContent = '';
            }
        }

        if (sectionExitCancel) {
            sectionExitCancel.textContent = hasNextSection ? `Stay in ${sectionLabel}` : 'Back to Test';
        }

        if (sectionExitConfirm) {
            sectionExitConfirm.textContent = hasNextSection ? 'Yes, continue' : 'Yes, submit';
        }

        sectionExitModal.classList.remove('hidden');
        sectionExitModal.classList.add('flex');

        return false;
    }

    if (timerText && currentSeconds !== null) {
        timerText.textContent = formatTime(currentSeconds);

        window.setInterval(() => {
            if (currentSeconds === null) return;
            currentSeconds = Math.max(0, currentSeconds - 1);
            timerText.textContent = formatTime(currentSeconds);

            if (currentSeconds === 0) {
                if (timeoutRedirectHandled) {
                    return;
                }

                timeoutRedirectHandled = true;
                window.fetch(pingUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': @json(csrf_token()),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                .then(response => response.json())
                .then(payload => {
                    if (payload.redirect) {
                        allowExamUnload();
                        window.location.href = payload.redirect;
                    }
                });
            }
        }, 1000);

        window.setInterval(() => {
            window.fetch(pingUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(response => response.json())
            .then(payload => {
                if (payload.redirect) {
                    allowExamUnload();
                    window.location.href = payload.redirect;
                }
            })
            .catch(() => {});
        }, 20000);
    }

    if (listeningAudio) {
        const syncAudioInputs = (root = document) => {
            root.querySelectorAll('[data-audio-position]').forEach((input) => {
                input.value = Number.isFinite(listeningAudio.currentTime) ? listeningAudio.currentTime.toFixed(3) : '0';
            });
            root.querySelectorAll('[data-audio-playing]').forEach((input) => {
                input.value = (!listeningAudio.paused && !listeningAudio.ended) ? '1' : '0';
            });
        };

        const readAudioState = () => {
            try {
                const raw = window.sessionStorage.getItem(audioStateKey);
                return raw ? JSON.parse(raw) : null;
            } catch (_) {
                return null;
            }
        };

        const withAudioState = (rawUrl) => {
            const url = new URL(rawUrl, window.location.origin);
            const currentTime = Number.isFinite(listeningAudio.currentTime) ? listeningAudio.currentTime : 0;

            url.searchParams.set('audio', currentTime.toFixed(3));

            if (!listeningAudio.paused && !listeningAudio.ended) {
                url.searchParams.set('resume_audio', '1');
            } else {
                url.searchParams.delete('resume_audio');
            }

            return url.toString();
        };

        syncAttemptInputs = syncAudioInputs;
        withAttemptStateUrl = withAudioState;

        const writeAudioState = () => {
            try {
                window.sessionStorage.setItem(audioStateKey, JSON.stringify({
                    time: Number.isFinite(listeningAudio.currentTime) ? listeningAudio.currentTime : 0,
                    wasPlaying: !listeningAudio.paused && !listeningAudio.ended,
                }));
            } catch (_) {}
        };

        const buildAudioPayload = () => {
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('audio_position', Number.isFinite(listeningAudio.currentTime) ? listeningAudio.currentTime.toFixed(3) : '0');
            formData.append('audio_playing', (!listeningAudio.paused && !listeningAudio.ended) ? '1' : '0');

            return formData;
        };

        const syncAudioHeartbeat = (useBeacon = false) => {
            const payload = buildAudioPayload();

            if (useBeacon && navigator.sendBeacon) {
                navigator.sendBeacon(pingUrl, payload);
                return;
            }

            window.fetch(pingUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: payload,
                credentials: 'same-origin',
                keepalive: useBeacon,
            }).catch(() => {});
        };

        beforeAttemptTransition = () => {
            writeAudioState();
        };

        const hidePlayButton = () => {
            audioPlayButton?.classList.add('hidden');
        };

        const showPlayButton = () => {
            audioPlayButton?.classList.remove('hidden');
        };

        const hideRecoveryCard = () => {
            audioRecoveryCard?.classList.add('hidden');
        };

        const tryPlay = () => {
            const playPromise = listeningAudio.play();

            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise
                    .then(() => {
                        hidePlayButton();
                        hideRecoveryCard();
                    })
                    .catch(() => showPlayButton());
            } else if (listeningAudio.paused) {
                showPlayButton();
            } else {
                hidePlayButton();
                hideRecoveryCard();
            }
        };

        audioPlayButton?.addEventListener('click', () => {
            tryPlay();
        });
        audioResumeButton?.addEventListener('click', () => {
            tryPlay();
        });

        listeningAudio.addEventListener('play', () => {
            hidePlayButton();
            syncAudioInputs();
        });
        listeningAudio.addEventListener('pause', () => {
            syncAudioInputs();
            if (! listeningAudio.ended) {
                showPlayButton();
            }
        });
        listeningAudio.addEventListener('timeupdate', () => {
            writeAudioState();
            syncAudioInputs();
        });
        listeningAudio.addEventListener('ended', () => {
            writeAudioState();
            syncAudioInputs();
        });
        window.addEventListener('pagehide', () => {
            writeAudioState();
            syncAudioInputs();
            syncAudioHeartbeat(true);
        });

        const applyStoredAudioState = () => {
            const stored = readAudioState();
            const targetTime = typeof initialAudioStartAt === 'number'
                ? initialAudioStartAt
                : (stored && typeof stored.time === 'number' ? stored.time : 0);
            const shouldAutoPlay = !showAudioRecoveryPrompt && (shouldResumeAudio || (stored && stored.wasPlaying) || @json($autoplayAudio));

            const finishRestore = (allowAutoplay = shouldAutoPlay) => {
                syncAudioInputs();

                if (showAudioRecoveryPrompt) {
                    showPlayButton();
                    return;
                }

                if (allowAutoplay) {
                    tryPlay();
                } else if (listeningAudio.paused) {
                    showPlayButton();
                }
            };

            if (targetTime <= 0) {
                finishRestore();
                return;
            }

            const safeTime = Number.isFinite(listeningAudio.duration) && listeningAudio.duration > 0
                ? Math.min(targetTime, Math.max(0, listeningAudio.duration - 0.25))
                : targetTime;

            let restoreFinished = false;
            const finishOnce = (allowAutoplay = shouldAutoPlay) => {
                if (restoreFinished) {
                    return;
                }

                restoreFinished = true;
                finishRestore(allowAutoplay);
            };

            const hasReachedTarget = () => Math.abs((listeningAudio.currentTime || 0) - safeTime) <= 0.5;

            const verifySeek = () => {
                if (hasReachedTarget()) {
                    finishOnce();
                }
            };

            const fallbackTimer = window.setTimeout(() => {
                if (hasReachedTarget()) {
                    finishOnce();
                    return;
                }

                // If the browser still has not restored the target offset, avoid autoplaying from 0.
                finishOnce(false);
            }, 1200);

            const cleanupAndFinish = (allowAutoplay = shouldAutoPlay) => {
                window.clearTimeout(fallbackTimer);
                finishOnce(allowAutoplay);
            };

            listeningAudio.addEventListener('seeked', verifySeek, { once: true });
            listeningAudio.addEventListener('canplay', verifySeek, { once: true });

            try {
                listeningAudio.currentTime = safeTime;
            } catch (_) {
                cleanupAndFinish(false);
                return;
            }

            window.setTimeout(() => {
                if (hasReachedTarget()) {
                    cleanupAndFinish();
                }
            }, 120);
        };

        if (listeningAudio.readyState >= 1) {
            applyStoredAudioState();
        } else {
            listeningAudio.addEventListener('loadedmetadata', applyStoredAudioState, { once: true });
        }

        window.setInterval(() => {
            syncAudioHeartbeat();
        }, 5000);
    }

    const initReadingPassageScrollers = () => {
        document.querySelectorAll('[data-reading-scroll-sync]').forEach((container) => {
            const top = container.querySelector('[data-reading-scroll-top]');
            const bottom = container.querySelector('[data-reading-scroll-bottom]');
            const track = container.querySelector('[data-reading-scroll-track]');
            const frame = container.querySelector('[data-reading-scroll-frame]');

            if (!top || !bottom || !track || !frame) {
                return;
            }

            const syncTrackWidth = () => {
                track.style.width = `${frame.scrollWidth}px`;
            };

            if (container.dataset.scrollReady !== '1') {
                let syncing = false;
                const syncScroll = (source, target) => {
                    if (syncing) {
                        return;
                    }

                    syncing = true;
                    target.scrollLeft = source.scrollLeft;
                    window.requestAnimationFrame(() => {
                        syncing = false;
                    });
                };

                top.addEventListener('scroll', () => syncScroll(top, bottom));
                bottom.addEventListener('scroll', () => syncScroll(bottom, top));
                window.addEventListener('resize', syncTrackWidth);
                container.dataset.scrollReady = '1';
            }

            syncTrackWidth();
        });
    };

    const initReadingNavDrawer = () => {
        if (!readingNavToggle || !readingNavDrawer || !readingNavOverlay) {
            return;
        }

        const openDrawer = () => {
            readingNavDrawer.classList.remove('hidden');
            readingNavOverlay.classList.remove('hidden');
        };

        closeReadingNavDrawer = () => {
            readingNavDrawer.classList.add('hidden');
            readingNavOverlay.classList.add('hidden');
        };

        if (readingNavToggle.dataset.drawerReady !== '1') {
            readingNavToggle.addEventListener('click', () => {
                const isHidden = readingNavDrawer.classList.contains('hidden');

                if (isHidden) {
                    openDrawer();
                } else {
                    closeReadingNavDrawer();
                }
            });

            readingNavOverlay.addEventListener('click', () => {
                closeReadingNavDrawer();
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeReadingNavDrawer();
                }
            });

            readingNavToggle.dataset.drawerReady = '1';
        }
    };

    afterAttemptFragmentsReplaced = () => {
        initReadingPassageScrollers();
        initReadingNavDrawer();
    };

    const setAttemptNavigationLocked = (locked) => {
        attemptNavigationLocked = locked;

        if (questionStage) {
            questionStage.classList.toggle('pointer-events-none', locked);
            questionStage.classList.toggle('opacity-70', locked);
        }
    };

    const replaceAttemptFragments = (doc) => {
        const nextMeta = doc.getElementById('examMeta');
        const nextProgressBar = doc.getElementById('examProgressBar');
        const nextSidebar = doc.getElementById('questionSidebar');
        const nextStage = doc.getElementById('questionStage');

        if (!nextMeta || !nextProgressBar || !nextSidebar || !nextStage || !examMeta || !examProgressBar || !questionSidebar || !questionStage) {
            return false;
        }

        examMeta.innerHTML = nextMeta.innerHTML;
        examProgressBar.style.width = nextProgressBar.style.width;
        questionSidebar.innerHTML = nextSidebar.innerHTML;
        questionStage.innerHTML = nextStage.innerHTML;
        closeReadingNavDrawer();
        afterAttemptFragmentsReplaced();
        syncAttemptInputs(document);

        return true;
    };

    const fetchAttemptPage = async (url, pushState = true) => {
        setAttemptNavigationLocked(true);
        beforeAttemptTransition();
        const targetUrl = withAttemptStateUrl(url);

        try {
            const response = await window.fetch(targetUrl, {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                allowExamUnload();
                window.location.href = targetUrl;
                return;
            }

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            if (!replaceAttemptFragments(doc)) {
                allowExamUnload();
                window.location.href = targetUrl;
                return;
            }

            if (pushState) {
                window.history.pushState({ url: targetUrl }, '', targetUrl);
            }

            document.title = doc.title || document.title;
            window.scrollTo({ top: 0, behavior: 'auto' });
        } catch (_) {
            allowExamUnload();
            window.location.href = targetUrl;
        } finally {
            setAttemptNavigationLocked(false);
        }
    };

    const submitAttemptForm = async (form, submitter = null) => {
        setAttemptNavigationLocked(true);
        syncAttemptInputs(form);
        beforeAttemptTransition();

        try {
            const formData = (() => {
                try {
                    return submitter ? new FormData(form, submitter) : new FormData(form);
                } catch (_) {
                    const fallback = new FormData(form);

                    if (submitter?.name) {
                        fallback.append(submitter.name, submitter.value ?? '');
                    }

                    return fallback;
                }
            })();
            const response = await window.fetch(form.action, {
                method: form.method || 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
                credentials: 'same-origin',
            });

            const payload = await response.json();

            if (payload && payload.redirect) {
                await fetchAttemptPage(payload.redirect);
                return;
            }

            allowExamUnload();
            window.location.reload();
        } catch (_) {
            allowExamUnload();
            form.submit();
        } finally {
            setAttemptNavigationLocked(false);
        }
    };

    document.addEventListener('click', (event) => {
        const answerButton = event.target.closest('form[data-attempt-answer-form="1"] button[name="answer"]');
        if (answerButton && !attemptNavigationLocked) {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            event.preventDefault();

            const form = answerButton.form;
            if (!form) {
                return;
            }

            const hiddenAnswerInput = form.querySelector('[data-selected-answer]');
            if (hiddenAnswerInput) {
                hiddenAnswerInput.value = answerButton.value ?? '';
            }

            submitAttemptForm(form, answerButton);
            return;
        }

        const link = event.target.closest('[data-attempt-nav="1"]');
        if (!link || attemptNavigationLocked) {
            return;
        }

        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        event.preventDefault();
        fetchAttemptPage(link.href);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-attempt-ajax="1"]');
        if (!form || attemptNavigationLocked) {
            return;
        }

        event.preventDefault();
        submitAttemptForm(form, event.submitter ?? null);
    });

    window.addEventListener('popstate', () => {
        fetchAttemptPage(window.location.href, false);
    });

    initReadingPassageScrollers();
    initReadingNavDrawer();

    if (sectionExitModal) {
        const closeSectionExitModal = () => {
            sectionExitModal.classList.add('hidden');
            sectionExitModal.classList.remove('flex');
        };

        sectionExitCancel?.addEventListener('click', () => {
            closeSectionExitModal();
        });

        sectionExitModal.addEventListener('click', (event) => {
            if (event.target === sectionExitModal) {
                closeSectionExitModal();
            }
        });

        sectionExitConfirm?.addEventListener('click', () => {
            const currentSectionProgressForm = document.querySelector('form[data-section-progress-form="1"]');

            if (!currentSectionProgressForm) {
                closeSectionExitModal();
                return;
            }

            sectionExitApproved = true;
            closeSectionExitModal();
            currentSectionProgressForm.requestSubmit();
        });

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !sectionExitModal.classList.contains('hidden')) {
                closeSectionExitModal();
            }
        });
    }
</script>
@endpush
