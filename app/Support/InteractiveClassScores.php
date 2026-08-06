<?php

namespace App\Support;

use App\Models\InteractiveClassScore;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InteractiveClassScores
{
    public static function normalizeTrack(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            InteractiveClassScore::TRACK_ARABIC, 'arab', 'bahasa_arab', 'arabic', 'interactive_arabic' => InteractiveClassScore::TRACK_ARABIC,
            default => InteractiveClassScore::TRACK_ENGLISH,
        };
    }

    public static function maxSemester(string $track): int
    {
        return static::normalizeTrack($track) === InteractiveClassScore::TRACK_ARABIC ? 2 : 6;
    }

    public static function fieldName(string $track, int $semester): ?string
    {
        $track = static::normalizeTrack($track);

        if ($track === InteractiveClassScore::TRACK_ARABIC) {
            return in_array($semester, [1, 2], true)
                ? 'interactive_bahasa_arab_' . $semester
                : null;
        }

        return $semester >= 1 && $semester <= 6
            ? 'interactive_class_' . $semester
            : null;
    }

    public static function trackLabel(string $track): string
    {
        return static::normalizeTrack($track) === InteractiveClassScore::TRACK_ARABIC
            ? 'Interactive Bahasa Arab'
            : 'Interactive Class';
    }

    public static function semesterLabel(string $track, ?int $semester): ?string
    {
        if (! is_numeric($semester)) {
            return null;
        }

        $semester = (int) $semester;
        $track = static::normalizeTrack($track);

        if ($track === InteractiveClassScore::TRACK_ARABIC) {
            return in_array($semester, [1, 2], true)
                ? 'Bahasa Arab ' . $semester
                : null;
        }

        return $semester >= 1 && $semester <= 6
            ? 'Semester ' . $semester
            : null;
    }

    public static function normalizeSrn(null|string|int $value): ?string
    {
        $normalized = strtoupper((string) $value);
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized ?? '');

        return filled($normalized) ? $normalized : null;
    }

    public static function normalizeName(?string $value): ?string
    {
        $normalized = Str::of((string) $value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9\s]/', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        return filled($normalized) ? $normalized : null;
    }

    public static function applyScoreToUser(User $user, string $track, int $semester, ?float $score): void
    {
        $field = static::fieldName($track, $semester);
        if ($field === null) {
            return;
        }

        $user->{$field} = is_numeric($score) ? round((float) $score, 2) : null;
        $user->save();
    }

    public static function applySearch(Builder $query, string $rawQuery): Builder
    {
        $term = trim($rawQuery);
        $srnNormalized = static::normalizeSrn($term);
        $nameNormalized = static::normalizeName($term);

        return $query
            ->where(function (Builder $sub) use ($srnNormalized, $nameNormalized): void {
                if ($srnNormalized !== null) {
                    $sub->orWhere('srn_normalized', $srnNormalized)
                        ->orWhere('srn_normalized', 'like', $srnNormalized . '%');
                }

                if ($nameNormalized !== null) {
                    $sub->orWhere('name_normalized', 'like', $nameNormalized . '%');
                }
            })
            ->orderByRaw(
                'CASE WHEN srn_normalized = ? THEN 0 WHEN srn_normalized LIKE ? THEN 1 ELSE 2 END',
                [$srnNormalized ?? '', ($srnNormalized ?? '') . '%']
            )
            ->orderByRaw(
                'CASE WHEN track = ? THEN 0 ELSE 1 END',
                [InteractiveClassScore::TRACK_ENGLISH]
            )
            ->orderByDesc('source_year')
            ->orderBy('semester')
            ->orderBy('name');
    }

    public static function nameTokens(?string $value): Collection
    {
        return collect(explode(' ', static::normalizeName($value) ?? ''))
            ->filter(fn (string $token): bool => mb_strlen($token) >= 2)
            ->values();
    }

    public static function toBooleanFullText(Collection $tokens): string
    {
        return $tokens
            ->map(fn (string $token): string => '+' . $token . '*')
            ->implode(' ');
    }

    public static function findByIdentity(null|string|int $srn, ?string $name, ?int $sourceYear, ?int $semester, ?string $track = null): ?InteractiveClassScore
    {
        $track = static::normalizeTrack($track);
        $semester = is_numeric($semester) ? (int) $semester : null;
        if (! $semester || $semester < 1 || $semester > static::maxSemester($track)) {
            return null;
        }

        $srnNormalized = static::normalizeSrn($srn);
        if ($srnNormalized !== null) {
            return InteractiveClassScore::query()
                ->where('track', $track)
                ->where('srn_normalized', $srnNormalized)
                ->where('semester', $semester)
                ->first();
        }

        $nameNormalized = static::normalizeName($name);
        if ($nameNormalized === null || ! $sourceYear) {
            return null;
        }

        $query = InteractiveClassScore::query()
            ->where('track', $track)
            ->where('name_normalized', $nameNormalized)
            ->where('source_year', $sourceYear)
            ->where('semester', $semester);

        return (clone $query)->count() === 1 ? $query->first() : null;
    }
}
