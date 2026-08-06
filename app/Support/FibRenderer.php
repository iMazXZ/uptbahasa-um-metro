<?php

namespace App\Support;

class FibRenderer
{
    public static function render(string $paragraph, int $questionId, array $old = []): string
    {
        $safe = e($paragraph);
        return preg_replace_callback('/\[\[(\d+)\]\]/', function($m) use ($questionId, $old) {
            $idx   = $m[1];
            $value = $old[$idx] ?? '';
            $name  = "answers[{$questionId}][{$idx}]";
            return '<input type="text" class="fib-input" name="'.e($name).'" value="'.e($value).'" autocomplete="off" />';
        }, $safe);
    }
}
