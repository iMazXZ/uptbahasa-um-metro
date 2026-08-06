<?php

namespace App\Support;

class NormalizeWhatsAppNumber
{
    /**
     * Normalisasi nomor WhatsApp ke format internasional (tanpa +)
     * 
     * Input yang didukung:
     * - 085712345678 → 6285712345678
     * - 6285712345678 → 6285712345678
     * - +6285712345678 → 6285712345678
     * - 0857-1234-5678 → 6285712345678
     * 
     * @param string|null $number Input dari user
     * @return string|null Nomor ternormalisasi atau null jika invalid
     */
    public static function normalize(?string $number): ?string
    {
        if (empty($number)) {
            return null;
        }

        // Hapus semua karakter non-digit
        $clean = preg_replace('/\D/', '', $number);

        // Jika kosong setelah dibersihkan
        if (empty($clean)) {
            return null;
        }

        // Jika mulai dengan 0, ganti dengan 62 (Indonesia)
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        }

        // Validasi panjang (minimum 10, maksimum 15 digit)
        if (strlen($clean) < 10 || strlen($clean) > 15) {
            return null;
        }

        return $clean;
    }

    /**
     * Validasi apakah nomor valid untuk WhatsApp
     */
    public static function isValid(?string $number): bool
    {
        return self::normalize($number) !== null;
    }

    /**
     * Format nomor untuk display (dengan +)
     * 
     * @param string|null $number Nomor ternormalisasi
     * @return string|null Nomor dengan format +62xxx
     */
    public static function formatForDisplay(?string $number): ?string
    {
        $normalized = self::normalize($number);
        
        if ($normalized === null) {
            return null;
        }

        return '+' . $normalized;
    }
}
