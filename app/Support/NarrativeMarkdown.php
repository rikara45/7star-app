<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Satu-satunya jalur render narasi AI. HTML dari model diperlakukan sebagai
 * teks tidak tepercaya: tag dibuang, tautan tidak aman dinonaktifkan, markdown
 * tetap dirender.
 */
final class NarrativeMarkdown
{
    public static function toHtml(?string $text): string
    {
        return Str::markdown($text ?? '', [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
