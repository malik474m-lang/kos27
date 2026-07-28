<?php
/**
 * Безопасная минификация HTML/CSS для PHP-сайта.
 * JS не агрессивно не трогаем, чтобы не ломать inline-скрипты.
 */

function minifyCssContent(string $css): string {
    // Убираем CSS-комментарии
    $css = preg_replace('!\/\*.*?\*\/!s', '', $css);
    // Схлопываем пробелы
    $css = preg_replace('/\s+/', ' ', $css);
    // Убираем пробелы вокруг символов
    $css = preg_replace('/\s*([{}:;,>])\s*/', '$1', $css);
    // ;} -> }
    $css = str_replace(';}', '}', $css);
    return trim($css);
}

function minifyHtmlOutput(string $html): string {
    if ($html === '' || stripos($html, '<html') === false) {
        return $html;
    }

    // Минифицируем только содержимое style-блоков
    $html = preg_replace_callback('/<style\b([^>]*)>(.*?)<\/style>/is', function($m) {
        return '<style' . $m[1] . '>' . minifyCssContent($m[2]) . '</style>';
    }, $html);

    // Удаляем HTML-комментарии, кроме условных IE и JSON-LD/markup-safe зон
    $html = preg_replace('/<!--(?!\s*\[if|\s*<!|\s*JSON-LD)(?!.*?application\/ld\+json)(.*?)-->/is', '', $html);

    // Убираем пробелы между тегами
    $html = preg_replace('/>\s+</', '><', $html);
    // Схлопываем множественные пробелы в обычном HTML
    $html = preg_replace('/[ \t]{2,}/', ' ', $html);

    return trim($html);
}
