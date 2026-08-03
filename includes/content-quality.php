<?php
/**
 * Анализ качества контента и базовое улучшение текста
 */

function cq_strip_text(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/<[^>]+>/', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

function cq_word_count(string $text): int {
    $plain = cq_strip_text($text);
    if ($plain === '') return 0;
    $parts = preg_split('/\s+/u', $plain);
    return count(array_filter($parts, fn($w) => trim($w) !== ''));
}

function cq_sentence_count(string $text): int {
    $plain = cq_strip_text($text);
    if ($plain === '') return 0;
    $parts = preg_split('/[.!?]+/u', $plain);
    return count(array_filter($parts, fn($s) => trim($s) !== ''));
}

function cq_paragraph_count(string $text): int {
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $parts = preg_split('/\n{2,}/', trim($text));
    return count(array_filter($parts, fn($p) => trim($p) !== ''));
}

function cq_detect_repeated_phrases(string $text): array {
    $plain = mb_strtolower(cq_strip_text($text));
    $phrases = [
        'на этой странице',
        'мы собрали',
        'обратите внимание',
        'это поможет',
        'подходит для',
        'актуальные условия',
        'выберите подходящий вариант',
        'сравните предложения',
    ];
    $found = [];
    foreach ($phrases as $phrase) {
        if (substr_count($plain, $phrase) > 1) $found[] = $phrase;
    }
    return $found;
}

function cq_analyze(string $text, string $entity = 'generic', string $title = '', string $description = ''): array {
    $issues = [];
    $recommendations = [];
    $score = 100;

    $wordCount = cq_word_count($text);
    $sentenceCount = cq_sentence_count($text);
    $paragraphCount = cq_paragraph_count($text);
    $plain = cq_strip_text($text);

    if (trim($text) === '') {
        return [
            'score' => 0,
            'issues' => [['level' => 'error', 'msg' => 'Текст пустой']],
            'recommendations' => ['Добавьте содержательный текст'],
            'stats' => ['words' => 0, 'sentences' => 0, 'paragraphs' => 0],
        ];
    }

    if (preg_match('/```|^#\s|^##\s|^###\s/m', $text)) {
        $issues[] = ['level' => 'warning', 'msg' => 'Обнаружены markdown-артефакты'];
        $recommendations[] = 'Очистите markdown-разметку';
        $score -= 10;
    }

    if (preg_match('/<html|<body|<head|&lt;h\d|&lt;p&gt;/i', $text)) {
        $issues[] = ['level' => 'warning', 'msg' => 'Обнаружены лишние HTML/encoded HTML артефакты'];
        $recommendations[] = 'Очистите HTML-мусор';
        $score -= 10;
    }

    $minWords = match ($entity) {
        'article' => 600,
        'tag' => 120,
        'city_seo' => 180,
        'city_tag_seo' => 180,
        'offer' => 40,
        default => 80,
    };
    if ($wordCount < $minWords) {
        $issues[] = ['level' => 'warning', 'msg' => 'Текст слишком короткий: ' . $wordCount . ' слов'];
        $recommendations[] = 'Раскройте тему подробнее, добавьте конкретику и полезные детали';
        $score -= 15;
    }

    if ($sentenceCount < 3) {
        $issues[] = ['level' => 'info', 'msg' => 'Слишком мало предложений'];
        $recommendations[] = 'Разбейте мысль на несколько коротких предложений';
        $score -= 5;
    }

    if ($paragraphCount < 2 && $entity !== 'offer') {
        $issues[] = ['level' => 'info', 'msg' => 'Текст слабо структурирован по абзацам'];
        $recommendations[] = 'Добавьте абзацы или подзаголовки';
        $score -= 5;
    }

    if ($title && mb_stripos($plain, mb_strtolower($title)) === false) {
        $issues[] = ['level' => 'info', 'msg' => 'Заголовок/ключевая тема почти не отражены в тексте'];
        $recommendations[] = 'Добавьте формулировку темы в основной текст';
        $score -= 5;
    }

    $repeated = cq_detect_repeated_phrases($text);
    if ($repeated) {
        $issues[] = ['level' => 'info', 'msg' => 'Есть шаблонные повторяющиеся фразы: ' . implode(', ', $repeated)];
        $recommendations[] = 'Сделайте формулировки более разнообразными';
        $score -= 8;
    }

    if (preg_match('/\b(очень|максимально|идеальный|лучший из лучших|самый лучший)\b/ui', $plain)) {
        $issues[] = ['level' => 'info', 'msg' => 'Есть слишком рекламные формулировки'];
        $recommendations[] = 'Сделайте текст более нейтральным и полезным';
        $score -= 6;
    }

    if ($description && mb_strlen(trim($description)) < 40) {
        $issues[] = ['level' => 'info', 'msg' => 'Краткое описание слишком короткое'];
        $recommendations[] = 'Уточните выгоду и содержание в описании';
        $score -= 4;
    }

    $score = max(0, min(100, $score));
    return [
        'score' => $score,
        'issues' => $issues,
        'recommendations' => array_values(array_unique($recommendations)),
        'stats' => [
            'words' => $wordCount,
            'sentences' => $sentenceCount,
            'paragraphs' => $paragraphCount,
            'chars' => mb_strlen($plain),
        ],
    ];
}

function cq_improve_fallback(string $text, string $entity = 'generic', string $title = '', string $description = ''): string {
    $text = trim($text);
    if ($text === '') return $text;

    // базовая очистка
    $text = preg_replace('/^```\s*html?\s*\n?/i', '', $text);
    $text = preg_replace('/\n?```\s*$/', '', $text);
    $text = preg_replace('/```/', '', $text);
    $text = preg_replace('/^###\s+(.+)$/m', '$1', $text);
    $text = preg_replace('/^##\s+(.+)$/m', '$1', $text);
    $text = preg_replace('/^#\s+(.+)$/m', '$1', $text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/<\/?(?:html|body|head|meta|title)[^>]*>/i', '', $text);

    $replacements = [
        'На этой странице' => 'В подборке ниже',
        'Мы собрали' => 'Ниже собраны',
        'Это поможет' => 'Так проще оценить условия',
        'Обратите внимание' => 'Важно проверить',
        'Подходит для' => 'Может быть актуально для',
        'актуальные условия' => 'текущие условия',
        'выберите подходящий вариант' => 'подберите подходящее решение',
    ];
    $improved = $text;
    foreach ($replacements as $from => $to) {
        $improved = preg_replace('/' . preg_quote($from, '/') . '/ui', $to, $improved, 1);
    }

    if ($title && mb_stripos(cq_strip_text($improved), mb_strtolower($title)) === false) {
        $improved = '<p>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' — ключевая тема этой страницы.</p>' . "\n" . $improved;
    }

    return trim($improved);
}


function cq_recommend_status(int $score): string {
    if ($score >= 80) return 'ready';
    if ($score >= 60) return 'reviewed';
    return 'draft';
}

function cq_status_label(string $status): string {
    return match ($status) {
        'ready' => 'Готово к публикации',
        'reviewed' => 'Проверено',
        default => 'Черновик',
    };
}
