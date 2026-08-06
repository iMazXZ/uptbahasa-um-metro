<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasicListeningQuestion extends Model
{

    protected $fillable = [
        'quiz_id',
        'type',
        'question',
        'option_a', 'option_b', 'option_c', 'option_d', 'correct',
        'order',

        'paragraph_text',
        'fib_placeholders',     // array angka hasil deteksi [[n]]
        'fib_answer_key',       // map: { "1": "x", "2": ["a","b"], "3": {"regex":"..."} }
        'fib_weights',          // map: { "1": 1, "2": 2, ... }
        'fib_scoring',          // map: { mode:'exact', case_sensitive:false, allow_trim:true, strip_punctuation:true }

        // Legacy (jika masih ada data lama)
        'answer_keys',
        'scoring',
    ];

    /**
     * Casting tipe data dari/ke database.
     * Pastikan JSON terbaca sebagai array di PHP/Filament.
     */
    protected $casts = [
        'order'           => 'integer',

        // Legacy
        'answer_keys'     => 'array',
        'scoring'         => 'array',

        // FIB
        'fib_placeholders'=> 'array',
        'fib_answer_key'  => 'array',
        'fib_weights'     => 'array',
        'fib_scoring'     => 'array',
    ];

    /**
     * Default value yang aman.
     */
    protected $attributes = [
        'type' => 'multiple_choice',
    ];

    /**
     * Relasi ke quiz.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(BasicListeningQuiz::class, 'quiz_id');
    }

    /**
     * ---- Normalisasi halus (opsional tapi membantu) ----
     * Bikin semua flag di fib_scoring selalu boolean murni saat diakses.
     * Menghindari bug ketika nilai tersimpan sebagai "1"/"0"/"true"/null.
     */
    public function getFibScoringAttribute($value)
    {
        $data = is_string($value) ? (json_decode($value, true) ?: []) : ((array) $value);

        $bool = static function ($v) {
            // true untuk: true, 1, "1", "true", "on", "yes"
            // false untuk lainnya (termasuk null)
            return filter_var($v, FILTER_VALIDATE_BOOLEAN);
        };

        return [
            'mode'              => $data['mode']              ?? 'exact',
            'case_sensitive'    => $bool($data['case_sensitive'] ?? false),
            'allow_trim'        => $bool($data['allow_trim'] ?? true),
            'strip_punctuation' => $bool($data['strip_punctuation'] ?? true),
        ];
    }

    /**
     * Pastikan saat menyimpan fib_scoring tetap JSON array (bukan string ganda-encode).
     */
    public function setFibScoringAttribute($value): void
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $this->attributes['fib_scoring'] = json_encode($decoded ?: []);
        } else {
            $this->attributes['fib_scoring'] = json_encode($value ?: []);
        }
    }
}
