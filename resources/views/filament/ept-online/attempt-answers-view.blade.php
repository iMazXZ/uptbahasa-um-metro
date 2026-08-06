@php
    /** @var \App\Models\EptOnlineAttempt|null $record */
    $record = $getRecord();

    $record?->loadMissing([
        'form.sections',
        'form.questions.section',
        'answers',
        'result',
    ]);

    $questions = $record?->form?->questions
        ? $record->form->questions->sortBy([
            ['section.sort_order', 'asc'],
            ['sort_order', 'asc'],
            ['id', 'asc'],
        ])->values()
        : collect();

    $answersByQuestionId = ($record?->answers ?? collect())->keyBy('question_id');

    $sectionLabels = [
        'listening' => 'Listening',
        'structure' => 'Structure',
        'reading' => 'Reading',
    ];
@endphp

@if ($questions->isEmpty())
    <div class="text-sm text-gray-500 dark:text-gray-400">Belum ada soal untuk attempt ini.</div>
@else
    @php
        $grouped = $questions->groupBy(fn ($question) => $question->section?->type ?? 'unknown');
    @endphp

    <div class="space-y-6">
        @foreach ($grouped as $sectionType => $sectionQuestions)
            @php
                $answeredCount = $sectionQuestions->filter(function ($question) use ($answersByQuestionId) {
                    $selected = $answersByQuestionId->get($question->id)?->selected_option;

                    return filled($selected);
                })->count();

                $correctCount = $sectionQuestions->filter(function ($question) use ($answersByQuestionId) {
                    $selected = $answersByQuestionId->get($question->id)?->selected_option;

                    return filled($selected) && $selected === $question->correct_option;
                })->count();
            @endphp

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <div>
                        <div class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            {{ $sectionLabels[$sectionType] ?? ucfirst((string) $sectionType) }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $answeredCount }} answered / {{ $sectionQuestions->count() }} questions
                        </div>
                    </div>
                    <div class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                        {{ $correctCount }} correct
                    </div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($sectionQuestions as $question)
                        @php
                            $answer = $answersByQuestionId->get($question->id);
                            $selected = $answer?->selected_option;
                            $correct = $question->correct_option;
                            $isAnswered = filled($selected);
                            $isCorrect = $isAnswered && $selected === $correct;

                            $stateClasses = match (true) {
                                ! $isAnswered => 'bg-amber-50 text-amber-700 border-amber-200',
                                $isCorrect => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                default => 'bg-rose-50 text-rose-700 border-rose-200',
                            };

                            $number = $question->number_in_section ?: $loop->iteration;
                            $prompt = trim((string) ($question->prompt ?? ''));
                            $instruction = trim((string) ($question->instruction ?? ''));
                            $options = [
                                'A' => $question->option_a,
                                'B' => $question->option_b,
                                'C' => $question->option_c,
                                'D' => $question->option_d,
                            ];
                        @endphp

                        <div class="space-y-3 px-4 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        Question {{ $number }}
                                        @if(filled($question->part_label))
                                            <span class="ml-2 text-xs font-medium uppercase tracking-wide text-gray-400">{{ $question->part_label }}</span>
                                        @endif
                                    </div>
                                    @if ($prompt !== '')
                                        <div class="mt-1 whitespace-pre-wrap text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $prompt }}</div>
                                    @elseif ($instruction !== '')
                                        <div class="mt-1 whitespace-pre-wrap text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $instruction }}</div>
                                    @endif
                                </div>

                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold {{ $stateClasses }}">
                                    @if (! $isAnswered)
                                        Unanswered
                                    @elseif ($isCorrect)
                                        Correct
                                    @else
                                        Wrong
                                    @endif
                                </span>
                            </div>

                            <div class="grid gap-2 md:grid-cols-2">
                                @foreach ($options as $key => $label)
                                    @php
                                        $optionClasses = match (true) {
                                            $selected === $key && $correct === $key => 'border-emerald-300 bg-emerald-50',
                                            $selected === $key => 'border-rose-300 bg-rose-50',
                                            $correct === $key => 'border-emerald-200 bg-emerald-50/70',
                                            default => 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/40',
                                        };
                                    @endphp
                                    <div class="rounded-lg border px-3 py-2 {{ $optionClasses }}">
                                        <div class="flex items-start gap-2">
                                            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-current/15 text-xs font-bold text-gray-700 dark:text-gray-200">
                                                {{ $key }}
                                            </span>
                                            <div class="min-w-0 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                                {{ $label ?: '—' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                <span>Selected: <strong class="text-gray-700 dark:text-gray-200">{{ $selected ?: '—' }}</strong></span>
                                <span>Correct: <strong class="text-gray-700 dark:text-gray-200">{{ $correct ?: '—' }}</strong></span>
                                @if ($answer?->answered_at)
                                    <span>Answered at: <strong class="text-gray-700 dark:text-gray-200">{{ $answer->answered_at->format('d M Y H:i:s') }}</strong></span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
