<?php

namespace App\Models;

use App\Support\InteractiveClassScores;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InteractiveClassScore extends Model
{
    public const TRACK_ENGLISH = 'english';
    public const TRACK_ARABIC = 'arabic';

    protected $fillable = [
        'srn',
        'srn_normalized',
        'name',
        'name_normalized',
        'study_program',
        'track',
        'semester',
        'source_year',
        'score',
        'grade',
        'source_file',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'source_year' => 'integer',
            'score' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->name = filled($model->name)
                ? mb_strtoupper(trim((string) $model->name), 'UTF-8')
                : null;
            $model->grade = filled($model->grade)
                ? mb_strtoupper(trim((string) $model->grade), 'UTF-8')
                : null;
            $model->track = InteractiveClassScores::normalizeTrack($model->track);
            $model->srn_normalized = InteractiveClassScores::normalizeSrn($model->srn);
            $model->name_normalized = InteractiveClassScores::normalizeName($model->name);

            if (is_numeric($model->score)) {
                $model->score = round((float) $model->score, 2);
            }
        });
    }

    public function scopeSearch(Builder $query, string $rawQuery): Builder
    {
        return InteractiveClassScores::applySearch($query, $rawQuery);
    }

    /** @return array<string, string> */
    public static function trackOptions(): array
    {
        return [
            static::TRACK_ENGLISH => 'Interactive Class',
            static::TRACK_ARABIC => 'Interactive Bahasa Arab',
        ];
    }
}
