<?php
require_once __DIR__ . '/../../includes/article-inline-cta.php';

$days = max(1, min(365, (int)($_GET['days'] ?? 30)));

echo json_encode(getArticleInlineCtaStats($days));
