<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EptOnlineResult extends Model
{
    public const SCALE_VERSION_AUTO = 'EPT_AUTO_TABLE_V1';
    public const CEFR_BELOW_A2 = 'Below A2';

    private const TOTAL_CEFR_CUT_SCORES = [
        'C1' => 620,
        'B2' => 543,
        'B1' => 433,
        'A2' => 343,
    ];

    private const SECTION_CEFR_CUT_SCORES = [
        'listening' => [
            'C1' => 62,
            'B2' => 55,
            'B1' => 46,
            'A2' => 38,
        ],
        'structure' => [
            'C1' => 64,
            'B2' => 53,
            'B1' => 43,
            'A2' => 32,
        ],
        'reading' => [
            'C1' => 60,
            'B2' => 55,
            'B1' => 41,
            'A2' => 33,
        ],
    ];

    private const LISTENING_SCALE_MAP = [
        50 => 68, 49 => 67, 48 => 66, 47 => 65, 46 => 63, 45 => 62, 44 => 61, 43 => 60, 42 => 59, 41 => 58,
        40 => 57, 39 => 57, 38 => 56, 37 => 55, 36 => 54, 35 => 54, 34 => 53, 33 => 52, 32 => 51, 31 => 51,
        30 => 51, 29 => 50, 28 => 49, 27 => 49, 26 => 48, 25 => 48, 24 => 47, 23 => 47, 22 => 46, 21 => 45,
        20 => 45, 19 => 44, 18 => 43, 17 => 42, 16 => 41, 15 => 41, 14 => 37, 13 => 38, 12 => 37, 11 => 35,
        10 => 33, 9 => 32, 8 => 32, 7 => 31, 6 => 30, 5 => 29, 4 => 28, 3 => 27, 2 => 26, 1 => 25, 0 => 24,
    ];

    private const STRUCTURE_SCALE_MAP = [
        40 => 68, 39 => 67, 38 => 65, 37 => 63, 36 => 61, 35 => 58, 34 => 57, 33 => 56, 32 => 56, 31 => 55,
        30 => 54, 29 => 53, 28 => 52, 27 => 52, 26 => 50, 25 => 49, 24 => 48, 23 => 47, 22 => 46, 21 => 45,
        20 => 44, 19 => 43, 18 => 42, 17 => 41, 16 => 40, 15 => 40, 14 => 38, 13 => 37, 12 => 36, 11 => 35,
        10 => 33, 9 => 31, 8 => 29, 7 => 27, 6 => 26, 5 => 25, 4 => 23, 3 => 22, 2 => 21, 1 => 20, 0 => 20,
    ];

    private const READING_SCALE_MAP = [
        50 => 67, 49 => 66, 48 => 65, 47 => 63, 46 => 61, 45 => 60, 44 => 59, 43 => 58, 42 => 57, 41 => 56,
        40 => 55, 39 => 54, 38 => 54, 37 => 53, 36 => 52, 35 => 52, 34 => 51, 33 => 50, 32 => 49, 31 => 48,
        30 => 48, 29 => 47, 28 => 46, 27 => 46, 26 => 45, 25 => 44, 24 => 43, 23 => 43, 22 => 42, 21 => 41,
        20 => 40, 19 => 39, 18 => 38, 17 => 37, 16 => 36, 15 => 35, 14 => 34, 13 => 32, 12 => 31, 11 => 30,
        10 => 29, 9 => 28, 8 => 28, 7 => 27, 6 => 26, 5 => 25, 4 => 24, 3 => 23, 2 => 23, 1 => 22, 0 => 21,
    ];

    protected $fillable = [
        'attempt_id',
        'listening_raw',
        'structure_raw',
        'reading_raw',
        'listening_scaled',
        'structure_scaled',
        'reading_scaled',
        'total_scaled',
        'scale_version',
        'is_published',
        'published_at',
        'meta',
    ];

    protected $casts = [
        'listening_raw' => 'integer',
        'structure_raw' => 'integer',
        'reading_raw' => 'integer',
        'listening_scaled' => 'integer',
        'structure_scaled' => 'integer',
        'reading_scaled' => 'integer',
        'total_scaled' => 'integer',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'meta' => 'array',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(EptOnlineAttempt::class, 'attempt_id');
    }

    public function overallCefrLevel(): ?string
    {
        return self::totalCefrLevel($this->total_scaled);
    }

    public function listeningCefrLevel(): ?string
    {
        return self::sectionCefrLevel('listening', $this->listening_scaled);
    }

    public function structureCefrLevel(): ?string
    {
        return self::sectionCefrLevel('structure', $this->structure_scaled);
    }

    public function readingCefrLevel(): ?string
    {
        return self::sectionCefrLevel('reading', $this->reading_scaled);
    }

    public static function scaleSectionScore(string $sectionType, int $rawScore): ?int
    {
        $map = match ($sectionType) {
            'listening' => self::LISTENING_SCALE_MAP,
            'structure' => self::STRUCTURE_SCALE_MAP,
            'reading' => self::READING_SCALE_MAP,
            default => null,
        };

        if ($map === null) {
            return null;
        }

        if (array_key_exists($rawScore, $map)) {
            return $map[$rawScore];
        }

        $keys = array_keys($map);
        $min = min($keys);
        $max = max($keys);
        $clamped = max($min, min($max, $rawScore));

        return $map[$clamped] ?? null;
    }

    public static function calculateTotalScaled(?int $listeningScaled, ?int $structureScaled, ?int $readingScaled): ?int
    {
        if ($listeningScaled === null || $structureScaled === null || $readingScaled === null) {
            return null;
        }

        return (int) round((($listeningScaled + $structureScaled + $readingScaled) / 3) * 10, 0, PHP_ROUND_HALF_UP);
    }

    public static function totalCefrLevel(?int $totalScaled): ?string
    {
        if ($totalScaled === null) {
            return null;
        }

        foreach (self::TOTAL_CEFR_CUT_SCORES as $level => $cutScore) {
            if ($totalScaled >= $cutScore) {
                return $level;
            }
        }

        return self::CEFR_BELOW_A2;
    }

    public static function sectionCefrLevel(string $sectionType, ?int $scaledScore): ?string
    {
        if ($scaledScore === null) {
            return null;
        }

        $cutScores = self::SECTION_CEFR_CUT_SCORES[$sectionType] ?? null;
        if ($cutScores === null) {
            return null;
        }

        foreach ($cutScores as $level => $cutScore) {
            if ($scaledScore >= $cutScore) {
                return $level;
            }
        }

        return self::CEFR_BELOW_A2;
    }
}
