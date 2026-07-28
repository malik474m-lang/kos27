<?php
/**
 * Безопасная минификация HTML/CSS для PHP-сайта.
 * Содержимое script/pre/textarea/code защищается от изменения.
 * Общий JS вынесен в /assets/site.min.js, сторонние библиотеки уже *.min.js.
 */

function minifyCssContent(string $css): string {
    $css = preg_replace('!\/\*.*?\*\/!s', '', $css);
    $css = preg_replace('/\s+/', ' ', $css);
    $css = preg_replace('/\s*([{}:;,>])\s*/', '$1', $css);
    $css = str_replace(';}', '}', $css);
    return trim($css);
}

function minifyHtmlOutput(string $html): string {
    if ($html === '' || stripos($html, '<html') === false) {
        return $html;
    }

    // Сначала минифицируем CSS. Затем raw-блоки защищаем плейсхолдерами.
    $html = preg_replace_callback('/<style\b([^>]*)>(.*?)<\/style>/is', function($m) {
        return '<style' . $m[1] . '>' . minifyCssContent($m[2]) . '</style>';
    }, $html);

    $protected = [];
    $html = preg_replace_callback('/<(script|pre|textarea|code)\b[^>]*>.*?<\/\1>/is', function($m) use (&$protected) {
        $key = '___KOSMO_RAW_BLOCK_' . count($protected) . '___';
        $protected[$key] = $m[0];
        return $key;
    }, $html);

    // Удаляем обычные HTML-комментарии, условные комментарии сохраняем.
    $html = preg_replace('/<!--(?!\s*\[if|\s*<!)(.*?)-->/is', '', $html);
    $html = preg_replace('/>\s+</', '><', $html);
    $html = preg_replace('/[ \t]{2,}/', ' ', $html);
    $html = trim($html);

    if ($protected) {
        $html = strtr($html, $protected);
    }

    return $html;
}
