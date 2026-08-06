<?php

namespace App\Support;

class Verification
{
    public static function generateCode(int $bytes = 18): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }
}
