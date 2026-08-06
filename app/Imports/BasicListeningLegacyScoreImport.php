<?php

namespace App\Imports;

use App\Models\BasicListeningLegacyScore;
use App\Models\User;
use App\Support\BlGrading;
use App\Support\LegacyBasicListeningScores;
use Illuminate\Support\Collection;
use RuntimeException;

class BasicListeningLegacyScoreImport
{
    private int $totalRows = 0;
    private int $validRows = 0;
    private int $readyInsertRows = 0;
    private int $readyUpdateRows = 0;
    private int $importedRows = 0;
    private int $updatedRows = 0;
    private int $skippedRows = 0;
    private int $conflictRows = 0;
    private int $syncedUsers = 0;

    /** @var array<string, int> */
    private array $issueReasons = [];

    /** @var array<string, int> */
    private array $skipReasons = [];

    /** @var array<string, int> */
    private array $conflictReasons = [];

    /** @var array<string, array<int, int>> */
    private array $usersBySrn = [];

    /** @var array<int, int> */
    private array $createdRecordIds = [];

    /** @var array<int, array<string, mixed>> */
    private array $updatedRecordSnapshots = [];

    /** @var array<int, array<string, mixed>> */
    private array $syncedUserSnapshots = [];

    /** @var array<string, array<string, mixed>> */
    private array $seenIdentityKeys = [];

    /** @var array<int, array<string, mixed>> */
    private array $sampleReadyRows = [];

    /** @var array<int, array<string, mixed>> */
    private array $sampleIssueRows = [];

    /** @var array<int, array<string, mixed>> */
    private array $conflictDetails = [];

    /** @var array<int, array<string, mixed>> */
    private array $updateDetails = [];

    public function preview(string $filePath, ?int $defaultYear = null): array
    {
        $this->resetState();
        $this->processFile($filePath, $defaultYear, false);

        return $this->summary();
    }

    public function import(string $filePath, ?int $defaultYear = null): array
    {
        $this->resetState();
        $this->bootstrapUserLookup();
        $this->processFile($filePath, $defaultYear, true);

        return $this->summary();
    }

    public function summary(): array
    {
        return [
            'total_rows' => $this->totalRows,
            'valid_rows' => $this->validRows,
            'ready_insert_rows' => $this->readyInsertRows,
            'ready_update_rows' => $this->readyUpdateRows,
            'imported_rows' => $this->importedRows,
            'updated_rows' => $this->updatedRows,
            'skipped_rows' => $this->skippedRows,
            'conflict_rows' => $this->conflictRows,
            'synced_users' => $this->syncedUsers,
            'reason_counts' => $this->issueReasons,
            'skip_reasons' => $this->skipReasons,
            'conflict_reasons' => $this->conflictReasons,
            'sample_ready_rows' => $this->sampleReadyRows,
            'sample_issue_rows' => $this->sampleIssueRows,
            'conflict_details' => $this->conflictDetails,
            'update_details' => $this->updateDetails,
        ];
    }

    public function undoPayload(): array
    {
        return [
            'created_record_ids' => array_values(array_unique(array_map('intval', $this->createdRecordIds))),
            'updated_record_snapshots' => array_values($this->updatedRecordSnapshots),
            'synced_user_snapshots' => array_values($this->syncedUserSnapshots),
        ];
    }

    private function resetState(): void
    {
        $this->totalRows = 0;
        $this->validRows = 0;
        $this->readyInsertRows = 0;
        $this->readyUpdateRows = 0;
        $this->importedRows = 0;
        $this->updatedRows = 0;
        $this->skippedRows = 0;
        $this->conflictRows = 0;
        $this->syncedUsers = 0;
        $this->issueReasons = [];
        $this->skipReasons = [];
        $this->conflictReasons = [];
        $this->usersBySrn = [];
        $this->createdRecordIds = [];
        $this->updatedRecordSnapshots = [];
        $this->syncedUserSnapshots = [];
        $this->seenIdentityKeys = [];
        $this->sampleReadyRows = [];
        $this->sampleIssueRows = [];
        $this->conflictDetails = [];
        $this->updateDetails = [];
    }

    private function bootstrapUserLookup(): void
    {
        $this->usersBySrn = User::query()
            ->whereNotNull('srn')
            ->get(['id', 'srn'])
            ->groupBy(fn (User $user): ?string => LegacyBasicListeningScores::normalizeSrn($user->srn))
            ->map(fn (Collection $rows): array => $rows->pluck('id')->all())
            ->filter(fn (array $ids, ?string $key): bool => $key !== null)
            ->all();
    }

    private function processFile(string $filePath, ?int $defaultYear, bool $persist): void
    {
        if (! is_file($filePath)) {
            throw new RuntimeException('File CSV tidak ditemukan.');
        }

        $delimiter = $this->detectDelimiter($filePath);
        $sourceYear = $defaultYear ?? $this->extractYearFromFilename($filePath);
        $sourceFile = basename($filePath);

        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            throw new RuntimeException('CSV tidak bisa dibaca.');
        }

        $header = fgetcsv($handle, 0, $delimiter, '"', '\\');
        if (! is_array($header) || $header === []) {
            fclose($handle);
            throw new RuntimeException('Header CSV kosong atau tidak valid.');
        }

        $map = $this->mapHeaders($header);
        if (! isset($map['score'])) {
            fclose($handle);
            throw new RuntimeException('Kolom nilai tidak ditemukan. Gunakan header seperti SCORE atau NILAI.');
        }

        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $rowNumber++;

            if ($this->isBlankRow($row)) {
                continue;
            }

            $this->totalRows++;

            $payload = $this->extractPayload($row, $map, $sourceYear, $sourceFile);
            if ($payload === null) {
                $this->skip('Format baris tidak valid', $rowNumber, null);

                continue;
            }

            $inspection = $this->inspectPayload($payload);
            if ($inspection['status'] === 'skip') {
                $this->skip((string) $inspection['reason'], $rowNumber, $payload);

                continue;
            }

            if ($inspection['status'] === 'conflict') {
                $this->conflict(
                    (string) $inspection['reason'],
                    $rowNumber,
                    $payload,
                    is_array($inspection['conflict_context'] ?? null) ? $inspection['conflict_context'] : null,
                );

                continue;
            }

            $identityKey = $inspection['identity_key'];
            if (is_string($identityKey) && $identityKey !== '') {
                $this->seenIdentityKeys[$identityKey] = [
                    'row' => $rowNumber,
                    'srn' => $payload['srn'] ?? null,
                    'name' => $payload['name'] ?? null,
                    'study_program' => $payload['study_program'] ?? null,
                    'source_year' => $payload['source_year'] ?? null,
                ];
            }

            /** @var BasicListeningLegacyScore $record */
            $record = $inspection['record'];
            $exists = $record->exists;

            $this->validRows++;
            if ($exists) {
                $this->readyUpdateRows++;
                $this->storeUpdateDetail($rowNumber, $payload, $record);
            } else {
                $this->readyInsertRows++;
            }
            $this->storeReadySample($rowNumber, $payload, $exists ? 'update' : 'insert', $record);

            if (! $persist) {
                continue;
            }

            if ($exists && ! isset($this->updatedRecordSnapshots[$record->id])) {
                $this->updatedRecordSnapshots[$record->id] = $record->only([
                    'id',
                    'srn',
                    'srn_normalized',
                    'name',
                    'name_normalized',
                    'study_program',
                    'source_year',
                    'score',
                    'grade',
                    'source_file',
                    'meta',
                ]);
            }

            $record->fill($payload);
            $record->save();

            if ($exists) {
                $this->updatedRows++;
            } else {
                $this->importedRows++;
                $this->createdRecordIds[] = $record->id;
            }

            $this->syncMatchingUsers($record);
        }

        fclose($handle);
    }

    private function detectDelimiter(string $filePath): string
    {
        $handle = fopen($filePath, 'rb');
        $line = $handle ? (string) fgets($handle) : '';
        if (is_resource($handle)) {
            fclose($handle);
        }

        $delimiters = [
            ';' => substr_count($line, ';'),
            ',' => substr_count($line, ','),
            "\t" => substr_count($line, "\t"),
        ];

        arsort($delimiters);
        $delimiter = array_key_first($delimiters);

        return $delimiter ?: ';';
    }

    /** @return array<string, int> */
    private function mapHeaders(array $header): array
    {
        $map = [];

        foreach ($header as $index => $column) {
            $normalized = $this->normalizeHeader((string) $column);

            if (in_array($normalized, ['SRN', 'NPM', 'NIM', 'NPM NIM'], true)) {
                $map['srn'] = $index;
                continue;
            }

            if (in_array($normalized, ['NAME', 'NAMA', 'NAMA MAHASISWA', 'STUDENT NAME'], true)) {
                $map['name'] = $index;
                continue;
            }

            if (in_array($normalized, ['PRODI', 'PROGRAM STUDI', 'STUDY PROGRAM', 'PROGRAMSTUDI'], true)) {
                $map['study_program'] = $index;
                continue;
            }

            if (in_array($normalized, ['GROUP', 'GRUP'], true)) {
                $map['group'] = $index;
                continue;
            }

            if (in_array($normalized, ['NO ABSEN', 'NOABSEN', 'ABSEN', 'NO ABSN'], true)) {
                $map['attendance_no'] = $index;
                continue;
            }

            if (in_array($normalized, ['NO INDUK', 'NOINDUK'], true)) {
                $map['student_no'] = $index;
                continue;
            }

            if (in_array($normalized, ['SCORE', 'NILAI', 'NILAI BL', 'NILAI BASIC LISTENING', 'BASIC LISTENING', 'FINAL SCORE', 'NILAI AKHIR'], true)) {
                $map['score'] = $index;
                continue;
            }

            if (in_array($normalized, ['GRADE', 'ALPHABETICAL', 'HURUF'], true)) {
                $map['grade'] = $index;
                continue;
            }

            if (in_array($normalized, ['YEAR', 'TAHUN', 'ANGKATAN'], true)) {
                $map['source_year'] = $index;
            }
        }

        return $map;
    }

    /** @return array<string, mixed>|null */
    private function extractPayload(array $row, array $map, ?int $defaultYear, string $sourceFile): ?array
    {
        $srn = isset($map['srn']) ? trim((string) ($row[$map['srn']] ?? '')) : null;
        $name = isset($map['name']) ? trim((string) ($row[$map['name']] ?? '')) : null;
        $name = filled($name) ? mb_strtoupper($name, 'UTF-8') : null;
        $studyProgram = isset($map['study_program']) ? trim((string) ($row[$map['study_program']] ?? '')) : null;
        $group = isset($map['group']) ? trim((string) ($row[$map['group']] ?? '')) : null;
        $attendanceNo = isset($map['attendance_no']) ? trim((string) ($row[$map['attendance_no']] ?? '')) : null;
        $studentNo = isset($map['student_no']) ? trim((string) ($row[$map['student_no']] ?? '')) : null;
        $score = isset($map['score']) ? $this->parseScore($row[$map['score']] ?? null) : null;
        $grade = isset($map['grade']) ? trim((string) ($row[$map['grade']] ?? '')) : null;
        $grade = filled($grade) ? mb_strtoupper($grade, 'UTF-8') : null;
        $sourceYear = isset($map['source_year'])
            ? $this->parseYear($row[$map['source_year']] ?? null)
            : $defaultYear;

        return [
            'srn' => $srn ?: null,
            'srn_normalized' => LegacyBasicListeningScores::normalizeSrn($srn),
            'name' => $name ?: null,
            'name_normalized' => LegacyBasicListeningScores::normalizeName($name),
            'study_program' => $studyProgram ?: null,
            'source_year' => $sourceYear,
            'score' => $score,
            'grade' => $grade ?: null,
            'source_file' => $sourceFile,
            'meta' => [
                'imported_via' => 'csv',
                'group' => $group ?: null,
                'attendance_no' => $attendanceNo ?: null,
                'student_no' => $studentNo ?: null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{status: string, reason?: string, identity_key?: string|null, record?: BasicListeningLegacyScore, conflict_context?: array<string, mixed>}
     */
    private function inspectPayload(array $payload): array
    {
        if (! is_numeric($payload['score'] ?? null)) {
            return [
                'status' => 'skip',
                'reason' => 'Nilai kosong atau tidak valid',
            ];
        }

        if ((float) $payload['score'] < 55 || (($payload['grade'] ?? null) === 'E')) {
            return [
                'status' => 'skip',
                'reason' => 'Tidak lulus (nilai di bawah 55 / grade E)',
            ];
        }

        if (blank($payload['srn']) && blank($payload['name'])) {
            return [
                'status' => 'skip',
                'reason' => 'SRN/NPM dan nama sama-sama kosong',
            ];
        }

        $identityKey = $this->resolveIdentityKey($payload);
        if ($identityKey === null) {
            return [
                'status' => 'skip',
                'reason' => 'Nama tanpa SRN wajib disertai tahun data',
            ];
        }

        if (isset($this->seenIdentityKeys[$identityKey])) {
            return [
                'status' => 'conflict',
                'reason' => 'Duplikat identitas pada CSV',
                'conflict_context' => [
                    'type' => 'csv_duplicate',
                    'conflicting_with' => $this->seenIdentityKeys[$identityKey],
                ],
            ];
        }

        if (filled($payload['srn_normalized'])) {
            return [
                'status' => 'ok',
                'identity_key' => $identityKey,
                'record' => BasicListeningLegacyScore::query()->firstOrNew([
                    'srn_normalized' => $payload['srn_normalized'],
                ]),
            ];
        }

        $query = BasicListeningLegacyScore::query()
            ->where('name_normalized', $payload['name_normalized'])
            ->where('source_year', $payload['source_year'])
            ->where('study_program', $payload['study_program']);

        $matches = (clone $query)->count();
        if ($matches > 1) {
            $existingMatches = (clone $query)
                ->orderBy('id')
                ->limit(5)
                ->get(['id', 'srn', 'name', 'study_program', 'source_year', 'score'])
                ->map(fn (BasicListeningLegacyScore $row): array => [
                    'id' => $row->id,
                    'srn' => $row->srn,
                    'name' => $row->name,
                    'study_program' => $row->study_program,
                    'source_year' => $row->source_year,
                    'score' => is_numeric($row->score) ? (int) round((float) $row->score) : null,
                ])
                ->all();

            return [
                'status' => 'conflict',
                'reason' => 'Data existing ambigu (nama/tahun/prodi)',
                'conflict_context' => [
                    'type' => 'existing_ambiguous',
                    'existing_match_count' => $matches,
                    'existing_matches' => $existingMatches,
                ],
            ];
        }

        return [
            'status' => 'ok',
            'identity_key' => $identityKey,
            'record' => $query->first() ?? new BasicListeningLegacyScore(),
        ];
    }

    /** @param array<string, mixed> $payload */
    private function resolveIdentityKey(array $payload): ?string
    {
        $srnNormalized = $payload['srn_normalized'] ?? null;
        if (is_string($srnNormalized) && $srnNormalized !== '') {
            return 'srn:' . $srnNormalized;
        }

        $nameNormalized = $payload['name_normalized'] ?? null;
        $sourceYear = $payload['source_year'] ?? null;
        if (! is_string($nameNormalized) || $nameNormalized === '' || ! is_numeric($sourceYear)) {
            return null;
        }

        return 'identity:' . implode('|', [
            $nameNormalized,
            (string) (int) $sourceYear,
            (string) ($payload['study_program'] ?? ''),
        ]);
    }

    private function parseScore(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, ',') && ! str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function parseYear(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/[^0-9]/', '', (string) $value);
        if (! $normalized || strlen($normalized) !== 4) {
            return null;
        }

        return (int) $normalized;
    }

    private function normalizeHeader(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;

        return (string) str($value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', ' ')
            ->trim();
    }

    private function extractYearFromFilename(string $filePath): ?int
    {
        if (preg_match('/(20\d{2})/', basename($filePath), $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed>|null $payload */
    private function skip(string $reason, int $rowNumber, ?array $payload): void
    {
        $this->skippedRows++;
        $this->skipReasons[$reason] = ($this->skipReasons[$reason] ?? 0) + 1;
        $this->issueReasons[$reason] = ($this->issueReasons[$reason] ?? 0) + 1;
        $this->storeIssueSample($rowNumber, $reason, 'skip', $payload);
    }

    /** @param array<string, mixed>|null $payload */
    private function conflict(string $reason, int $rowNumber, ?array $payload, ?array $context = null): void
    {
        $this->skippedRows++;
        $this->conflictRows++;
        $this->conflictReasons[$reason] = ($this->conflictReasons[$reason] ?? 0) + 1;
        $this->issueReasons[$reason] = ($this->issueReasons[$reason] ?? 0) + 1;
        $this->storeConflictDetail($rowNumber, $reason, $payload, $context);
        $this->storeIssueSample($rowNumber, $reason, 'conflict', $payload);
    }

    /** @param array<string, mixed> $payload */
    private function storeReadySample(int $rowNumber, array $payload, string $mode, ?BasicListeningLegacyScore $record = null): void
    {
        if (count($this->sampleReadyRows) >= 8) {
            return;
        }

        $score = is_numeric($payload['score'] ?? null)
            ? (int) round((float) $payload['score'])
            : null;

        $this->sampleReadyRows[] = [
            'row' => $rowNumber,
            'mode' => $mode,
            'srn' => $payload['srn'] ?? null,
            'name' => $payload['name'] ?? null,
            'study_program' => $payload['study_program'] ?? null,
            'source_year' => $payload['source_year'] ?? null,
            'score' => $score,
            'grade' => $payload['grade'] ?: ($score !== null ? BlGrading::letter((float) $score) : null),
            'existing_id' => $mode === 'update' ? $record?->id : null,
        ];
    }

    /** @param array<string, mixed>|null $payload */
    private function storeIssueSample(int $rowNumber, string $reason, string $type, ?array $payload): void
    {
        if (count($this->sampleIssueRows) >= 8) {
            return;
        }

        $this->sampleIssueRows[] = [
            'row' => $rowNumber,
            'type' => $type,
            'reason' => $reason,
            'srn' => $payload['srn'] ?? null,
            'name' => $payload['name'] ?? null,
            'study_program' => $payload['study_program'] ?? null,
            'source_year' => $payload['source_year'] ?? null,
        ];
    }

    /** @param array<string, mixed>|null $payload */
    private function storeConflictDetail(int $rowNumber, string $reason, ?array $payload, ?array $context = null): void
    {
        $this->conflictDetails[] = [
            'row' => $rowNumber,
            'reason' => $reason,
            'srn' => $payload['srn'] ?? null,
            'name' => $payload['name'] ?? null,
            'study_program' => $payload['study_program'] ?? null,
            'source_year' => $payload['source_year'] ?? null,
            'score' => is_numeric($payload['score'] ?? null)
                ? (int) round((float) $payload['score'])
                : null,
            'context' => $context,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function storeUpdateDetail(int $rowNumber, array $payload, BasicListeningLegacyScore $record): void
    {
        $this->updateDetails[] = [
            'row' => $rowNumber,
            'existing_id' => $record->id,
            'existing_score' => is_numeric($record->score) ? (int) round((float) $record->score) : null,
            'existing_grade' => $record->grade,
            'srn' => $payload['srn'] ?? null,
            'name' => $payload['name'] ?? null,
            'study_program' => $payload['study_program'] ?? null,
            'source_year' => $payload['source_year'] ?? null,
            'incoming_score' => is_numeric($payload['score'] ?? null)
                ? (int) round((float) $payload['score'])
                : null,
            'incoming_grade' => filled($payload['grade'] ?? null)
                ? $payload['grade']
                : (is_numeric($payload['score'] ?? null) ? BlGrading::letter((float) $payload['score']) : null),
        ];
    }

    private function syncMatchingUsers(BasicListeningLegacyScore $record): void
    {
        $srn = $record->srn_normalized;
        if ($srn === null || ! is_numeric($record->score)) {
            return;
        }

        $userIds = $this->usersBySrn[$srn] ?? [];
        if ($userIds === []) {
            return;
        }

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'year', 'nilaibasiclistening']);

        foreach ($users as $user) {
            if (! isset($this->syncedUserSnapshots[$user->id])) {
                $this->syncedUserSnapshots[$user->id] = [
                    'id' => $user->id,
                    'year' => $user->year,
                    'nilaibasiclistening' => $user->nilaibasiclistening,
                ];
            }

            LegacyBasicListeningScores::applyScoreToUser($user, (float) $record->score);
            $this->syncedUsers++;
        }
    }
}
