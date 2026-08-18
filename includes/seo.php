<?php
// SEO-разметка: JSON-LD, Open Graph, хлебные крошки
// Используется в layout.php

function jsonLdOrganization(): string {
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Космозайм',
        'url' => SITE_URL,
        'logo' => SITE_URL . '/favicon.svg',
        'description' => 'Сервис подбора займов, кредитов и банковских карт',
        'contactPoint' => ['@type' => 'ContactPoint', 'contactType' => 'customer service', 'availableLanguage' => 'Russian'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function jsonLdWebsite(): string {
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Космозайм',
        'url' => SITE_URL,
        'description' => 'Подбор займов, кредитов и банковских карт онлайн',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => SITE_URL . '/search?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function jsonLdOffer(array $offer, array $reviews = []): string {
    $logo = normalizeMediaUrl($offer['logo_url'] ?? '');
    $image = $logo ? (str_starts_with((string)($logo), 'http') ? $logo : SITE_URL . $logo) : SITE_URL . '/favicon.svg';
    $offerUrl = SITE_URL . '/offer/' . $offer['slug'];

    $additionalType = match($offer['category'] ?? 'microloans') {
        'microloans', 'credits' => 'https://schema.org/LoanOrCredit',
        'credit_cards', 'debit_cards' => 'https://schema.org/PaymentCard',
        default => 'https://schema.org/FinancialProduct',
    };

    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'additionalType' => $additionalType,
        '@id' => $offerUrl,
        'url' => $offerUrl,
        'name' => $offer['title'],
        'image' => $image,
        'description' => $offer['description'] ?: "Финансовое предложение от {$offer['title']}",
        'sku' => (string)($offer['slug'] ?? $offer['id'] ?? ''),
        'category' => (string)($offer['category'] ?? ''),
        'brand' => ['@type' => 'Brand', 'name' => $offer['title']],
        'offers' => [
            '@type' => 'Offer',
            'url' => $offerUrl,
            'priceCurrency' => 'RUB',
            'price' => '0',
            'availability' => 'https://schema.org/InStock',
            'priceValidUntil' => date('Y-12-31'),
        ],
    ];

    if (!empty($offer['rate'])) {
        $data['additionalProperty'][] = [
            '@type' => 'PropertyValue',
            'name' => getRateUnit($offer) === 'year' ? 'Процентная ставка годовая' : 'Процентная ставка',
            'value' => (string)$offer['rate'] . '%',
        ];
    }
    if (!empty($offer['amount_min']) || !empty($offer['amount_max'])) {
        $data['additionalProperty'][] = [
            '@type' => 'PropertyValue',
            'name' => 'Сумма',
            'value' => formatMoney((int)($offer['amount_min'] ?? 0)) . ' — ' . formatMoney((int)($offer['amount_max'] ?? 0)),
        ];
    }
    if (!empty($offer['term_min_days']) || !empty($offer['term_max_days'])) {
        $data['additionalProperty'][] = [
            '@type' => 'PropertyValue',
            'name' => 'Срок',
            'value' => formatDays((int)($offer['term_min_days'] ?? 0)) . ' — ' . formatDays((int)($offer['term_max_days'] ?? 0)),
        ];
    }

    if ((float)$offer['rating'] > 0 && (int)$offer['review_count'] > 0) {
        $data['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format((float)$offer['rating'], 1, '.', ''),
            'reviewCount' => (int)$offer['review_count'],
            'bestRating' => '5',
            'worstRating' => '1',
        ];
    }

    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function jsonLdArticle(array $article): string {
    $cover = normalizeMediaUrl($article['cover_image'] ?? '');
    $image = $cover ? (str_starts_with((string)($cover), 'http') ? $cover : SITE_URL . $cover) : SITE_URL . '/favicon.svg';
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article['title'],
        'description' => $article['excerpt'] ?: ($article['meta_description'] ?: ''),
        'image' => $image,
        'datePublished' => $article['created_at'],
        'dateModified' => $article['updated_at'] ?? $article['created_at'],
        'author' => ['@type' => 'Organization', 'name' => 'Космозайм'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Космозайм', 'logo' => ['@type' => 'ImageObject', 'url' => SITE_URL . '/favicon.svg']],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => SITE_URL . '/articles/' . $article['slug']],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function jsonLdFAQ(array $questions): string {
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(function($q) {
            return [
                '@type' => 'Question',
                'name' => $q['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q['a']],
            ];
        }, $questions),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function jsonLdBreadcrumb(array $items): string {
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(function($item, $i) {
            return [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => str_starts_with((string)($item['url']), 'http') ? $item['url'] : SITE_URL . $item['url'],
            ];
        }, $items, array_keys($items)),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Вывод JSON-LD тегов (вызывается в layout.php)
function renderJsonLd(string ...$schemas): string {
    $html = '';
    foreach ($schemas as $s) {
        if ($s) $html .= '<script type="application/ld+json">' . $s . '</script>' . "\n";
    }
    return $html;
}
