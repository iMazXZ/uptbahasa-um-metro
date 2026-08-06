<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Penerjemahan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'submission_date'        => 'datetime',
        'completion_date'        => 'datetime',
        'issued_at'              => 'datetime',
        'revoked_at'             => 'datetime',
        'source_word_count'      => 'integer',
        'translated_word_count'  => 'integer',
        'version'                => 'integer',
    ];

    /* =========================
     |  Relations
     |=========================*/
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function translator()
    {
        return $this->belongsTo(User::class, 'translator_id');
    }

    /* =========================
     |  Model Events
     |=========================*/
    protected static function booted(): void
    {
        static::saving(function (self $m) {
            // Normalisasi teks (hapus spasi berlebih, kosongkan jika jadi string kosong)
            $m->source_text     = self::cleanText($m->source_text);
            $m->translated_text = self::cleanText($m->translated_text);

            // Hitung kata (fallback bila tidak dikirim dari form)
            $m->source_word_count     = self::countWords($m->source_text);
            $m->translated_word_count = self::countWords($m->translated_text);
        });
    }

    /* =========================
     |  Helpers (text/wordcount)
     |=========================*/
    protected static function cleanText(?string $text): ?string
    {
        if ($text === null) return null;
        $plain = trim(preg_replace('/\s+/u', ' ', $text));
        return $plain === '' ? null : $plain;
    }

    protected static function countWords(?string $text): int
    {
        if (!$text) return 0;
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
        if ($plain === '') return 0;

        // Tambahkan alfabet latin umum agar hitungan lebih akurat
        return str_word_count(
            $plain,
            0,
            'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'
        );
    }

    /* =========================
     |  Verification & PDF
     |=========================*/
    public function isRevoked(): bool
    {
        return !is_null($this->revoked_at);
    }

    /**
     * Pastikan record memiliki verification_code & verification_url.
     * Panggil sebelum generate PDF.
     */
    public function ensureVerification(): void
    {
        if ($this->verification_code) {
            return;
        }

        // Kode pendek, readable (misal: 10 char upper)
        $code = strtoupper(Str::random(10));

        $this->verification_code = $code;
        $this->verification_url = route('verification.show', ['code' => $code]);

        $this->save();
    }

    /**
     * URL publik PDF (jika tersimpan).
     */
    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->pdf_path) return null;
        return Storage::disk('public')->url($this->pdf_path);
    }

    public function officialPdfFilename(?int $version = null): string
    {
        $code = $this->verification_code ?: 'UNVERIFIED';
        $ver  = max(1, (int) ($version ?? $this->version ?? 1));
        return "{$code}-v{$ver}.pdf";
    }
}
