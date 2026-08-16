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


function cq_strip_markdown(string $text): string {
    $text = trim($text);
    if ($text === '') return '';
    // Remove code fences
    $text = preg_replace('/^```\s*\w*\s*\n?/mi', '', $text);
    $text = preg_replace('/\n?```\s*$/m', '', $text);
    $text = str_replace('```', '', $text);
    // ### headers → plain text
    $text = preg_replace('/^#{1,6}\s+/m', '', $text);
    // **bold** → text
    $text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text);
    // *italic* → text
    $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '$1', $text);
    // __bold__ → text
    $text = preg_replace('/__(.+?)__/s', '$1', $text);
    // _italic_ → text (careful not to hit filenames)
    $text = preg_replace('/(?<!\w)_([^_]+?)_(?!\w)/s', '$1', $text);
    // ~~ strikethrough ~~
    $text = preg_replace('/~~(.+?)~~/s', '$1', $text);
    // > blockquote
    $text = preg_replace('/^>\s?/m', '', $text);
    // Remove horizontal rules
    $text = preg_replace('/^\s*[-*_]{3,}\s*$/m', '', $text);
    // Clean up excessive blank lines
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

function cq_min_words(string $entity): int {
    return match ($entity) {
        'article' => 600,
        'tag' => 120,
        'city_seo' => 180,
        'city_tag_seo' => 180,
        'offer' => 40,
        default => 80,
    };
}

function cq_supporting_paragraph(string $entity, string $title, string $description): string {
    $title = trim($title);
    $description = trim($description);
    $base = $description !== '' ? $description : ($title !== '' ? $title : 'Материал');
    return match ($entity) {
        'article' => "{$base}. Дополнительно стоит рассмотреть практические нюансы: кому подходит решение, какие условия нужно проверить заранее, какие ограничения могут повлиять на итоговый выбор и как сравнить несколько предложений между собой без лишней переплаты.",
        'tag' => "{$base}. Перед выбором предложения важно сравнить ставку, лимит, сроки, требования к заёмщику и дополнительные условия, чтобы подобрать вариант под конкретную ситуацию.",
        'offer' => "{$base}. Перед оформлением уточните ставку, срок, сумму, требования к клиенту и возможные ограничения по продукту.",
        default => "{$base}. Добавьте больше полезных деталей, примеров применения и критериев выбора, чтобы текст лучше отвечал на запрос пользователя.",
    };
}

function cq_enforce_recommendations(string $text, array $analysis, string $entity = 'generic', string $title = '', string $description = ''): string {
    $text = cq_strip_markdown($text);
    $issues = $analysis['issues'] ?? [];
    $plain = cq_strip_text($text);

    // Если тема не отражена — добавляем в начало короткий вводный абзац
    if ($title && mb_stripos($plain, mb_strtolower($title)) === false) {
        $intro = $title . ' — ключевая тема этого материала.';
        if (preg_match('/<[^>]+>/', $text)) {
            $text = '<p>' . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</p>' . "
" . $text;
        } else {
            $text = $intro . "

" . $text;
        }
    }

    // Если текст короткий — дополняем полезным абзацем
    $currentWords = cq_word_count($text);
    $minWords = cq_min_words($entity);
    if ($currentWords < $minWords) {
        $extra = cq_supporting_paragraph($entity, $title, $description);
        if (preg_match('/<[^>]+>/', $text)) {
            $text .= "
<p>" . htmlspecialchars($extra, ENT_QUOTES, 'UTF-8') . "</p>";
        } else {
            $text .= "

" . $extra;
        }
        // если всё ещё коротко — добавим второй абзац
        if (cq_word_count($text) < $minWords) {
            $extra2 = 'Отдельно оцените прозрачность условий, порядок одобрения, возможные дополнительные комиссии и удобство оформления. Это поможет избежать ошибок и выбрать более подходящее решение.';
            if (preg_match('/<[^>]+>/', $text)) {
                $text .= "
<p>" . htmlspecialchars($extra2, ENT_QUOTES, 'UTF-8') . "</p>";
            } else {
                $text .= "

" . $extra2;
            }
        }
    }

    // Удаляем самые частые шаблонные формулировки повторно
    $replacements = [
        'На этой странице' => 'В материале ниже',
        'Мы собрали' => 'Ниже представлены',
        'Это поможет' => 'Так проще принять решение',
        'Обратите внимание' => 'Важно учитывать',
        'выберите подходящий вариант' => 'подберите подходящее решение',
        'актуальные условия' => 'текущие условия',
    ];
    foreach ($replacements as $from => $to) {
        $text = preg_replace('/' . preg_quote($from, '/') . '/ui', $to, $text, 1);
    }

    return trim($text);
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

function cq_offer_context_line(array $context): string {
    $parts = [];
    if (!empty($context['amountMin']) || !empty($context['amountMax'])) {
        $amountMin = !empty($context['amountMin']) ? formatMoney((int)$context['amountMin']) : '';
        $amountMax = !empty($context['amountMax']) ? formatMoney((int)$context['amountMax']) : '';
        if ($amountMin && $amountMax) $parts[] = 'сумма от ' . $amountMin . ' до ' . $amountMax;
        elseif ($amountMax) $parts[] = 'сумма до ' . $amountMax;
    }
    if (!empty($context['termMinDays']) || !empty($context['termMaxDays'])) {
        $termMin = !empty($context['termMinDays']) ? formatDays((int)$context['termMinDays']) : '';
        $termMax = !empty($context['termMaxDays']) ? formatDays((int)$context['termMaxDays']) : '';
        if ($termMin && $termMax) $parts[] = 'срок от ' . $termMin . ' до ' . $termMax;
        elseif ($termMax) $parts[] = 'срок до ' . $termMax;
    }
    if (!empty($context['rate']) && (float)$context['rate'] > 0) {
        $rateUnit = ($context['rateUnit'] ?? 'day') === 'year' ? 'в год' : 'в день';
        $parts[] = 'ставка от ' . $context['rate'] . '% ' . $rateUnit;
    }
    if (!empty($context['freeTermDays']) && (int)$context['freeTermDays'] > 0) {
        $parts[] = 'льготный период ' . formatDays((int)$context['freeTermDays']);
    }
    return implode(', ', $parts);
}

function cq_improve_offer_fallback(string $text, string $title = '', string $description = '', array $context = []): string {
    $base = cq_strip_text($text);
    $title = trim($title);
    $details = cq_offer_context_line($context);
    $category = $context['category'] ?? 'microloans';

    $actionLine = match ($category) {
        'credits' => 'Подходит тем, кто хочет заранее оценить базовые условия кредита и сравнить их с альтернативами.',
        'credit_cards' => 'Удобно, если нужен кредитный лимит, льготный период и понятные условия использования карты.',
        'debit_cards' => 'Полезно тем, кто выбирает карту для ежедневных покупок, переводов, кэшбэка и обслуживания.',
        default => 'Подходит тем, кто хочет быстро сравнить основные параметры займа и выбрать подходящий вариант.',
    };

    $lines = [];
    if ($title !== '') {
        $lines[] = $title . ' — предложение с понятными условиями оформления онлайн.';
    }
    if ($details !== '') {
        $lines[] = 'По открытым параметрам доступны ' . $details . '.';
    }
    if ($base !== '') {
        $base = preg_replace('/\s+/u', ' ', $base);
        $base = trim($base);
        if ($base !== '' && mb_strlen($base) > 30) {
            $lines[] = rtrim($base, '. ') . '.';
        }
    }
    $lines[] = $actionLine;
    $lines[] = 'Перед оформлением стоит проверить итоговые условия на сайте партнёра, включая лимиты, обслуживание и дополнительные требования.';

    $unique = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $key = mb_strtolower($line);
        if (!isset($unique[$key])) $unique[$key] = $line;
    }

    return implode(' ', array_values($unique));
}

function cq_improve_fallback(string $text, string $entity = 'generic', string $title = '', string $description = '', array $context = []): string {
    $text = trim($text);
    if ($text === '') return $text;

    if ($entity === 'offer') {
        return cq_improve_offer_fallback($text, $title, $description, $context);
    }

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
