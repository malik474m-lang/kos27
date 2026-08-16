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
    $image = $logo ? (str_starts_with($logo, 'http') ? $logo : SITE_URL . $logo) : SITE_URL . '/favicon.svg';

    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'FinancialProduct',
        '@id' => SITE_URL . '/offer/' . $offer['slug'],
        'url' => SITE_URL . '/offer/' . $offer['slug'],
        'name' => $offer['title'],
        'image' => $image,
        'description' => $offer['description'] ?: "Финансовое предложение от {$offer['title']}",
        'brand' => ['@type' => 'Brand', 'name' => $offer['title']],
        'provider' => ['@type' => 'FinancialService', 'name' => $offer['title']],
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'RUB',
            'price' => '0',
            'availability' => 'https://schema.org/InStock',
            'url' => SITE_URL . '/offer/' . $offer['slug'],
        ],
        'interestRate' => ['@type' => 'QuantitativeValue', 'value' => $offer['rate'], 'unitText' => (getRateUnit($offer) === 'year' ? 'percent per year' : 'percent per day')],
        'amount' => ['@type' => 'MonetaryAmount', 'minValue' => $offer['amount_min'], 'maxValue' => $offer['amount_max'], 'currency' => 'RUB'],
    ];

    if ((float)$offer['rating'] > 0 && (int)$offer['review_count'] > 0) {
        $data['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format((float)$offer['rating'], 1, '.', ''),
            'reviewCount' => (int)$offer['review_count'],
            'bestRating' => '5',
            'worstRating' => '1',
        ];
    }

    // Отзывы выносим в отдельный JSON-LD блок (не внутрь FinancialProduct)
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Отдельный JSON-LD для отзывов — как Product с reviews
 * Google не поддерживает review внутри FinancialProduct,
 * но поддерживает для Product
 */
function jsonLdOfferReviews(array $offer, array $reviews): string {
    if (!$reviews) return '';
    
    $logo = normalizeMediaUrl($offer['logo_url'] ?? '');
    $image = $logo ? (str_starts_with($logo, 'http') ? $logo : SITE_URL . $logo) : SITE_URL . '/favicon.svg';
    
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $offer['title'],
        'image' => $image,
        'description' => $offer['description'] ?: "Финансовое предложение от {$offer['title']}",
        'brand' => ['@type' => 'Brand', 'name' => $offer['title']],
    ];
    
    if ((float)($offer['rating'] ?? 0) > 0 && (int)($offer['review_count'] ?? 0) > 0) {
        $data['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format((float)$offer['rating'], 1, '.', ''),
            'reviewCount' => (int)$offer['review_count'],
            'bestRating' => '5',
            'worstRating' => '1',
        ];
    }
    
    $data['review'] = array_map(function($review) {
        return [
            '@type' => 'Review',
            'author' => ['@type' => 'Person', 'name' => $review['author_name'] ?: 'Пользователь'],
            'reviewBody' => $review['comment'] ?: '',
            'datePublished' => !empty($review['created_at']) ? date('c', strtotime($review['created_at'])) : date('c'),
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => (int)($review['rating'] ?? 5),
                'bestRating' => '5',
                'worstRating' => '1',
            ],
        ];
    }, array_slice($reviews, 0, 10));
    
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function jsonLdArticle(array $article): string {
    $cover = normalizeMediaUrl($article['cover_image'] ?? '');
    $image = $cover ? (str_starts_with($cover, 'http') ? $cover : SITE_URL . $cover) : SITE_URL . '/favicon.svg';
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
                'item' => str_starts_with($item['url'], 'http') ? $item['url'] : SITE_URL . $item['url'],
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
