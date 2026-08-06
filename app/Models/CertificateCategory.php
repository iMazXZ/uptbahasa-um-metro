<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CertificateCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'code_prefix',
        'number_format',
        'last_sequence',
        'semesters',
        'score_fields',
        'grade_rules',
        'pdf_template',
        'is_active',
    ];

    protected $casts = [
        'semesters' => 'array',
        'score_fields' => 'array',
        'grade_rules' => 'array',
        'is_active' => 'boolean',
        'last_sequence' => 'integer',
    ];

    public function certificates(): HasMany
    {
        return $this->hasMany(ManualCertificate::class, 'category_id');
    }

    /**
     * Generate nomor sertifikat berikutnya
     * Format placeholders:
     * - Default: {seq}, {seq3}, {semester}, {year}, {year_short}
     * - CSV import: {no_induk}, {no_induk3}, {group}, {absen}, {year_csv}, {year_plus_one}
     */
    public function generateCertificateNumber(?int $semester = null, array $extraReplacements = []): string
    {
        $this->increment('last_sequence');
        
        $replacements = [
            '{seq}' => $this->last_sequence,
            '{seq3}' => str_pad((string) $this->last_sequence, 3, '0', STR_PAD_LEFT),
            '{semester}' => $semester ?? '',
            '{year}' => now()->year,
            '{year_short}' => now()->format('y'),
        ];

        if (! empty($extraReplacements)) {
            $replacements = array_merge($replacements, $extraReplacements);
        }

        return str_replace(
            array_keys($replacements),
            array_map(static fn ($value): string => (string) $value, array_values($replacements)),
            $this->number_format
        );
    }

    /**
     * Get semester options for select
     */
    public function getSemesterOptions(): array
    {
        if (empty($this->semesters)) {
            return [];
        }

        return collect($this->semesters)
            ->mapWithKeys(fn($s) => [$s => "Semester $s"])
            ->all();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }
}
