-- Удалить конструкции вида [2026] из уже сохранённых H1 SEO-текстов городов
UPDATE city_seo_texts
SET seo_h1 = TRIM(
    REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(seo_h1, ' [2026]', ''),
            '[2026]', ''),
        ' [2025]', ''),
    '[2025]', '')
)
WHERE seo_h1 LIKE '%[2026]%' OR seo_h1 LIKE '%[2025]%';
