<?php

namespace App\Models;

use App\Support\BlGrading;
use App\Support\LegacyBasicListeningScores;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BasicListeningLegacyScore extends Model
{
    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'source_year' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->name = filled($model->name)
                ? mb_strtoupper(trim((string) $model->name), 'UTF-8')
                : null;
            $model->srn_normalized = LegacyBasicListeningScores::normalizeSrn($model->srn);
            $model->name_normalized = LegacyBasicListeningScores::normalizeName($model->name);

            if (is_numeric($model->score)) {
                $model->score = round((float) $model->score, 2);
                $model->grade = BlGrading::letter((float) $model->score);
            } else {
                $model->grade = null;
            }
        });
    }

    public function scopeSearch(Builder $query, string $rawQuery): Builder
    {
        return LegacyBasicListeningScores::applySearch($query, $rawQuery);
    }
}
