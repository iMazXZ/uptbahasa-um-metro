<?php

namespace App\Support;

class BlGrading
{
    public static function letter(float $x): string
    {
        if ($x < 48.5) return 'E';
        if ($x < 52.5) return 'D';
        if ($x < 56.5) return 'C-';
        if ($x < 60.5) return 'C';
        if ($x < 64.5) return 'C+';
        if ($x < 68.5) return 'B-';
        if ($x < 72.5) return 'B';
        if ($x < 76.5) return 'B+';
        if ($x < 79.5) return 'A-';
        return 'A';
    }
}
