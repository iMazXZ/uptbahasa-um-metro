<?php

namespace App\Support;

use App\Models\EptOnlineForm;
use App\Models\EptOnlinePassage;
use App\Models\EptOnlineSection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class EptOnlineWorkbookImportService
{
    public const IMPORT_SCOPE_FULL = 'full';
    public const IMPORT_SCOPE_LISTENING = EptOnlineSection::TYPE_LISTENING;
    public const IMPORT_SCOPE_STRUCTURE = EptOnlineSection::TYPE_STRUCTURE;
    public const IMPORT_SCOPE_READING = EptOnlineSection::TYPE_READING;

    private const DEFAULT_DURATIONS = [
        EptOnlineSection::TYPE_LISTENING => 35,
        EptOnlineSection::TYPE_STRUCTURE => 25,
        EptOnlineSection::TYPE_READING => 55,
    ];

    private const EXPECTED_QUESTION_COUNTS = [
        EptOnlineSection::TYPE_LISTENING => 50,
        EptOnlineSection::TYPE_STRUCTURE => 40,
        EptOnlineSection::TYPE_READING => 50,
    ];

    public function preview(string $filePath): array
    {
        $parsed = $this->parseWorkbook($filePath);

        return $this->makeSummary($parsed, basename($filePath));
    }

    public function import(
        EptOnlineForm $form,
        string $filePath,
        ?int $actorId = null,
        string $scope = self::IMPORT_SCOPE_FULL,
    ): array
    {
        if ($form->status === EptOnlineForm::STATUS_PUBLISHED) {
            throw new RuntimeException('Paket yang sudah published tidak boleh di-import ulang. Ubah status ke draft terlebih dahulu.');
        }

        $scope = $this->normalizeImportScope($scope);
        $parsed = $this->parseWorkbook($filePath, $scope);
        $summary = $this->makeSummary($parsed, basename($filePath), $scope);
        $now = now();

        DB::transaction(function () use ($form, $parsed, $summary, $actorId, $now, $scope): void {
            $meta = $parsed['meta'];
            $title = $meta['title'] ?? null;

            $form->forceFill([
                'title' => filled($title) ? (string) $title : $form->title,
                'imported_at' => $now,
                'last_import_summary' => $summary,
                'updated_by' => $actorId,
            ])->save();

            if ($scope === self::IMPORT_SCOPE_FULL) {
                $this->replaceEntireWorkbook($form, $parsed);

                return;
            }

            $this->replaceSingleSection($form, $parsed, $scope);
        });

        return $summary;
    }

    public static function importScopeOptions(): array
    {
        return [
            self::IMPORT_SCOPE_FULL => 'Full Workbook (Final 50/40/50)',
            self::IMPORT_SCOPE_LISTENING => 'Listening Only (Draft/Testing)',
            self::IMPORT_SCOPE_STRUCTURE => 'Structure Only (Draft/Testing)',
            self::IMPORT_SCOPE_READING => 'Reading Only (Draft/Testing)',
        ];
    }

    public static function importScopeLabel(string $scope): string
    {
        return self::importScopeOptions()[$scope] ?? ucfirst($scope);
    }

    private function replaceEntireWorkbook(EptOnlineForm $form, array $parsed): void
    {
        $meta = $parsed['meta'];

        $form->questions()->delete();
        $form->passages()->delete();
        $form->sections()->delete();

        $sections = [];
        foreach ($this->buildSectionRows($form, $meta) as $type => $sectionData) {
            $sections[$type] = $form->sections()->create(array_merge($sectionData, [
                'type' => $type,
            ]));
        }

        $passageMap = $this->persistReadingPassages($form, $sections[EptOnlineSection::TYPE_READING], $parsed['reading_passages']);

        $this->persistQuestions(
            $form,
            $sections[EptOnlineSection::TYPE_LISTENING],
            $parsed['listening'],
            [],
            $this->extractPartInstructions($meta, 'listening')
        );
        $this->persistQuestions(
            $form,
            $sections[EptOnlineSection::TYPE_STRUCTURE],
            $parsed['structure'],
            [],
            $this->extractPartInstructions($meta, 'structure')
        );
        $this->persistQuestions(
            $form,
            $sections[EptOnlineSection::TYPE_READING],
            $parsed['reading_questions'],
            $passageMap
        );
    }

    private function replaceSingleSection(EptOnlineForm $form, array $parsed, string $scope): void
    {
        $meta = $parsed['meta'];
        $sectionRows = $this->buildSectionRows($form, $meta);
        $sectionData = $sectionRows[$scope] ?? null;

        if ($sectionData === null) {
            throw new RuntimeException('Scope import section tidak valid.');
        }

        /** @var EptOnlineSection $section */
        $section = $form->sections()->updateOrCreate(
            ['type' => $scope],
            $sectionData
        );

        $section->questions()->delete();

        if ($scope === self::IMPORT_SCOPE_READING) {
            $section->passages()->delete();
            $passageMap = $this->persistReadingPassages($form, $section, $parsed['reading_passages']);
            $this->persistQuestions($form, $section, $parsed['reading_questions'], $passageMap);

            return;
        }

        $partInstructions = $scope === self::IMPORT_SCOPE_LISTENING
            ? $this->extractPartInstructions($meta, 'listening')
            : $this->extractPartInstructions($meta, 'structure');

        $rows = $scope === self::IMPORT_SCOPE_LISTENING
            ? $parsed['listening']
            : $parsed['structure'];

        $this->persistQuestions($form, $section, $rows, [], $partInstructions);
    }

    private function buildSectionRows(EptOnlineForm $form, array $meta): array
    {
        return [
            EptOnlineSection::TYPE_LISTENING => [
                'title' => $meta['listening_title'] ?? 'Listening Comprehension',
                'instructions' => $meta['listening_instructions'] ?? null,
                'duration_minutes' => (int) ($meta['listening_duration_minutes'] ?? self::DEFAULT_DURATIONS[EptOnlineSection::TYPE_LISTENING]),
                'sort_order' => 1,
                'audio_path' => $form->listening_audio_path,
                'audio_duration_seconds' => isset($meta['listening_audio_duration_seconds']) && is_numeric($meta['listening_audio_duration_seconds'])
                    ? (int) $meta['listening_audio_duration_seconds']
                    : null,
                'meta' => $this->buildListeningSectionMeta($meta),
            ],
            EptOnlineSection::TYPE_STRUCTURE => [
                'title' => $meta['structure_title'] ?? 'Structure and Written Expression',
                'instructions' => $meta['structure_instructions'] ?? null,
                'duration_minutes' => (int) ($meta['structure_duration_minutes'] ?? self::DEFAULT_DURATIONS[EptOnlineSection::TYPE_STRUCTURE]),
                'sort_order' => 2,
                'audio_path' => null,
                'audio_duration_seconds' => null,
                'meta' => $this->filterNestedMeta([
                    'source' => 'workbook',
                    'part_instructions' => $this->extractPartInstructions($meta, 'structure'),
                ]),
            ],
            EptOnlineSection::TYPE_READING => [
                'title' => $meta['reading_title'] ?? 'Reading Comprehension',
                'instructions' => $meta['reading_instructions'] ?? null,
                'duration_minutes' => (int) ($meta['reading_duration_minutes'] ?? self::DEFAULT_DURATIONS[EptOnlineSection::TYPE_READING]),
                'sort_order' => 3,
                'audio_path' => null,
                'audio_duration_seconds' => null,
                'meta' => ['source' => 'workbook'],
            ],
        ];
    }

    /**
     * @return array<string, EptOnlinePassage>
     */
    private function persistReadingPassages(EptOnlineForm $form, EptOnlineSection $section, array $rows): array
    {
        $passageMap = [];

        foreach ($rows as $passageRow) {
            $passage = $form->passages()->create([
                'section_id' => $section->id,
                'passage_code' => $passageRow['passage_code'],
                'title' => $passageRow['title'],
                'content' => $passageRow['passage_text'],
                'sort_order' => $passageRow['sort_order'],
                'meta' => ['source_sheet' => 'READING_PASSAGES'],
            ]);

            $passageMap[$passage->passage_code] = $passage;
        }

        return $passageMap;
    }

    private function persistQuestions(
        EptOnlineForm $form,
        EptOnlineSection $section,
        array $rows,
        array $passageMap = [],
        array $partInstructions = [],
    ): void {
        foreach ($rows as $row) {
            $passageId = null;
            if (filled($row['passage_code'] ?? null)) {
                /** @var EptOnlinePassage|null $passage */
                $passage = $passageMap[$row['passage_code']] ?? null;
                $passageId = $passage?->id;
            }

            $partKey = strtoupper((string) ($row['part'] ?? ''));
            $instruction = $row['instruction']
                ?: ($partInstructions[$partKey] ?? null);

            $form->questions()->create([
                'section_id' => $section->id,
                'passage_id' => $passageId,
                'part_label' => $row['part'],
                'group_code' => $row['group_code'],
                'number_in_section' => $row['question_no'],
                'sort_order' => $row['question_no'],
                'instruction' => $instruction,
                'prompt' => $row['question_text'],
                'option_a' => $row['option_a'],
                'option_b' => $row['option_b'],
                'option_c' => $row['option_c'],
                'option_d' => $row['option_d'],
                'correct_option' => $row['correct_option'],
                'meta' => [
                    'source_sheet' => strtoupper($section->type),
                    'group_code' => $row['group_code'],
                ],
            ]);
        }
    }

    private function parseWorkbook(string $filePath, string $scope = self::IMPORT_SCOPE_FULL): array
    {
        if (! is_file($filePath)) {
            throw new RuntimeException('File workbook tidak ditemukan.');
        }

        $scope = $this->normalizeImportScope($scope);

        $spreadsheet = IOFactory::load($filePath);

        $sheets = [];
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $sheets[strtoupper(trim((string) $worksheet->getTitle()))] = $worksheet;
        }

        $errors = [];

        $meta = isset($sheets['META']) ? $this->parseMetaSheet($sheets['META']) : [];
        $listening = in_array($scope, [self::IMPORT_SCOPE_FULL, self::IMPORT_SCOPE_LISTENING], true)
            ? $this->parseQuestionSheet($sheets['LISTENING'] ?? null, 'LISTENING', EptOnlineSection::TYPE_LISTENING, false, $errors)
            : [];
        $structure = in_array($scope, [self::IMPORT_SCOPE_FULL, self::IMPORT_SCOPE_STRUCTURE], true)
            ? $this->parseQuestionSheet($sheets['STRUCTURE'] ?? null, 'STRUCTURE', EptOnlineSection::TYPE_STRUCTURE, false, $errors)
            : [];
        $readingPassages = in_array($scope, [self::IMPORT_SCOPE_FULL, self::IMPORT_SCOPE_READING], true)
            ? $this->parsePassageSheet($sheets['READING_PASSAGES'] ?? null, $errors)
            : [];
        $readingQuestions = in_array($scope, [self::IMPORT_SCOPE_FULL, self::IMPORT_SCOPE_READING], true)
            ? $this->parseQuestionSheet($sheets['READING_QUESTIONS'] ?? null, 'READING_QUESTIONS', EptOnlineSection::TYPE_READING, true, $errors)
            : [];

        $passageCodes = [];
        foreach ($readingPassages as $row) {
            $passageCodes[$row['passage_code']] = true;
        }

        foreach ($readingQuestions as $row) {
            if (! isset($passageCodes[$row['passage_code']])) {
                $errors[] = 'READING_QUESTIONS: passage_code "' . $row['passage_code'] . '" tidak ditemukan di sheet READING_PASSAGES.';
            }
        }

        if ($scope === self::IMPORT_SCOPE_FULL) {
            foreach (self::EXPECTED_QUESTION_COUNTS as $sectionType => $expectedCount) {
                $actualCount = match ($sectionType) {
                    EptOnlineSection::TYPE_LISTENING => count($listening),
                    EptOnlineSection::TYPE_STRUCTURE => count($structure),
                    EptOnlineSection::TYPE_READING => count($readingQuestions),
                    default => 0,
                };

                if ($actualCount !== $expectedCount) {
                    $errors[] = strtoupper($sectionType) . ': jumlah soal harus ' . $expectedCount . ', ditemukan ' . $actualCount . '.';
                }
            }
        } else {
            $targetCount = match ($scope) {
                self::IMPORT_SCOPE_LISTENING => count($listening),
                self::IMPORT_SCOPE_STRUCTURE => count($structure),
                self::IMPORT_SCOPE_READING => count($readingQuestions),
                default => 0,
            };

            if ($targetCount < 1) {
                $errors[] = strtoupper($scope) . ': minimal 1 soal diperlukan untuk import per section.';
            }

            if ($scope === self::IMPORT_SCOPE_READING && count($readingPassages) < 1) {
                $errors[] = 'READING_PASSAGES: minimal 1 passage diperlukan untuk import section reading.';
            }
        }

        foreach (self::DEFAULT_DURATIONS as $sectionType => $defaultDuration) {
            $metaKey = $sectionType . '_duration_minutes';
            if (isset($meta[$metaKey]) && (! is_numeric($meta[$metaKey]) || (int) $meta[$metaKey] <= 0)) {
                $errors[] = 'META: ' . $metaKey . ' harus berupa angka lebih dari 0.';
            }

            if (! isset($meta[$metaKey])) {
                $meta[$metaKey] = $defaultDuration;
            }
        }

        if ($errors !== []) {
            throw new RuntimeException("Workbook EPT Online tidak valid:\n- " . implode("\n- ", $errors));
        }

        return [
            'meta' => $meta,
            'listening' => $listening,
            'structure' => $structure,
            'reading_passages' => $readingPassages,
            'reading_questions' => $readingQuestions,
        ];
    }

    private function normalizeImportScope(string $scope): string
    {
        $scope = trim(strtolower($scope));

        return in_array($scope, [
            self::IMPORT_SCOPE_FULL,
            self::IMPORT_SCOPE_LISTENING,
            self::IMPORT_SCOPE_STRUCTURE,
            self::IMPORT_SCOPE_READING,
        ], true)
            ? $scope
            : self::IMPORT_SCOPE_FULL;
    }

    private function buildListeningSectionMeta(array $meta): array
    {
        $partExamples = $this->extractPartExamples($meta, 'listening');

        if ($partExamples === []) {
            $legacyExample = $this->buildExampleMetaByPrefix($meta, 'listening_example_');

            if ($legacyExample !== []) {
                $partExamples['A'] = [$legacyExample];
            }
        }

        return $this->filterNestedMeta([
            'source' => 'workbook',
            'intro' => [
                'heading' => $meta['listening_intro_heading'] ?? 'Petunjuk Listening',
                'text' => $meta['listening_intro_text'] ?? null,
            ],
            'part_instructions' => $this->extractPartInstructions($meta, 'listening'),
            'part_examples' => $partExamples,
        ]);
    }

    private function extractPartInstructions(array $meta, string $sectionPrefix): array
    {
        return $this->filterNestedMeta([
            'A' => $meta[$sectionPrefix . '_part_a_instruction'] ?? null,
            'B' => $meta[$sectionPrefix . '_part_b_instruction'] ?? null,
            'C' => $meta[$sectionPrefix . '_part_c_instruction'] ?? null,
        ]);
    }

    private function extractPartExamples(array $meta, string $sectionPrefix): array
    {
        $examples = [];

        foreach (['A', 'B', 'C'] as $part) {
            $list = $this->buildPartExampleList($meta, $sectionPrefix, strtolower($part));
            if ($list !== []) {
                $examples[$part] = $list;
            }
        }

        return $examples;
    }

    private function buildPartExampleList(array $meta, string $sectionPrefix, string $part): array
    {
        $examples = [];

        foreach (range(1, 5) as $index) {
            $example = $this->buildExampleMetaByPrefix(
                $meta,
                $sectionPrefix . '_part_' . strtolower($part) . '_example_' . $index . '_'
            );

            if ($example !== []) {
                $examples[] = $example;
            }
        }

        if ($examples !== []) {
            return array_values($examples);
        }

        $legacyExample = $this->buildExampleMetaByPrefix(
            $meta,
            $sectionPrefix . '_part_' . strtolower($part) . '_example_'
        );

        return $legacyExample !== []
            ? [$legacyExample]
            : [];
    }

    private function buildExampleMetaByPrefix(array $meta, string $prefix): array
    {
        return $this->filterNestedMeta([
            'title' => $meta[$prefix . 'title'] ?? null,
            'audio_label' => $meta[$prefix . 'audio_label'] ?? 'On the recording, you will hear:',
            'audio_text' => $meta[$prefix . 'audio_text'] ?? null,
            'book_label' => $meta[$prefix . 'book_label'] ?? 'In your test book, you will read:',
            'book_text' => $meta[$prefix . 'book_text'] ?? null,
            'explanation' => $meta[$prefix . 'explanation'] ?? null,
        ]);
    }

    private function parseMetaSheet(Worksheet $sheet): array
    {
        $rows = $sheet->toArray(null, true, true, false);
        $meta = [];

        foreach ($rows as $index => $row) {
            $key = $this->normalizeString($row[0] ?? null);
            $value = $row[1] ?? null;

            if ($index === 0 && $key === 'key') {
                continue;
            }

            if (! filled($key)) {
                continue;
            }

            $meta[$this->normalizeHeading($key)] = is_string($value)
                ? trim($value)
                : $value;
        }

        return $meta;
    }

    private function parsePassageSheet(?Worksheet $sheet, array &$errors): array
    {
        if (! $sheet) {
            $errors[] = 'Sheet READING_PASSAGES wajib ada.';

            return [];
        }

        $rows = $sheet->toArray(null, true, true, false);
        if ($rows === []) {
            $errors[] = 'Sheet READING_PASSAGES kosong.';

            return [];
        }

        $headerMap = $this->buildHeaderMap($rows[0]);
        $sheetErrors = [];
        foreach (['passage_code', 'passage_text'] as $requiredHeading) {
            if (! isset($headerMap[$requiredHeading])) {
                $sheetErrors[] = 'Sheet READING_PASSAGES wajib memiliki kolom ' . $requiredHeading . '.';
            }
        }

        if ($sheetErrors !== []) {
            array_push($errors, ...$sheetErrors);

            return [];
        }

        $passages = [];
        $seenCodes = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $rowNumber = $offset + 2;
            $code = $this->normalizeString($row[$headerMap['passage_code']] ?? null);
            $text = $this->normalizeString($row[$headerMap['passage_text']] ?? null);
            $title = $this->normalizeString($row[$headerMap['title']] ?? null);

            if (! filled($code)) {
                $errors[] = 'READING_PASSAGES baris ' . $rowNumber . ': passage_code wajib diisi.';
                continue;
            }

            if (! filled($text)) {
                $errors[] = 'READING_PASSAGES baris ' . $rowNumber . ': passage_text wajib diisi.';
                continue;
            }

            if (isset($seenCodes[$code])) {
                $errors[] = 'READING_PASSAGES: passage_code "' . $code . '" duplikat.';
                continue;
            }

            $seenCodes[$code] = true;

            $passages[] = [
                'passage_code' => $code,
                'title' => $title,
                'passage_text' => $text,
                'sort_order' => count($passages) + 1,
            ];
        }

        return $passages;
    }

    private function parseQuestionSheet(
        ?Worksheet $sheet,
        string $sheetName,
        string $sectionType,
        bool $requiresPassageCode,
        array &$errors,
    ): array {
        if (! $sheet) {
            $errors[] = 'Sheet ' . $sheetName . ' wajib ada.';

            return [];
        }

        $rows = $sheet->toArray(null, true, true, false);
        if ($rows === []) {
            $errors[] = 'Sheet ' . $sheetName . ' kosong.';

            return [];
        }

        $headerMap = $this->buildHeaderMap($rows[0]);
        $requiredHeadings = [
            'question_no',
            'option_a',
            'option_b',
            'option_c',
            'option_d',
            'correct_option',
        ];

        if ($sectionType !== EptOnlineSection::TYPE_LISTENING) {
            $requiredHeadings[] = 'question_text';
        }

        if ($requiresPassageCode) {
            $requiredHeadings[] = 'passage_code';
        }

        $sheetErrors = [];
        foreach ($requiredHeadings as $requiredHeading) {
            if (! isset($headerMap[$requiredHeading])) {
                $sheetErrors[] = 'Sheet ' . $sheetName . ' wajib memiliki kolom ' . $requiredHeading . '.';
            }
        }

        if ($sheetErrors !== []) {
            array_push($errors, ...$sheetErrors);

            return [];
        }

        $questions = [];
        $seenNumbers = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $rowNumber = $offset + 2;
            $questionNoValue = $row[$headerMap['question_no']] ?? null;
            $questionNo = is_numeric($questionNoValue) ? (int) $questionNoValue : null;
            $questionText = isset($headerMap['question_text'])
                ? $this->normalizeString($row[$headerMap['question_text']] ?? null)
                : null;
            $correctOption = strtoupper((string) $this->normalizeString($row[$headerMap['correct_option']] ?? null));
            $passageCode = $requiresPassageCode
                ? $this->normalizeString($row[$headerMap['passage_code']] ?? null)
                : null;

            if ($questionNo === null || $questionNo <= 0) {
                $errors[] = $sheetName . ' baris ' . $rowNumber . ': question_no harus berupa angka lebih dari 0.';
                continue;
            }

            if (isset($seenNumbers[$questionNo])) {
                $errors[] = $sheetName . ': question_no ' . $questionNo . ' duplikat.';
                continue;
            }

            $seenNumbers[$questionNo] = true;

            if ($sectionType !== EptOnlineSection::TYPE_LISTENING && ! filled($questionText)) {
                $errors[] = $sheetName . ' baris ' . $rowNumber . ': question_text wajib diisi.';
            }

            if ($sectionType === EptOnlineSection::TYPE_LISTENING && ! filled($questionText)) {
                $questionText = 'Listening Question ' . $questionNo;
            }

            foreach (['option_a', 'option_b', 'option_c', 'option_d'] as $optionKey) {
                if (! filled($row[$headerMap[$optionKey]] ?? null)) {
                    $errors[] = $sheetName . ' baris ' . $rowNumber . ': ' . $optionKey . ' wajib diisi.';
                }
            }

            if (! in_array($correctOption, ['A', 'B', 'C', 'D'], true)) {
                $errors[] = $sheetName . ' baris ' . $rowNumber . ': correct_option harus A/B/C/D.';
            }

            if ($requiresPassageCode && ! filled($passageCode)) {
                $errors[] = $sheetName . ' baris ' . $rowNumber . ': passage_code wajib diisi.';
            }

            $questions[] = [
                'section_type' => $sectionType,
                'question_no' => $questionNo,
                'part' => $this->normalizeString($row[$headerMap['part']] ?? null),
                'passage_code' => $passageCode,
                'group_code' => $this->normalizeString($row[$headerMap['group_code']] ?? null),
                'instruction' => $this->normalizeString($row[$headerMap['instruction']] ?? null),
                'question_text' => $questionText,
                'option_a' => $this->normalizeString($row[$headerMap['option_a']] ?? null),
                'option_b' => $this->normalizeString($row[$headerMap['option_b']] ?? null),
                'option_c' => $this->normalizeString($row[$headerMap['option_c']] ?? null),
                'option_d' => $this->normalizeString($row[$headerMap['option_d']] ?? null),
                'correct_option' => $correctOption,
            ];
        }

        usort($questions, fn (array $a, array $b): int => $a['question_no'] <=> $b['question_no']);

        return $questions;
    }

    private function buildHeaderMap(array $headingRow): array
    {
        $map = [];

        foreach ($headingRow as $index => $heading) {
            $normalized = $this->normalizeHeading($this->normalizeString($heading));
            if ($normalized !== '') {
                $map[$normalized] = $index;
            }
        }

        return $map;
    }

    private function makeSummary(array $parsed, string $fileName, string $scope = self::IMPORT_SCOPE_FULL): array
    {
        $meta = $parsed['meta'];

        return [
            'file_name' => $fileName,
            'scope' => $scope,
            'scope_label' => self::importScopeLabel($scope),
            'title' => $meta['title'] ?? null,
            'durations' => [
                EptOnlineSection::TYPE_LISTENING => (int) $meta['listening_duration_minutes'],
                EptOnlineSection::TYPE_STRUCTURE => (int) $meta['structure_duration_minutes'],
                EptOnlineSection::TYPE_READING => (int) $meta['reading_duration_minutes'],
            ],
            'counts' => [
                EptOnlineSection::TYPE_LISTENING => count($parsed['listening']),
                EptOnlineSection::TYPE_STRUCTURE => count($parsed['structure']),
                EptOnlineSection::TYPE_READING => count($parsed['reading_questions']),
            ],
            'reading_passages' => count($parsed['reading_passages']),
            'total_questions' => count($parsed['listening']) + count($parsed['structure']) + count($parsed['reading_questions']),
            'imported_at' => now()->toDateTimeString(),
        ];
    }

    private function filterNestedMeta(array $data): array
    {
        $filtered = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nested = $this->filterNestedMeta($value);
                if ($nested !== []) {
                    $filtered[$key] = $nested;
                }

                continue;
            }

            if ($value !== null && $value !== '') {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (filled($this->normalizeString($value))) {
                return false;
            }
        }

        return true;
    }

    private function normalizeHeading(?string $value): string
    {
        return (string) preg_replace('/[^a-z0-9]+/i', '_', strtolower(trim((string) $value)));
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
