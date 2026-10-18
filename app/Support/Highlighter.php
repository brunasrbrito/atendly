<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Highlighter
{
    /**
     * Escapa o texto e destaca os termos buscados com <mark>.
     * Quando $around > 0, recorta um trecho em volta do primeiro termo encontrado.
     *
     * @param  list<string>  $terms
     */
    public static function mark(?string $text, array $terms, int $around = 0): HtmlString
    {
        $text = (string) $text;

        if ($around > 0 && mb_strlen($text) > $around * 2) {
            $pos = null;
            foreach ($terms as $term) {
                $found = mb_stripos($text, $term);
                if ($found !== false && ($pos === null || $found < $pos)) {
                    $pos = $found;
                }
            }
            $start = max(0, ($pos ?? 0) - $around);
            $text = ($start > 0 ? '…' : '').mb_substr($text, $start, $around * 2).'…';
        }

        $html = e($text);

        if ($terms !== []) {
            $pattern = '/('.implode('|', array_map(fn ($t) => preg_quote(e($t), '/'), $terms)).')/iu';
            $html = preg_replace($pattern, '<mark>$1</mark>', $html) ?? $html;
        }

        return new HtmlString(nl2br($html));
    }

    public static function duration(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }
        if ($minutes < 60) {
            return "{$minutes} min";
        }
        if ($minutes < 60 * 24) {
            return sprintf('%dh%02d', intdiv($minutes, 60), $minutes % 60);
        }

        $days = intdiv($minutes, 60 * 24);

        return $days.' '.Str::plural('dia', $days).' '.intdiv($minutes % (60 * 24), 60).'h';
    }
}
