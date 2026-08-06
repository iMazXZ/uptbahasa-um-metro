<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ManualCertificate extends Model
{
    protected $fillable = [
        'category_id',
        'certificate_number',
        'semester',
        'name',
        'srn',
        'study_program',
        'scores',
        'total_score',
        'average_score',
        'grade',
        'issued_at',
        'verification_code',
    ];

    protected $casts = [
        'scores' => 'array',
        'total_score' => 'integer',
        'average_score' => 'decimal:2',
        'issued_at' => 'date',
        'semester' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CertificateCategory::class, 'category_id');
    }

    /**
     * Calculate total and average from scores
     */
    public function calculateScores(): void
    {
        if (empty($this->scores)) {
            return;
        }

        $values = array_filter(array_values($this->scores), fn($v) => is_numeric($v));
        
        if (count($values) > 0) {
            $this->total_score = array_sum($values);
            $this->average_score = round($this->total_score / count($values), 2);
        }
    }

    /**
     * Auto-determine grade based on category rules or default
     */
    public function determineGrade(): void
    {
        if ($this->average_score === null) {
            return;
        }

        $avg = (float) $this->average_score;
        
        // Load category jika belum
        $category = $this->category ?? CertificateCategory::find($this->category_id);
        $rules = $category?->grade_rules;

        // Jika kategori punya grade_rules, gunakan itu
        if (!empty($rules) && is_array($rules)) {
            // Format: [{"min": 79.5, "grade": "A", "level": "Excellent"}, ...]
            // Sort descending by min
            usort($rules, fn($a, $b) => ($b['min'] ?? 0) <=> ($a['min'] ?? 0));
            
            foreach ($rules as $rule) {
                if ($avg >= ($rule['min'] ?? 0)) {
                    $grade = $rule['grade'] ?? 'A';
                    $level = $rule['level'] ?? '';
                    $this->grade = $level ? "{$grade} {$level}" : $grade;
                    return;
                }
            }
            
            // Default ke rule terakhir jika tidak ada yang match
            $lastRule = end($rules);
            $this->grade = ($lastRule['level'] ?? '') 
                ? "{$lastRule['grade']} {$lastRule['level']}" 
                : ($lastRule['grade'] ?? 'E');
            return;
        }

        // Fallback: default grading
        $this->grade = match (true) {
            $avg >= 79.5 => 'A Excellent',
            $avg >= 76.5 => 'A- Very good',
            $avg >= 72.5 => 'B+ Very good',
            $avg >= 68.5 => 'B Good',
            $avg >= 64.5 => 'B- Good',
            $avg >= 60.5 => 'C+ Enough',
            $avg >= 56.5 => 'C Enough',
            $avg >= 52.5 => 'C- Bad',
            $avg >= 48.5 => 'D Bad',
            default => 'E Very bad',
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // 1. Generate certificate number if missing
            if (empty($model->certificate_number) && !empty($model->category_id)) {
                $category = $model->category ?? CertificateCategory::find($model->category_id);
                if ($category) {
                    $model->certificate_number = $category->generateCertificateNumber($model->semester);
                }
            }

            // 2. Generate verification code dari category prefix + SRN
            if (empty($model->verification_code) && !empty($model->srn)) {
                $category = $model->category ?? CertificateCategory::find($model->category_id);
                $prefix = $category?->code_prefix ?? 'MC';
                $model->verification_code = strtoupper($prefix) . '-' . strtoupper($model->srn);
            } elseif (empty($model->verification_code)) {
                // Fallback jika tidak ada SRN
                $model->verification_code = 'MC-' . strtoupper(Str::random(8));
            }

            // 3. Calculate scores
            $model->calculateScores();
            $model->determineGrade();
        });

        static::updating(function ($model) {
            // Update verification code jika SRN berubah
            if ($model->isDirty('srn') && !empty($model->srn)) {
                $category = $model->category ?? CertificateCategory::find($model->category_id);
                $prefix = $category?->code_prefix ?? 'MC';
                $model->verification_code = strtoupper($prefix) . '-' . strtoupper($model->srn);
            }

            $model->calculateScores();
            $model->determineGrade();
        });
    }
}
