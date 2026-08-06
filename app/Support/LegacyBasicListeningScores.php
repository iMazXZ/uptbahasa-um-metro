<?php

namespace App\Support;

use App\Models\BasicListeningLegacyScore;
use App\Models\BasicListeningGrade;
use App\Models\Prody;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LegacyBasicListeningScores
{
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

    public static function requiresLegacyScore(?int $year, ?string $prodyName): bool
    {
        if (! $year || $year > 2024) {
            return false;
        }

        $prodyName = trim((string) $prodyName);
        if ($prodyName === '') {
            return true;
        }

        $normalizedProdyName = Str::of($prodyName)
            ->lower()
            ->squish()
            ->value();

        if (in_array($normalizedProdyName, ['umum', 'program studi umum'], true)) {
            return false;
        }

        if (str_starts_with($prodyName, 'S2')) {
            return false;
        }

        if ($prodyName === 'Pendidikan Bahasa Inggris') {
            return false;
        }

        return true;
    }

    public static function requiresLegacyScoreForUser(User $user): bool
    {
        return static::requiresLegacyScore(
            year: (int) ($user->year ?? 0),
            prodyName: $user->prody->name ?? null,
        );
    }

    public static function findBySrn(null|string|int $srn): ?BasicListeningLegacyScore
    {
        $srnNormalized = static::normalizeSrn($srn);

        if ($srnNormalized === null) {
            return null;
        }

        return BasicListeningLegacyScore::query()
            ->where('srn_normalized', $srnNormalized)
            ->first();
    }

    public static function findByIdentity(
        null|string|int $srn,
        ?string $name = null,
        ?int $year = null,
    ): ?BasicListeningLegacyScore {
        $bySrn = static::findBySrn($srn);
        if ($bySrn) {
            return $bySrn;
        }

        $nameNormalized = static::normalizeName($name);
        if ($nameNormalized === null || ! $year) {
            return null;
        }

        $query = BasicListeningLegacyScore::query()
            ->where('name_normalized', $nameNormalized)
            ->where('source_year', $year)
            ->orderByDesc('score');

        $count = (clone $query)->count();

        return $count === 1 ? $query->first() : null;
    }

    public static function effectiveScoreForUser(User $user): ?float
    {
        if (! static::requiresLegacyScoreForUser($user)) {
            return is_numeric($user->nilaibasiclistening) ? (float) $user->nilaibasiclistening : null;
        }

        $record = static::findByIdentity(
            srn: $user->srn,
            name: $user->name,
            year: (int) ($user->year ?? 0),
        );
        if ($record && is_numeric($record->score)) {
            return (float) $record->score;
        }

        return is_numeric($user->nilaibasiclistening) ? (float) $user->nilaibasiclistening : null;
    }

    public static function syncUserScore(User $user, bool $save = true): ?float
    {
        $score = static::effectiveScoreForUser($user);

        if ($score === null) {
            return null;
        }

        if ((float) ($user->nilaibasiclistening ?? -1) !== (float) $score) {
            $user->nilaibasiclistening = $score;
            if ($save) {
                $user->save();
            }
        }

        return $score;
    }

    public static function applyScoreToUser(User $user, ?float $score, ?int $gradeYear = null): void
    {
        $numeric = is_numeric($score) ? round((float) $score, 2) : null;
        $user->nilaibasiclistening = $numeric;
        $user->save();

        $gradeYear = $gradeYear ?: ((int) ($user->year ?? 0) ?: null);

        if (! $gradeYear || $gradeYear > 2024) {
            return;
        }

        $grade = BasicListeningGrade::query()
            ->where('user_id', $user->id)
            ->where('user_year', $gradeYear)
            ->first();

        if ($grade === null && $numeric === null) {
            return;
        }

        $grade ??= new BasicListeningGrade([
            'user_id' => $user->id,
            'user_year' => $gradeYear,
        ]);

        $grade->final_numeric_cached = $numeric;
        $grade->final_letter_cached = $numeric !== null
            ? BlGrading::letter($numeric)
            : null;
        $grade->save();
    }

    public static function resolveProdyName(?int $prodyId): ?string
    {
        if (! $prodyId) {
            return null;
        }

        return Prody::query()->whereKey($prodyId)->value('name');
    }

    public static function applySearch(Builder $query, string $rawQuery): Builder
    {
        $term = trim($rawQuery);
        $srnNormalized = static::normalizeSrn($term);
        $nameNormalized = static::normalizeName($term);
        $tokens = static::nameTokens($term);
        $driver = $query->getConnection()->getDriverName();

        return $query
            ->where(function (Builder $sub) use ($srnNormalized, $nameNormalized, $tokens, $driver): void {
                if ($srnNormalized !== null) {
                    $sub->orWhere('srn_normalized', $srnNormalized)
                        ->orWhere('srn_normalized', 'like', $srnNormalized . '%');
                }

                if ($nameNormalized !== null) {
                    $sub->orWhere('name_normalized', 'like', $nameNormalized . '%');
                }

                if ($tokens->isNotEmpty() && in_array($driver, ['mysql', 'mariadb'], true)) {
                    $sub->orWhereFullText('name_normalized', static::toBooleanFullText($tokens), ['mode' => 'boolean']);
                }
            })
            ->orderByRaw(
                'CASE WHEN srn_normalized = ? THEN 0 WHEN srn_normalized LIKE ? THEN 1 ELSE 2 END',
                [$srnNormalized ?? '', ($srnNormalized ?? '') . '%']
            )
            ->orderByDesc('source_year')
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
}
